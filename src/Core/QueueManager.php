<?php
namespace LacVo\WPToolsPro\Core;

if (!defined('ABSPATH')) { exit; }

final class QueueManager {
    private const HOOK = 'lacvo_wtp_queue_run';
    private const STALE_SECONDS = 900;

    public static function boot(): void {
        add_action(self::HOOK, [self::class, 'run'], 10, 1);
        add_action('lacvo_wtp_queue_cron', [self::class, 'fallbackTick']);
        add_action('lacvo_wtp_queue_recovery', [self::class, 'recoverStale']);
        if (!wp_next_scheduled('lacvo_wtp_queue_recovery')) {
            wp_schedule_event(time() + 5 * MINUTE_IN_SECONDS, 'hourly', 'lacvo_wtp_queue_recovery');
        }
    }

    public static function enqueue(string $type, array $payload, int $maxAttempts = 3): int {
        global $wpdb;
        $table = Database::table('queue');
        $now = self::dbNow();
        $wpdb->insert(
            $table,
            [
                'job_type' => sanitize_key($type),
                'payload' => wp_json_encode($payload),
                'status' => 'pending',
                'attempts' => 0,
                'max_attempts' => max(1, min(10, $maxAttempts)),
                'available_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
                'lock_token' => '',
                'locked_at' => null,
            ],
            ['%s','%s','%s','%d','%d','%s','%s','%s','%s','%s']
        );
        $id = (int) $wpdb->insert_id;
        if ($id) { self::schedule($id); }
        return $id;
    }

    public static function run(int $id): void {
        global $wpdb;
        $table = Database::table('queue');
        $token = wp_generate_uuid4();
        $now = self::dbNow();
        $claimed = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET status='running',lock_token=%s,locked_at=%s,updated_at=%s WHERE id=%d AND status='pending' AND available_at<=%s",
                $token, $now, $now, $id, $now
            )
        );
        if ($claimed !== 1) { return; }

        $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d AND lock_token=%s", $id, $token));
        if (!$job) { return; }

        $payload = json_decode((string) $job->payload, true);
        $payload = is_array($payload) ? $payload : [];
        $result = ['ok' => false, 'message' => 'No queue handler accepted this job.'];

        try {
            /**
             * Execute an application-specific queue job.
             * Handlers return ['ok' => bool, 'message' => string].
             */
            $result = apply_filters('lacvo_wtp_queue_execute', $result, (string) $job->job_type, $payload, $job);
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'message' => $e->getMessage()];
        }

        $attempts = (int) $job->attempts + 1;
        if (!empty($result['ok'])) {
            $wpdb->update($table, ['status'=>'complete','attempts'=>$attempts,'last_error'=>'','updated_at'=>self::dbNow(),'lock_token'=>'','locked_at'=>null], ['id'=>$id,'lock_token'=>$token]);
            return;
        }

        $error = substr(sanitize_text_field((string) ($result['message'] ?? 'Job failed')), 0, 1000);
        if ($attempts < (int) $job->max_attempts) {
            $delay = min(1800, 30 * (2 ** max(0, $attempts - 1)));
            $wpdb->update($table, ['status'=>'pending','attempts'=>$attempts,'last_error'=>$error,'available_at'=>self::dbFuture($delay),'updated_at'=>self::dbNow(),'lock_token'=>'','locked_at'=>null], ['id'=>$id,'lock_token'=>$token]);
            self::schedule($id, $delay);
            return;
        }

        $wpdb->update($table, ['status'=>'failed','attempts'=>$attempts,'last_error'=>$error,'updated_at'=>self::dbNow(),'lock_token'=>'','locked_at'=>null], ['id'=>$id,'lock_token'=>$token]);
    }

    public static function recoverStale(): int {
        global $wpdb;
        $table = Database::table('queue');
        $cutoff = self::dbPast(self::STALE_SECONDS);
        $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$table} WHERE status='running' AND locked_at IS NOT NULL AND locked_at<%s LIMIT 100", $cutoff));
        $count = 0;
        foreach ((array) $ids as $id) {
            $ok = $wpdb->update($table, ['status'=>'pending','lock_token'=>'','locked_at'=>null,'last_error'=>'Recovered stale lock','available_at'=>self::dbNow(),'updated_at'=>self::dbNow()], ['id'=>(int)$id,'status'=>'running']);
            if ($ok) { $count++; self::schedule((int) $id, 5); }
        }
        return $count;
    }

    public static function fallbackTick(): void {
        global $wpdb;
        self::recoverStale();
        $ids = $wpdb->get_col($wpdb->prepare('SELECT id FROM '.Database::table('queue')." WHERE status='pending' AND available_at<=%s ORDER BY id ASC LIMIT 5", self::dbNow()));
        foreach ((array) $ids as $id) { self::run((int) $id); }
        if (self::hasPending()) { self::scheduleFallback(); }
    }

    public static function stats(): array {
        global $wpdb;
        $rows = $wpdb->get_results('SELECT status,COUNT(*) c FROM '.Database::table('queue').' GROUP BY status');
        $out = ['pending'=>0,'running'=>0,'failed'=>0,'complete'=>0,'driver'=>self::driver(),'stale'=>self::staleCount()];
        foreach ((array) $rows as $row) { $out[$row->status] = (int) $row->c; }
        return $out;
    }

    public static function retry(int $id): bool {
        global $wpdb;
        $ok = $wpdb->update(Database::table('queue'), ['status'=>'pending','attempts'=>0,'last_error'=>'','available_at'=>self::dbNow(),'updated_at'=>self::dbNow(),'lock_token'=>'','locked_at'=>null], ['id'=>$id]);
        if ($ok !== false) { self::schedule($id); }
        return $ok !== false;
    }

    public static function staleCount(): int {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".Database::table('queue')." WHERE status='running' AND locked_at IS NOT NULL AND locked_at<%s", self::dbPast(self::STALE_SECONDS)));
    }

    public static function driver(): string { return function_exists('as_schedule_single_action') ? 'Action Scheduler' : 'WP-Cron fallback'; }

    private static function schedule(int $id, int $delay = 0): void {
        if (function_exists('as_schedule_single_action')) {
            as_schedule_single_action(time() + $delay, self::HOOK, [$id], 'lacvo-wtp', true);
            return;
        }
        self::scheduleFallback(max(5, $delay));
    }

    private static function scheduleFallback(int $delay = 10): void {
        if (!wp_next_scheduled('lacvo_wtp_queue_cron')) { wp_schedule_single_event(time() + $delay, 'lacvo_wtp_queue_cron'); }
    }

    private static function hasPending(): bool {
        global $wpdb;
        return (bool) $wpdb->get_var("SELECT id FROM ".Database::table('queue')." WHERE status='pending' LIMIT 1");
    }

    private static function dbNow(): string { return gmdate('Y-m-d H:i:s'); }
    private static function dbFuture(int $seconds): string { return gmdate('Y-m-d H:i:s', time() + max(0, $seconds)); }
    private static function dbPast(int $seconds): string { return gmdate('Y-m-d H:i:s', time() - max(0, $seconds)); }
}
