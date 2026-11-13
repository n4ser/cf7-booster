<?php
namespace CF7ON\Modules\Telegram;

defined( 'ABSPATH' ) || exit;

use CF7ON\Services\ChannelAdapter;

class TelegramAdapter extends ChannelAdapter {

    protected function getOptionKey(): string {
        return 'cf7on_telegram';
    }

    protected function getApiEndpoint( string $botToken ): string {
        return "https://api.telegram.org/bot{$botToken}/sendMessage";
    }

    protected function buildPayload( string $message, string $destination ): array {
        return [
            'chat_id'    => $destination,
            'text'       => $message,
        ];
    }

    /**
     * Test the bot token and return account info.
     */
    public function testConnection( string $botToken ): array {
        if ( ! preg_match( '/^[A-Za-z0-9:_-]{8,250}$/D', $botToken ) ) {
            return [ 'success' => false, 'error' => 'Invalid bot token format.' ];
        }
        $response = wp_safe_remote_get(
            "https://api.telegram.org/bot{$botToken}/getMe",
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
