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
        $response = wp_remote_get(
            "https://tapi.bale.ai/bot{$botToken}/getMe",
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
