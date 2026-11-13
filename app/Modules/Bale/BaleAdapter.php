<?php
namespace CF7ON\Modules\Bale;

defined( 'ABSPATH' ) || exit;

use CF7ON\Services\ChannelAdapter;

class BaleAdapter extends ChannelAdapter {

    protected function getOptionKey(): string {
        return 'cf7on_bale';
    }

    protected function getApiEndpoint( string $botToken ): string {
        return "https://tapi.bale.ai/bot{$botToken}/sendMessage";
    }

    protected function buildPayload( string $message, string $destination ): array {
        return [
            'chat_id' => $destination,
            'text'    => $message,
        ];
    }

    /**
     * Test the Bale bot token.
     */
    public function testConnection( string $botToken ): array {
        if ( ! preg_match( '/^[A-Za-z0-9:_-]{8,250}$/D', $botToken ) ) {
            return [ 'success' => false, 'error' => 'Invalid bot token format.' ];
        }
        $response = wp_safe_remote_get(
            "https://tapi.bale.ai/bot{$botToken}/getMe",
            [ 'timeout' => 10, 'redirection' => 0 ]
        );
        if ( is_wp_error( $response ) ) {
            return [ 'success' => false, 'error' => 'Unable to reach messaging API.' ];
        }
        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( $code >= 200 && $code < 300 && is_array( $body ) && ! empty( $body['ok'] ) ) {
            $bot = isset( $body['result'] ) && is_array( $body['result'] ) ? $body['result'] : [];
            return [ 'success' => true, 'bot' => [
                'username' => sanitize_text_field( $bot['username'] ?? '' ),
            ] ];
        }
        return [ 'success' => false, 'error' => 'Bot verification failed (HTTP ' . $code . ').' ];
    }
}
