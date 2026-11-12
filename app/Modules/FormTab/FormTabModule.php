<?php
namespace CF7ON\Modules\FormTab;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a "CF7 Booster" tab to every CF7 form edit page.
 * URL pattern: /wp-admin/admin.php?page=wpcf7&post=XXX&active-tab=cf7booster
 *
 * Stores per-form settings under option key:  cf7on_form_{$form_id}
 * Structure:
 * [
 *   'telegram' => [
 *     'enabled'      => bool,
 *     'destinations' => string[],   // override global — empty = use global
 *     'template'     => string,
 *   ],
 *   'bale' => [ same ],
 * ]
 */
class FormTabModule {

    public function register(): void {
        // Register the tab in CF7's tab list
        add_filter( 'wpcf7_editor_panels', [ $this, 'addPanel' ] );

        // Enqueue our assets on CF7 edit pages
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );

        // Save when CF7 saves the form
        add_action( 'wpcf7_save_contact_form', [ $this, 'saveFormSettings' ] );

        // AJAX save (manual Save button inside the tab)
        add_action( 'wp_ajax_cf7on_save_form_settings', [ $this, 'ajaxSaveFormSettings' ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Register the panel / tab                                            */
    /* ------------------------------------------------------------------ */

    public function addPanel( array $panels ): array {
        $panels['cf7booster'] = [
            'title'    => '🚀 CF7 Booster',
            'callback' => [ $this, 'renderPanel' ],
        ];
        return $panels;
    }

    /* ------------------------------------------------------------------ */
    /*  Enqueue assets only on CF7 form edit pages                         */
    /* ------------------------------------------------------------------ */

    public function enqueueAssets( string $hook ): void {
        // CF7 edit page hook is 'contact_page_wpcf7' or similar; check page param
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'wpcf7' ) {
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
            'strings' => \CF7ON\Core\AdminTranslations::all(),
        ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Render the tab panel                                                */
    /* ------------------------------------------------------------------ */

    public function renderPanel( \WPCF7_ContactForm $cf7 ): void {
        $form_id  = $cf7->id();
        $settings = get_option( 'cf7on_form_' . $form_id, [] );

        // Global channel options (for reference / defaults)
        $global_tg = get_option( 'cf7on_telegram', [] );
        $global_ba = get_option( 'cf7on_bale', [] );

        // Default template
        $default_tpl = get_user_locale() === 'fa_IR'
            ? "📩 فرم: {form_name}\n\nنام: {name}\nتلفن: {phone}\nایمیل: {email}\n\nپیام:\n{message}\n\nتاریخ: {submission_date}"
            : "📩 Form: {form_name}\n\nName: {name}\nPhone: {phone}\nEmail: {email}\n\nMessage:\n{message}\n\nDate: {submission_date}";

        // This form's field names
        $field_names = [];
        foreach ( $cf7->scan_form_tags() as $tag ) {
            if ( ! empty( $tag->name ) ) {
                $field_names[] = [ 'name' => $tag->name, 'type' => $tag->basetype ];
            }
        }

        $system_placeholders = [
            '{name}'            => __( 'Name (your-name or similar)', 'cf7-booster' ),
            '{email}'           => __( 'Email', 'cf7-booster' ),
            '{phone}'           => __( 'Phone', 'cf7-booster' ),
            '{message}'         => __( 'Message', 'cf7-booster' ),
            '{subject}'         => __( 'Subject', 'cf7-booster' ),
            '{form_name}'       => __( 'Form title', 'cf7-booster' ),
            '{submission_date}' => __( 'Submission date', 'cf7-booster' ),
            '{form_id}'         => __( 'Form ID', 'cf7-booster' ),
            '{ip_address}'      => __( 'Visitor IP address', 'cf7-booster' ),
            '{referrer}'        => __( 'Page URL', 'cf7-booster' ),
            '{all_fields}'      => __( 'All fields', 'cf7-booster' ),
        ];

        require CF7ON_PATH . 'templates/admin/form-tab.php';
    }

    /* ------------------------------------------------------------------ */
    /*  Save on CF7 native save                                             */
    /* ------------------------------------------------------------------ */

    public function saveFormSettings( \WPCF7_ContactForm $cf7 ): void {
        if ( ! isset( $_POST['cf7on_form_tab_nonce'] ) ) return;
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cf7on_form_tab_nonce'] ) ), 'cf7on_form_tab_' . $cf7->id() ) ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        $this->persistFormSettings( $cf7->id() );
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX save                                                           */
    /* ------------------------------------------------------------------ */

    public function ajaxSaveFormSettings(): void {
        if ( ! check_ajax_referer( 'cf7on_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Security check failed.' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized.' );
        }

        $form_id = absint( $_POST['form_id'] ?? 0 );
        if ( ! $form_id || ! \WPCF7_ContactForm::get_instance( $form_id ) ) {
            wp_send_json_error( __( 'Invalid form ID.', 'cf7-booster' ) );
        }

        $this->persistFormSettings( $form_id );
        wp_send_json_success( __( 'Form settings saved.', 'cf7-booster' ) );
    }

    /* ------------------------------------------------------------------ */
    /*  Persist helper                                                      */
    /* ------------------------------------------------------------------ */

    private function persistFormSettings( int $form_id ): void {
        $tg_dests = array_filter( array_map(
            'sanitize_text_field',
            explode( "\n", wp_unslash( $_POST['cf7on_tg_destinations'] ?? '' ) )
        ) );
        $ba_dests = array_filter( array_map(
            'sanitize_text_field',
            explode( "\n", wp_unslash( $_POST['cf7on_ba_destinations'] ?? '' ) )
        ) );

        $data = [
            'telegram' => [
                'enabled'      => ! empty( $_POST['cf7on_tg_enabled'] ),
                'destinations' => array_values( $tg_dests ),
                'template'     => sanitize_textarea_field( wp_unslash( $_POST['cf7on_tg_template'] ?? '' ) ),
            ],
            'bale' => [
                'enabled'      => ! empty( $_POST['cf7on_ba_enabled'] ),
                'destinations' => array_values( $ba_dests ),
                'template'     => sanitize_textarea_field( wp_unslash( $_POST['cf7on_ba_template'] ?? '' ) ),
            ],
        ];

        update_option( 'cf7on_form_' . $form_id, $data );
    }

    /* ------------------------------------------------------------------ */
    /*  Static helper: get per-form settings                               */
    /* ------------------------------------------------------------------ */

    public static function getFormSettings( int $form_id ): array {
        $settings = get_option( 'cf7on_form_' . $form_id, [] );
        return is_array( $settings ) ? $settings : [];
    }
}
