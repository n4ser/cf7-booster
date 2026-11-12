<?php
/**
 * Lightweight queue behavior tests with WordPress/CF7 dependencies mocked.
 * Run: php tests/queue-smoke.php
 */
declare(strict_types=1);

namespace {
    define( 'ABSPATH', __DIR__ . '/' );
    $GLOBALS['cf7on_test_options'] = [
        'cf7on_engine'   => [ 'queue_mode' => 'sync', 'max_retries' => 1, 'retry_interval' => 10 ],
        'cf7on_telegram' => [ 'enabled' => true, 'destinations' => [ '@global' ], 'template' => 'Global: {name}' ],
        'cf7on_bale'     => [ 'enabled' => false, 'destinations' => [] ],
    ];
    $GLOBALS['cf7on_test_events'] = [];

    function get_option( $name, $default = false ) {
        return $GLOBALS['cf7on_test_options'][ $name ] ?? $default;
    }
    function add_action( ...$args ): void {}
    function absint( $value ): int { return abs( (int) $value ); }
    function sanitize_key( $value ): string {
        return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
    }
    function sanitize_text_field( $value ): string { return trim( (string) $value ); }
    function wp_next_scheduled( $hook, $args = [] ) {
        foreach ( $GLOBALS['cf7on_test_events'] as $event ) {
            if ( $event['hook'] === $hook && $event['args'] === $args ) {
                return $event['time'];
            }
        }
        return false;
    }
    function wp_schedule_single_event( $time, $hook, $args = [] ): bool {
        $GLOBALS['cf7on_test_events'][] = [ 'time' => $time, 'hook' => $hook, 'args' => $args ];
        return true;
    }
}

namespace CF7ON\Models {
    class Submission {
        public static array $rows = [];
        public static function find( int $id ): ?array { return self::$rows[ $id ] ?? null; }
        public static function updateStatus( int $id, string $status ): void {
            self::$rows[ $id ]['status'] = $status;
        }
    }

    class Delivery {
        public static array $rows = [];
        public static function create( int $submissionId, string $channel, string $destination ): int {
            $id = count( self::$rows ) + 1;
            self::$rows[ $id ] = [
                'id' => $id, 'submission_id' => $submissionId,
                'channel' => $channel, 'destination' => $destination,
                'status' => 'pending', 'attempts' => 0,
            ];
            return $id;
        }
        public static function find( int $id ): ?array { return self::$rows[ $id ] ?? null; }
        public static function forSubmission( int $id ): array {
            return array_values( array_filter( self::$rows, static function ( $row ) use ( $id ) {
                return $row['submission_id'] === $id;
            } ) );
        }
        public static function markSuccess( int $id ): void { self::$rows[ $id ]['status'] = 'success'; }
        public static function markFailed( int $id, string $error = '' ): void { self::$rows[ $id ]['status'] = 'failed'; }
        public static function incrementAttempt( int $id, string $error = '' ): void {
            ++self::$rows[ $id ]['attempts'];
        }
    }
}

namespace CF7ON\Modules\FormTab {
    class FormTabModule {
        public static array $settings = [];
        public static function getFormSettings( int $formId ): array {
            return self::$settings[ $formId ] ?? [];
        }
    }
}

namespace CF7ON\Modules\Automation {
    class AutomationEngine {
        public array $targets = [];
        public function resolveTargets( array $data ): array { return $this->targets; }
    }
}

namespace CF7ON\Services {
    class LogService {
        public static array $messages = [];
        public static function write( ...$args ): void { self::$messages[] = $args; }
    }
}

namespace {
    require_once dirname( __DIR__ ) . '/app/Services/QueueService.php';

    class TestAdapter {
        public array $responses = [];
        public array $sent = [];
        public function send( array $data, array $target ): array {
            $this->sent[] = $target;
            return $this->responses ? array_shift( $this->responses ) : [ 'success' => true ];
        }
    }

    function expect( bool $success, string $message ): void {
        if ( ! $success ) {
            throw new \RuntimeException( $message );
        }
    }

    $queue = new \CF7ON\Services\QueueService();
    $adapter = new TestAdapter();
    $automation = new \CF7ON\Modules\Automation\AutomationEngine();
    $queue->setAdapters( [ 'telegram' => $adapter ] );
    $queue->setAutomation( $automation );
    $queue->register();

    foreach ( [ 1, 2, 3, 4 ] as $id ) {
        \CF7ON\Models\Submission::$rows[ $id ] = [
            'form_id' => $id, 'fields' => [ 'name' => 'Sample' ],
            'status' => 'pending', 'form_name' => 'Test form',
        ];
    }

    // A form with disabled channels must NOT fall back to the global enabled channel.
    \CF7ON\Modules\FormTab\FormTabModule::$settings[1] = [
        'telegram' => [ 'enabled' => false ],
        'bale' => [ 'enabled' => false ],
    ];
    $queue->processJob( 1 );
    expect( count( \CF7ON\Models\Delivery::$rows ) === 0, 'Disabled form produced a delivery.' );
    expect( \CF7ON\Models\Submission::$rows[1]['status'] !== 'delivered', 'Disabled form marked delivered.' );

    // Global routing works and replaying a completed cron event is idempotent.
    \CF7ON\Modules\FormTab\FormTabModule::$settings[1] = [];
    $queue->processJob( 1 );
    $queue->processJob( 1 );
    expect( count( \CF7ON\Models\Delivery::forSubmission(1) ) === 1, 'Duplicate delivery created.' );
    expect( \CF7ON\Models\Submission::$rows[1]['status'] === 'delivered', 'Successful submission not delivered.' );

    // Failed first attempt should stay pending until its retry succeeds.
    $adapter->responses = [ [ 'success' => false, 'error' => 'timeout' ], [ 'success' => true ] ];
    $queue->processJob( 2 );
    expect( \CF7ON\Models\Submission::$rows[2]['status'] === 'pending', 'Pending retry incorrectly finalized.' );
    $id = \CF7ON\Models\Delivery::forSubmission(2)[0]['id'];
    $queue->retryDelivery( $id, 1 );
    expect( \CF7ON\Models\Submission::$rows[2]['status'] === 'delivered', 'Retry success did not update status.' );

    // Disabling automatic retries still allows manual resending of failed deliveries.
    $GLOBALS['cf7on_test_options']['cf7on_engine']['max_retries'] = 0;
    $adapter->responses = [ [ 'success' => false, 'error' => 'timeout' ], [ 'success' => true ] ];
    $queue->processJob( 3 );
    expect( \CF7ON\Models\Submission::$rows[3]['status'] === 'failed', 'Final failure status not recorded.' );
    expect( $queue->resendFailed(3) === 1, 'Manual resend did not retry failed delivery.' );
    expect( \CF7ON\Models\Submission::$rows[3]['status'] === 'delivered', 'Manual resend did not complete.' );
    expect( $queue->resendFailed(3) === 0, 'Already delivered message was resent again.' );

    // Form-specific template wins over the global template.
    \CF7ON\Modules\FormTab\FormTabModule::$settings[4] = [
        'telegram' => [ 'enabled' => true, 'destinations' => [ '@specific' ], 'template' => 'Per form' ],
    ];
    $queue->processJob( 4 );
    $last = end( $adapter->sent );
    expect( $last['destination'] === '@specific' && $last['template'] === 'Per form',
        'Per-form destination or template was not applied.' );

    echo "Queue smoke tests passed.\n";
}
