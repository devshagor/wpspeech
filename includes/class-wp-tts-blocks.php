<?php
/**
 * Gutenberg blocks registration for WP Text to Speech.
 *
 * @package WP_Text_To_Speech
 * @since   1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_TTS_Blocks
 *
 * Registers all Gutenberg blocks provided by the plugin.
 * Blocks source lives in src/blocks/{block-name}/.
 * After `npm run build`, compiled files go to build/blocks/{block-name}/.
 * The class tries build/ first, falls back to src/ for development.
 *
 * @since 1.1.0
 */
class WP_TTS_Blocks {

	/**
	 * Constructor. Register hooks.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'localize_editor_data' ) );
	}

	/**
	 * Register all plugin blocks.
	 *
	 * Scans for block.json in build/ (production) or src/ (development)
	 * and registers every block found automatically.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function register_blocks() {
		$blocks = $this->discover_blocks();

		foreach ( $blocks as $block_path ) {
			register_block_type( $block_path );
		}
	}

	/**
	 * Discover all block directories.
	 *
	 * Prefers build/ (compiled) over src/ (source) for each block.
	 * This allows the plugin to work both with and without a build step.
	 *
	 * @since 1.1.0
	 *
	 * @return array List of absolute paths to block directories containing block.json.
	 */
	private function discover_blocks() {
		$blocks     = array();
		$build_dir  = WP_TTS_PLUGIN_DIR . 'build/blocks/';
		$src_dir    = WP_TTS_PLUGIN_DIR . 'src/blocks/';

		// Scan src/blocks/ for all block folders.
		$scan_dir = is_dir( $src_dir ) ? $src_dir : '';
		if ( ! $scan_dir ) {
			return $blocks;
		}

		$entries = scandir( $scan_dir );
		if ( ! $entries ) {
			return $blocks;
		}

		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			// Use build/ version if it exists, otherwise fall back to src/.
			$build_path = $build_dir . $entry;
			$src_path   = $src_dir . $entry;

			if ( file_exists( $build_path . '/block.json' ) ) {
				$blocks[] = $build_path;
			} elseif ( file_exists( $src_path . '/block.json' ) ) {
				$blocks[] = $src_path;
			}
		}

		return $blocks;
	}

	/**
	 * Pass plugin settings to the block editor so blocks can show
	 * smart warnings about duplicate players.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function localize_editor_data() {
		$options       = get_option( WP_TTS_OPTION_KEY, array() );
		$enabled_types = isset( $options['enabled_post_types'] ) ? (array) $options['enabled_post_types'] : array( 'post' );
		$button_color  = isset( $options['button_color'] ) ? $options['button_color'] : '#d60017';

		wp_localize_script( 'wp-tts-player-editor-script', 'wpTtsBlockEditor', array(
			'enabledPostTypes' => $enabled_types,
			'buttonColor'      => $button_color,
			'settingsUrl'      => admin_url( 'admin.php?page=wp-text-to-speech' ),
		) );
	}
}
