<?php
namespace CF7ON\Services;

defined( 'ABSPATH' ) || exit;

class LogService {

    public static function write(
        string $channel,
        string $level,
        string $message,
        array $context = [],
        ?int $deliveryId = null
    ): void {
        $engine = get_option( 'cf7on_engine', [] );
        if ( empty( $engine['logging_enabled'] ) ) {
            return;
        }

        // Mask sensitive data if configured
        if ( ! empty( $engine['mask_sensitive'] ) ) {
            $context = self::maskContext( $context );
        }

        // Scrub credentials from messages and context before persisting.
        $scrub = static function ( $value ) {
            return is_string( $value )
                ? preg_replace( '/[0-9]{5,15}:[A-Za-z0-9_-]{10,}/', '[redacted-token]', $value )
                : $value;
        };
        $message = $scrub( $message );
        array_walk_recursive( $context, static function ( &$value ) use ( $scrub ) {
            $value = $scrub( $value );
        } );

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'cf7on_logs',
            [
                'delivery_id' => $deliveryId,
                'channel'     => sanitize_key( $channel ),
                'level'       => in_array( $level, [ 'info', 'warning', 'error' ], true ) ? $level : 'info',
                'message'     => sanitize_textarea_field( $message ),
                'context'     => wp_json_encode( $context ),
                'created_at'  => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s' ]
        );
    }

    public static function getRecent( array $args = [] ): array {
        global $wpdb;

        $where  = '1=1';
        $values = [];

        if ( ! empty( $args['channel'] ) ) {
            $where   .= ' AND channel = %s';
            $values[] = sanitize_key( $args['channel'] );
        }
        if ( ! empty( $args['level'] ) ) {
            $where   .= ' AND level = %s';
            $values[] = sanitize_key( $args['level'] );
        }

        $limit  = absint( $args['limit'] ?? 50 );
        $sql    = "SELECT * FROM {$wpdb->prefix}cf7on_logs WHERE $where ORDER BY created_at DESC LIMIT %d";
        $values[] = $limit;

        return $wpdb->get_results(
            $wpdb->prepare( $sql, $values ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Mask phone numbers and email addresses in log context arrays.
     */
    private static function maskContext( array $context ): array {
        array_walk_recursive( $context, function ( &$value ) {
            if ( ! is_string( $value ) ) {
                return;
            }
            // Mask email: us***@domain.com
            if ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
                [ $local, $domain ] = explode( '@', $value, 2 );
                $value = substr( $local, 0, 2 ) . '***@' . $domain;
            }
            // Mask phone-like strings (7+ digits)
            if ( preg_match( '/^\+?[\d\s\-()]{7,}$/', $value ) ) {
                $digits = preg_replace( '/\D/', '', $value );
                $len    = strlen( $digits );
                $value  = substr( $digits, 0, 4 ) . str_repeat( '*', max( 0, $len - 6 ) ) . substr( $digits, -2 );
            }
        } );
        return $context;
    }
}
