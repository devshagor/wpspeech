<?php
/**
 * Admin settings page for WP Text to Speech.
 *
 * @package WP_Text_To_Speech
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_TTS_Admin
 *
 * Handles the plugin settings page under Settings > Text to Speech.
 *
 * @since 1.0.0
 */
class WP_TTS_Admin {

	/**
	 * Constructor. Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add top-level settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_menu_page(
			__( 'Text to Speech', 'wp-text-to-speech' ),
			__( 'Text to Speech', 'wp-text-to-speech' ),
			'manage_options',
			'wp-text-to-speech',
			array( $this, 'render_settings_page' ),
			'dashicons-controls-volumeon',
			80
		);

		add_submenu_page(
			'wp-text-to-speech',
			__( 'Settings', 'wp-text-to-speech' ),
			__( 'Settings', 'wp-text-to-speech' ),
			'manage_options',
			'wp-text-to-speech',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'wp-text-to-speech',
			__( 'Analytics', 'wp-text-to-speech' ),
			__( 'Analytics', 'wp-text-to-speech' ),
			'manage_options',
			'wp-tts-analytics',
			array( $this, 'render_analytics_page' )
		);

		add_submenu_page(
			'wp-text-to-speech',
			__( 'Help', 'wp-text-to-speech' ),
			__( 'Help', 'wp-text-to-speech' ),
			'manage_options',
			'wp-tts-help',
			array( $this, 'render_help_page' )
		);
	}

	/**
	 * Register settings for sanitization.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'wp_tts_settings_group',
			WP_TTS_OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize all settings before saving.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input Raw input from the settings form.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['enabled_post_types'] = array();
		if ( ! empty( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
			$sanitized['enabled_post_types'] = array_map( 'sanitize_key', $input['enabled_post_types'] );
		}

		$sanitized['voice_name'] = isset( $input['voice_name'] ) ? sanitize_text_field( $input['voice_name'] ) : '';

		$sanitized['speech_rate'] = isset( $input['speech_rate'] ) ? (float) $input['speech_rate'] : 1.0;
		$sanitized['speech_rate'] = max( 0.5, min( 2.0, $sanitized['speech_rate'] ) );

		$sanitized['pitch'] = isset( $input['pitch'] ) ? (float) $input['pitch'] : 1.0;
		$sanitized['pitch'] = max( 0.0, min( 2.0, $sanitized['pitch'] ) );

		$sanitized['volume'] = isset( $input['volume'] ) ? (float) $input['volume'] : 1.0;
		$sanitized['volume'] = max( 0.0, min( 1.0, $sanitized['volume'] ) );

		$color = isset( $input['button_color'] ) ? sanitize_hex_color( $input['button_color'] ) : '';
		$sanitized['button_color'] = $color ? $color : '#d60017';

		$valid_positions              = array( 'before', 'after' );
		$position                     = isset( $input['button_position'] ) ? $input['button_position'] : 'before';
		$sanitized['button_position'] = in_array( $position, $valid_positions, true ) ? $position : 'before';

		$sanitized['show_progress_bar']  = ! empty( $input['show_progress_bar'] );
		$sanitized['show_speed_control'] = ! empty( $input['show_speed_control'] );
		$sanitized['sticky_player']      = ! empty( $input['sticky_player'] );
		$sanitized['rest_api_enabled']   = ! empty( $input['rest_api_enabled'] );

		return $sanitized;
	}

	/**
	 * Enqueue admin CSS and JS on the plugin settings page only.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		$plugin_pages = array(
			'toplevel_page_wp-text-to-speech',
			'text-to-speech_page_wp-tts-analytics',
			'text-to-speech_page_wp-tts-help',
		);

		if ( ! in_array( $hook, $plugin_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'wp-tts-admin',
			WP_TTS_PLUGIN_URL . 'assets/css/wp-tts-admin.css',
			array(),
			WP_TTS_VERSION
		);

		// Settings page needs color picker and admin JS.
		if ( 'toplevel_page_wp-text-to-speech' === $hook ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script(
				'wp-tts-admin',
				WP_TTS_PLUGIN_URL . 'assets/js/wp-tts-admin.js',
				array( 'jquery', 'wp-color-picker' ),
				WP_TTS_VERSION,
				true
			);
		}
	}

	/**
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options       = get_option( WP_TTS_OPTION_KEY, array() );
		$voice         = isset( $options['voice_name'] ) ? $options['voice_name'] : '';
		$rate          = isset( $options['speech_rate'] ) ? (float) $options['speech_rate'] : 1.0;
		$pitch         = isset( $options['pitch'] ) ? (float) $options['pitch'] : 1.0;
		$volume        = isset( $options['volume'] ) ? (float) $options['volume'] : 1.0;
		$color         = isset( $options['button_color'] ) ? $options['button_color'] : '#d60017';
		$position      = isset( $options['button_position'] ) ? $options['button_position'] : 'before';
		$progress_bar  = ! empty( $options['show_progress_bar'] );
		$speed_control = ! empty( $options['show_speed_control'] );
		$sticky_player  = ! empty( $options['sticky_player'] );
		$rest_api       = ! empty( $options['rest_api_enabled'] );
		$enabled_types  = isset( $options['enabled_post_types'] ) ? (array) $options['enabled_post_types'] : array( 'post' );
		$post_types    = get_post_types( array( 'public' => true ), 'objects' );
		$opt_key       = WP_TTS_OPTION_KEY;
		$site_url      = home_url();
		?>
		<div class="wp-tts-admin-wrap">

			<!-- Header -->
			<div class="wp-tts-header">
				<div class="wp-tts-header-inner">
					<div class="wp-tts-header-left">
						<div class="wp-tts-logo">
							<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
						</div>
						<div>
							<h1 class="wp-tts-header-title"><?php esc_html_e( 'WP Text to Speech', 'wp-text-to-speech' ); ?></h1>
							<p class="wp-tts-header-version"><?php echo esc_html( 'v' . WP_TTS_VERSION ); ?></p>
						</div>
					</div>
					<div class="wp-tts-header-right">
						<span class="wp-tts-status-badge wp-tts-status-active">
							<span class="wp-tts-status-dot"></span>
							<?php esc_html_e( 'Active', 'wp-text-to-speech' ); ?>
						</span>
					</div>
				</div>
			</div>

			<!-- Tabs -->
			<div class="wp-tts-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'wp-text-to-speech' ); ?>">
				<button type="button" class="wp-tts-tab wp-tts-tab-active" data-tab="voice" role="tab" aria-selected="true" aria-controls="wp-tts-panel-voice" id="wp-tts-tab-voice">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
					<?php esc_html_e( 'Voice', 'wp-text-to-speech' ); ?>
				</button>
				<button type="button" class="wp-tts-tab" data-tab="display" role="tab" aria-selected="false" aria-controls="wp-tts-panel-display" id="wp-tts-tab-display" tabindex="-1">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
					<?php esc_html_e( 'Display', 'wp-text-to-speech' ); ?>
				</button>
				<button type="button" class="wp-tts-tab" data-tab="preview" role="tab" aria-selected="false" aria-controls="wp-tts-panel-preview" id="wp-tts-tab-preview" tabindex="-1">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><polygon points="5 3 19 12 5 21 5 3"/></svg>
					<?php esc_html_e( 'Preview', 'wp-text-to-speech' ); ?>
				</button>
				<button type="button" class="wp-tts-tab" data-tab="api" role="tab" aria-selected="false" aria-controls="wp-tts-panel-api" id="wp-tts-tab-api" tabindex="-1">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/></svg>
					<?php esc_html_e( 'API', 'wp-text-to-speech' ); ?>
				</button>
			</div>

			<!-- Form wraps all tab panels -->
			<form action="options.php" method="post" class="wp-tts-form">
				<?php settings_fields( 'wp_tts_settings_group' ); ?>

				<!-- Voice Tab -->
				<div class="wp-tts-tab-panel wp-tts-tab-panel-active" data-panel="voice" role="tabpanel" id="wp-tts-panel-voice" aria-labelledby="wp-tts-tab-voice">
					<div class="wp-tts-card">
						<div class="wp-tts-card-header">
							<h2 class="wp-tts-card-title"><?php esc_html_e( 'Voice Configuration', 'wp-text-to-speech' ); ?></h2>
							<p class="wp-tts-card-desc"><?php esc_html_e( 'Select a voice and adjust playback parameters. Voices come from your browser/OS.', 'wp-text-to-speech' ); ?></p>
						</div>
						<div class="wp-tts-card-body">

							<!-- Voice Select -->
							<div class="wp-tts-field">
								<label class="wp-tts-field-label" for="wp-tts-voice-name"><?php esc_html_e( 'Voice', 'wp-text-to-speech' ); ?></label>
								<div class="wp-tts-field-control">
									<select id="wp-tts-voice-name" name="<?php echo esc_attr( $opt_key ); ?>[voice_name]" class="wp-tts-select">
										<option value=""><?php esc_html_e( 'Browser Default', 'wp-text-to-speech' ); ?></option>
									</select>
									<input type="hidden" id="wp-tts-voice-saved" value="<?php echo esc_attr( $voice ); ?>" />
								</div>
								<p class="wp-tts-field-hint"><?php esc_html_e( 'Available voices vary by browser and OS. Visitors hear their own device voices.', 'wp-text-to-speech' ); ?></p>
							</div>

							<!-- Speed Slider -->
							<div class="wp-tts-field">
								<label class="wp-tts-field-label" for="wp-tts-speech-rate"><?php esc_html_e( 'Speed', 'wp-text-to-speech' ); ?></label>
								<div class="wp-tts-slider-row">
									<span class="wp-tts-slider-min">0.5x</span>
									<input type="range" id="wp-tts-speech-rate" name="<?php echo esc_attr( $opt_key ); ?>[speech_rate]"
										class="wp-tts-range" min="0.5" max="2" step="0.1" value="<?php echo esc_attr( $rate ); ?>" />
									<span class="wp-tts-slider-max">2x</span>
									<span class="wp-tts-slider-value" id="wp-tts-rate-val"><?php echo esc_html( $rate ); ?>x</span>
								</div>
							</div>

							<!-- Pitch Slider -->
							<div class="wp-tts-field">
								<label class="wp-tts-field-label" for="wp-tts-pitch"><?php esc_html_e( 'Pitch', 'wp-text-to-speech' ); ?></label>
								<div class="wp-tts-slider-row">
									<span class="wp-tts-slider-min">0</span>
									<input type="range" id="wp-tts-pitch" name="<?php echo esc_attr( $opt_key ); ?>[pitch]"
										class="wp-tts-range" min="0" max="2" step="0.1" value="<?php echo esc_attr( $pitch ); ?>" />
									<span class="wp-tts-slider-max">2</span>
									<span class="wp-tts-slider-value" id="wp-tts-pitch-val"><?php echo esc_html( $pitch ); ?></span>
								</div>
							</div>

							<!-- Volume Slider -->
							<div class="wp-tts-field">
								<label class="wp-tts-field-label" for="wp-tts-volume"><?php esc_html_e( 'Volume', 'wp-text-to-speech' ); ?></label>
								<div class="wp-tts-slider-row">
									<span class="wp-tts-slider-min">0</span>
									<input type="range" id="wp-tts-volume" name="<?php echo esc_attr( $opt_key ); ?>[volume]"
										class="wp-tts-range" min="0" max="1" step="0.1" value="<?php echo esc_attr( $volume ); ?>" />
									<span class="wp-tts-slider-max">1</span>
									<span class="wp-tts-slider-value" id="wp-tts-volume-val"><?php echo esc_html( $volume ); ?></span>
								</div>
							</div>

						</div>
					</div>

					<div class="wp-tts-save-row">
						<?php submit_button( __( 'Save Settings', 'wp-text-to-speech' ), 'primary wp-tts-save-btn', 'submit', false ); ?>
					</div>
				</div>

				<!-- Display Tab -->
				<div class="wp-tts-tab-panel" data-panel="display" role="tabpanel" id="wp-tts-panel-display" aria-labelledby="wp-tts-tab-display">
					<div class="wp-tts-card">
						<div class="wp-tts-card-header">
							<h2 class="wp-tts-card-title"><?php esc_html_e( 'Player Appearance', 'wp-text-to-speech' ); ?></h2>
							<p class="wp-tts-card-desc"><?php esc_html_e( 'Control where and how the player appears on your site.', 'wp-text-to-speech' ); ?></p>
						</div>
						<div class="wp-tts-card-body">

							<!-- Post Types -->
							<div class="wp-tts-field">
								<label class="wp-tts-field-label"><?php esc_html_e( 'Enable on Post Types', 'wp-text-to-speech' ); ?></label>
								<div class="wp-tts-post-types-grid">
									<?php foreach ( $post_types as $pt ) : ?>
										<?php if ( 'attachment' === $pt->name ) continue; ?>
										<label class="wp-tts-chip">
											<input type="checkbox" name="<?php echo esc_attr( $opt_key ); ?>[enabled_post_types][]"
												value="<?php echo esc_attr( $pt->name ); ?>"
												<?php checked( in_array( $pt->name, $enabled_types, true ) ); ?> />
											<span class="wp-tts-chip-inner">
												<svg class="wp-tts-chip-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
												<span class="wp-tts-chip-label"><?php echo esc_html( $pt->labels->singular_name ); ?></span>
											</span>
										</label>
									<?php endforeach; ?>
								</div>
							</div>

							<!-- Button Color -->
							<div class="wp-tts-field">
								<label class="wp-tts-field-label" for="wp-tts-button-color"><?php esc_html_e( 'Button Color', 'wp-text-to-speech' ); ?></label>
								<div class="wp-tts-field-control">
									<input type="text" id="wp-tts-button-color" name="<?php echo esc_attr( $opt_key ); ?>[button_color]"
										value="<?php echo esc_attr( $color ); ?>" class="wp-tts-color-field" data-default-color="#d60017" />
								</div>
							</div>

							<!-- Position -->
							<div class="wp-tts-field">
								<label class="wp-tts-field-label"><?php esc_html_e( 'Button Position', 'wp-text-to-speech' ); ?></label>
								<div class="wp-tts-position-cards">
									<label class="wp-tts-position-card">
										<input type="radio" name="<?php echo esc_attr( $opt_key ); ?>[button_position]" value="before" <?php checked( $position, 'before' ); ?> />
										<span class="wp-tts-position-card-inner">
											<span class="wp-tts-position-preview">
												<span class="wp-tts-position-player-bar"></span>
												<span class="wp-tts-position-line wp-tts-position-line-full"></span>
												<span class="wp-tts-position-line wp-tts-position-line-full"></span>
												<span class="wp-tts-position-line wp-tts-position-line-short"></span>
											</span>
											<span class="wp-tts-position-label"><?php esc_html_e( 'Before Content', 'wp-text-to-speech' ); ?></span>
										</span>
									</label>
									<label class="wp-tts-position-card">
										<input type="radio" name="<?php echo esc_attr( $opt_key ); ?>[button_position]" value="after" <?php checked( $position, 'after' ); ?> />
										<span class="wp-tts-position-card-inner">
											<span class="wp-tts-position-preview">
												<span class="wp-tts-position-line wp-tts-position-line-full"></span>
												<span class="wp-tts-position-line wp-tts-position-line-full"></span>
												<span class="wp-tts-position-line wp-tts-position-line-short"></span>
												<span class="wp-tts-position-player-bar"></span>
											</span>
											<span class="wp-tts-position-label"><?php esc_html_e( 'After Content', 'wp-text-to-speech' ); ?></span>
										</span>
									</label>
								</div>
							</div>

							<!-- Toggles -->
							<div class="wp-tts-field">
								<h2><?php esc_html_e( 'Player Features', 'wp-text-to-speech' ); ?></h2>
								<div class="wp-tts-toggles-list">
									<label class="wp-tts-toggle-row">
										<span class="wp-tts-toggle-info">
											<span class="wp-tts-toggle-name"><?php esc_html_e( 'Progress Bar', 'wp-text-to-speech' ); ?></span>
											<span class="wp-tts-toggle-desc"><?php esc_html_e( 'Visual indicator showing reading progress below the controls', 'wp-text-to-speech' ); ?></span>
										</span>
										<span class="wp-tts-toggle-switch-wrap">
											<input type="checkbox" id="wp-tts-progress" name="<?php echo esc_attr( $opt_key ); ?>[show_progress_bar]" value="1" <?php checked( $progress_bar ); ?> />
											<span class="wp-tts-toggle-switch"><span class="wp-tts-toggle-knob"></span></span>
										</span>
									</label>
									<label class="wp-tts-toggle-row">
										<span class="wp-tts-toggle-info">
											<span class="wp-tts-toggle-name"><?php esc_html_e( 'Speed Control', 'wp-text-to-speech' ); ?></span>
											<span class="wp-tts-toggle-desc"><?php esc_html_e( 'Dropdown for visitors to change playback speed (0.75x - 2x)', 'wp-text-to-speech' ); ?></span>
										</span>
										<span class="wp-tts-toggle-switch-wrap">
											<input type="checkbox" id="wp-tts-speed" name="<?php echo esc_attr( $opt_key ); ?>[show_speed_control]" value="1" <?php checked( $speed_control ); ?> />
											<span class="wp-tts-toggle-switch"><span class="wp-tts-toggle-knob"></span></span>
										</span>
									</label>
									<label class="wp-tts-toggle-row">
										<span class="wp-tts-toggle-info">
											<span class="wp-tts-toggle-name"><?php esc_html_e( 'Sticky Player', 'wp-text-to-speech' ); ?></span>
											<span class="wp-tts-toggle-desc"><?php esc_html_e( 'Show a mini player at the bottom of the screen while scrolling during playback', 'wp-text-to-speech' ); ?></span>
										</span>
										<span class="wp-tts-toggle-switch-wrap">
											<input type="checkbox" id="wp-tts-sticky" name="<?php echo esc_attr( $opt_key ); ?>[sticky_player]" value="1" <?php checked( $sticky_player ); ?> />
											<span class="wp-tts-toggle-switch"><span class="wp-tts-toggle-knob"></span></span>
										</span>
									</label>
								</div>
							</div>

						</div>
					</div>

					<div class="wp-tts-save-row">
						<?php submit_button( __( 'Save Settings', 'wp-text-to-speech' ), 'primary wp-tts-save-btn', 'submit', false ); ?>
					</div>
				</div>

				<!-- Preview Tab -->
				<div class="wp-tts-tab-panel" data-panel="preview" role="tabpanel" id="wp-tts-panel-preview" aria-labelledby="wp-tts-tab-preview">
					<div class="wp-tts-card">
						<div class="wp-tts-card-header">
							<h2 class="wp-tts-card-title"><?php esc_html_e( 'Live Preview', 'wp-text-to-speech' ); ?></h2>
							<p class="wp-tts-card-desc"><?php esc_html_e( 'Test the current voice settings before saving. Type or paste any text below.', 'wp-text-to-speech' ); ?></p>
						</div>
						<div class="wp-tts-card-body">
							<div class="wp-tts-preview-area">
								<textarea id="wp-tts-preview-text" rows="4" class="wp-tts-textarea"
									placeholder="<?php esc_attr_e( 'Type something to hear it spoken aloud...', 'wp-text-to-speech' ); ?>"
								><?php esc_html_e( 'Welcome to WP Text to Speech. This plugin lets your visitors listen to articles with a single click. Try adjusting the speed, pitch, and volume in the Voice tab to find the perfect settings for your audience.', 'wp-text-to-speech' ); ?></textarea>

								<div class="wp-tts-preview-controls">
									<button type="button" id="wp-tts-preview-btn" class="wp-tts-btn-preview">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>
										<span id="wp-tts-preview-label"><?php esc_html_e( 'Play Preview', 'wp-text-to-speech' ); ?></span>
									</button>
									<button type="button" id="wp-tts-stop-preview-btn" class="wp-tts-btn-stop" style="display:none;">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><rect x="5" y="5" width="14" height="14" rx="2"/></svg>
										<?php esc_html_e( 'Stop', 'wp-text-to-speech' ); ?>
									</button>
									<div class="wp-tts-preview-wave" id="wp-tts-wave" style="display:none;">
										<span></span><span></span><span></span><span></span><span></span>
									</div>
								</div>
							</div>

							<!-- Player Mockup -->
							<div class="wp-tts-mockup">
								<p class="wp-tts-mockup-label"><?php esc_html_e( 'This is how the player looks on your site:', 'wp-text-to-speech' ); ?></p>
								<div class="wp-tts-player-mockup" style="--wp-tts-color: <?php echo esc_attr( $color ); ?>;">
									<div class="wp-tts-mockup-controls">
										<span class="wp-tts-mockup-play">
											<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>
											<?php esc_html_e( 'Listen to this article', 'wp-text-to-speech' ); ?>
										</span>
										<span class="wp-tts-mockup-stop">
											<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><rect x="5" y="5" width="14" height="14" rx="2"/></svg>
										</span>
										<?php if ( $speed_control ) : ?>
											<span class="wp-tts-mockup-speed">1x</span>
										<?php endif; ?>
										<span class="wp-tts-mockup-counter">0 / 12</span>
									</div>
									<?php if ( $progress_bar ) : ?>
										<div class="wp-tts-mockup-progress">
											<div class="wp-tts-mockup-progress-fill"></div>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- API Tab -->
				<div class="wp-tts-tab-panel" data-panel="api" role="tabpanel" id="wp-tts-panel-api" aria-labelledby="wp-tts-tab-api">
					<div class="wp-tts-card">
						<div class="wp-tts-card-header">
							<h2 class="wp-tts-card-title"><?php esc_html_e( 'REST API', 'wp-text-to-speech' ); ?></h2>
							<p class="wp-tts-card-desc"><?php esc_html_e( 'Enable REST API endpoints for React Native and mobile apps.', 'wp-text-to-speech' ); ?></p>
						</div>
						<div class="wp-tts-card-body">

							<!-- Enable Toggle -->
							<div class="wp-tts-field">
								<div class="wp-tts-toggles-list">
									<label class="wp-tts-toggle-row">
										<span class="wp-tts-toggle-info">
											<span class="wp-tts-toggle-name"><?php esc_html_e( 'Enable REST API', 'wp-text-to-speech' ); ?></span>
											<span class="wp-tts-toggle-desc"><?php esc_html_e( 'Expose public endpoints for fetching article text and TTS settings', 'wp-text-to-speech' ); ?></span>
										</span>
										<span class="wp-tts-toggle-switch-wrap">
											<input type="checkbox" id="wp-tts-rest-api" name="<?php echo esc_attr( $opt_key ); ?>[rest_api_enabled]" value="1" <?php checked( $rest_api ); ?> />
											<span class="wp-tts-toggle-switch"><span class="wp-tts-toggle-knob"></span></span>
										</span>
									</label>
								</div>
							</div>

							<!-- Endpoints (shown always for reference, but note they only work when enabled) -->
							<div class="wp-tts-api-endpoints-wrap" id="wp-tts-api-endpoints">

								<div class="wp-tts-api-endpoint">
									<div class="wp-tts-api-method">GET</div>
									<div class="wp-tts-api-details">
										<h3 class="wp-tts-api-title"><?php esc_html_e( 'Get Speech Data', 'wp-text-to-speech' ); ?></h3>
										<code class="wp-tts-api-url"><?php echo esc_html( $site_url ); ?>/wp-json/wp-tts/v1/speech/{post_id}</code>
										<p class="wp-tts-api-desc"><?php esc_html_e( 'Returns article title, plain text, sentences array, word count, estimated duration, and TTS settings.', 'wp-text-to-speech' ); ?></p>
									</div>
								</div>

								<div class="wp-tts-api-endpoint">
									<div class="wp-tts-api-method">GET</div>
									<div class="wp-tts-api-details">
										<h3 class="wp-tts-api-title"><?php esc_html_e( 'Get TTS Settings', 'wp-text-to-speech' ); ?></h3>
										<code class="wp-tts-api-url"><?php echo esc_html( $site_url ); ?>/wp-json/wp-tts/v1/settings</code>
										<p class="wp-tts-api-desc"><?php esc_html_e( 'Returns speech rate, pitch, volume, voice name, and enabled post types.', 'wp-text-to-speech' ); ?></p>
									</div>
								</div>

								<div class="wp-tts-api-endpoint">
									<div class="wp-tts-api-method">GET</div>
									<div class="wp-tts-api-details">
										<h3 class="wp-tts-api-title"><?php esc_html_e( 'List TTS Posts', 'wp-text-to-speech' ); ?></h3>
										<code class="wp-tts-api-url"><?php echo esc_html( $site_url ); ?>/wp-json/wp-tts/v1/posts?per_page=10&amp;page=1</code>
										<p class="wp-tts-api-desc"><?php esc_html_e( 'Paginated list of TTS-enabled posts with metadata and speech endpoint URLs.', 'wp-text-to-speech' ); ?></p>
									</div>
								</div>

								<div class="wp-tts-api-note">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
									<p><?php esc_html_e( 'All endpoints are public and require no authentication. Use expo-speech or react-native-tts in your app to play the returned sentences.', 'wp-text-to-speech' ); ?></p>
								</div>

							</div>

						</div>
					</div>

					<div class="wp-tts-save-row">
						<?php submit_button( __( 'Save Settings', 'wp-text-to-speech' ), 'primary wp-tts-save-btn', 'submit', false ); ?>
					</div>
				</div>

			</form>

		</div>
		<?php
	}

	/**
	 * Render the Analytics page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_analytics_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options        = get_option( WP_TTS_OPTION_KEY, array() );
		$enabled_types  = isset( $options['enabled_post_types'] ) ? (array) $options['enabled_post_types'] : array( 'post' );
		$speech_rate    = isset( $options['speech_rate'] ) ? (float) $options['speech_rate'] : 1.0;
		$rest_api       = ! empty( $options['rest_api_enabled'] );
		$progress_bar   = ! empty( $options['show_progress_bar'] );
		$speed_control  = ! empty( $options['show_speed_control'] );
		$sticky_player  = ! empty( $options['sticky_player'] );
		$button_color   = isset( $options['button_color'] ) ? $options['button_color'] : '#d60017';
		$position       = isset( $options['button_position'] ) ? $options['button_position'] : 'before';

		// Gather basic stats.
		$total_posts = 0;
		foreach ( $enabled_types as $pt ) {
			$counts = wp_count_posts( $pt );
			if ( $counts ) {
				$total_posts += (int) $counts->publish;
			}
		}
		?>
		<div class="wp-tts-admin-wrap">

			<!-- Header -->
			<div class="wp-tts-header">
				<div class="wp-tts-header-inner">
					<div class="wp-tts-header-left">
						<div class="wp-tts-logo">
							<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/><ellipse cx="12" cy="5" rx="9" ry="3"/></svg>
						</div>
						<div>
							<h1 class="wp-tts-header-title"><?php esc_html_e( 'Analytics', 'wp-text-to-speech' ); ?></h1>
							<p class="wp-tts-header-version"><?php esc_html_e( 'Usage overview for your TTS player', 'wp-text-to-speech' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<!-- Overview Stats -->
			<div class="wp-tts-card" style="border-radius: 0 0 12px 12px;">
				<div class="wp-tts-card-header">
					<h2 class="wp-tts-card-title"><?php esc_html_e( 'Overview', 'wp-text-to-speech' ); ?></h2>
				</div>
				<div class="wp-tts-card-body">
					<div class="wp-tts-stats-grid">
						<div class="wp-tts-stat-card">
							<div class="wp-tts-stat-icon" style="background: #eff6ff; color: #3b82f6;">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
							</div>
							<div class="wp-tts-stat-info">
								<span class="wp-tts-stat-value"><?php echo esc_html( $total_posts ); ?></span>
								<span class="wp-tts-stat-label"><?php esc_html_e( 'TTS-Enabled Posts', 'wp-text-to-speech' ); ?></span>
							</div>
						</div>

						<div class="wp-tts-stat-card">
							<div class="wp-tts-stat-icon" style="background: #f0fdf4; color: #22c55e;">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
							</div>
							<div class="wp-tts-stat-info">
								<span class="wp-tts-stat-value"><?php echo esc_html( count( $enabled_types ) ); ?></span>
								<span class="wp-tts-stat-label"><?php esc_html_e( 'Enabled Post Types', 'wp-text-to-speech' ); ?></span>
							</div>
						</div>

						<div class="wp-tts-stat-card">
							<div class="wp-tts-stat-icon" style="background: #fef3c7; color: #f59e0b;">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
							</div>
							<div class="wp-tts-stat-info">
								<span class="wp-tts-stat-value"><?php echo esc_html( number_format( $speech_rate, 1 ) ); ?>x</span>
								<span class="wp-tts-stat-label"><?php esc_html_e( 'Default Speed', 'wp-text-to-speech' ); ?></span>
							</div>
						</div>

						<div class="wp-tts-stat-card">
							<div class="wp-tts-stat-icon" style="background: <?php echo $rest_api ? '#f0fdf4' : '#fef2f2'; ?>; color: <?php echo $rest_api ? '#22c55e' : '#ef4444'; ?>;">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/></svg>
							</div>
							<div class="wp-tts-stat-info">
								<span class="wp-tts-stat-value"><?php echo $rest_api ? esc_html__( 'Enabled', 'wp-text-to-speech' ) : esc_html__( 'Disabled', 'wp-text-to-speech' ); ?></span>
								<span class="wp-tts-stat-label"><?php esc_html_e( 'REST API', 'wp-text-to-speech' ); ?></span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Feature Status -->
			<div class="wp-tts-card" style="margin-top: 20px;">
				<div class="wp-tts-card-header">
					<h2 class="wp-tts-card-title"><?php esc_html_e( 'Feature Status', 'wp-text-to-speech' ); ?></h2>
					<p class="wp-tts-card-desc"><?php esc_html_e( 'Current configuration of all player features.', 'wp-text-to-speech' ); ?></p>
				</div>
				<div class="wp-tts-card-body">
					<div class="wp-tts-feature-list">
						<?php
						$features = array(
							array(
								'name'    => __( 'Progress Bar', 'wp-text-to-speech' ),
								'enabled' => $progress_bar,
								'icon'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>',
							),
							array(
								'name'    => __( 'Speed Control', 'wp-text-to-speech' ),
								'enabled' => $speed_control,
								'icon'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
							),
							array(
								'name'    => __( 'Sticky Player', 'wp-text-to-speech' ),
								'enabled' => $sticky_player,
								'icon'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
							),
							array(
								'name'    => __( 'REST API', 'wp-text-to-speech' ),
								'enabled' => $rest_api,
								'icon'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/></svg>',
							),
						);
						foreach ( $features as $feature ) :
						?>
						<div class="wp-tts-feature-row">
							<div class="wp-tts-feature-left">
								<span class="wp-tts-feature-icon"><?php echo $feature['icon']; ?></span>
								<span class="wp-tts-feature-name"><?php echo esc_html( $feature['name'] ); ?></span>
							</div>
							<?php if ( $feature['enabled'] ) : ?>
								<span class="wp-tts-feature-badge wp-tts-feature-on"><?php esc_html_e( 'ON', 'wp-text-to-speech' ); ?></span>
							<?php else : ?>
								<span class="wp-tts-feature-badge wp-tts-feature-off"><?php esc_html_e( 'OFF', 'wp-text-to-speech' ); ?></span>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</div>

					<!-- Configuration Summary -->
					<div class="wp-tts-config-summary">
						<div class="wp-tts-config-item">
							<span class="wp-tts-config-label"><?php esc_html_e( 'Player Position', 'wp-text-to-speech' ); ?></span>
							<span class="wp-tts-config-value"><?php echo 'before' === $position ? esc_html__( 'Before Content', 'wp-text-to-speech' ) : esc_html__( 'After Content', 'wp-text-to-speech' ); ?></span>
						</div>
						<div class="wp-tts-config-item">
							<span class="wp-tts-config-label"><?php esc_html_e( 'Button Color', 'wp-text-to-speech' ); ?></span>
							<span class="wp-tts-config-value">
								<span class="wp-tts-config-color" style="background: <?php echo esc_attr( $button_color ); ?>;"></span>
								<?php echo esc_html( $button_color ); ?>
							</span>
						</div>
						<div class="wp-tts-config-item">
							<span class="wp-tts-config-label"><?php esc_html_e( 'Post Types', 'wp-text-to-speech' ); ?></span>
							<span class="wp-tts-config-value"><?php echo esc_html( implode( ', ', array_map( 'ucfirst', $enabled_types ) ) ); ?></span>
						</div>
					</div>
				</div>
			</div>

			<!-- Coming Soon -->
			<div class="wp-tts-card" style="margin-top: 20px;">
				<div class="wp-tts-card-body">
					<div class="wp-tts-api-note">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
						<p><?php esc_html_e( 'Detailed playback analytics (total plays, average listen duration, most-listened posts) are coming in a future update. Stay tuned!', 'wp-text-to-speech' ); ?></p>
					</div>
				</div>
			</div>

		</div>
		<?php
	}

	/**
	 * Render the Help page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_help_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wp-tts-admin-wrap">

			<!-- Header -->
			<div class="wp-tts-header">
				<div class="wp-tts-header-inner">
					<div class="wp-tts-header-left">
						<div class="wp-tts-logo">
							<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
						</div>
						<div>
							<h1 class="wp-tts-header-title"><?php esc_html_e( 'Help & Support', 'wp-text-to-speech' ); ?></h1>
							<p class="wp-tts-header-version"><?php esc_html_e( 'Everything you need to get started', 'wp-text-to-speech' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<!-- FAQ -->
			<div class="wp-tts-card" style="border-radius: 0 0 12px 12px;">
				<div class="wp-tts-card-header">
					<h2 class="wp-tts-card-title"><?php esc_html_e( 'Frequently Asked Questions', 'wp-text-to-speech' ); ?></h2>
				</div>
				<div class="wp-tts-card-body">
					<div class="wp-tts-help-list">

						<div class="wp-tts-help-item">
							<h3 class="wp-tts-help-q"><?php esc_html_e( 'How does this plugin work?', 'wp-text-to-speech' ); ?></h3>
							<p class="wp-tts-help-a"><?php esc_html_e( 'WP Text to Speech uses the Web Speech API built into modern browsers. It reads the article text aloud using system voices on the visitor\'s device. No external API or server required - it\'s completely free.', 'wp-text-to-speech' ); ?></p>
						</div>

						<div class="wp-tts-help-item">
							<h3 class="wp-tts-help-q"><?php esc_html_e( 'Which browsers are supported?', 'wp-text-to-speech' ); ?></h3>
							<p class="wp-tts-help-a"><?php esc_html_e( 'Chrome, Edge, Safari, Firefox, and Opera all support the Web Speech API. If a visitor\'s browser doesn\'t support it, a friendly fallback message is shown instead of the player.', 'wp-text-to-speech' ); ?></p>
						</div>

						<div class="wp-tts-help-item">
							<h3 class="wp-tts-help-q"><?php esc_html_e( 'Why do voices sound different on different devices?', 'wp-text-to-speech' ); ?></h3>
							<p class="wp-tts-help-a"><?php esc_html_e( 'The Web Speech API uses voices installed on the visitor\'s operating system. Windows, macOS, Android, and iOS each have different built-in voices. The voice you select in settings is a preference - if it\'s not available on a visitor\'s device, their browser default is used.', 'wp-text-to-speech' ); ?></p>
						</div>

						<div class="wp-tts-help-item">
							<h3 class="wp-tts-help-q"><?php esc_html_e( 'Can I use this with custom post types?', 'wp-text-to-speech' ); ?></h3>
							<p class="wp-tts-help-a"><?php esc_html_e( 'Yes! Go to Text to Speech > Settings > Display tab and enable any public post type. The player will appear on singular views for all enabled types.', 'wp-text-to-speech' ); ?></p>
						</div>

						<div class="wp-tts-help-item">
							<h3 class="wp-tts-help-q"><?php esc_html_e( 'Does this plugin slow down my site?', 'wp-text-to-speech' ); ?></h3>
							<p class="wp-tts-help-a"><?php esc_html_e( 'No. The plugin loads a single lightweight CSS and JS file only on pages where the player is displayed. There are no external API calls, no server-side processing, and no impact on page load speed.', 'wp-text-to-speech' ); ?></p>
						</div>

						<div class="wp-tts-help-item">
							<h3 class="wp-tts-help-q"><?php esc_html_e( 'How do I use the REST API?', 'wp-text-to-speech' ); ?></h3>
							<p class="wp-tts-help-a"><?php esc_html_e( 'The plugin provides public REST API endpoints for React Native and mobile apps. Enable it from Text to Speech > Settings > API tab, then check the endpoint URLs and documentation. No authentication required.', 'wp-text-to-speech' ); ?></p>
						</div>

					</div>
				</div>
			</div>

			<!-- Quick Links -->
			<div class="wp-tts-card" style="margin-top: 20px;">
				<div class="wp-tts-card-header">
					<h2 class="wp-tts-card-title"><?php esc_html_e( 'Quick Links', 'wp-text-to-speech' ); ?></h2>
				</div>
				<div class="wp-tts-card-body">
					<div class="wp-tts-help-links">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-text-to-speech' ) ); ?>" class="wp-tts-help-link">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
							<span><?php esc_html_e( 'Plugin Settings', 'wp-text-to-speech' ); ?></span>
						</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-tts-analytics' ) ); ?>" class="wp-tts-help-link">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><ellipse cx="12" cy="5" rx="9" ry="3"/></svg>
							<span><?php esc_html_e( 'View Analytics', 'wp-text-to-speech' ); ?></span>
						</a>
						<a href="https://webappick.com/" target="_blank" rel="noopener noreferrer" class="wp-tts-help-link">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
							<span><?php esc_html_e( 'WebAppick Website', 'wp-text-to-speech' ); ?></span>
						</a>
					</div>
				</div>
			</div>

		</div>
		<?php
	}
}
