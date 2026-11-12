<?php
namespace CF7ON\Core;

defined( 'ABSPATH' ) || exit;

class Installer {

    public static function activate(): void {
        self::createTables();
        self::setDefaultOptions();
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook( 'cf7on_process_queue' );
        wp_clear_scheduled_hook( 'cf7on_retry_delivery' );
        flush_rewrite_rules();
    }

    private static function createTables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $submissions = "CREATE TABLE {$wpdb->prefix}cf7on_submissions (
            id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id      INT(11)    NOT NULL,
            form_name    VARCHAR(255) NOT NULL DEFAULT '',
            fields       LONGTEXT   NOT NULL,
            ip_address   VARCHAR(45) DEFAULT '',
            user_agent   TEXT,
            referrer     TEXT,
            status       ENUM('pending','delivered','partial','failed') DEFAULT 'pending',
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset;";

        $deliveries = "CREATE TABLE {$wpdb->prefix}cf7on_deliveries (
            id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            submission_id   BIGINT(20) UNSIGNED NOT NULL,
            channel         VARCHAR(50) NOT NULL,
            destination     VARCHAR(255) NOT NULL DEFAULT '',
            status          ENUM('pending','success','failed') DEFAULT 'pending',
            attempts        TINYINT(3) UNSIGNED DEFAULT 0,
            last_error      TEXT,
            delivered_at    DATETIME DEFAULT NULL,
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY channel (channel),
            KEY status (status)
        ) $charset;";

        $logs = "CREATE TABLE {$wpdb->prefix}cf7on_logs (
            id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            delivery_id   BIGINT(20) UNSIGNED DEFAULT NULL,
            channel       VARCHAR(50) NOT NULL,
            level         ENUM('info','warning','error') DEFAULT 'info',
            message       TEXT NOT NULL,
            context       LONGTEXT,
            created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY channel (channel),
            KEY level (level),
            KEY created_at (created_at)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $submissions );
        dbDelta( $deliveries );
        dbDelta( $logs );

        update_option( 'cf7on_db_version', CF7ON_VERSION );
    }

    private static function setDefaultOptions(): void {
        $defaults = [
            'cf7on_telegram' => [
                'bot_token'   => '',
                'destinations' => [],
                'template'    => "📩 New Contact Request\n\nName: {name}\nPhone: {phone}\nEmail: {email}\n\nMessage:\n{message}\n\nForm: {form_name}\nTime: {submission_date}",
                'enabled'     => false,
            ],
            'cf7on_bale' => [
                'bot_token'   => '',
                'destinations' => [],
                'template'    => "📩 New Contact Request\n\nName: {name}\nPhone: {phone}\nEmail: {email}\n\nMessage:\n{message}\n\nForm: {form_name}\nTime: {submission_date}",
                'enabled'     => false,
            ],
            'cf7on_engine' => [
                'queue_mode'       => 'async',
                'max_retries'      => 3,
                'retry_interval'   => 90,
                'logging_enabled'  => true,
                'mask_sensitive'   => true,
            ],
            'cf7on_automation_rules' => [],
        ];

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }
}
