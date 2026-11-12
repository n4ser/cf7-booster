<?php
/**
 * Smoke tests for settings persistence, guarded test sends, and retention SQL.
 * These are mocks, not an integration test with a real WordPress database.
 */
declare(strict_types=1);

namespace {
    define( 'ABSPATH', __DIR__ . '/' );
    define( 'DAY_IN_SECONDS', 86400 );

    class AjaxDone extends \RuntimeException {}

    class TestWpdb {
        public string $prefix = 'wp_';
        public array $queries = [];
        public array $ids = [];
        public function prepare( $sql, ...$args ): string {
            foreach ( $args as $value ) {
                $sql = preg_replace( '/%s/', "'" . str_replace( "'", "''", (string) $value ) . "'", $sql, 1 );
            }
            return $sql;
        }
        public function get_col( $query ): array {
            $this->queries[] = $query;
            $ids = $this->ids;
            $this->ids = [];
            return $ids;
        }
        public function query( $query ) {
            $this->queries[] = $query;
            return strpos( $query, 'LIMIT 500' ) !== false ? 2 : 1;
        }
    }

    $GLOBALS['wpdb'] = new TestWpdb();
    $GLOBALS['mock_options'] = [
        'cf7on_engine' => [ 'queue_mode' => 'sync', 'logging_enabled' => false ],
        'cf7on_telegram' => [ 'enabled' => false, 'bot_token' => '', 'template' => 'Old template' ],
        'cf7on_bale' => [ 'enabled' => false, 'bot_token' => '', 'template' => 'Old template' ],
    ];
    $GLOBALS['ajax_result'] = null;

    function get_option( $key, $default = false ) { return $GLOBALS['mock_options'][ $key ] ?? $default; }
    function update_option( $key, $value ): bool { $GLOBALS['mock_options'][ $key ] = $value; return true; }
    function check_ajax_referer( ...$args ): bool { return true; }
    function current_user_can( $capability ): bool { return $GLOBALS['is_admin'] ?? true; }
    function wp_unslash( $data ) {
        if ( is_array( $data ) ) {
            return array_map( 'wp_unslash', $data );
        }
        return is_string( $data ) ? stripslashes( $data ) : $data;
    }
    function sanitize_text_field( $value ): string { return trim( strip_tags( (string) $value ) ); }
    function sanitize_textarea_field( $value ): string { return trim( (string) $value ); }
    function sanitize_key( $value ): string { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
    function __( $text, $domain = '' ) { return $text; }
    function wp_send_json_success( $value = null ) {
        $GLOBALS['ajax_result'] = [ 'success' => true, 'data' => $value ];
        throw new AjaxDone();
    }
    function wp_send_json_error( $value = null ) {
        $GLOBALS['ajax_result'] = [ 'success' => false, 'data' => $value ];
        throw new AjaxDone();
    }
    function wp_date( $format, $timestamp = null ): string { return gmdate( $format, $timestamp ?? time() ); }
    function add_action( ...$args ): void {}
    function absint( $number ): int { return abs( (int) $number ); }
    function wp_next_scheduled( $hook ) { return false; }
    function wp_schedule_event( ...$args ) { $GLOBALS['schedule_called'] = true; return true; }

    function verify( bool $pass, string $message ): void {
        if ( ! $pass ) throw new \RuntimeException( $message );
    }
    function ajax( callable $handler ): array {
        $GLOBALS['ajax_result'] = null;
        try {
            $handler();
        } catch ( AjaxDone $ignore ) {}
        verify( is_array( $GLOBALS['ajax_result'] ), 'AJAX handler did not return a result.' );
        return $GLOBALS['ajax_result'];
    }
}

namespace CF7ON\Core {
    class Plugin {
        private static $instance;
        public static function getInstance() {
            if ( ! self::$instance ) self::$instance = new self();
            return self::$instance;
        }
        public function get( $name ) {
            return new \TestChannelAdapter();
        }
    }
}

namespace {
    class TestChannelAdapter {
        public function send( array $data, array $target ): array {
            $GLOBALS['last_test_send'] = [ 'data' => $data, 'target' => $target ];
            return [ 'success' => true ];
        }
    }

    require_once dirname( __DIR__ ) . '/app/Services/EngineSettings.php';
    require_once dirname( __DIR__ ) . '/app/Services/MaintenanceService.php';
    require_once dirname( __DIR__ ) . '/app/Modules/Dashboard/DashboardModule.php';

    $settingsClass = \CF7ON\Services\EngineSettings::class;
    $settings = $settingsClass::current();
    verify( $settings['queue_mode'] === 'sync', 'Existing saved queue mode was lost.' );
    verify( $settings['logging_enabled'] === false, 'Existing disabled logging option was lost.' );
    verify( $settings['notifications_paused'] === false, 'Notifications should not be paused by default.' );
    verify( $settings['retention_days'] === 0, 'Retention must be opt-in.' );

    $sanitized = $settingsClass::sanitize([
        'queue_mode' => 'evil',
        'max_retries' => 999,
        'retry_interval' => -12,
        'retention_days' => 8000,
        'log_retention_days' => 'abc',
        'notifications_paused' => '1',
        'store_ip_address' => '',
        'store_user_agent' => '1',
        'logging_enabled' => '1',
    ]);
    verify( $sanitized['queue_mode'] === 'async', 'Queue mode allowlist failed.' );
    verify( $sanitized['max_retries'] === 10, 'Retry clamp failed.' );
    verify( $sanitized['retry_interval'] === 90, 'Invalid interval must use default.' );
    verify( $sanitized['retention_days'] === 3650, 'Retention clamp failed.' );
    verify( $sanitized['log_retention_days'] === 0, 'Invalid log retention must be off.' );
    verify( $sanitized['notifications_paused'] === true, 'Pause switch was ignored.' );
    verify( $sanitized['store_ip_address'] === false, 'Privacy switch was ignored.' );

    $dashboard = new \CF7ON\Modules\Dashboard\DashboardModule();
    $_POST = [
        'queue_mode' => 'async',
        'max_retries' => '2',
        'retry_interval' => '50',
        'logging_enabled' => '1',
        'mask_sensitive' => '1',
        'notifications_paused' => '1',
        'retention_days' => '30',
        'log_retention_days' => '14',
        'store_ip_address' => '',
        'store_user_agent' => '1',
    ];
    verify( ajax( [ $dashboard, 'ajaxSaveSettings' ] )['success'], 'Engine options could not be saved.' );
    $saved = $settingsClass::current();
    verify( $saved['retention_days'] === 30 && $saved['log_retention_days'] === 14, 'Retention values were not persisted.' );
    verify( $saved['notifications_paused'] && ! $saved['store_ip_address'], 'Checkbox values were not persisted.' );

    $_POST = [
        'enabled' => '1',
        'bot_token' => '123:abc',
        'destinations' => [ '@news', '@news', '', [ 'invalid' ], '-10012' ],
    ];
    verify( ajax( [ $dashboard, 'ajaxSaveTelegram' ] )['success'], 'Telegram settings not saved.' );
    $tg = get_option( 'cf7on_telegram' );
    verify( $tg['destinations'] === [ '@news', '-10012' ], 'Destination sanitization or deduplication failed.' );
    verify( $tg['template'] === 'Old template', 'Global template lost during save.' );

    $_POST = [ 'enabled' => '', 'bot_token' => '456:def', 'destinations' => [ '@bale' ], 'template' => 'Bale: {name}' ];
    verify( ajax( [ $dashboard, 'ajaxSaveBale' ] )['success'], 'Bale settings not saved.' );
    verify( get_option( 'cf7on_bale' )['template'] === 'Bale: {name}', 'Bale template not persisted.' );

    $_POST = [ 'channel' => 'telegram' ];
    verify( ! ajax( [ $dashboard, 'ajaxSendTest' ] )['success'], 'Paused delivery should reject a test send.' );
    $GLOBALS['mock_options']['cf7on_engine']['notifications_paused'] = false;
    verify( ajax( [ $dashboard, 'ajaxSendTest' ] )['success'], 'Test delivery should work after resuming.' );
    verify( $GLOBALS['last_test_send']['target']['destination'] === '@news', 'Unexpected recipient of test message.' );
    verify( empty( $GLOBALS['last_test_send']['data']['fields'] ), 'Test message included submission fields.' );

    $_POST = [ 'channel' => 'unknown' ];
    verify( ! ajax( [ $dashboard, 'ajaxSendTest' ] )['success'], 'Unknown channel accepted.' );
    $GLOBALS['is_admin'] = false;
    $_POST = [ 'channel' => 'telegram' ];
    verify( ! ajax( [ $dashboard, 'ajaxSendTest' ] )['success'], 'Insufficient permissions were accepted.' );
    $GLOBALS['is_admin'] = true;

    $GLOBALS['mock_options']['cf7on_engine']['retention_days'] = 0;
    $GLOBALS['mock_options']['cf7on_engine']['log_retention_days'] = 0;
    $maint = new \CF7ON\Services\MaintenanceService();
    $maint->register();
    verify( ! empty( $GLOBALS['schedule_called'] ), 'Daily cleanup was not scheduled.' );
    $maint->run();
    verify( ! $GLOBALS['wpdb']->queries, 'Cleanup ran despite disabled retention.' );

    $GLOBALS['mock_options']['cf7on_engine']['retention_days'] = 30;
    $GLOBALS['mock_options']['cf7on_engine']['log_retention_days'] = 7;
    $GLOBALS['wpdb']->ids = [ '8', '9' ];
    $maint->run();
    $queries = implode( "\n", $GLOBALS['wpdb']->queries );
    verify( strpos( $queries, 'DELETE FROM wp_cf7on_logs WHERE delivery_id IN' ) !== false, 'Delivery logs were not cleaned.' );
    verify( strpos( $queries, 'DELETE FROM wp_cf7on_deliveries WHERE submission_id IN (8,9)' ) !== false, 'Delivery rows were not cleaned.' );
    verify( strpos( $queries, 'DELETE FROM wp_cf7on_submissions WHERE id IN (8,9)' ) !== false, 'Submission rows were not cleaned.' );
    verify( strpos( $queries, 'DELETE FROM wp_cf7on_logs WHERE created_at' ) !== false, 'Old logs were not cleaned.' );

    echo "Settings, test-message, and retention smoke tests passed.\n";
}
