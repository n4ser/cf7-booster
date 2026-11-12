<?php
namespace CF7ON\Models;

defined( 'ABSPATH' ) || exit;

class Delivery {

    public static function create( int $submissionId, string $channel, string $destination ): int {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'cf7on_deliveries',
            [
                'submission_id' => $submissionId,
                'channel'       => sanitize_key( $channel ),
                'destination'   => sanitize_text_field( $destination ),
                'status'        => 'pending',
                'attempts'      => 0,
                'created_at'    => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%s', '%d', '%s' ]
        );
        return (int) $wpdb->insert_id;
    }

    public static function find( int $id ): ?array {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}cf7on_deliveries WHERE id = %d", $id ),
            ARRAY_A
        );
    }

    public static function markSuccess( int $id ): void {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'cf7on_deliveries',
            [ 'status' => 'success', 'delivered_at' => current_time( 'mysql' ) ],
            [ 'id'     => $id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );
    }

    public static function markFailed( int $id, string $error = '' ): void {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'cf7on_deliveries',
            [ 'status' => 'failed', 'last_error' => sanitize_textarea_field( $error ) ],
            [ 'id'     => $id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );
    }

    public static function incrementAttempt( int $id, string $error = '' ): void {
        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}cf7on_deliveries SET attempts = attempts + 1, last_error = %s WHERE id = %d",
            sanitize_textarea_field( $error ),
            $id
        ) );
    }

    public static function forSubmission( int $submissionId ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cf7on_deliveries WHERE submission_id = %d",
                $submissionId
            ),
            ARRAY_A
        ) ?: [];
    }
}
