<?php
use PHPUnit\Framework\TestCase;
use LacVo\WPToolsPro\Core\Database;
use LacVo\WPToolsPro\Core\DatabaseDiagnostics;
use LacVo\WPToolsPro\Core\MigrationManager;
use LacVo\WPToolsPro\Core\ReleaseAudit;

final class ReleaseCandidateTest extends TestCase {
    public function testRequiredPluginTablesAreInstalled(): void {
        global $wpdb;
        Database::install();
        foreach (['queue','mail','redirects','firewall_rules','consent'] as $name) {
            $table = Database::table($name);
            $this->assertSame($table, $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)));
        }
    }

    public function testIndexReviewReturnsKnownTables(): void {
        Database::install();
        $review = DatabaseDiagnostics::indexReview();
        $this->assertArrayHasKey('queue', $review);
        $this->assertArrayHasKey('mail', $review);
    }

    public function testMigrationSnapshotCanRestorePluginOption(): void {
        update_option('lacvo_wtp_settings', ['qa_value' => 'before'], false);
        $result = MigrationManager::run('qa-a', 'qa-b', static function (): void {
            update_option('lacvo_wtp_settings', ['qa_value' => 'during'], false);
            throw new RuntimeException('Intentional migration failure.');
        });
        $this->assertFalse($result['ok']);
        $this->assertSame('before', get_option('lacvo_wtp_settings')['qa_value'] ?? '');
    }

    public function testReleaseAuditReturnsScore(): void {
        $audit = ReleaseAudit::run();
        $this->assertArrayHasKey('score', $audit);
        $this->assertGreaterThanOrEqual(0, $audit['score']);
        $this->assertLessThanOrEqual(100, $audit['score']);
    }

    public function test_queue_health_shape(): void {
        $health = \LacVo\WPToolsPro\Core\QueueManager::health();
        $this->assertArrayHasKey('driver', $health);
        $this->assertSame('UTC', $health['clock']);
        $this->assertArrayHasKey('stats', $health);
    }

    public function test_database_index_review_includes_rc2_indexes(): void {
        $review = \LacVo\WPToolsPro\Core\DatabaseDiagnostics::indexReview();
        $this->assertArrayHasKey('404', $review);
        $this->assertArrayHasKey('mail', $review);
    }
}
