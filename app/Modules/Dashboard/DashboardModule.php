<?php
namespace CF7ON\Modules\Dashboard;

defined( 'ABSPATH' ) || exit;

use CF7ON\Models\Submission;
use CF7ON\Models\Delivery;
use CF7ON\Services\LogService;
use CF7ON\Services\EngineSettings;

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
        add_action( 'wp_ajax_cf7on_send_test',      [ $this, 'ajaxSendTest' ] );
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
        if ( strpos( $hook, 'cf7on' ) === false ) {
            return;
        }
        wp_enqueue_style(
            'cf7on-admin',
            CF7ON_URL . 'assets/css/admin.css',
            [],
            CF7ON_VERSION
        );
        wp_enqueue_style(
            'cf7on-console',
            CF7ON_URL . 'assets/css/console.css',
            [ 'cf7on-admin' ],
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
            'strings' => \CF7ON\Core\AdminTranslations::all(),
        ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Pages                                                               */
    /* ------------------------------------------------------------------ */

    public function pageDashboard(): void {
        $total   = Submission::count();
        $today   = Submission::count( [ 'date_from' => current_time( 'Y-m-d' ) . ' 00:00:00' ] );
        $week    = Submission::count( [ 'date_from' => wp_date( 'Y-m-d', time() - 6 * DAY_IN_SECONDS ) . ' 00:00:00' ] );
        $failed  = Submission::count( [ 'status' => 'failed' ] );
        $pending = Submission::count( [ 'status' => 'pending' ] );
        $partial = Submission::count( [ 'status' => 'partial' ] );
        $delivered = Submission::count( [ 'status' => 'delivered' ] );
        $recent  = Submission::findAll( [ 'limit' => 7 ] );
        $trend = Submission::dailyCounts( 7 );
        $chartMax = max( 1, ...array_values( $trend ) );
        $engine = EngineSettings::current();
        $telegram = get_option( 'cf7on_telegram', [] );
        $bale = get_option( 'cf7on_bale', [] );
        $telegramReady = ! empty( $telegram['bot_token'] ) && ! empty( $telegram['destinations'] );
        $baleReady = ! empty( $bale['bot_token'] ) && ! empty( $bale['destinations'] );
        $cronDisabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
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
        $options = EngineSettings::current();
        $cronDisabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
        $nextCleanup = wp_next_scheduled( 'cf7on_daily_maintenance' );
        require CF7ON_PATH . 'templates/admin/settings.php';
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX handlers                                                       */
    /* ------------------------------------------------------------------ */

    public function ajaxSaveTelegram(): void {
        $this->verifyNonce();
        $this->saveChannel( 'telegram' );
        wp_send_json_success( __( 'Telegram settings saved.', 'cf7-booster' ) );
    }

    public function ajaxSaveBale(): void {
        $this->verifyNonce();
        $this->saveChannel( 'bale' );
        wp_send_json_success( __( 'Bale settings saved.', 'cf7-booster' ) );
    }

    private function saveChannel( string $channel ): void {
        $existing = get_option( 'cf7on_' . $channel, [] );
        $existing = is_array( $existing ) ? $existing : [];
        $input = wp_unslash( $_POST );
        $destinations = $input['destinations'] ?? [];
        $destinations = is_array( $destinations ) ? $destinations : [];
        $cleanDestinations = [];
        foreach ( array_slice( $destinations, 0, 50 ) as $destination ) {
            if ( ! is_scalar( $destination ) ) {
                continue;
            }
            $value = sanitize_text_field( (string) $destination );
            if ( $value !== '' ) {
                $cleanDestinations[] = $value;
            }
        }
        $token = $input['bot_token'] ?? '';
        $template = $input['template'] ?? ( $existing['template'] ?? '' );
        update_option( 'cf7on_' . $channel, [
            'enabled'      => ! empty( $input['enabled'] ),
            'bot_token'    => is_scalar( $token ) ? sanitize_text_field( (string) $token ) : '',
            'destinations' => array_values( array_unique( $cleanDestinations ) ),
            'template'     => is_string( $template ) ? sanitize_textarea_field( $template ) : '',
        ] );
    }

    public function ajaxSaveSettings(): void {
        $this->verifyNonce();
        $input = wp_unslash( $_POST );
        update_option( 'cf7on_engine', EngineSettings::sanitize( $input ) );
        wp_send_json_success( __( 'Settings saved.', 'cf7-booster' ) );
    }

    public function ajaxSaveRules(): void {
        $this->verifyNonce();
        $payload = $_POST['rules'] ?? '[]';
        if ( ! is_string( $payload ) || strlen( $payload ) > 250000 ) {
            wp_send_json_error( __( 'Invalid rules data.', 'cf7-booster' ) );
        }
        $rules = json_decode( wp_unslash( $payload ), true );
        if ( ! is_array( $rules ) ) {
            wp_send_json_error( __( 'Invalid rules data.', 'cf7-booster' ) );
        }

        $allowedOps = [ '=', '!=', '>', '<', '>=', '<=', 'contains', 'not_contains', 'starts', 'ends', 'regex', 'empty', 'not_empty' ];
        $allowedTypes = [ 'form_id', 'form_name', 'field_exact', 'field_name' ];
        $cleanRules = [];
        foreach ( array_slice( $rules, 0, 100 ) as $rule ) {
            if ( ! is_array( $rule ) ) {
                continue;
            }

            $conditions = [];
            $inputConditions = $rule['conditions'] ?? ( isset( $rule['condition'] ) ? [ $rule['condition'] ] : [] );
            if ( ! is_array( $inputConditions ) ) {
                $inputConditions = [];
            }
            foreach ( array_slice( $inputConditions, 0, 20 ) as $condition ) {
                if ( ! is_array( $condition ) ) {
                    continue;
                }
                $type = sanitize_key( $condition['type'] ?? 'field_name' );
                if ( ! in_array( $type, $allowedTypes, true ) ) {
                    $type = 'field_name';
                }
                $operator = sanitize_text_field( $condition['operator'] ?? 'contains' );
                $nameOperator = sanitize_text_field( $condition['name_operator'] ?? 'contains' );
                $conditions[] = [
                    'type'          => $type,
                    'field'         => sanitize_text_field( $condition['field'] ?? '' ),
                    'name_operator' => in_array( $nameOperator, $allowedOps, true ) ? $nameOperator : 'contains',
                    'name_value'    => sanitize_text_field( $condition['name_value'] ?? '' ),
                    'operator'      => in_array( $operator, $allowedOps, true ) ? $operator : 'contains',
                    'value'         => sanitize_text_field( $condition['value'] ?? '' ),
                ];
            }

            $actions = [];
            $inputActions = $rule['actions'] ?? [];
            if ( ! is_array( $inputActions ) ) {
                $inputActions = [];
            }
            foreach ( array_slice( $inputActions, 0, 20 ) as $action ) {
                if ( ! is_array( $action ) ) {
                    continue;
                }
                $channel = sanitize_key( $action['channel'] ?? '' );
                if ( ! in_array( $channel, [ 'telegram', 'bale' ], true ) ) {
                    continue;
                }
                $actions[] = [
                    'channel'     => $channel,
                    'destination' => sanitize_text_field( $action['destination'] ?? '' ),
                    'template'    => sanitize_textarea_field( $action['template'] ?? '' ),
                ];
            }
            $logic = strtoupper( sanitize_text_field( $rule['logic'] ?? 'AND' ) );
            $cleanRules[] = [
                'name'       => sanitize_text_field( $rule['name'] ?? '' ),
                'enabled'    => ! empty( $rule['enabled'] ),
                'logic'      => in_array( $logic, [ 'AND', 'OR' ], true ) ? $logic : 'AND',
                'conditions' => $conditions,
                'actions'    => $actions,
            ];
        }

        update_option( 'cf7on_automation_rules', $cleanRules );
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

    public function ajaxSendTest(): void {
        $this->verifyNonce();
        $channel = sanitize_key( $_POST['channel'] ?? '' );
        if ( ! in_array( $channel, [ 'telegram', 'bale' ], true ) ) {
            wp_send_json_error( __( 'Invalid channel.', 'cf7-booster' ) );
        }

        $settings = get_option( 'cf7on_' . $channel, [] );
        $destinations = (array) ( $settings['destinations'] ?? [] );
        $destination = isset( $destinations[0] ) && is_scalar( $destinations[0] )
            ? (string) $destinations[0] : '';

        if ( empty( $settings['bot_token'] ) || $destination === '' ) {
            wp_send_json_error( __( 'Save a bot token and destination first.', 'cf7-booster' ) );
        }

        if ( EngineSettings::current()['notifications_paused'] ) {
            wp_send_json_error( __( 'Notifications are paused in Settings.', 'cf7-booster' ) );
        }

        $adapter = \CF7ON\Core\Plugin::getInstance()->get( $channel );
        if ( ! $adapter ) {
            wp_send_json_error( __( 'Messaging adapter unavailable.', 'cf7-booster' ) );
        }

        // Send a harmless sample message; never use real submission information.
        $data = [
            'form_id' => 0,
            'form_name' => __( 'Test form', 'cf7-booster' ),
            'fields' => [],
        ];
        $result = $adapter->send( $data, [
            'destination' => $destination,
            'template' => __( '✅ Test notification from FormRelay CF7. Your messaging channel is working.', 'cf7-booster' ),
        ] );
        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( __( 'Test message sent to the first saved destination.', 'cf7-booster' ) );
        }
        wp_send_json_error( __( 'Test failed. Check your token, destination, and logs.', 'cf7-booster' ) );
    }

    public function ajaxResend(): void {
        $this->verifyNonce();
        if ( EngineSettings::current()['notifications_paused'] ) {
            wp_send_json_error( __( 'Notifications are paused in Settings.', 'cf7-booster' ) );
        }
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
