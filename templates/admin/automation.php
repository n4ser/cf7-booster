<?php
defined( 'ABSPATH' ) || exit;

// Collect CF7 field names grouped by form
$all_fields = [];
$fields_by_form = [];
foreach ( $forms as $f ) {
    $cf7_obj = WPCF7_ContactForm::get_instance( $f['id'] );
    if ( ! $cf7_obj ) continue;
    $fields_by_form[ $f['id'] ] = [];
    foreach ( $cf7_obj->scan_form_tags() as $tag ) {
        if ( empty( $tag->name ) ) continue;
        $entry = [ 'name' => $tag->name, 'type' => $tag->basetype, 'form_id' => $f['id'], 'form' => $f['title'] ];
        $fields_by_form[ $f['id'] ][] = $entry;
        // deduplicate for global list
        if ( ! in_array( $tag->name, array_column( $all_fields, 'name' ), true ) ) {
            $all_fields[] = $entry;
        }
    }
}

// Unique base types across all forms
$all_basetypes = array_values( array_unique( array_column( $all_fields, 'type' ) ) );
sort( $all_basetypes );
?>
<div class="wrap cf7on-wrap">
    <h1><?php esc_html_e( 'Automation Rules', 'cf7-booster' ); ?></h1>
    <div id="cf7on-notice"></div>

    <div class="cf7on-card" style="margin-bottom:16px;background:#f0f6ff;border-color:#c7d9fd;">
        <strong><?php esc_html_e( 'How rules work:', 'cf7-booster' ); ?></strong>
        <ul style="margin:.5em 0 0 1.5em;font-size:13px;line-height:1.8;">
            <li><?php esc_html_e( 'Rules are checked top-to-bottom. ALL matching rules fire (not just the first).', 'cf7-booster' ); ?></li>
            <li><?php esc_html_e( 'If NO rules match, the global Telegram/Bale settings are used as fallback.', 'cf7-booster' ); ?></li>
            <li><strong><?php esc_html_e( 'Field Name Pattern:', 'cf7-booster' ); ?></strong>
                <?php esc_html_e( 'Use this to match cross-form fields. Example: name contains "radio" AND value contains "sale" — this works even if the radio field is called radio-289 on one form and radio-389 on another.', 'cf7-booster' ); ?></li>
            <li><?php esc_html_e( 'Each rule can send to multiple channels and destinations simultaneously.', 'cf7-booster' ); ?></li>
        </ul>
    </div>

    <div id="cf7on-rules-app">
        <div id="cf7on-rules-list"></div>
        <p style="margin-top:16px;display:flex;gap:8px;">
            <button type="button" class="button" id="cf7on-add-rule">+ <?php esc_html_e( 'Add Rule', 'cf7-booster' ); ?></button>
            <button type="button" class="button button-primary" id="cf7on-save-rules"><?php esc_html_e( 'Save All Rules', 'cf7-booster' ); ?></button>
        </p>
    </div>
</div>

<script>
var cf7onForms     = <?php echo wp_json_encode( $forms ); ?>;
var cf7onFields    = <?php echo wp_json_encode( $all_fields ); ?>;
var cf7onBasetypes = <?php echo wp_json_encode( $all_basetypes ); ?>;
var cf7onRules     = <?php echo wp_json_encode( $rules ); ?>;
</script>
