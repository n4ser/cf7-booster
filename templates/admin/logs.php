<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap cf7on-wrap">
    <h1><?php esc_html_e( 'Delivery Logs', 'cf7-booster' ); ?></h1>

    <form method="get" class="cf7on-filters">
        <input type="hidden" name="page" value="cf7on-logs">
        <select name="channel">
            <option value=""><?php esc_html_e( 'All Channels', 'cf7-booster' ); ?></option>
            <option value="cf7on_telegram" <?php selected( $channel, 'cf7on_telegram' ); ?>>Telegram</option>
            <option value="cf7on_bale"     <?php selected( $channel, 'cf7on_bale' ); ?>>Bale</option>
        </select>
        <select name="level">
            <option value=""><?php esc_html_e( 'All Levels', 'cf7-booster' ); ?></option>
            <option value="info"    <?php selected( $level, 'info' ); ?>>Info</option>
            <option value="warning" <?php selected( $level, 'warning' ); ?>>Warning</option>
            <option value="error"   <?php selected( $level, 'error' ); ?>>Error</option>
        </select>
        <?php submit_button( __( 'Filter', 'cf7-booster' ), 'secondary', 'submit', false ); ?>
    </form>

    <?php if ( empty( $logs ) ) : ?>
        <div class="cf7on-card"><p class="cf7on-empty"><?php esc_html_e( 'No log entries found.', 'cf7-booster' ); ?></p></div>
    <?php else : ?>
    <table class="wp-list-table widefat fixed striped cf7on-table cf7on-log-table">
        <thead>
            <tr>
                <th width="140"><?php esc_html_e( 'Time', 'cf7-booster' ); ?></th>
                <th width="90"><?php esc_html_e( 'Channel', 'cf7-booster' ); ?></th>
                <th width="80"><?php esc_html_e( 'Level', 'cf7-booster' ); ?></th>
                <th><?php esc_html_e( 'Message', 'cf7-booster' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $logs as $log ) : ?>
            <tr class="cf7on-log-row cf7on-log-<?php echo esc_attr( $log['level'] ); ?>">
                <td><?php echo esc_html( $log['created_at'] ); ?></td>
                <td><?php echo esc_html( str_replace( 'cf7on_', '', $log['channel'] ) ); ?></td>
                <td><span class="cf7on-badge cf7on-badge--<?php echo esc_attr( $log['level'] ); ?>"><?php echo esc_html( strtoupper( $log['level'] ) ); ?></span></td>
                <td><?php echo esc_html( $log['message'] ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
