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
            'parse_mode' => 'HTML',
        ];
    }

    /**
     * Test the bot token and return account info.
     */
    public function testConnection( string $botToken ): array {
        $response = wp_remote_get(
            "https://api.telegram.org/bot{$botToken}/getMe",
            [ 'timeout' => 10 ]
        );

        if ( is_wp_error( $response ) ) {
            return [ 'success' => false, 'error' => $response->get_error_message() ];
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['ok'] ) ) {
            return [ 'success' => true, 'bot' => $body['result'] ];
        }

        return [ 'success' => false, 'error' => $body['description'] ?? 'Unknown error' ];
    }
}
