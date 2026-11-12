<?php
namespace CF7ON\Modules\Automation;

defined( 'ABSPATH' ) || exit;

/**
 * Automation Engine — evaluates rules against a submission and returns targets.
 *
 * Condition types
 * ───────────────
 *   form_id        → submission matches a specific CF7 form (dropdown)
 *   form_name      → form title contains / equals a string
 *   field_name     → a field whose NAME matches a pattern has a value that matches
 *                    This is the cross-form solution: instead of {radio-289},
 *                    match field_name contains "radio" + value contains "sale"
 *   field_exact    → field with an exact known name (classic: field name = X, value = Y)
 *
 * Logic
 * ─────
 *   AND — all conditions must pass
 *   OR  — any condition passes
 */
class AutomationEngine {

    public function register(): void {}

    public function resolveTargets( array $submission ): array {
        $rules   = get_option( 'cf7on_automation_rules', [] );
        $targets = [];

        foreach ( $rules as $rule ) {
            if ( empty( $rule['enabled'] ) ) {
                continue;
            }

            // Support legacy single-condition format
            $conditions = $rule['conditions'] ?? ( isset( $rule['condition'] ) ? [ $rule['condition'] ] : [] );
            $logic      = strtoupper( $rule['logic'] ?? 'AND' );

            if ( ! empty( $conditions ) && $this->evaluateConditions( $conditions, $logic, $submission ) ) {
                foreach ( (array) ( $rule['actions'] ?? [] ) as $action ) {
                    $targets[] = [
                        'channel'     => sanitize_key( $action['channel'] ?? '' ),
                        'destination' => sanitize_text_field( $action['destination'] ?? '' ),
                        'template'    => $action['template'] ?? '',  // optional per-action template override
                    ];
                }
            }
        }

        return $targets;
    }

    // ── Evaluate a set of conditions ─────────────────────────────────────────

    private function evaluateConditions( array $conditions, string $logic, array $submission ): bool {
        foreach ( $conditions as $cond ) {
            $result = $this->conditionMatches( $cond, $submission );
            if ( $logic === 'OR'  && $result  ) return true;
            if ( $logic === 'AND' && ! $result ) return false;
        }
        return $logic === 'AND';
    }

    private function conditionMatches( array $c, array $submission ): bool {
        $type = $c['type'] ?? 'field_exact';

        switch ( $type ) {

            // ── Match by form ID ─────────────────────────────────────────────
            case 'form_id':
                return (int) $submission['form_id'] === (int) ( $c['value'] ?? 0 );

            // ── Match by form title ──────────────────────────────────────────
            case 'form_name':
                return $this->compareString(
                    $submission['form_name'] ?? '',
                    $c['operator'] ?? 'contains',
                    $c['value'] ?? ''
                );

            // ── Match by field NAME pattern + its value ──────────────────────
            // e.g. field_name_pattern = "radio", value_operator = "contains", value = "sale"
            // Finds ALL fields whose name matches the pattern, checks if any value matches.
            case 'field_name':
                $name_op    = $c['name_operator'] ?? 'contains';
                $name_pat   = $c['name_value']    ?? '';
                $val_op     = $c['operator']      ?? 'contains';
                $val_expect = $c['value']         ?? '';

                foreach ( $submission['fields'] ?? [] as $field_name => $field_value ) {
                    if ( $this->compareString( (string) $field_name, $name_op, $name_pat ) ) {
                        $actual = is_array( $field_value )
                            ? implode( ', ', array_filter( array_map( 'strval', $field_value ) ) )
                            : (string) $field_value;
                        if ( $this->compareValues( $actual, $val_op, $val_expect ) ) {
                            return true; // any matching field is enough
                        }
                    }
                }
                return false;

            // ── Match by exact field name + value ────────────────────────────
            case 'field_exact':
            default:
                $field_name = $c['field'] ?? '';
                $raw        = $submission['fields'][ $field_name ] ?? null;
                if ( $raw === null ) return false;
                $actual = is_array( $raw )
                    ? implode( ', ', array_filter( array_map( 'strval', $raw ) ) )
                    : (string) $raw;
                return $this->compareValues( $actual, $c['operator'] ?? '=', $c['value'] ?? '' );
        }
    }

    // ── Comparison helpers ────────────────────────────────────────────────────

    private function compareString( string $actual, string $op, string $expected ): bool {
        return $this->compareValues( $actual, $op, $expected );
    }

    private function compareValues( string $actual, string $op, string $expected ): bool {
        switch ( $op ) {
            case '=':            return $actual === $expected;
            case '!=':           return $actual !== $expected;
            case '>':            return is_numeric($actual) && is_numeric($expected) && (float)$actual >  (float)$expected;
            case '<':            return is_numeric($actual) && is_numeric($expected) && (float)$actual <  (float)$expected;
            case '>=':           return is_numeric($actual) && is_numeric($expected) && (float)$actual >= (float)$expected;
            case '<=':           return is_numeric($actual) && is_numeric($expected) && (float)$actual <= (float)$expected;
            case 'contains':     return $expected !== '' && mb_stripos( $actual, $expected ) !== false;
            case 'not_contains': return $expected === '' || mb_stripos( $actual, $expected ) === false;
            case 'starts':       return $expected !== '' && mb_stripos( $actual, $expected ) === 0;
            case 'ends':         return $expected !== '' && mb_strrpos( mb_strtolower($actual), mb_strtolower($expected) ) === mb_strlen($actual) - mb_strlen($expected);
            case 'regex':
                $pattern = '@' . str_replace('@', '\@', $expected) . '@iu';
                return (bool) @preg_match( $pattern, $actual );
            case 'empty':        return $actual === '';
            case 'not_empty':    return $actual !== '';
            default:             return false;
        }
    }
}
