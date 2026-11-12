<?php
namespace CF7ON\Modules\Dashboard;

defined( 'ABSPATH' ) || exit;

use CF7ON\Models\Submission;
use CF7ON\Models\Delivery;
use CF7ON\Services\LogService;

class DashboardModule {

    public function register(): void {
        add_action( 'admin_menu',             [ $this, 'registerMenus' ] );
        add_action( 'admin_enqueue_scripts',  [ $this, 'enqueueAssets' ] );
        add_action( 'wp_ajax_cf7on_save_telegram',  [ $this, 'ajaxSaveTelegram' ] );
        add_action( 'wp_ajax_cf7on_save_bale',      [ $this, 'ajaxSaveBale' ] );
        add_action( 'wp_ajax_cf7on_save_settings',  [ $this, 'ajaxSaveSettings' ] );
        add_action( 'wp_ajax_cf7on_save_rules',     [ $this, 'ajaxSaveRules' ] );
        add_action( 'wp_ajax_cf7on_test_telegram',  [ $this, 'ajaxTestTelegram' ] );
        add_action( 'wp_ajax_cf7on_test_bale',      [ $this, 'ajaxTestBale' ] );
        add_action( 'wp_ajax_cf7on_resend',         [ $this, 'ajaxResend' ] );
        add_action( 'wp_ajax_cf7on_export_csv',     [ $this, 'ajaxExportCsv' ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Menus                                                               */
    /* ------------------------------------------------------------------ */

    public function registerMenus(): void {
        add_menu_page(
            __( 'CF7 Booster', 'cf7-booster' ),
            __( 'CF7 Booster', 'cf7-booster' ),
            'manage_options',
            'cf7on-dashboard',
            [ $this, 'pageDashboard' ],
            'dashicons-megaphone',
            30
        );

        $pages = [
            [ 'cf7on-dashboard',    __( 'Dashboard',   'cf7-booster' ), [ $this, 'pageDashboard' ] ],
            [ 'cf7on-submissions',  __( 'Submissions',  'cf7-booster' ), [ $this, 'pageSubmissions' ] ],
            [ 'cf7on-telegram',     __( 'Telegram',     'cf7-booster' ), [ $this, 'pageTelegram' ] ],
            [ 'cf7on-bale',         __( 'Bale',         'cf7-booster' ), [ $this, 'pageBale' ] ],
            [ 'cf7on-automation',   __( 'Automation',   'cf7-booster' ), [ $this, 'pageAutomation' ] ],
            [ 'cf7on-logs',         __( 'Logs',         'cf7-booster' ), [ $this, 'pageLogs' ] ],
            [ 'cf7on-settings',     __( 'Settings',     'cf7-booster' ), [ $this, 'pageSettings' ] ],
        ];

        foreach ( $pages as [ $slug, $title, $cb ] ) {
            add_submenu_page( 'cf7on-dashboard', $title, $title, 'manage_options', $slug, $cb );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Assets                                                              */
    /* ------------------------------------------------------------------ */

    public function enqueueAssets( string $hook ): void {
        if ( strpos( $hook, 'cf7on' ) === false && ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'wpcf7' ) ) {
            return;
        }
        wp_enqueue_style(
            'cf7on-admin',
            CF7ON_URL . 'assets/css/admin.css',
            [],
            CF7ON_VERSION
        );
        if ( is_rtl() ) {
            wp_enqueue_style(
                'cf7on-admin-rtl',
                CF7ON_URL . 'assets/css/admin-rtl.css',
                [ 'cf7on-admin' ],
                CF7ON_VERSION
            );
        }
        wp_enqueue_script(
            'cf7on-admin',
            CF7ON_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            CF7ON_VERSION,
            true
        );
        wp_localize_script( 'cf7on-admin', 'cf7on', [
            'nonce'   => wp_create_nonce( 'cf7on_nonce' ),
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'strings' => [
                'Saving…'        => __( 'Saving…', 'cf7-booster' ),
                'Sending…'       => __( 'Sending…', 'cf7-booster' ),
                'Request failed.' => __( 'Request failed.', 'cf7-booster' ),
                'Resend'         => __( 'Resend', 'cf7-booster' ),
                'Error'          => __( 'Error', 'cf7-booster' ),
                'Testing…'       => __( 'Testing…', 'cf7-booster' ),
                'Failed'         => __( 'Failed', 'cf7-booster' ),
                'Connected'      => __( 'Connected', 'cf7-booster' ),
                'Save All Rules' => __( 'Save All Rules', 'cf7-booster' ),
                'New Rule'       => __( 'New Rule', 'cf7-booster' ),
                'Delete this rule?' => __( 'Delete this rule?', 'cf7-booster' ),
            ],
        ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Pages                                                               */
    /* ------------------------------------------------------------------ */

    public function pageDashboard(): void {
        $total   = Submission::count();
        $today   = Submission::count( [ 'date_from' => current_time( 'Y-m-d' ) . ' 00:00:00' ] );
        $week    = Submission::count( [ 'date_from' => date( 'Y-m-d', strtotime( '-7 days' ) ) . ' 00:00:00' ] );
        $failed  = Submission::count( [ 'status' => 'failed' ] );
        $recent  = Submission::findAll( [ 'limit' => 10 ] );
        require CF7ON_PATH . 'templates/admin/dashboard.php';
    }

    public function pageSubmissions(): void {
        $search  = sanitize_text_field( $_GET['search'] ?? '' );
        $form_id = absint( $_GET['form_id'] ?? 0 );
        $status  = sanitize_key( $_GET['status'] ?? '' );
        $page    = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $limit   = 20;
        $offset  = ( $page - 1 ) * $limit;

        $args = array_filter( compact( 'search', 'form_id', 'status', 'limit', 'offset' ) );
        $submissions = Submission::findAll( $args );
        $total       = Submission::count( array_filter( compact( 'search', 'form_id', 'status' ) ) );
        $forms       = $this->getAllForms();
        require CF7ON_PATH . 'templates/admin/submissions.php';
    }

    public function pageTelegram(): void {
        $options = get_option( 'cf7on_telegram', [] );
        require CF7ON_PATH . 'templates/admin/telegram.php';
    }

    public function pageBale(): void {
        $options = get_option( 'cf7on_bale', [] );
        require CF7ON_PATH . 'templates/admin/bale.php';
    }

    public function pageAutomation(): void {
        $rules = get_option( 'cf7on_automation_rules', [] );
        $forms = $this->getAllForms();
        require CF7ON_PATH . 'templates/admin/automation.php';
    }

    public function pageLogs(): void {
        $channel = sanitize_key( $_GET['channel'] ?? '' );
        $level   = sanitize_key( $_GET['level'] ?? '' );
        $logs    = LogService::getRecent( array_filter( compact( 'channel', 'level' ) ) );
        require CF7ON_PATH . 'templates/admin/logs.php';
    }

    public function pageSettings(): void {
        $options = get_option( 'cf7on_engine', [] );
        require CF7ON_PATH . 'templates/admin/settings.php';
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX handlers                                                       */
    /* ------------------------------------------------------------------ */

    public function ajaxSaveTelegram(): void {
        $this->verifyNonce();
        $existing = get_option( 'cf7on_telegram', [] );
        $data = [
            'enabled'      => ! empty( $_POST['enabled'] ),
            'bot_token'    => sanitize_text_field( $_POST['bot_token'] ?? '' ),
            'destinations' => array_map( 'sanitize_text_field', (array) ( $_POST['destinations'] ?? [] ) ),
            'template'     => sanitize_textarea_field( wp_unslash( $_POST['template'] ?? ( $existing['template'] ?? '' ) ) ),
        ];
        update_option( 'cf7on_telegram', $data );
        wp_send_json_success( __( 'Telegram settings saved.', 'cf7-booster' ) );
    }

    public function ajaxSaveBale(): void {
        $this->verifyNonce();
        $existing = get_option( 'cf7on_bale', [] );
        $data = [
            'enabled'      => ! empty( $_POST['enabled'] ),
            'bot_token'    => sanitize_text_field( $_POST['bot_token'] ?? '' ),
            'destinations' => array_map( 'sanitize_text_field', (array) ( $_POST['destinations'] ?? [] ) ),
            'template'     => sanitize_textarea_field( wp_unslash( $_POST['template'] ?? ( $existing['template'] ?? '' ) ) ),
        ];
        update_option( 'cf7on_bale', $data );
        wp_send_json_success( __( 'Bale settings saved.', 'cf7-booster' ) );
    }

    public function ajaxSaveSettings(): void {
        $this->verifyNonce();
        $data = [
            'queue_mode'      => sanitize_key( $_POST['queue_mode'] ?? 'async' ),
            'max_retries'     => absint( $_POST['max_retries'] ?? 3 ),
            'retry_interval'  => absint( $_POST['retry_interval'] ?? 90 ),
            'logging_enabled' => ! empty( $_POST['logging_enabled'] ),
            'mask_sensitive'  => ! empty( $_POST['mask_sensitive'] ),
        ];
        update_option( 'cf7on_engine', $data );
        wp_send_json_success( __( 'Settings saved.', 'cf7-booster' ) );
    }

    public function ajaxSaveRules(): void {
        $this->verifyNonce();
        $raw   = $_POST['rules'] ?? '[]';
        $rules = json_decode( wp_unslash( $raw ), true );
        if ( ! is_array( $rules ) ) {
            wp_send_json_error( 'Invalid rules data.' );
        }

        $allowed_ops   = [ '=', '!=', '>', '<', '>=', '<=', 'contains', 'not_contains', 'starts', 'ends', 'regex', 'empty', 'not_empty' ];
        $allowed_types = [ 'form_id', 'form_name', 'field_exact', 'field_name' ];

        $clean = [];
        foreach ( $rules as $rule ) {
            $conditions = [];
            foreach ( (array) ( $rule['conditions'] ?? [] ) as $cond ) {
                $type = sanitize_key( $cond['type'] ?? 'field_name' );
                if ( ! in_array( $type, $allowed_types, true ) ) {
                    $type = 'field_name';
                }
                $op = sanitize_text_field( $cond['operator'] ?? 'contains' );
                if ( ! in_array( $op, $allowed_ops, true ) ) {
                    $op = 'contains';
                }
                $name_op = sanitize_text_field( $cond['name_operator'] ?? 'contains' );
                if ( ! in_array( $name_op, $allowed_ops, true ) ) {
                    $name_op = 'contains';
                }

                $conditions[] = [
                    'type'          => $type,
                    // field_exact
                    'field'         => sanitize_text_field( $cond['field'] ?? '' ),
                    // field_name
                    'name_operator' => $name_op,
                    'name_value'    => sanitize_text_field( $cond['name_value'] ?? '' ),
                    // shared
                    'operator'      => $op,
                    'value'         => sanitize_text_field( $cond['value'] ?? '' ),
                ];
            }

            $clean[] = [
                'name'       => sanitize_text_field( $rule['name'] ?? '' ),
                'enabled'    => ! empty( $rule['enabled'] ),
                'logic'      => in_array( strtoupper( $rule['logic'] ?? 'AND' ), [ 'AND', 'OR' ], true )
                                    ? strtoupper( $rule['logic'] ) : 'AND',
                'conditions' => $conditions,
                'actions'    => array_map( fn( $a ) => [
                    'channel'     => sanitize_key( $a['channel'] ?? '' ),
                    'destination' => sanitize_text_field( $a['destination'] ?? '' ),
                    'template'    => sanitize_textarea_field( $a['template'] ?? '' ),
                ], (array) ( $rule['actions'] ?? [] ) ),
            ];
        }

        update_option( 'cf7on_automation_rules', $clean );
        wp_send_json_success( __( 'Rules saved.', 'cf7-booster' ) );
    }

    public function ajaxTestTelegram(): void {
        $this->verifyNonce();
        $token   = sanitize_text_field( $_POST['bot_token'] ?? '' );
        $adapter = \CF7ON\Core\Plugin::getInstance()->get( 'telegram' );
        $result  = $adapter->testConnection( $token );
        $result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result['error'] );
    }

    public function ajaxTestBale(): void {
        $this->verifyNonce();
        $token   = sanitize_text_field( $_POST['bot_token'] ?? '' );
        $adapter = \CF7ON\Core\Plugin::getInstance()->get( 'bale' );
        $result  = $adapter->testConnection( $token );
        $result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result['error'] );
    }

    public function ajaxResend(): void {
        $this->verifyNonce();
        $id = absint( $_POST['submission_id'] ?? 0 );
        if ( ! $id ) {
            wp_send_json_error( 'Invalid submission ID.' );
        }
        $queue = \CF7ON\Core\Plugin::getInstance()->get( 'queue' );
        $count = $queue->resendFailed( $id );
        if ( ! $count ) {
            wp_send_json_error( __( 'No failed notifications are available for retry.', 'cf7-booster' ) );
        }
        wp_send_json_success( __( 'Failed notifications retried.', 'cf7-booster' ) );
    }

    public function ajaxExportCsv(): void {
        $this->verifyNonce();
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $submissions = Submission::findAll( [ 'limit' => 9999 ] );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=cf7-submissions-' . date( 'Y-m-d' ) . '.csv' );

        $out = fopen( 'php://output', 'w' );
        if ( ! $out ) {
            wp_die( esc_html__( 'Unable to generate CSV.', 'cf7-booster' ) );
        }
        // Prevent spreadsheet formula injection from user-controlled form fields.
        $csvSafe = static function ( $value ): string {
            $value = (string) $value;
            return preg_match( '/^\\s*[=+\\-@]/u', $value ) ? "'" . $value : $value;
        };
        fputcsv( $out, [ 'ID', 'Form', 'Status', 'IP', 'Date', 'Fields' ] );
        foreach ( $submissions as $s ) {
            fputcsv( $out, array_map( $csvSafe, [
                $s['id'],
                $s['form_name'],
                $s['status'],
                $s['ip_address'],
                $s['created_at'],
                wp_json_encode( $s['fields'] ),
            ] ) );
        }
        fclose( $out );
        exit;
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    private function verifyNonce(): void {
        if ( ! check_ajax_referer( 'cf7on_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Security check failed.' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized.' );
        }
    }

    private function getAllForms(): array {
        $posts = get_posts( [
            'post_type'   => 'wpcf7_contact_form',
            'numberposts' => -1,
            'post_status' => 'publish',
        ] );
        $forms = [];
        foreach ( $posts as $post ) {
            $forms[] = [ 'id' => $post->ID, 'title' => $post->post_title ];
        }
        return $forms;
    }
}
