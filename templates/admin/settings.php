<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap cf7on-wrap">
    <h1><?php esc_html_e( 'CF7 Booster — Settings', 'cf7-booster' ); ?></h1>
    <div id="cf7on-notice"></div>

    <div class="cf7on-card" style="max-width:700px;">
        <h2><?php esc_html_e( 'Notification Engine', 'cf7-booster' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="s_queue_mode"><?php esc_html_e( 'Queue Mode', 'cf7-booster' ); ?></label></th>
                <td>
                    <select id="s_queue_mode">
                        <option value="async" <?php selected( $options['queue_mode'] ?? 'async', 'async' ); ?>>
                            <?php esc_html_e( 'Asynchronous — background (recommended)', 'cf7-booster' ); ?>
                        </option>
                        <option value="sync" <?php selected( $options['queue_mode'] ?? '', 'sync' ); ?>>
                            <?php esc_html_e( 'Synchronous — immediate (slower)', 'cf7-booster' ); ?>
                        </option>
                    </select>
                    <div class="cf7on-setting-explain">
                        <div class="cf7on-explain-row">
                            <strong><?php esc_html_e( 'Asynchronous (recommended):', 'cf7-booster' ); ?></strong>
                            <?php esc_html_e( 'After the visitor submits the form, WordPress immediately returns the success message. The Telegram/Bale notification is sent in the background a few seconds later via WP-Cron. The visitor never waits for the API call — best for almost all sites.', 'cf7-booster' ); ?>
                        </div>
                        <div class="cf7on-explain-row">
                            <strong><?php esc_html_e( 'Synchronous:', 'cf7-booster' ); ?></strong>
                            <?php esc_html_e( 'The API call happens during the form submission itself. If Telegram/Bale is slow or unreachable, the visitor waits. Use only if WP-Cron is disabled on your server.', 'cf7-booster' ); ?>
                        </div>
                        <p class="description" style="margin-top:6px;">
                            ⚠ <?php esc_html_e( 'Async mode requires WP-Cron. If you have DISABLE_WP_CRON = true in wp-config.php, use Synchronous or set up a real server cron job.', 'cf7-booster' ); ?>
                        </p>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="s_max_retries"><?php esc_html_e( 'Max Retry Attempts', 'cf7-booster' ); ?></label></th>
                <td>
                    <input type="number" id="s_max_retries" min="0" max="10"
                           value="<?php echo absint( $options['max_retries'] ?? 3 ); ?>" class="small-text">
                    <p class="description"><?php esc_html_e( 'How many times to retry a failed delivery. Set to 0 to disable retries.', 'cf7-booster' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="s_retry_interval"><?php esc_html_e( 'Retry Interval (seconds)', 'cf7-booster' ); ?></label></th>
                <td>
                    <input type="number" id="s_retry_interval" min="10"
                           value="<?php echo absint( $options['retry_interval'] ?? 90 ); ?>" class="small-text">
                    <p class="description"><?php esc_html_e( 'Seconds to wait between each retry.', 'cf7-booster' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Enable Delivery Logs', 'cf7-booster' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" id="s_logging" value="1"
                               <?php checked( ! empty( $options['logging_enabled'] ) ); ?>>
                        <?php esc_html_e( 'Record every delivery attempt in the Logs table', 'cf7-booster' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Mask Sensitive Data', 'cf7-booster' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" id="s_mask" value="1"
                               <?php checked( ! empty( $options['mask_sensitive'] ) ); ?>>
                        <?php esc_html_e( 'Mask phone numbers and emails in Logs (e.g. 0912******34)', 'cf7-booster' ); ?>
                    </label>
                </td>
            </tr>
        </table>

        <button type="button" class="button button-primary cf7on-save" data-action="cf7on_save_settings"
                data-fields="s_queue_mode,s_max_retries,s_retry_interval,s_logging,s_mask"
                data-map='{"s_queue_mode":"queue_mode","s_max_retries":"max_retries","s_retry_interval":"retry_interval","s_logging":"logging_enabled","s_mask":"mask_sensitive"}'>
            <?php esc_html_e( 'Save Settings', 'cf7-booster' ); ?>
        </button>
    </div>

    <!-- Plugin info & support footer -->
    <div class="cf7on-card cf7on-support-card" style="max-width:700px;">
        <div class="cf7on-support-inner">
            <div class="cf7on-support-logo">🚀</div>
            <div class="cf7on-support-body">
                <h3><?php esc_html_e( 'CF7 Booster', 'cf7-booster' ); ?> <span style="font-size:12px;font-weight:400;color:#646970;">v<?php echo esc_html( CF7ON_VERSION ); ?></span></h3>
                <p><?php esc_html_e( 'Supercharge Contact Form 7 with Telegram & Bale notifications, per-form settings, automation rules, and submission management.', 'cf7-booster' ); ?></p>
                <div class="cf7on-support-links">
                    <a href="https://inaser.ir/plugin/cf7-booster" target="_blank" rel="noopener" class="button">
                        🌐 <?php esc_html_e( 'Plugin Page', 'cf7-booster' ); ?>
                    </a>
                    <a href="https://inaser.ir/plugin/cf7-booster#docs" target="_blank" rel="noopener" class="button">
                        📖 <?php esc_html_e( 'Documentation', 'cf7-booster' ); ?>
                    </a>
                    <a href="https://inaser.ir/contact" target="_blank" rel="noopener" class="button button-primary">
                        💬 <?php esc_html_e( 'Contact for Support', 'cf7-booster' ); ?>
                    </a>
                </div>
                <p class="description" style="margin-top:10px;">
                    <?php esc_html_e( 'For bugs, feature requests, or custom development, visit', 'cf7-booster' ); ?>
                    <a href="https://inaser.ir" target="_blank" rel="noopener">inaser.ir</a>
                    <?php esc_html_e( 'or open a support request.', 'cf7-booster' ); ?>
                </p>
            </div>
        </div>
    </div>

</div>
