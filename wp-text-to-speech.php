<?php
/**
 * WP Text to Speech
 *
 * @package           WP_Text_To_Speech
 * @author            WebAppick
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       WP Text to Speech
 * Plugin URI:        https://webappick.com/wp-text-to-speech
 * Description:       Add a browser-based text-to-speech player to your posts and pages using the Web Speech API. Includes REST API for React Native and mobile apps.
 * Version:           1.0.0
 * Author:            WebAppick
 * Author URI:        https://webappick.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-text-to-speech
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
if ( ! defined( 'WP_TTS_VERSION' ) ) {
	define( 'WP_TTS_VERSION', '1.0.0' );
}
if ( ! defined( 'WP_TTS_PLUGIN_FILE' ) ) {
	define( 'WP_TTS_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'WP_TTS_PLUGIN_DIR' ) ) {
	define( 'WP_TTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WP_TTS_PLUGIN_URL' ) ) {
	define( 'WP_TTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WP_TTS_OPTION_KEY' ) ) {
	define( 'WP_TTS_OPTION_KEY', 'wp_tts_settings' );
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
function wp_tts_activate() {
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

	if ( false === get_option( WP_TTS_OPTION_KEY ) ) {
		add_option( WP_TTS_OPTION_KEY, $defaults );
	}
}
register_activation_hook( __FILE__, 'wp_tts_activate' );

/**
 * Load plugin text domain for translations.
 *
 * @since 1.0.0
 *
 * @return void
 */
function wp_tts_load_textdomain() {
	load_plugin_textdomain( 'wp-text-to-speech', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'wp_tts_load_textdomain' );

// Load admin class (admin only).
if ( is_admin() ) {
	require_once WP_TTS_PLUGIN_DIR . 'includes/class-wp-tts-admin.php';
	new WP_TTS_Admin();
}

// Load frontend class (frontend only — skip admin, AJAX, REST, and CLI).
if ( ! is_admin() && ! wp_doing_ajax() && ! defined( 'REST_REQUEST' ) && ! defined( 'WP_CLI' ) ) {
	require_once WP_TTS_PLUGIN_DIR . 'includes/class-wp-tts-frontend.php';
	new WP_TTS_Frontend();
}

// Load Gutenberg blocks (needed on both admin and frontend for SSR).
require_once WP_TTS_PLUGIN_DIR . 'includes/class-wp-tts-blocks.php';
new WP_TTS_Blocks();

// Load REST API class only if enabled in settings.
$wp_tts_opts = get_option( WP_TTS_OPTION_KEY, array() );
if ( ! empty( $wp_tts_opts['rest_api_enabled'] ) ) {
	require_once WP_TTS_PLUGIN_DIR . 'includes/class-wp-tts-rest-api.php';
	new WP_TTS_REST_API();
}
unset( $wp_tts_opts );
