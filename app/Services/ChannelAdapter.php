<?php
namespace CF7ON\Services;

defined( 'ABSPATH' ) || exit;

abstract class ChannelAdapter {

    abstract protected function getOptionKey(): string;
    abstract protected function getApiEndpoint( string $destination ): string;
    abstract protected function buildPayload( string $message, string $destination ): array;

    public function send( array $data, array $target ): array {
        $options = get_option( $this->getOptionKey(), [] );

        if ( empty( $options['bot_token'] ) ) {
            return [ 'success' => false, 'error' => 'Bot token not configured' ];
        }

        $destination = $target['destination'] ?? '';
        if ( empty( $destination ) ) {
            return [ 'success' => false, 'error' => 'No destination specified' ];
        }

        // Use rule-specific template if provided, otherwise fall back to global
        $template = $target['template'] ?? $options['template'] ?? '';
        if ( ! is_string( $template ) || trim( $template ) === '' ) {
            // Saving global settings without a template must never send a blank notification.
            $template = "📩 {form_name}\n\n{all_fields}";
        }
        $message  = $this->renderTemplate( $template, $data );
        $payload  = $this->buildPayload( $message, $destination );
        $token = (string) $options['bot_token'];
        if ( ! preg_match( '/^[A-Za-z0-9:_-]{8,250}$/D', $token ) ) {
            return [ 'success' => false, 'retryable' => false, 'error' => 'Invalid bot token format.' ];
        }
        $url = $this->getApiEndpoint( $token );

        $response = wp_safe_remote_post( $url, [
            'timeout' => 15,
            'redirection' => 0,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body' => wp_json_encode( $payload ),
        ] );

        if ( is_wp_error( $response ) ) {
            LogService::write( $this->getOptionKey(), 'error', 'Messaging transport failure.' );
            return [ 'success' => false, 'retryable' => true, 'error' => 'Network request failed.' ];
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( $code >= 200 && $code < 300 && is_array( $body ) && ! empty( $body['ok'] ) ) {
            LogService::write( $this->getOptionKey(), 'info', 'Message delivered.' );
            return [ 'success' => true, 'retryable' => false ];
        }

        $retryable = $code === 429 || $code >= 500 || $code === 0
            || ( $code >= 200 && $code < 300 );
        LogService::write( $this->getOptionKey(), 'error', 'Messaging API returned HTTP ' . $code );
        return [ 'success' => false, 'retryable' => $retryable, 'error' => 'HTTP ' . $code ];
    }

    /** Convert any CF7 field value (string or array) to a plain string. */
    protected function fieldToString( $value ): string {
        if ( is_array( $value ) ) {
            $value = implode( ', ', array_filter( array_map( 'strval', $value ), fn( $v ) => $v !== '' ) );
        }
        return (string) $value;
    }

    /**
     * Replace placeholders in a template with submission values.
     *
     * HOW PLACEHOLDERS WORK
     * ─────────────────────
     * 1. Exact field name:  {your-name}, {radio-289}  → exact CF7 field
     *
     * 2. Base-type alias:   {radio}  → value of the FIRST radio field found
     *                       {checkbox} → first checkbox field
     *                       {select}   → first select field
     *                       Useful when the same form structure is reused across
     *                       multiple forms but field IDs differ (radio-289, radio-389…)
     *
     * 3. Semantic aliases:  {name} → any field named your-name / full-name / etc.
     *                       {email}, {phone}, {message}, {subject}
     *
     * 4. System values:     {form_name}, {submission_date}, {form_id},
     *                       {ip_address}, {referrer}
     *
     * 5. All fields:        {all_fields} → every field as "Label: Value" list
     *
     * Unmatched placeholders are silently removed from the output.
     */
    protected function renderTemplate( string $template, array $data ): string {
        $fields = $data['fields'] ?? [];

        // ── 4. System placeholders ──────────────────────────────────────────
        $replace = [
            '{form_name}'       => $data['form_name'] ?? '',
            '{submission_date}' => $data['created_at'] ?? current_time( 'Y-m-d H:i' ),
            '{form_id}'         => (string) ( $data['form_id'] ?? '' ),
            '{ip_address}'      => $data['ip_address'] ?? '',
            '{referrer}'        => $data['referrer'] ?? '',
        ];

        // ── 1. Exact field names ────────────────────────────────────────────
        foreach ( $fields as $key => $value ) {
            $replace[ '{' . $key . '}' ] = $this->fieldToString( $value );
        }

        // ── 2. Base-type aliases  e.g. {radio} → first radio field value ───
        // Build a map of basetype → [field_name, …]
        $form_id = (int) ( $data['form_id'] ?? 0 );
        $type_map = [];  // basetype => first value found
        if ( $form_id ) {
            $cf7_obj = \WPCF7_ContactForm::get_instance( $form_id );
            if ( $cf7_obj ) {
                foreach ( $cf7_obj->scan_form_tags() as $tag ) {
                    if ( empty( $tag->name ) ) continue;
                    $basetype = $tag->basetype; // 'radio', 'checkbox', 'select', 'text', etc.
                    // First occurrence of each basetype wins
                    if ( ! isset( $type_map[ $basetype ] ) && array_key_exists( $tag->name, $fields ) ) {
                        $type_map[ $basetype ] = $this->fieldToString( $fields[ $tag->name ] );
                    }
                    // Also numbered: {radio-1}, {radio-2} for second radio field, etc.
                    // (track count)
                }
            }
        }
        foreach ( $type_map as $basetype => $value ) {
            // Only set if not already claimed by an exact field named the same
            if ( ! isset( $replace[ '{' . $basetype . '}' ] ) ) {
                $replace[ '{' . $basetype . '}' ] = $value;
            }
        }

        // ── 3. Semantic convenience aliases ────────────────────────────────
        $aliases = [
            'name'    => [ 'name', 'your-name', 'full-name', 'fullname', 'full_name' ],
            'email'   => [ 'email', 'your-email', 'email-address', 'email_address' ],
            'phone'   => [ 'phone', 'tel', 'your-phone', 'phone-number', 'mobile', 'telephone' ],
            'message' => [ 'message', 'your-message', 'msg', 'text', 'content', 'body' ],
            'subject' => [ 'subject', 'your-subject' ],
        ];
        foreach ( $aliases as $placeholder => $keys ) {
            if ( ! isset( $replace[ '{' . $placeholder . '}' ] ) ) {
                foreach ( $keys as $key ) {
                    if ( array_key_exists( $key, $fields ) ) {
                        $replace[ '{' . $placeholder . '}' ] = $this->fieldToString( $fields[ $key ] );
                        break;
                    }
                }
            }
        }

        // ── 5. {all_fields} — dump every field as a labelled list ──────────
        if ( strpos( $template, '{all_fields}' ) !== false ) {
            $lines = [];
            foreach ( $fields as $key => $value ) {
                $label  = ucwords( str_replace( [ '-', '_' ], ' ', $key ) );
                $lines[] = $label . ': ' . $this->fieldToString( $value );
            }
            $replace['{all_fields}'] = implode( "\n", $lines );
        }

        // ── Replace & clean up ──────────────────────────────────────────────
        $result = str_replace( array_keys( $replace ), array_values( $replace ), $template );
        // Remove any leftover {placeholder} that had no match
        $result = preg_replace( '/\{[a-zA-Z0-9_\-]+\}/', '', $result );

        return $result;
    }

    public function register(): void {}
}
