<?php
/**
 * Uninstall handler for WP Text to Speech.
 *
 * Removes all plugin data when the plugin is deleted via the WordPress admin.
 *
 * @package WP_Text_To_Speech
 * @since   1.0.0
 */

// Exit if not called by WordPress uninstaller.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wp_tts_settings' );
