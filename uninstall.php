<?php
/**
 * Uninstall handler for Wpspeech.
 *
 * Removes all plugin data when the plugin is deleted via the WordPress admin.
 *
 * @package Wpspeech
 * @since   1.0.0
 */

// Exit if not called by WordPress uninstaller.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wpspeech_settings' );
