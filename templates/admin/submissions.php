<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap cf7on-wrap">
    <h1><?php esc_html_e( 'Submissions', 'cf7-booster' ); ?></h1>

    <form method="get" class="cf7on-filters">
        <input type="hidden" name="page" value="cf7on-submissions">
        <input type="text" name="search" value="<?php echo esc_attr( $search ); ?>"
               placeholder="<?php esc_attr_e( 'Search…', 'cf7-booster' ); ?>" class="regular-text">
        <select name="form_id">
            <option value=""><?php esc_html_e( 'All Forms', 'cf7-booster' ); ?></option>
            <?php foreach ( $forms as $f ) : ?>
                <option value="<?php echo absint( $f['id'] ); ?>" <?php selected( $form_id, $f['id'] ); ?>>
                    <?php echo esc_html( $f['title'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value=""><?php esc_html_e( 'All Statuses', 'cf7-booster' ); ?></option>
            <?php foreach ( [ 'pending', 'delivered', 'partial', 'failed' ] as $st ) : ?>
                <option value="<?php echo esc_attr( $st ); ?>" <?php selected( $status, $st ); ?>><?php echo esc_html( ucfirst( $st ) ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php submit_button( __( 'Filter', 'cf7-booster' ), 'secondary', 'submit', false ); ?>
        <a href="<?php echo esc_url( add_query_arg( [ 'action' => 'cf7on_export_csv', 'nonce' => wp_create_nonce( 'cf7on_nonce' ) ], admin_url( 'admin-ajax.php' ) ) ); ?>"
           class="button"><?php esc_html_e( 'Export CSV', 'cf7-booster' ); ?></a>
    </form>

    <?php if ( empty( $submissions ) ) : ?>
        <div class="cf7on-card"><p class="cf7on-empty"><?php esc_html_e( 'No submissions found.', 'cf7-booster' ); ?></p></div>
    <?php else : ?>
    <table class="wp-list-table widefat fixed striped cf7on-table">
        <thead>
            <tr>
                <th width="50">#</th>
                <th><?php esc_html_e( 'Form', 'cf7-booster' ); ?></th>
                <th><?php esc_html_e( 'Fields Preview', 'cf7-booster' ); ?></th>
                <th><?php esc_html_e( 'IP', 'cf7-booster' ); ?></th>
                <th><?php esc_html_e( 'Status', 'cf7-booster' ); ?></th>
                <th><?php esc_html_e( 'Date', 'cf7-booster' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'cf7-booster' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $submissions as $s ) :
            $preview = implode( ', ', array_slice( array_map(
                fn($k,$v) => "$k: $v",
                array_keys( $s['fields'] ),
                $s['fields']
            ), 0, 3 ) );
        ?>
            <tr>
                <td><?php echo absint( $s['id'] ); ?></td>
                <td><?php echo esc_html( $s['form_name'] ); ?></td>
                <td class="cf7on-preview"><?php echo esc_html( $preview ); ?></td>
                <td><?php echo esc_html( $s['ip_address'] ); ?></td>
                <td><span class="cf7on-badge cf7on-badge--<?php echo esc_attr( $s['status'] ); ?>"><?php echo esc_html( ucfirst( $s['status'] ) ); ?></span></td>
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

    <?php
    $total_pages = (int) ceil( $total / $limit );
    if ( $total_pages > 1 ) :
        echo paginate_links( [
            'base'      => add_query_arg( 'paged', '%#%' ),
            'format'    => '',
            'current'   => $page,
            'total'     => $total_pages,
        ] );
    endif;
    ?>
    <?php endif; ?>
</div>
