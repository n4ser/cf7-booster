<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap cf7on-wrap">
    <h1><?php esc_html_e( 'CF7 Booster', 'cf7-booster' ); ?></h1>

    <div class="cf7on-stats-grid">
        <div class="cf7on-stat-card">
            <span class="cf7on-stat-icon dashicons dashicons-email-alt"></span>
            <div class="cf7on-stat-body">
                <div class="cf7on-stat-val"><?php echo esc_html( number_format( $total ) ); ?></div>
                <div class="cf7on-stat-label"><?php esc_html_e( 'Total Submissions', 'cf7-booster' ); ?></div>
            </div>
        </div>
        <div class="cf7on-stat-card">
            <span class="cf7on-stat-icon dashicons dashicons-calendar-alt"></span>
            <div class="cf7on-stat-body">
                <div class="cf7on-stat-val"><?php echo esc_html( number_format( $today ) ); ?></div>
                <div class="cf7on-stat-label"><?php esc_html_e( 'Today', 'cf7-booster' ); ?></div>
            </div>
        </div>
        <div class="cf7on-stat-card">
            <span class="cf7on-stat-icon dashicons dashicons-clock"></span>
            <div class="cf7on-stat-body">
                <div class="cf7on-stat-val"><?php echo esc_html( number_format( $week ) ); ?></div>
                <div class="cf7on-stat-label"><?php esc_html_e( 'This Week', 'cf7-booster' ); ?></div>
            </div>
        </div>
        <div class="cf7on-stat-card cf7on-stat-card--danger">
            <span class="cf7on-stat-icon dashicons dashicons-warning"></span>
            <div class="cf7on-stat-body">
                <div class="cf7on-stat-val"><?php echo esc_html( number_format( $failed ) ); ?></div>
                <div class="cf7on-stat-label"><?php esc_html_e( 'Failed', 'cf7-booster' ); ?></div>
            </div>
        </div>
    </div>

    <div class="cf7on-card">
        <h2><?php esc_html_e( 'Recent Submissions', 'cf7-booster' ); ?></h2>
        <?php if ( empty( $recent ) ) : ?>
            <p class="cf7on-empty"><?php esc_html_e( 'No submissions yet.', 'cf7-booster' ); ?></p>
        <?php else : ?>
        <table class="wp-list-table widefat fixed striped cf7on-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( '#', 'cf7-booster' ); ?></th>
                    <th><?php esc_html_e( 'Form', 'cf7-booster' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'cf7-booster' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'cf7-booster' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'cf7-booster' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $recent as $s ) : ?>
                <tr>
                    <td><?php echo absint( $s['id'] ); ?></td>
                    <td><?php echo esc_html( $s['form_name'] ); ?></td>
                    <td><span class="cf7on-badge cf7on-badge--<?php echo esc_attr( $s['status'] ); ?>"><?php echo esc_html( $s['status'] ); ?></span></td>
                    <td><?php echo esc_html( $s['created_at'] ); ?></td>
                    <td>
                        <button class="button button-small cf7on-resend" data-id="<?php echo absint( $s['id'] ); ?>">
                            <?php esc_html_e( 'Resend', 'cf7-booster' ); ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=cf7on-submissions' ) ); ?>" class="button">
            <?php esc_html_e( 'View All Submissions →', 'cf7-booster' ); ?>
        </a></p>
    </div>
</div>
