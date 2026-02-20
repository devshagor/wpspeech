<?php
/**
 * Frontend player for Wpspeech.
 *
 * @package Wpspeech
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPSPEECH_Frontend
 *
 * Injects the TTS player into post content and enqueues frontend assets.
 *
 * @since 1.0.0
 */
class WPSPEECH_Frontend {

	/**
	 * Plugin options.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $options;

	/**
	 * Constructor. Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->options = get_option( WPSPEECH_OPTION_KEY, array() );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
		add_filter( 'the_content', array( $this, 'inject_player' ), 20 );
	}

	/**
	 * Conditionally enqueue frontend CSS and JS.
	 *
	 * Only loads assets on singular views for enabled post types.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function maybe_enqueue_assets() {
		if ( ! $this->should_display() && ! $this->has_player_block() ) {
			return;
		}

		wp_enqueue_style(
			'wpspeech-frontend',
			WPSPEECH_PLUGIN_URL . 'assets/css/wpspeech-frontend.css',
			array(),
			WPSPEECH_VERSION
		);

		wp_enqueue_script(
			'wpspeech-frontend',
			WPSPEECH_PLUGIN_URL . 'assets/js/wpspeech-frontend.js',
			array(),
			WPSPEECH_VERSION,
			true
		);

		wp_localize_script( 'wpspeech-frontend', 'wpTtsSettings', array(
			'voiceName'        => isset( $this->options['voice_name'] ) ? $this->options['voice_name'] : '',
			'speechRate'       => isset( $this->options['speech_rate'] ) ? (float) $this->options['speech_rate'] : 1.0,
			'pitch'            => isset( $this->options['pitch'] ) ? (float) $this->options['pitch'] : 1.0,
			'volume'           => isset( $this->options['volume'] ) ? (float) $this->options['volume'] : 1.0,
			'showProgressBar'  => ! empty( $this->options['show_progress_bar'] ),
			'showSpeedControl' => ! empty( $this->options['show_speed_control'] ),
			'buttonColor'      => isset( $this->options['button_color'] ) ? $this->options['button_color'] : '#d60017',
			'stickyPlayer'     => ! empty( $this->options['sticky_player'] ),
			'i18n'             => array(
				'listen'      => __( 'Listen', 'wpspeech' ),
				'pause'       => __( 'Pause', 'wpspeech' ),
				'resume'      => __( 'Resume', 'wpspeech' ),
				'unsupported' => __( 'Text-to-speech is not supported in this browser.', 'wpspeech' ),
			),
		) );
	}

	/**
	 * Inject the TTS player HTML into post content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The post content.
	 * @return string Modified content with player HTML.
	 */
	public function inject_player( $content ) {
		if ( ! $this->should_display() || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		// Skip auto-injection if the TTS block is manually placed in the content.
		if ( has_block( 'wpspeech/player', get_the_ID() ) ) {
			return $content;
		}

		$button_color  = isset( $this->options['button_color'] ) ? $this->options['button_color'] : '#d60017';
		$show_progress = ! empty( $this->options['show_progress_bar'] );
		$show_speed    = ! empty( $this->options['show_speed_control'] );

		ob_start();
		?>
		<div class="wpspeech-player" role="region" aria-label="<?php esc_attr_e( 'WP Speech Player', 'wpspeech' ); ?>"
			style="--wpspeech-color: <?php echo esc_attr( $button_color ); ?>;">

			<div class="wpspeech-header-row">
				<span class="wpspeech-headphone-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
				</span>
				<div>
					<p class="wpspeech-player-title"><?php esc_html_e( 'Listen to this article', 'wpspeech' ); ?></p>
					<p class="wpspeech-player-subtitle"><?php esc_html_e( 'Powered by WP Speech', 'wpspeech' ); ?></p>
				</div>
			</div>

			<div class="wpspeech-controls">
				<button type="button" class="wpspeech-btn wpspeech-play" aria-label="<?php esc_attr_e( 'Play', 'wpspeech' ); ?>">
					<svg class="wpspeech-icon wpspeech-icon-play" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false"><polygon points="5,3 19,12 5,21"/></svg>
					<svg class="wpspeech-icon wpspeech-icon-pause" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="display:none;" aria-hidden="true" focusable="false"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
					<span class="wpspeech-btn-label"><?php esc_html_e( 'Listen', 'wpspeech' ); ?></span>
				</button>

				<button type="button" class="wpspeech-btn wpspeech-stop" aria-label="<?php esc_attr_e( 'Stop', 'wpspeech' ); ?>" disabled>
					<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true" focusable="false"><rect x="5" y="5" width="14" height="14" rx="2"/></svg>
				</button>

				<div class="wpspeech-wave" aria-hidden="true">
					<span></span><span></span><span></span><span></span><span></span>
				</div>

				<?php if ( $show_speed ) : ?>
				<div class="wpspeech-speed-control">
					<label for="wpspeech-speed" class="screen-reader-text"><?php esc_html_e( 'Playback speed', 'wpspeech' ); ?></label>
					<select id="wpspeech-speed" class="wpspeech-speed-select">
						<option value="0.75">0.75x</option>
						<option value="1" selected>1x</option>
						<option value="1.25">1.25x</option>
						<option value="1.5">1.5x</option>
						<option value="2">2x</option>
					</select>
				</div>
				<?php endif; ?>

				<span class="wpspeech-time" role="status" aria-live="polite"></span>
			</div>

			<?php if ( $show_progress ) : ?>
			<div class="wpspeech-progress-bar" role="progressbar" aria-label="<?php esc_attr_e( 'Reading progress', 'wpspeech' ); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
				<div class="wpspeech-progress-fill"></div>
			</div>
			<?php endif; ?>
		</div>
		<?php
		$player_html = ob_get_clean();

		$position = isset( $this->options['button_position'] ) ? $this->options['button_position'] : 'before';
		if ( 'after' === $position ) {
			return $content . $player_html;
		}

		return $player_html . $content;
	}

	/**
	 * Check if the player should be displayed on the current page.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the player should display.
	 */
	private function should_display() {
		if ( ! is_singular() ) {
			return false;
		}
		$enabled_types = isset( $this->options['enabled_post_types'] ) ? (array) $this->options['enabled_post_types'] : array( 'post' );
		return is_singular( $enabled_types );
	}

	/**
	 * Check if the current singular post contains the TTS player block.
	 *
	 * @since 1.1.0
	 *
	 * @return bool True if the block is present.
	 */
	private function has_player_block() {
		if ( ! is_singular() ) {
			return false;
		}
		return has_block( 'wpspeech/player', get_queried_object_id() );
	}
}
