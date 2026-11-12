<?php
namespace CF7ON\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for persisted notification, privacy and queue settings.
 * New keys have safe defaults so existing installations need no data migration.
 */
final class EngineSettings {
    public static function defaults(): array {
        return [
            'queue_mode'          => 'async',
            'max_retries'         => 3,
            'retry_interval'      => 90,
            'logging_enabled'     => true,
            'mask_sensitive'      => true,
            'notifications_paused'=> false,
            'retention_days'      => 0,
            'log_retention_days'  => 0,
            'store_ip_address'    => true,
            'store_user_agent'    => true,
        ];
    }

    public static function current(): array {
        $stored = get_option( 'cf7on_engine', [] );
        return array_merge( self::defaults(), is_array( $stored ) ? $stored : [] );
    }

    /**
     * All settings are saved atomically. Booleans are explicit, numerical values
     * are clamped to UI bounds, and queue mode is checked against an allowlist.
     */
    public static function sanitize( array $input ): array {
        $defaults = self::defaults();
        $mode = $input['queue_mode'] ?? $defaults['queue_mode'];
        $mode = is_scalar( $mode ) ? (string) $mode : '';
        $settings = [
            'queue_mode'     => in_array( $mode, [ 'sync', 'async' ], true ) ? $mode : 'async',
            'max_retries'    => self::integer( $input['max_retries'] ?? 3, 0, 10, 3 ),
            'retry_interval' => self::integer( $input['retry_interval'] ?? 90, 10, 86400, 90 ),
        ];
        foreach ( [
            'logging_enabled', 'mask_sensitive', 'notifications_paused',
            'store_ip_address', 'store_user_agent',
        ] as $name ) {
            $value = $input[ $name ] ?? false;
            $settings[ $name ] = $value === true || $value === 1 || $value === '1' || $value === 'on';
        }
        foreach ( [ 'retention_days', 'log_retention_days' ] as $name ) {
            $settings[ $name ] = self::integer( $input[ $name ] ?? 0, 0, 3650, 0 );
        }
        return $settings;
    }

    private static function integer( $value, int $min, int $max, int $fallback ): int {
        if ( ! is_scalar( $value ) || ! preg_match( '/^\d+$/', (string) $value ) ) {
            return $fallback;
        }
        return max( $min, min( $max, (int) $value ) );
    }
}
