<?php
/**
 * Plugin Name:       CF7 Booster
 * Plugin URI:        https://inaser.ir/plugin/cf7-booster
 * Description:       Supercharge Contact Form 7 — per-form Telegram & Bale notifications, automation rules, submission management, and delivery logs.
 * Version:           1.1.0
 * Author:            inaser.ir
 * Author URI:        https://inaser.ir
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cf7-booster
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'CF7ON_VERSION',  '1.1.0' );
define( 'CF7ON_FILE',     __FILE__ );
define( 'CF7ON_PATH',     plugin_dir_path( __FILE__ ) );
define( 'CF7ON_URL',      plugin_dir_url( __FILE__ ) );
define( 'CF7ON_BASENAME', plugin_basename( __FILE__ ) );
define( 'CF7ON_SLUG',     'cf7-booster' );

// PSR-4 style autoloader  CF7ON\ → app/
spl_autoload_register( function ( $class ) {
    $prefix   = 'CF7ON\\';
    $base_dir = CF7ON_PATH . 'app/';
    if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) return;
    $file = $base_dir . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
    if ( file_exists( $file ) ) require $file;
} );

add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WPCF7' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>'
               . '<strong>CF7 Booster</strong> requires <strong>Contact Form 7</strong> to be installed and active.'
               . '</p></div>';
        } );
        return;
    }
    \CF7ON\Core\Plugin::getInstance()->boot();
} );

register_activation_hook(   __FILE__, [ '\CF7ON\Core\Installer', 'activate' ] );
register_deactivation_hook( __FILE__, [ '\CF7ON\Core\Installer', 'deactivate' ] );
