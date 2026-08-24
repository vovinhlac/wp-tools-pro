<?php
namespace LacVo\WPToolsPro\Core;

if (!defined('ABSPATH')) { exit; }

final class Database {
    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $redirects = $wpdb->prefix . 'lacvo_wtp_redirects';
        $notfound = $wpdb->prefix . 'lacvo_wtp_404_log';
        $spam = $wpdb->prefix . 'lacvo_wtp_spam_log';
        $mail = $wpdb->prefix . 'lacvo_wtp_mail_log';
        $consent = $wpdb->prefix . 'lacvo_wtp_consent_log';

        $redirectHits = $wpdb->prefix . 'lacvo_wtp_redirect_hits';
        $rules = $wpdb->prefix . 'lacvo_wtp_firewall_rules';
        $bans = $wpdb->prefix . 'lacvo_wtp_firewall_bans';
        $queue = $wpdb->prefix . 'lacvo_wtp_queue';
        $referrers = $wpdb->prefix . 'lacvo_wtp_redirect_referrers';
        $audit = $wpdb->prefix . 'lacvo_wtp_firewall_audit';
        $quarantine = $wpdb->prefix . 'lacvo_wtp_media_quarantine';

        dbDelta("CREATE TABLE {$redirects} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            source varchar(512) NOT NULL,
            target varchar(1024) NOT NULL,
            status smallint unsigned NOT NULL DEFAULT 301,
            match_type varchar(20) NOT NULL DEFAULT 'exact',
            rule_order int unsigned NOT NULL DEFAULT 100,
            hits bigint unsigned NOT NULL DEFAULT 0,
            enabled tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY source_match (source(180),match_type),
            KEY enabled_order (enabled,rule_order)
        ) {$charset};");

        dbDelta("CREATE TABLE {$notfound} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            path varchar(1024) NOT NULL,
            referrer varchar(1024) NOT NULL DEFAULT '',
            user_agent varchar(512) NOT NULL DEFAULT '',
            ip_hash char(64) NOT NULL DEFAULT '',
            hits bigint unsigned NOT NULL DEFAULT 1,
            first_seen datetime NOT NULL,
            last_seen datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY path (path(191)),
            KEY last_seen (last_seen),
            KEY hits_last_seen (hits,last_seen)
        ) {$charset};");

        dbDelta("CREATE TABLE {$spam} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            source varchar(40) NOT NULL DEFAULT 'comment',
            object_id bigint unsigned NOT NULL DEFAULT 0,
            comment_id bigint unsigned NOT NULL DEFAULT 0,
            score smallint unsigned NOT NULL DEFAULT 0,
            action varchar(20) NOT NULL DEFAULT 'allow',
            reasons text NOT NULL,
            identifier_hash char(64) NOT NULL DEFAULT '',
            email_hash char(64) NOT NULL DEFAULT '',
            ip_hash char(64) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY source (source),
            KEY comment_id (comment_id),
            KEY score (score),
            KEY created_at (created_at)
        ) {$charset};");

        dbDelta("CREATE TABLE {$mail} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            recipient varchar(255) NOT NULL DEFAULT '',
            subject varchar(500) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'sent',
            error_message text NOT NULL,
            transport varchar(30) NOT NULL DEFAULT '',
            provider_message_id varchar(191) NOT NULL DEFAULT '',
            provider_event varchar(40) NOT NULL DEFAULT '',
            delivered_at datetime NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at),
            KEY provider_message_id (provider_message_id),
            KEY transport_event (transport,provider_event),
            KEY status_created (status,created_at)
        ) {$charset};");

        dbDelta("CREATE TABLE {$redirectHits} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            redirect_id bigint unsigned NOT NULL,
            hit_date date NOT NULL,
            hits bigint unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY redirect_day (redirect_id,hit_date),
            KEY hit_date (hit_date)
        ) {$charset};");

        dbDelta("CREATE TABLE {$rules} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            rule_type varchar(20) NOT NULL,
            pattern varchar(1000) NOT NULL,
            action varchar(20) NOT NULL DEFAULT 'score',
            score smallint unsigned NOT NULL DEFAULT 50,
            priority int unsigned NOT NULL DEFAULT 100,
            enabled tinyint(1) NOT NULL DEFAULT 1,
            expires_at datetime NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY enabled_priority (enabled,priority),
            KEY rule_type (rule_type)
        ) {$charset};");

        dbDelta("CREATE TABLE {$bans} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            ip_hash char(64) NOT NULL,
            expires_at datetime NOT NULL,
            reason varchar(255) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY ip_hash (ip_hash),
            KEY expires_at (expires_at)
        ) {$charset};");

        dbDelta("CREATE TABLE {$queue} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            job_type varchar(50) NOT NULL,
            payload longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            attempts smallint unsigned NOT NULL DEFAULT 0,
            max_attempts smallint unsigned NOT NULL DEFAULT 3,
            last_error varchar(1000) NOT NULL DEFAULT '',
            available_at datetime NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            lock_token char(36) NOT NULL DEFAULT '',
            locked_at datetime NULL,
            PRIMARY KEY (id),
            KEY status_available (status,available_at),
            KEY status_locked (status,locked_at),
            KEY job_type (job_type)
        ) {$charset};");

        dbDelta("CREATE TABLE {$referrers} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            redirect_id bigint unsigned NOT NULL,
            referrer_host varchar(255) NOT NULL DEFAULT '(direct)',
            hit_date date NOT NULL,
            hits bigint unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY redirect_ref_day (redirect_id,referrer_host(120),hit_date),
            KEY hit_date (hit_date)
        ) {$charset};");

        dbDelta("CREATE TABLE {$audit} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            action varchar(40) NOT NULL,
            rule_id bigint unsigned NOT NULL DEFAULT 0,
            actor_id bigint unsigned NOT NULL DEFAULT 0,
            details text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY action (action),
            KEY created_at (created_at)
        ) {$charset};");

        dbDelta("CREATE TABLE {$quarantine} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            file_path text NOT NULL,
            file_hash char(64) NOT NULL,
            bytes bigint unsigned NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'quarantined',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY file_hash (file_hash),
            KEY status (status)
        ) {$charset};");

        dbDelta("CREATE TABLE {$consent} (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            consent_key char(36) NOT NULL,
            user_id bigint unsigned NOT NULL DEFAULT 0,
            necessary tinyint(1) NOT NULL DEFAULT 1,
            analytics tinyint(1) NOT NULL DEFAULT 0,
            marketing tinyint(1) NOT NULL DEFAULT 0,
            ad_user_data tinyint(1) NOT NULL DEFAULT 0,
            ad_personalization tinyint(1) NOT NULL DEFAULT 0,
            policy_version varchar(40) NOT NULL DEFAULT '1.0',
            user_agent_hash char(64) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY consent_key (consent_key),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) {$charset};");
    }

    public static function table(string $name): string {
        global $wpdb;
        $allowed = [
            'redirects'=>'lacvo_wtp_redirects','404'=>'lacvo_wtp_404_log','spam'=>'lacvo_wtp_spam_log',
            'mail'=>'lacvo_wtp_mail_log','consent'=>'lacvo_wtp_consent_log','redirect_hits'=>'lacvo_wtp_redirect_hits',
            'firewall_rules'=>'lacvo_wtp_firewall_rules','firewall_bans'=>'lacvo_wtp_firewall_bans','queue'=>'lacvo_wtp_queue','redirect_referrers'=>'lacvo_wtp_redirect_referrers','firewall_audit'=>'lacvo_wtp_firewall_audit','media_quarantine'=>'lacvo_wtp_media_quarantine'
        ];
        return $wpdb->prefix . ($allowed[$name] ?? 'lacvo_wtp_' . sanitize_key($name));
    }

    public static function hash(string $value): string {
        return $value === '' ? '' : hash_hmac('sha256', $value, wp_salt('auth'));
    }
}
