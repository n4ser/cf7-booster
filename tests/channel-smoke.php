<?php
/**
 * Smoke test for real channel adapters using mocked WordPress HTTP functions.
 */
declare(strict_types=1);

namespace {
    define( 'ABSPATH', __DIR__ . '/' );
    $GLOBALS['cf7on_test_options'] = [
        'cf7on_telegram' => [ 'bot_token' => '12345:token', 'template' => '' ],
        'cf7on_bale' => [ 'bot_token' => '12345:token', 'template' => '' ],
    ];
    $GLOBALS['cf7on_test_http'] = [];
    $GLOBALS['cf7on_test_http_response'] = [
        'response' => [ 'code' => 200 ],
        'body' => '{"ok":true}',
    ];
    function get_option( $key, $default = false ) {
        return $GLOBALS['cf7on_test_options'][ $key ] ?? $default;
    }
    function wp_safe_remote_post( $url, $options = [] ) {
        $GLOBALS['cf7on_test_http'][] = [ 'url' => $url, 'options' => $options ];
        return $GLOBALS['cf7on_test_http_response'];
    }
    function is_wp_error( $value ): bool { return false; }
    function wp_remote_retrieve_response_code( $response ): int { return (int) $response['response']['code']; }
    function wp_remote_retrieve_body( $response ): string { return $response['body']; }
    function wp_json_encode( $value ): string { return json_encode( $value, JSON_UNESCAPED_UNICODE ); }
    function current_time( $format ): string { return '2026-11-13 12:00'; }
}

namespace CF7ON\Services {
    class LogService { public static function write( ...$args ): void {} }
}

namespace {
    require_once dirname( __DIR__ ) . '/app/Services/ChannelAdapter.php';
    require_once dirname( __DIR__ ) . '/app/Modules/Telegram/TelegramAdapter.php';
    require_once dirname( __DIR__ ) . '/app/Modules/Bale/BaleAdapter.php';

    function check( bool $value, string $message ): void {
        if ( ! $value ) {
            throw new \RuntimeException( $message );
        }
    }

    $submission = [
        'form_id' => 0,
        'form_name' => 'Contact',
        'fields' => [ 'your-name' => '<Alice & Bob>', 'message' => 'Hello' ],
    ];
    $telegram = new \CF7ON\Modules\Telegram\TelegramAdapter();
    $bale = new \CF7ON\Modules\Bale\BaleAdapter();

    $ok = $telegram->send( $submission, [ 'destination' => '@sample', 'template' => '' ] );
    check( $ok['success'] === true, 'Telegram send failed.' );
    $request = end( $GLOBALS['cf7on_test_http'] );
    $payload = json_decode( $request['options']['body'], true );
    check( ! empty( $payload['text'] ), 'Blank template yielded an empty message.' );
    check( strpos( $payload['text'], '<Alice & Bob>' ) !== false, 'Form fields were lost.' );
    check( ! isset( $payload['parse_mode'] ), 'Telegram should send plain text by default.' );

    $ok = $bale->send( $submission, [ 'destination' => '@sample' ] );
    check( $ok['success'] === true, 'Bale send failed.' );

    $GLOBALS['cf7on_test_http_response'] = [
        'response' => [ 'code' => 429 ],
        'body' => '{"ok":false,"description":"rate limited"}',
    ];
    $bad = $telegram->send( $submission, [ 'destination' => '@sample' ] );
    check( $bad['success'] === false, 'API failure was marked successful.' );

    echo "Channel smoke tests passed.\n";
}
