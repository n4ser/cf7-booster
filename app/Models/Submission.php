<?php
namespace CF7ON\Models;

defined( 'ABSPATH' ) || exit;

class Submission {

    private array $data;

    public function __construct( array $data ) {
        $this->data = $data;
    }

    public function save(): ?int {
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'cf7on_submissions',
            [
                'form_id'    => absint( $this->data['form_id'] ?? 0 ),
                'form_name'  => sanitize_text_field( $this->data['form_name'] ?? '' ),
                'fields'     => wp_json_encode( $this->data['fields'] ?? [] ),
                'ip_address' => sanitize_text_field( $this->data['ip_address'] ?? '' ),
                'user_agent' => sanitize_textarea_field( $this->data['user_agent'] ?? '' ),
                'referrer'   => esc_url_raw( $this->data['referrer'] ?? '' ),
                'status'     => sanitize_key( $this->data['status'] ?? 'pending' ),
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );

        return $result ? (int) $wpdb->insert_id : null;
    }

    public static function find( int $id ): ?array {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}cf7on_submissions WHERE id = %d", $id ),
            ARRAY_A
        );
        if ( ! $row ) {
            return null;
        }
        $row['fields'] = json_decode( $row['fields'], true ) ?: [];
        return $row;
    }

    public static function findAll( array $args = [] ): array {
        global $wpdb;

        $where  = '1=1';
        $values = [];

        if ( ! empty( $args['form_id'] ) ) {
            $where   .= ' AND form_id = %d';
            $values[] = absint( $args['form_id'] );
        }
        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $values[] = sanitize_key( $args['status'] );
        }
        if ( ! empty( $args['search'] ) ) {
            $where   .= ' AND (form_name LIKE %s OR fields LIKE %s)';
            $like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $values[] = $like;
            $values[] = $like;
        }

        $limit  = absint( $args['limit'] ?? 20 );
        $offset = absint( $args['offset'] ?? 0 );
        $sql    = "SELECT * FROM {$wpdb->prefix}cf7on_submissions WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $values[] = $limit;
        $values[] = $offset;

        $rows = $wpdb->get_results(
            empty( $values ) ? $sql : $wpdb->prepare( $sql, $values ),
            ARRAY_A
        );

        foreach ( $rows as &$row ) {
            $row['fields'] = json_decode( $row['fields'], true ) ?: [];
        }

        return $rows ?: [];
    }

    public static function updateStatus( int $id, string $status ): void {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'cf7on_submissions',
            [ 'status' => sanitize_key( $status ) ],
            [ 'id'     => $id ],
            [ '%s' ],
            [ '%d' ]
        );
    }

    public static function count( array $args = [] ): int {
        global $wpdb;
        $where  = '1=1';
        $values = [];

        if ( ! empty( $args['form_id'] ) ) {
            $where   .= ' AND form_id = %d';
            $values[] = absint( $args['form_id'] );
        }
        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $values[] = sanitize_key( $args['status'] );
        }
        if ( ! empty( $args['date_from'] ) ) {
            $where   .= ' AND created_at >= %s';
            $values[] = sanitize_text_field( $args['date_from'] );
        }

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}cf7on_submissions WHERE $where";
        return (int) $wpdb->get_var( empty( $values ) ? $sql : $wpdb->prepare( $sql, $values ) );
    }
}
