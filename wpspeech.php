<?php
/**
 * Wpspeech
 *
 * @package           Wpspeech
 * @author            Devshagor
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       WP Speech
 * Plugin URI:        https://devshagor.com/wpspeech
 * Description:       Add a browser-based text-to-speech player to your posts and pages using the Web Speech API. Includes REST API for React Native and mobile apps.
 * Version:           1.0.0
 * Author:            Devshagor
 * Author URI:        https://devshagor.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpspeech
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Tested up to:      6.9
 * Requires PHP:      7.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prevent constant redefinition.
if ( ! defined( 'WPSPEECH_VERSION' ) ) {
	define( 'WPSPEECH_VERSION', '1.0.0' );
}
if ( ! defined( 'WPSPEECH_PLUGIN_FILE' ) ) {
	define( 'WPSPEECH_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'WPSPEECH_PLUGIN_DIR' ) ) {
	define( 'WPSPEECH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WPSPEECH_PLUGIN_URL' ) ) {
	define( 'WPSPEECH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WPSPEECH_OPTION_KEY' ) ) {
	define( 'WPSPEECH_OPTION_KEY', 'wpspeech_settings' );
}

/**
 * Plugin activation callback.
 *
 * Sets default options on first activation.
 *
 * @since 1.0.0
 *
 * @return void
 */
function wpspeech_activate() {
	$defaults = array(
		'enabled_post_types' => array( 'post' ),
		'voice_name'         => '',
		'speech_rate'        => 1.0,
		'pitch'              => 1.0,
		'volume'             => 1.0,
		'button_color'       => '#d60017',
		'button_position'    => 'before',
		'show_progress_bar'  => true,
		'show_speed_control' => true,
		'sticky_player'      => true,
		'rest_api_enabled'   => false,
	);

	if ( false === get_option( WPSPEECH_OPTION_KEY ) ) {
		add_option( WPSPEECH_OPTION_KEY, $defaults );
	}
}
register_activation_hook( __FILE__, 'wpspeech_activate' );

/**
 * Load plugin text domain for translations.
 *
 * @since 1.0.0
 *
 * @return void
 */
function wpspeech_load_textdomain() {
	load_plugin_textdomain( 'wpspeech', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'wpspeech_load_textdomain' );

// Load admin class (admin only).
if ( is_admin() ) {
	require_once WPSPEECH_PLUGIN_DIR . 'includes/class-wpspeech-admin.php';
	new WPSPEECH_Admin();
}

// Load frontend class (frontend only — skip admin, AJAX, REST, and CLI).
if ( ! is_admin() && ! wp_doing_ajax() && ! defined( 'REST_REQUEST' ) && ! defined( 'WP_CLI' ) ) {
	require_once WPSPEECH_PLUGIN_DIR . 'includes/class-wpspeech-frontend.php';
	new WPSPEECH_Frontend();
}

// Load Gutenberg blocks (needed on both admin and frontend for SSR).
require_once WPSPEECH_PLUGIN_DIR . 'includes/class-wpspeech-blocks.php';
new WPSPEECH_Blocks();

// Load REST API class only if enabled in settings.
$wpspeech_opts = get_option( WPSPEECH_OPTION_KEY, array() );
if ( ! empty( $wpspeech_opts['rest_api_enabled'] ) ) {
	require_once WPSPEECH_PLUGIN_DIR . 'includes/class-wpspeech-rest-api.php';
	new WPSPEECH_REST_API();
}
unset( $wpspeech_opts );
