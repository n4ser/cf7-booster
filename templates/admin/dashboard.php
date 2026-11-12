<?php
/**
 * The operations overview. Values are prepared in DashboardModule::pageDashboard().
 */
defined( 'ABSPATH' ) || exit;

$statusLabels = [
    'pending'   => __( 'Pending', 'cf7-booster' ),
    'delivered' => __( 'Delivered', 'cf7-booster' ),
    'partial'   => __( 'Partially delivered', 'cf7-booster' ),
    'failed'    => __( 'Failed', 'cf7-booster' ),
];
$tiles = [
    [ 'label' => __( 'Total submissions', 'cf7-booster' ), 'value' => $total, 'icon' => 'email-alt', 'tone' => 'indigo', 'hint' => __( 'All time', 'cf7-booster' ) ],
    [ 'label' => __( 'Today', 'cf7-booster' ), 'value' => $today, 'icon' => 'calendar-alt', 'tone' => 'blue', 'hint' => __( 'New requests', 'cf7-booster' ) ],
    [ 'label' => __( 'Last 7 days', 'cf7-booster' ), 'value' => $week, 'icon' => 'chart-bar', 'tone' => 'violet', 'hint' => __( 'Recent activity', 'cf7-booster' ) ],
    [ 'label' => __( 'Delivered', 'cf7-booster' ), 'value' => $delivered, 'icon' => 'yes-alt', 'tone' => 'green', 'hint' => __( 'All destinations succeeded', 'cf7-booster' ) ],
    [ 'label' => __( 'Requires attention', 'cf7-booster' ), 'value' => $failed + $partial, 'icon' => 'warning', 'tone' => 'red', 'hint' => __( 'Failed or partial', 'cf7-booster' ) ],
];
?>
<div class="wrap cf7on-wrap cf7on-console">
    <div class="cf7on-console-hero">
        <div>
            <p class="cf7on-eyebrow"><?php esc_html_e( 'CONTACT FORM OPERATIONS', 'cf7-booster' ); ?></p>
            <h1><?php esc_html_e( 'FormRelay Dashboard', 'cf7-booster' ); ?></h1>
            <p><?php esc_html_e( 'Track form submissions, check notification channels, and respond to delivery issues.', 'cf7-booster' ); ?></p>
            <div class="cf7on-hero-tags">
                <span class="cf7on-health-pill <?php echo ! empty( $engine['notifications_paused'] ) ? 'is-paused' : 'is-active'; ?>">
                    <span aria-hidden="true" class="cf7on-health-dot"></span>
                    <?php echo ! empty( $engine['notifications_paused'] )
                        ? esc_html__( 'Notifications paused', 'cf7-booster' )
                        : esc_html__( 'Notifications active', 'cf7-booster' ); ?>
                </span>
                <span class="cf7on-hero-version">v<?php echo esc_html( CF7ON_VERSION ); ?></span>
            </div>
        </div>
        <div class="cf7on-hero-actions">
            <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=cf7on-settings' ) ); ?>">
                <span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
                <?php esc_html_e( 'Manage settings', 'cf7-booster' ); ?>
            </a>
            <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7' ) ); ?>">
                <?php esc_html_e( 'Open forms', 'cf7-booster' ); ?>
            </a>
        </div>
    </div>

    <?php if ( ! empty( $engine['notifications_paused'] ) ) : ?>
        <div class="notice notice-warning inline cf7on-console-alert" role="status">
            <p><strong><?php esc_html_e( 'Outbound messages are paused.', 'cf7-booster' ); ?></strong>
                <?php esc_html_e( 'Submissions are still stored. Pending deliveries will resume when notifications are enabled.', 'cf7-booster' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=cf7on-settings' ) ); ?>"><?php esc_html_e( 'Resume notifications', 'cf7-booster' ); ?></a>
            </p>
        </div>
    <?php endif; ?>
    <?php if ( $cronDisabled && $engine['queue_mode'] === 'async' ) : ?>
        <div class="notice notice-warning inline cf7on-console-alert" role="status">
            <p><strong><?php esc_html_e( 'WP-Cron is disabled.', 'cf7-booster' ); ?></strong>
                <?php esc_html_e( 'Background notifications need a configured server cron job.', 'cf7-booster' ); ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="cf7on-console-kpis">
        <?php foreach ( $tiles as $tile ) : ?>
            <div class="cf7on-kpi cf7on-kpi--<?php echo esc_attr( $tile['tone'] ); ?>">
                <span class="cf7on-kpi-icon dashicons dashicons-<?php echo esc_attr( $tile['icon'] ); ?>" aria-hidden="true"></span>
                <span class="cf7on-kpi-label"><?php echo esc_html( $tile['label'] ); ?></span>
                <strong class="cf7on-kpi-number"><?php echo esc_html( number_format_i18n( $tile['value'] ) ); ?></strong>
                <span class="cf7on-kpi-hint"><?php echo esc_html( $tile['hint'] ); ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="cf7on-console-main">
        <section class="cf7on-panel cf7on-trends" aria-labelledby="cf7on-trend-title">
            <header class="cf7on-panel-head">
                <div>
                    <p class="cf7on-panel-kicker"><?php esc_html_e( 'Activity', 'cf7-booster' ); ?></p>
                    <h2 id="cf7on-trend-title"><?php esc_html_e( 'Submission trend', 'cf7-booster' ); ?></h2>
                </div>
                <span class="cf7on-panel-meta"><?php esc_html_e( 'Last 7 days', 'cf7-booster' ); ?></span>
            </header>
            <div class="cf7on-chart" role="img" aria-label="<?php esc_attr_e( 'Submission counts for the last seven days', 'cf7-booster' ); ?>">
                <?php foreach ( $trend as $date => $count ) : ?>
                    <?php $height = $count > 0 ? max( 8, (int) round( $count / $chartMax * 100 ) ) : 0; ?>
                    <div class="cf7on-chart-col" title="<?php echo esc_attr( $date . ': ' . $count ); ?>">
                        <span class="cf7on-chart-num"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
                        <div class="cf7on-chart-track">
                            <span class="cf7on-chart-fill" style="height:<?php echo esc_attr( $height ); ?>%"></span>
                        </div>
                        <span class="cf7on-chart-day"><?php echo esc_html( wp_date( 'D', strtotime( $date . ' 12:00:00' ) ) ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="cf7on-panel" aria-labelledby="cf7on-health-title">
            <header class="cf7on-panel-head">
                <div>
                    <p class="cf7on-panel-kicker"><?php esc_html_e( 'System overview', 'cf7-booster' ); ?></p>
                    <h2 id="cf7on-health-title"><?php esc_html_e( 'Delivery health', 'cf7-booster' ); ?></h2>
                </div>
            </header>
            <div class="cf7on-health-list">
                <div class="cf7on-health-row">
                    <span><span class="dashicons dashicons-email-alt" aria-hidden="true"></span> <?php esc_html_e( 'Telegram', 'cf7-booster' ); ?></span>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=cf7on-telegram' ) ); ?>" class="cf7on-status <?php echo $telegramReady ? 'is-good' : 'is-warning'; ?>"><?php echo $telegramReady ? esc_html__( 'Configured', 'cf7-booster' ) : esc_html__( 'Set up', 'cf7-booster' ); ?></a>
                </div>
                <div class="cf7on-health-row">
                    <span><span class="dashicons dashicons-format-chat" aria-hidden="true"></span> <?php esc_html_e( 'Bale', 'cf7-booster' ); ?></span>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=cf7on-bale' ) ); ?>" class="cf7on-status <?php echo $baleReady ? 'is-good' : 'is-warning'; ?>"><?php echo $baleReady ? esc_html__( 'Configured', 'cf7-booster' ) : esc_html__( 'Set up', 'cf7-booster' ); ?></a>
                </div>
                <div class="cf7on-health-row">
                    <span><span class="dashicons dashicons-clock" aria-hidden="true"></span> <?php esc_html_e( 'Queue mode', 'cf7-booster' ); ?></span>
                    <strong><?php echo $engine['queue_mode'] === 'async' ? esc_html__( 'Background', 'cf7-booster' ) : esc_html__( 'Immediate', 'cf7-booster' ); ?></strong>
                </div>
                <div class="cf7on-health-row">
                    <span><span class="dashicons dashicons-update" aria-hidden="true"></span> <?php esc_html_e( 'WP-Cron configuration', 'cf7-booster' ); ?></span>
                    <span class="cf7on-status <?php echo $cronDisabled ? 'is-warning' : 'is-good'; ?>">
                        <?php echo $cronDisabled ? esc_html__( 'Browser cron disabled', 'cf7-booster' ) : esc_html__( 'Browser cron enabled', 'cf7-booster' ); ?>
                    </span>
                </div>
                <div class="cf7on-health-row">
                    <span><span class="dashicons dashicons-backup" aria-hidden="true"></span> <?php esc_html_e( 'Waiting submissions', 'cf7-booster' ); ?></span>
                    <strong><?php echo esc_html( number_format_i18n( $pending ) ); ?></strong>
                </div>
            </div>
            <p class="cf7on-health-footnote"><?php esc_html_e( 'Configuration status does not guarantee message delivery. Use Send test message to verify the channel.', 'cf7-booster' ); ?></p>
            <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=cf7on-logs' ) ); ?>"><?php esc_html_e( 'View delivery logs', 'cf7-booster' ); ?></a>
        </section>
    </div>

    <section class="cf7on-panel cf7on-recent" aria-labelledby="cf7on-recent-title">
        <header class="cf7on-panel-head">
            <div>
                <p class="cf7on-panel-kicker"><?php esc_html_e( 'Inbox', 'cf7-booster' ); ?></p>
                <h2 id="cf7on-recent-title"><?php esc_html_e( 'Recent submissions', 'cf7-booster' ); ?></h2>
            </div>
            <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=cf7on-submissions' ) ); ?>"><?php esc_html_e( 'View all', 'cf7-booster' ); ?></a>
        </header>
        <?php if ( empty( $recent ) ) : ?>
            <div class="cf7on-console-empty">
                <span class="dashicons dashicons-feedback" aria-hidden="true"></span>
                <strong><?php esc_html_e( 'Your inbox is ready', 'cf7-booster' ); ?></strong>
                <p><?php esc_html_e( 'New Contact Form 7 submissions will appear here.', 'cf7-booster' ); ?></p>
                <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7' ) ); ?>"><?php esc_html_e( 'Open forms', 'cf7-booster' ); ?></a>
            </div>
        <?php else : ?>
            <div class="cf7on-table-scroll">
                <table class="wp-list-table widefat fixed striped cf7on-table">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e( 'Form', 'cf7-booster' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Status', 'cf7-booster' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Date', 'cf7-booster' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Actions', 'cf7-booster' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $recent as $submission ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $submission['form_name'] ); ?></strong><div class="cf7on-row-secondary">#<?php echo absint( $submission['id'] ); ?></div></td>
                                <td><span class="cf7on-badge cf7on-badge--<?php echo esc_attr( $submission['status'] ); ?>"><?php echo esc_html( $statusLabels[ $submission['status'] ] ?? $submission['status'] ); ?></span></td>
                                <td><?php echo esc_html( $submission['created_at'] ); ?></td>
                                <td>
                                    <?php if ( in_array( $submission['status'], [ 'failed', 'partial' ], true ) ) : ?>
                                        <button type="button" class="button button-small cf7on-resend" data-id="<?php echo absint( $submission['id'] ); ?>" <?php disabled( ! empty( $engine['notifications_paused'] ) ); ?>><?php esc_html_e( 'Retry failed', 'cf7-booster' ); ?></button>
                                    <?php else : ?>
                                        <span class="cf7on-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
