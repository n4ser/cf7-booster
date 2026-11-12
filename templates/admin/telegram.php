<?php
defined( 'ABSPATH' ) || exit;
$destinations = implode( "\n", (array) ( $options['destinations'] ?? [] ) );
?>
<div class="wrap cf7on-wrap">
    <h1>
        <span style="color:#2196f3">📨</span>
        <?php esc_html_e( 'Telegram — Global Settings', 'cf7-booster' ); ?>
    </h1>
    <div id="cf7on-notice"></div>

    <div class="cf7on-info-banner">
        <span class="dashicons dashicons-info-outline"></span>
        <?php esc_html_e( 'This page configures the global Telegram bot token and default destinations. To set a custom message template or per-form destinations, open any CF7 form and use the', 'cf7-booster' ); ?>
        <strong> 🚀 CF7 Booster</strong> <?php esc_html_e( 'tab.', 'cf7-booster' ); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7' ) ); ?>" class="button button-small" style="margin-left:8px;">
            <?php esc_html_e( 'Go to Forms', 'cf7-booster' ); ?> →
        </a>
    </div>

    <div class="cf7on-card" style="max-width:680px;">
        <h2><?php esc_html_e( 'Bot Configuration', 'cf7-booster' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="tg_enabled"><?php esc_html_e( 'Enable Telegram (global)', 'cf7-booster' ); ?></label></th>
                <td>
                    <input type="checkbox" id="tg_enabled" value="1" <?php checked( ! empty( $options['enabled'] ) ); ?>>
                    <p class="description"><?php esc_html_e( 'Master switch. Individual forms can also be enabled/disabled from their own tab.', 'cf7-booster' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="tg_token"><?php esc_html_e( 'Bot Token', 'cf7-booster' ); ?></label></th>
                <td>
                    <input type="password" id="tg_token" class="regular-text"
                           value="<?php echo esc_attr( $options['bot_token'] ?? '' ); ?>"
                           placeholder="7123456789:ABCdefGH…">
                    <button type="button" class="button" data-action="cf7on_test_telegram">
                        <?php esc_html_e( 'Test Connection', 'cf7-booster' ); ?>
                    </button>
                    <span id="cf7on-test-telegram-result"></span>
                    <p class="description"><?php esc_html_e( 'Get your token from @BotFather on Telegram.', 'cf7-booster' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="tg_destinations"><?php esc_html_e( 'Default Destinations', 'cf7-booster' ); ?></label></th>
                <td>
                    <textarea id="tg_destinations" rows="4" class="large-text"
                              placeholder="-100123456789&#10;@mychannel"><?php echo esc_textarea( $destinations ); ?></textarea>
                    <p class="description">
                        <?php esc_html_e( 'One Chat ID or @channel per line. Used when a form has no custom destinations set.', 'cf7-booster' ); ?>
                    </p>
                </td>
            </tr>
            <div class="cf7on-channel-template">
                <label for="tg_template"><strong><?php esc_html_e( 'Default message template', 'cf7-booster' ); ?></strong></label>
                <textarea class="large-text code" id="tg_template" rows="6"><?php echo esc_textarea( $options['template'] ?? '' ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Use placeholders such as {name}, {form_name}, {all_fields}. Form-specific templates override this default.', 'cf7-booster' ); ?></p>
            </div>
        </table>

        <button type="button" class="button button-primary cf7on-save" data-action="cf7on_save_telegram"
                data-fields="tg_enabled,tg_token,tg_destinations,tg_template"
                data-map='{"tg_enabled":"enabled","tg_token":"bot_token","tg_destinations":"destinations","tg_template":"template"}'>
            <?php esc_html_e( 'Save Telegram Settings', 'cf7-booster' ); ?>
        </button>
        <div class="cf7on-channel-test">
            <button type="button" class="button button-secondary" data-cf7on-send-test="telegram" aria-controls="cf7on-send-test-telegram-result">
                <span class="dashicons dashicons-controls-play" aria-hidden="true"></span>
                <?php esc_html_e( 'Send test message', 'cf7-booster' ); ?>
            </button>
            <span id="cf7on-send-test-telegram-result" role="status" aria-live="polite" class="cf7on-channel-test-result"></span>
        </div>
        <p class="description"><?php esc_html_e( 'Sends a harmless sample to the first saved destination. Save the settings before testing.', 'cf7-booster' ); ?></p>
    </div>

    <!-- Forms quick-access -->
    <?php
    $forms = get_posts( [ 'post_type' => 'wpcf7_contact_form', 'numberposts' => -1, 'post_status' => 'publish' ] );
    if ( $forms ) :
    ?>
    <div class="cf7on-card" style="max-width:680px;">
        <h2><?php esc_html_e( 'Per-form Settings', 'cf7-booster' ); ?></h2>
        <p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Click a form to configure its Telegram message template and destinations.', 'cf7-booster' ); ?></p>
        <table class="wp-list-table widefat fixed striped cf7on-table">
            <thead><tr>
                <th><?php esc_html_e( 'Form', 'cf7-booster' ); ?></th>
                <th><?php esc_html_e( 'Telegram', 'cf7-booster' ); ?></th>
                <th><?php esc_html_e( 'Bale', 'cf7-booster' ); ?></th>
                <th><?php esc_html_e( 'Action', 'cf7-booster' ); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ( $forms as $f ) :
                $fs = get_option( 'cf7on_form_' . $f->ID, [] );
                $tg_ok = ! empty( $fs['telegram']['enabled'] );
                $ba_ok = ! empty( $fs['bale']['enabled'] );
                $edit_url = admin_url( 'admin.php?page=wpcf7&post=' . $f->ID . '&action=edit&active-tab=cf7booster' );
            ?>
                <tr>
                    <td><strong><?php echo esc_html( $f->post_title ); ?></strong></td>
                    <td><?php echo $tg_ok ? '<span class="cf7on-badge cf7on-badge--delivered">✓ On</span>' : '<span class="cf7on-badge cf7on-badge--pending">Off</span>'; ?></td>
                    <td><?php echo $ba_ok ? '<span class="cf7on-badge cf7on-badge--delivered">✓ On</span>' : '<span class="cf7on-badge cf7on-badge--pending">Off</span>'; ?></td>
                    <td><a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php esc_html_e( 'Configure', 'cf7-booster' ); ?> →</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
