<?php
/**
 * Form tab and automation checks with WordPress and CF7 mocked.
 */
declare(strict_types=1);

namespace {
    define( 'ABSPATH', __DIR__ . '/' );
    class FormAjaxExit extends \RuntimeException {}
    $GLOBALS['form_test_options'] = [];
    $GLOBALS['form_ajax_result'] = null;

    class WPCF7_ContactForm {
        public static function get_instance( int $id ) { return $id === 42 ? new self() : null; }
    }
    function get_option( $key, $fallback = false ) {
        return $GLOBALS['form_test_options'][ $key ] ?? $fallback;
    }
    function update_option( $key, $value ) { $GLOBALS['form_test_options'][ $key ] = $value; return true; }
    function current_user_can( $cap ) { return $GLOBALS['form_is_admin'] ?? true; }
    function check_ajax_referer( ...$args ) { return true; }
    function wp_unslash( $value ) {
        return is_array( $value ) ? array_map( 'wp_unslash', $value )
            : ( is_string( $value ) ? stripslashes( $value ) : $value );
    }
    function sanitize_text_field( $text ): string { return trim( strip_tags( (string) $text ) ); }
    function sanitize_textarea_field( $text ): string { return trim( (string) $text ); }
    function sanitize_key( $text ): string { return strtolower( trim( (string) $text ) ); }
    function absint( $value ): int { return abs( (int) $value ); }
    function __( $label, $domain = '' ) { return $label; }
    function wp_send_json_success( $text = null ) {
        $GLOBALS['form_ajax_result'] = [ 'success' => true, 'data' => $text ];
        throw new FormAjaxExit();
    }
    function wp_send_json_error( $text = null ) {
        $GLOBALS['form_ajax_result'] = [ 'success' => false, 'data' => $text ];
        throw new FormAjaxExit();
    }
    function assert_ok( bool $result, string $label ): void {
        if ( ! $result ) throw new \RuntimeException( $label );
    }
    function call_form_ajax( callable $handler ): array {
        $GLOBALS['form_ajax_result'] = null;
        try { $handler(); } catch ( FormAjaxExit $e ) {}
        assert_ok( is_array( $GLOBALS['form_ajax_result'] ), 'No AJAX response.' );
        return $GLOBALS['form_ajax_result'];
    }

    require_once dirname( __DIR__ ) . '/app/Modules/FormTab/FormTabModule.php';
    require_once dirname( __DIR__ ) . '/app/Modules/Automation/AutomationEngine.php';

    $form = new \CF7ON\Modules\FormTab\FormTabModule();
    $_POST = [
        'form_id' => '42',
        'cf7on_tg_enabled' => '1',
        'cf7on_tg_destinations' => "@first\n\n@second\n",
        'cf7on_tg_template' => 'Hello {name}',
        'cf7on_ba_enabled' => '',
        'cf7on_ba_destinations' => "@bale\n",
        'cf7on_ba_template' => 'Bale {all_fields}',
    ];
    assert_ok( call_form_ajax( [ $form, 'ajaxSaveFormSettings' ] )['success'], 'Form settings could not be saved.' );
    $saved = \CF7ON\Modules\FormTab\FormTabModule::getFormSettings( 42 );
    assert_ok( $saved['telegram']['enabled'] === true, 'Telegram toggle not saved.' );
    assert_ok( $saved['telegram']['destinations'] === [ '@first', '@second' ], 'Destinations not parsed.' );
    assert_ok( $saved['bale']['enabled'] === false, 'Bale toggle incorrectly enabled.' );
    assert_ok( $saved['telegram']['template'] === 'Hello {name}', 'Message template lost.' );

    $_POST['form_id'] = '999';
    assert_ok( ! call_form_ajax( [ $form, 'ajaxSaveFormSettings' ] )['success'], 'Nonexistent form accepted.' );
    $GLOBALS['form_is_admin'] = false;
    $_POST['form_id'] = '42';
    assert_ok( ! call_form_ajax( [ $form, 'ajaxSaveFormSettings' ] )['success'], 'Unauthorized form edit accepted.' );
    $GLOBALS['form_is_admin'] = true;

    $engine = new \CF7ON\Modules\Automation\AutomationEngine();
    $submission = [
        'form_id' => 42,
        'form_name' => 'Registration',
        'fields' => [ 'your-name' => 'Sample' ],
    ];
    $GLOBALS['form_test_options']['cf7on_automation_rules'] = [
        [
            'enabled' => true,
            'logic' => 'AND',
            'conditions' => [ [ 'type' => 'form_id', 'value' => '42' ] ],
            'actions' => [ [ 'channel' => 'telegram', 'destination' => '' ] ],
        ],
    ];
    assert_ok( $engine->resolveTargets( $submission ) === [], 'Empty destination suppressed routing fallback.' );
    $GLOBALS['form_test_options']['cf7on_automation_rules'][0]['actions'][0]['destination'] = '@target';
    $targets = $engine->resolveTargets( $submission );
    assert_ok( count( $targets ) === 1 && $targets[0]['destination'] === '@target', 'Configured rule was not resolved.' );

    $GLOBALS['form_test_options']['cf7on_form_9'] = 'corrupt option';
    assert_ok( \CF7ON\Modules\FormTab\FormTabModule::getFormSettings( 9 ) === [],
        'Malformed legacy form option should be ignored.' );

    echo "Form settings and automation smoke tests passed.\n";
}
