<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap cf7on-wrap cf7on-settings-v2">
    <header class="cf7on-settings-header">
        <div>
            <h1><?php esc_html_e( 'Notification settings', 'cf7-booster' ); ?></h1>
            <p><?php esc_html_e( 'Configure delivery behavior, safety, privacy and data retention.', 'cf7-booster' ); ?></p>
        </div>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=cf7on-dashboard' ) ); ?>" class="button">
            <?php esc_html_e( 'Back to dashboard', 'cf7-booster' ); ?>
        </a>
    </header>
    <div id="cf7on-notice" role="status" aria-live="polite"></div>

    <div class="cf7on-settings-layout">
        <main>
            <section class="cf7on-settings-panel" aria-labelledby="cf7on-delivery-heading">
                <h2 id="cf7on-delivery-heading"><?php esc_html_e( 'Delivery engine', 'cf7-booster' ); ?></h2>
                <p class="cf7on-section-desc"><?php esc_html_e( 'Control how quickly messages are sent and what happens if a messenger is unavailable.', 'cf7-booster' ); ?></p>
                <div class="cf7on-field">
                    <label for="s_queue_mode"><?php esc_html_e( 'Delivery mode', 'cf7-booster' ); ?></label>
                    <div>
                        <select id="s_queue_mode">
                            <option value="async" <?php selected( $options['queue_mode'], 'async' ); ?>><?php esc_html_e( 'Background — WP-Cron (recommended)', 'cf7-booster' ); ?></option>
                            <option value="sync" <?php selected( $options['queue_mode'], 'sync' ); ?>><?php esc_html_e( 'Immediate — during form submission', 'cf7-booster' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Background mode avoids making visitors wait for Telegram or Bale. It needs a working WordPress or server cron.', 'cf7-booster' ); ?></p>
                    </div>
                </div>
                <div class="cf7on-field">
                    <label for="s_max_retries"><?php esc_html_e( 'Maximum automatic retries', 'cf7-booster' ); ?></label>
                    <div>
                        <input id="s_max_retries" class="small-text" type="number" min="0" max="10" step="1" value="<?php echo esc_attr( $options['max_retries'] ); ?>">
                        <p class="description"><?php esc_html_e( '0 disables automatic retries. A failed message can still be retried manually.', 'cf7-booster' ); ?></p>
                    </div>
                </div>
                <div class="cf7on-field">
                    <label for="s_retry_interval"><?php esc_html_e( 'Retry interval (seconds)', 'cf7-booster' ); ?></label>
                    <div>
                        <input id="s_retry_interval" class="small-text" type="number" min="10" max="86400" step="1" value="<?php echo esc_attr( $options['retry_interval'] ); ?>">
                        <p class="description"><?php esc_html_e( 'Between 10 seconds and 24 hours. Existing scheduled attempts keep their original times.', 'cf7-booster' ); ?></p>
                    </div>
                </div>
            </section>

            <section class="cf7on-settings-panel" aria-labelledby="cf7on-safety-heading">
                <h2 id="cf7on-safety-heading"><?php esc_html_e( 'Safety controls', 'cf7-booster' ); ?></h2>
                <p class="cf7on-section-desc"><?php esc_html_e( 'Pause all outbound messenger delivery without losing new form submissions.', 'cf7-booster' ); ?></p>
                <div class="cf7on-danger-option">
                    <label class="cf7on-check-label" for="s_paused">
                        <input type="checkbox" id="s_paused" value="1" <?php checked( ! empty( $options['notifications_paused'] ) ); ?>>
                        <?php esc_html_e( 'Pause Telegram and Bale notifications', 'cf7-booster' ); ?>
                    </label>
                    <p><?php esc_html_e( 'New form submissions will still be stored. Queued and retry jobs wait until this option is turned off. Does not affect Contact Form 7 email.', 'cf7-booster' ); ?></p>
                </div>
                <div class="cf7on-field">
                    <span class="cf7on-field-label"><?php esc_html_e( 'Delivery logs', 'cf7-booster' ); ?></span>
                    <div>
                        <label class="cf7on-check-label" for="s_logging">
                            <input type="checkbox" id="s_logging" value="1" <?php checked( ! empty( $options['logging_enabled'] ) ); ?>>
                            <?php esc_html_e( 'Record delivery attempts and errors', 'cf7-booster' ); ?>
                        </label>
                    </div>
                </div>
                <div class="cf7on-field">
                    <span class="cf7on-field-label"><?php esc_html_e( 'Sensitive data in logs', 'cf7-booster' ); ?></span>
                    <div>
                        <label class="cf7on-check-label" for="s_mask">
                            <input type="checkbox" id="s_mask" value="1" <?php checked( ! empty( $options['mask_sensitive'] ) ); ?>>
                            <?php esc_html_e( 'Mask email addresses and phone numbers', 'cf7-booster' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'This masks log context, not the original form submission fields.', 'cf7-booster' ); ?></p>
                    </div>
                </div>
            </section>

            <section class="cf7on-settings-panel" aria-labelledby="cf7on-privacy-heading">
                <h2 id="cf7on-privacy-heading"><?php esc_html_e( 'Privacy and data retention', 'cf7-booster' ); ?></h2>
                <p class="cf7on-section-desc"><?php esc_html_e( 'Choose which metadata to store and optionally delete old records automatically.', 'cf7-booster' ); ?></p>
                <div class="cf7on-field">
                    <span class="cf7on-field-label"><?php esc_html_e( 'Visitor IP address', 'cf7-booster' ); ?></span>
                    <div><label class="cf7on-check-label" for="s_store_ip"><input type="checkbox" id="s_store_ip" value="1" <?php checked( ! empty( $options['store_ip_address'] ) ); ?>><?php esc_html_e( 'Store IP addresses with new submissions', 'cf7-booster' ); ?></label></div>
                </div>
                <div class="cf7on-field">
                    <span class="cf7on-field-label"><?php esc_html_e( 'Browser information', 'cf7-booster' ); ?></span>
                    <div><label class="cf7on-check-label" for="s_store_agent"><input type="checkbox" id="s_store_agent" value="1" <?php checked( ! empty( $options['store_user_agent'] ) ); ?>><?php esc_html_e( 'Store user-agent with new submissions', 'cf7-booster' ); ?></label></div>
                </div>
                <div class="cf7on-field">
                    <label for="s_retention"><?php esc_html_e( 'Delete submissions after (days)', 'cf7-booster' ); ?></label>
                    <div>
                        <input type="number" id="s_retention" min="0" max="3650" step="1" class="small-text" value="<?php echo esc_attr( $options['retention_days'] ); ?>">
                        <p class="description"><?php esc_html_e( '0 = keep indefinitely. A non-zero value permanently deletes old submissions and related deliveries/log entries in scheduled batches.', 'cf7-booster' ); ?></p>
                    </div>
                </div>
                <div class="cf7on-field">
                    <label for="s_log_retention"><?php esc_html_e( 'Delete logs after (days)', 'cf7-booster' ); ?></label>
                    <div>
                        <input type="number" id="s_log_retention" min="0" max="3650" step="1" class="small-text" value="<?php echo esc_attr( $options['log_retention_days'] ); ?>">
                        <p class="description"><?php esc_html_e( '0 = keep logs indefinitely. Deletion cannot be undone; back up data first.', 'cf7-booster' ); ?></p>
                    </div>
                </div>
            </section>
        </main>

        <aside class="cf7on-settings-aside" aria-label="<?php esc_attr_e( 'System details', 'cf7-booster' ); ?>">
            <div class="cf7on-settings-panel">
                <h2><?php esc_html_e( 'System details', 'cf7-booster' ); ?></h2>
                <p class="cf7on-section-desc"><?php esc_html_e( 'Important details for checking delivery.', 'cf7-booster' ); ?></p>
                <div class="cf7on-settings-status">
                    <span><?php esc_html_e( 'Plugin version', 'cf7-booster' ); ?></span>
                    <strong><?php echo esc_html( CF7ON_VERSION ); ?></strong>
                </div>
                <div class="cf7on-settings-status">
                    <span><?php esc_html_e( 'WP-Cron', 'cf7-booster' ); ?></span>
                    <strong><?php echo $cronDisabled ? esc_html__( 'Disabled by configuration', 'cf7-booster' ) : esc_html__( 'Enabled by configuration', 'cf7-booster' ); ?></strong>
                </div>
                <div class="cf7on-settings-status">
                    <span><?php esc_html_e( 'Maintenance schedule', 'cf7-booster' ); ?></span>
                    <strong><?php echo $nextCleanup ? esc_html( wp_date( 'Y-m-d H:i', $nextCleanup ) ) : esc_html__( 'Not scheduled', 'cf7-booster' ); ?></strong>
                </div>
                <div class="cf7on-settings-status">
                    <span><?php esc_html_e( 'Data deletion', 'cf7-booster' ); ?></span>
                    <strong><?php echo ( $options['retention_days'] || $options['log_retention_days'] ) ? esc_html__( 'Enabled', 'cf7-booster' ) : esc_html__( 'Off by default', 'cf7-booster' ); ?></strong>
                </div>
                <p class="description"><?php esc_html_e( 'Settings are saved for this site only. Scheduled cleanup runs when WordPress cron is executed.', 'cf7-booster' ); ?></p>
                <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=cf7on-logs' ) ); ?>"><?php esc_html_e( 'Open delivery logs', 'cf7-booster' ); ?></a>
            </div>
        </aside>
    </div>
    <footer class="cf7on-settings-footer">
        <span class="description"><?php esc_html_e( 'Changes take effect after saving. Retention cleanup is irreversible.', 'cf7-booster' ); ?></span>
        <button type="button" class="button button-primary cf7on-save"
            data-action="cf7on_save_settings"
            data-fields="s_queue_mode,s_max_retries,s_retry_interval,s_logging,s_mask,s_paused,s_retention,s_log_retention,s_store_ip,s_store_agent"
            data-map='{"s_queue_mode":"queue_mode","s_max_retries":"max_retries","s_retry_interval":"retry_interval","s_logging":"logging_enabled","s_mask":"mask_sensitive","s_paused":"notifications_paused","s_retention":"retention_days","s_log_retention":"log_retention_days","s_store_ip":"store_ip_address","s_store_agent":"store_user_agent"}'>
            <?php esc_html_e( 'Save all settings', 'cf7-booster' ); ?>
        </button>
    </footer>
</div>
