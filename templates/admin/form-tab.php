<?php
/**
 * CF7 Booster — Per-form settings tab
 * Rendered inside the CF7 form editor at ?page=wpcf7&post=XXX&active-tab=cf7booster
 *
 * Available vars:
 *   $cf7           WPCF7_ContactForm instance
 *   $form_id       int
 *   $settings      array  (current saved settings for this form)
 *   $global_tg     array  (global Telegram option)
 *   $global_ba     array  (global Bale option)
 *   $field_names   array  of ['name'=>..,'type'=>..]
 *   $system_placeholders  array
 *   $default_tpl   string
 */
defined( 'ABSPATH' ) || exit;

$tg  = $settings['telegram'] ?? [];
$ba  = $settings['bale']     ?? [];

$tg_dests = implode( "\n", (array) ( $tg['destinations'] ?? [] ) );
$ba_dests = implode( "\n", (array) ( $ba['destinations'] ?? [] ) );
$tg_tpl   = $tg['template'] ?? $default_tpl;
$ba_tpl   = $ba['template'] ?? $default_tpl;
?>

<div class="cf7on-form-tab-wrap">
    <div id="cf7on-notice-tab"></div>

    <?php wp_nonce_field( 'cf7on_form_tab_' . $form_id, 'cf7on_form_tab_nonce' ); ?>
    <input type="hidden" name="form_id" value="<?php echo absint( $form_id ); ?>">

    <!-- Placeholder reference box -->
    <div class="cf7on-tab-info-box">
        <strong>📌 <?php esc_html_e( 'Available Placeholders', 'cf7-booster' ); ?></strong>
        <div class="cf7on-placeholder-chips" style="margin-top:8px;">
            <?php foreach ( $system_placeholders as $ph => $desc ) : ?>
                <span class="cf7on-chip" data-ph="<?php echo esc_attr( $ph ); ?>" title="<?php echo esc_attr( $desc ); ?>">
                    <?php echo esc_html( $ph ); ?>
                </span>
            <?php endforeach; ?>
        </div>
        <?php if ( ! empty( $field_names ) ) : ?>
        <p style="font-size:12px;font-weight:600;margin:10px 0 5px;"><?php esc_html_e( 'This form\'s fields:', 'cf7-booster' ); ?></p>
        <div class="cf7on-placeholder-chips">
            <?php foreach ( $field_names as $f ) : ?>
                <span class="cf7on-chip cf7on-chip--field" data-ph="{<?php echo esc_attr( $f['name'] ); ?>}"
                      title="<?php echo esc_attr( $f['type'] ); ?> field">
                    {<?php echo esc_html( $f['name'] ); ?>}
                    <small style="opacity:.65;font-size:10px;"><?php echo esc_html( $f['type'] ); ?></small>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Two-column: Telegram | Bale -->
    <div class="cf7on-tab-two-col">

        <!-- ── Telegram ─────────────────────────────────────────────── -->
        <div class="cf7on-tab-channel-card cf7on-card">
            <h3 class="cf7on-channel-title">
                <span class="dashicons dashicons-email-alt" style="color:#2196f3"></span>
                <?php esc_html_e( 'Telegram', 'cf7-booster' ); ?>
                <?php if ( ! empty( $global_tg['bot_token'] ) ) : ?>
                    <span class="cf7on-global-badge"><?php esc_html_e( 'Bot: configured globally', 'cf7-booster' ); ?></span>
                <?php else : ?>
                    <span class="cf7on-global-badge cf7on-global-badge--warn"><?php esc_html_e( '⚠ Bot token not set globally', 'cf7-booster' ); ?></span>
                <?php endif; ?>
            </h3>

            <label class="cf7on-toggle-label">
                <input type="checkbox" id="cf7on_tg_enabled" name="cf7on_tg_enabled" value="1"
                       <?php checked( ! empty( $tg['enabled'] ) ); ?>>
                <?php esc_html_e( 'Enable Telegram for this form', 'cf7-booster' ); ?>
            </label>

            <div class="cf7on-tab-field">
                <label for="cf7on_tg_destinations">
                    <?php esc_html_e( 'Destinations', 'cf7-booster' ); ?>
                    <span class="cf7on-hint"><?php esc_html_e( '(one Chat ID or @channel per line — overrides global destinations)', 'cf7-booster' ); ?></span>
                </label>
                <textarea id="cf7on_tg_destinations" name="cf7on_tg_destinations" rows="3"
                          class="large-text" placeholder="-100123456789&#10;@mychannel"><?php echo esc_textarea( $tg_dests ); ?></textarea>
                <?php if ( ! empty( $global_tg['destinations'] ) ) : ?>
                    <p class="cf7on-hint">
                        <?php esc_html_e( 'Global destinations:', 'cf7-booster' ); ?>
                        <code><?php echo esc_html( implode( ', ', (array) $global_tg['destinations'] ) ); ?></code>
                        — <?php esc_html_e( 'leave blank to use them', 'cf7-booster' ); ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="cf7on-tab-field">
                <label for="cf7on_tg_template">
                    <?php esc_html_e( 'Message Template', 'cf7-booster' ); ?>
                    <span class="cf7on-hint"><?php esc_html_e( '(click a placeholder above to insert it)', 'cf7-booster' ); ?></span>
                </label>
                <textarea id="cf7on_tg_template" name="cf7on_tg_template" rows="9"
                          class="large-text code cf7on-tpl-textarea"
                          style="font-family:monospace;"><?php echo esc_textarea( $tg_tpl ); ?></textarea>
            </div>
        </div>

        <!-- ── Bale ─────────────────────────────────────────────────── -->
        <div class="cf7on-tab-channel-card cf7on-card">
            <h3 class="cf7on-channel-title">
                <span class="dashicons dashicons-format-chat" style="color:#4caf50"></span>
                <?php esc_html_e( 'Bale', 'cf7-booster' ); ?>
                <?php if ( ! empty( $global_ba['bot_token'] ) ) : ?>
                    <span class="cf7on-global-badge"><?php esc_html_e( 'Bot: configured globally', 'cf7-booster' ); ?></span>
                <?php else : ?>
                    <span class="cf7on-global-badge cf7on-global-badge--warn"><?php esc_html_e( '⚠ Bot token not set globally', 'cf7-booster' ); ?></span>
                <?php endif; ?>
            </h3>

            <label class="cf7on-toggle-label">
                <input type="checkbox" id="cf7on_ba_enabled" name="cf7on_ba_enabled" value="1"
                       <?php checked( ! empty( $ba['enabled'] ) ); ?>>
                <?php esc_html_e( 'Enable Bale for this form', 'cf7-booster' ); ?>
            </label>

            <div class="cf7on-tab-field">
                <label for="cf7on_ba_destinations">
                    <?php esc_html_e( 'Destinations', 'cf7-booster' ); ?>
                    <span class="cf7on-hint"><?php esc_html_e( '(one Chat ID or @channel per line)', 'cf7-booster' ); ?></span>
                </label>
                <textarea id="cf7on_ba_destinations" name="cf7on_ba_destinations" rows="3"
                          class="large-text" placeholder="@mychannel&#10;-100123456789"><?php echo esc_textarea( $ba_dests ); ?></textarea>
                <?php if ( ! empty( $global_ba['destinations'] ) ) : ?>
                    <p class="cf7on-hint">
                        <?php esc_html_e( 'Global destinations:', 'cf7-booster' ); ?>
                        <code><?php echo esc_html( implode( ', ', (array) $global_ba['destinations'] ) ); ?></code>
                    </p>
                <?php endif; ?>
            </div>

            <div class="cf7on-tab-field">
                <label for="cf7on_ba_template">
                    <?php esc_html_e( 'Message Template', 'cf7-booster' ); ?>
                </label>
                <textarea id="cf7on_ba_template" name="cf7on_ba_template" rows="9"
                          class="large-text code cf7on-tpl-textarea"
                          style="font-family:monospace;"><?php echo esc_textarea( $ba_tpl ); ?></textarea>
            </div>
        </div>

    </div><!-- end two-col -->

    <!-- Save button (AJAX — saves without leaving the CF7 editor) -->
    <p class="cf7on-tab-save-row">
        <button type="button" class="button button-primary" id="cf7on-save-form-tab"
                data-form-id="<?php echo absint( $form_id ); ?>">
            💾 <?php esc_html_e( 'Save CF7 Booster Settings', 'cf7-booster' ); ?>
        </button>
        <span id="cf7on-tab-save-result" style="margin-left:10px;font-size:13px;"></span>
        <span class="cf7on-hint" style="margin-left:12px;">
            <?php esc_html_e( 'Or click the main "Save" button of CF7 to save everything at once.', 'cf7-booster' ); ?>
        </span>
    </p>

</div><!-- end form-tab-wrap -->
