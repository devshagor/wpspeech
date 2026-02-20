<?php
/**
 * Admin settings page for Wpspeech.
 *
 * @package Wpspeech
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPSPEECH_Admin
 *
 * Handles the plugin settings page under Settings > WP Speech.
 *
 * @since 1.0.0
 */
class WPSPEECH_Admin {

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
			__( 'WP Speech', 'wpspeech' ),
			__( 'WP Speech', 'wpspeech' ),
			'manage_options',
			'wpspeech',
			array( $this, 'render_settings_page' ),
			'dashicons-controls-volumeon',
			80
		);

		add_submenu_page(
			'wpspeech',
			__( 'Settings', 'wpspeech' ),
			__( 'Settings', 'wpspeech' ),
			'manage_options',
			'wpspeech',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'wpspeech',
			__( 'Analytics', 'wpspeech' ),
			__( 'Analytics', 'wpspeech' ),
			'manage_options',
			'wpspeech-analytics',
			array( $this, 'render_analytics_page' )
		);

		add_submenu_page(
			'wpspeech',
			__( 'Help', 'wpspeech' ),
			__( 'Help', 'wpspeech' ),
			'manage_options',
			'wpspeech-help',
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
			'wpspeech_settings_group',
			WPSPEECH_OPTION_KEY,
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
			'toplevel_page_wpspeech',
			'text-to-speech_page_wpspeech-analytics',
			'text-to-speech_page_wpspeech-help',
		);

		if ( ! in_array( $hook, $plugin_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'wpspeech-admin',
			WPSPEECH_PLUGIN_URL . 'assets/css/wpspeech-admin.css',
			array(),
			WPSPEECH_VERSION
		);

		// Settings page needs color picker and admin JS.
		if ( 'toplevel_page_wpspeech' === $hook ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script(
				'wpspeech-admin',
				WPSPEECH_PLUGIN_URL . 'assets/js/wpspeech-admin.js',
				array( 'jquery', 'wp-color-picker' ),
				WPSPEECH_VERSION,
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

		$options       = get_option( WPSPEECH_OPTION_KEY, array() );
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
		$opt_key       = WPSPEECH_OPTION_KEY;
		$site_url      = home_url();
		?>
		<div class="wpspeech-admin-wrap">

			<!-- Header -->
			<div class="wpspeech-header">
				<div class="wpspeech-header-inner">
					<div class="wpspeech-header-left">
						<div class="wpspeech-logo">
							<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
						</div>
						<div>
							<h1 class="wpspeech-header-title"><?php esc_html_e( 'WP Speech', 'wpspeech' ); ?></h1>
							<p class="wpspeech-header-version"><?php echo esc_html( 'v' . WPSPEECH_VERSION ); ?></p>
						</div>
					</div>
					<div class="wpspeech-header-right">
						<span class="wpspeech-status-badge wpspeech-status-active">
							<span class="wpspeech-status-dot"></span>
							<?php esc_html_e( 'Active', 'wpspeech' ); ?>
						</span>
					</div>
				</div>
			</div>

			<!-- Tabs -->
			<div class="wpspeech-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'wpspeech' ); ?>">
				<button type="button" class="wpspeech-tab wpspeech-tab-active" data-tab="voice" role="tab" aria-selected="true" aria-controls="wpspeech-panel-voice" id="wpspeech-tab-voice">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
					<?php esc_html_e( 'Voice', 'wpspeech' ); ?>
				</button>
				<button type="button" class="wpspeech-tab" data-tab="display" role="tab" aria-selected="false" aria-controls="wpspeech-panel-display" id="wpspeech-tab-display" tabindex="-1">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
					<?php esc_html_e( 'Display', 'wpspeech' ); ?>
				</button>
				<button type="button" class="wpspeech-tab" data-tab="preview" role="tab" aria-selected="false" aria-controls="wpspeech-panel-preview" id="wpspeech-tab-preview" tabindex="-1">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><polygon points="5 3 19 12 5 21 5 3"/></svg>
					<?php esc_html_e( 'Preview', 'wpspeech' ); ?>
				</button>
				<button type="button" class="wpspeech-tab" data-tab="api" role="tab" aria-selected="false" aria-controls="wpspeech-panel-api" id="wpspeech-tab-api" tabindex="-1">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/></svg>
					<?php esc_html_e( 'API', 'wpspeech' ); ?>
				</button>
			</div>

			<!-- Form wraps all tab panels -->
			<form action="options.php" method="post" class="wpspeech-form">
				<?php settings_fields( 'wpspeech_settings_group' ); ?>

				<!-- Voice Tab -->
				<div class="wpspeech-tab-panel wpspeech-tab-panel-active" data-panel="voice" role="tabpanel" id="wpspeech-panel-voice" aria-labelledby="wpspeech-tab-voice">
					<div class="wpspeech-card">
						<div class="wpspeech-card-header">
							<h2 class="wpspeech-card-title"><?php esc_html_e( 'Voice Configuration', 'wpspeech' ); ?></h2>
							<p class="wpspeech-card-desc"><?php esc_html_e( 'Select a voice and adjust playback parameters. Voices come from your browser/OS.', 'wpspeech' ); ?></p>
						</div>
						<div class="wpspeech-card-body">

							<!-- Voice Select -->
							<div class="wpspeech-field">
								<label class="wpspeech-field-label" for="wpspeech-voice-name"><?php esc_html_e( 'Voice', 'wpspeech' ); ?></label>
								<div class="wpspeech-field-control">
									<select id="wpspeech-voice-name" name="<?php echo esc_attr( $opt_key ); ?>[voice_name]" class="wpspeech-select">
										<option value=""><?php esc_html_e( 'Browser Default', 'wpspeech' ); ?></option>
									</select>
									<input type="hidden" id="wpspeech-voice-saved" value="<?php echo esc_attr( $voice ); ?>" />
								</div>
								<p class="wpspeech-field-hint"><?php esc_html_e( 'Available voices vary by browser and OS. Visitors hear their own device voices.', 'wpspeech' ); ?></p>
							</div>

							<!-- Speed Slider -->
							<div class="wpspeech-field">
								<label class="wpspeech-field-label" for="wpspeech-speech-rate"><?php esc_html_e( 'Speed', 'wpspeech' ); ?></label>
								<div class="wpspeech-slider-row">
									<span class="wpspeech-slider-min">0.5x</span>
									<input type="range" id="wpspeech-speech-rate" name="<?php echo esc_attr( $opt_key ); ?>[speech_rate]"
										class="wpspeech-range" min="0.5" max="2" step="0.1" value="<?php echo esc_attr( $rate ); ?>" />
									<span class="wpspeech-slider-max">2x</span>
									<span class="wpspeech-slider-value" id="wpspeech-rate-val"><?php echo esc_html( $rate ); ?>x</span>
								</div>
							</div>

							<!-- Pitch Slider -->
							<div class="wpspeech-field">
								<label class="wpspeech-field-label" for="wpspeech-pitch"><?php esc_html_e( 'Pitch', 'wpspeech' ); ?></label>
								<div class="wpspeech-slider-row">
									<span class="wpspeech-slider-min">0</span>
									<input type="range" id="wpspeech-pitch" name="<?php echo esc_attr( $opt_key ); ?>[pitch]"
										class="wpspeech-range" min="0" max="2" step="0.1" value="<?php echo esc_attr( $pitch ); ?>" />
									<span class="wpspeech-slider-max">2</span>
									<span class="wpspeech-slider-value" id="wpspeech-pitch-val"><?php echo esc_html( $pitch ); ?></span>
								</div>
							</div>

							<!-- Volume Slider -->
							<div class="wpspeech-field">
								<label class="wpspeech-field-label" for="wpspeech-volume"><?php esc_html_e( 'Volume', 'wpspeech' ); ?></label>
								<div class="wpspeech-slider-row">
									<span class="wpspeech-slider-min">0</span>
									<input type="range" id="wpspeech-volume" name="<?php echo esc_attr( $opt_key ); ?>[volume]"
										class="wpspeech-range" min="0" max="1" step="0.1" value="<?php echo esc_attr( $volume ); ?>" />
									<span class="wpspeech-slider-max">1</span>
									<span class="wpspeech-slider-value" id="wpspeech-volume-val"><?php echo esc_html( $volume ); ?></span>
								</div>
							</div>

						</div>
					</div>

					<div class="wpspeech-save-row">
						<?php submit_button( __( 'Save Settings', 'wpspeech' ), 'primary wpspeech-save-btn', 'submit', false ); ?>
					</div>
				</div>

				<!-- Display Tab -->
				<div class="wpspeech-tab-panel" data-panel="display" role="tabpanel" id="wpspeech-panel-display" aria-labelledby="wpspeech-tab-display">
					<div class="wpspeech-card">
						<div class="wpspeech-card-header">
							<h2 class="wpspeech-card-title"><?php esc_html_e( 'Player Appearance', 'wpspeech' ); ?></h2>
							<p class="wpspeech-card-desc"><?php esc_html_e( 'Control where and how the player appears on your site.', 'wpspeech' ); ?></p>
						</div>
						<div class="wpspeech-card-body">

							<!-- Post Types -->
							<div class="wpspeech-field">
								<label class="wpspeech-field-label"><?php esc_html_e( 'Enable on Post Types', 'wpspeech' ); ?></label>
								<div class="wpspeech-post-types-grid">
									<?php foreach ( $post_types as $pt ) : ?>
										<?php if ( 'attachment' === $pt->name ) continue; ?>
										<label class="wpspeech-chip">
											<input type="checkbox" name="<?php echo esc_attr( $opt_key ); ?>[enabled_post_types][]"
												value="<?php echo esc_attr( $pt->name ); ?>"
												<?php checked( in_array( $pt->name, $enabled_types, true ) ); ?> />
											<span class="wpspeech-chip-inner">
												<svg class="wpspeech-chip-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
												<span class="wpspeech-chip-label"><?php echo esc_html( $pt->labels->singular_name ); ?></span>
											</span>
										</label>
									<?php endforeach; ?>
								</div>
							</div>

							<!-- Button Color -->
							<div class="wpspeech-field">
								<label class="wpspeech-field-label" for="wpspeech-button-color"><?php esc_html_e( 'Button Color', 'wpspeech' ); ?></label>
								<div class="wpspeech-field-control">
									<input type="text" id="wpspeech-button-color" name="<?php echo esc_attr( $opt_key ); ?>[button_color]"
										value="<?php echo esc_attr( $color ); ?>" class="wpspeech-color-field" data-default-color="#d60017" />
								</div>
							</div>

							<!-- Position -->
							<div class="wpspeech-field">
								<label class="wpspeech-field-label"><?php esc_html_e( 'Button Position', 'wpspeech' ); ?></label>
								<div class="wpspeech-position-cards">
									<label class="wpspeech-position-card">
										<input type="radio" name="<?php echo esc_attr( $opt_key ); ?>[button_position]" value="before" <?php checked( $position, 'before' ); ?> />
										<span class="wpspeech-position-card-inner">
											<span class="wpspeech-position-preview">
												<span class="wpspeech-position-player-bar"></span>
												<span class="wpspeech-position-line wpspeech-position-line-full"></span>
												<span class="wpspeech-position-line wpspeech-position-line-full"></span>
												<span class="wpspeech-position-line wpspeech-position-line-short"></span>
											</span>
											<span class="wpspeech-position-label"><?php esc_html_e( 'Before Content', 'wpspeech' ); ?></span>
										</span>
									</label>
									<label class="wpspeech-position-card">
										<input type="radio" name="<?php echo esc_attr( $opt_key ); ?>[button_position]" value="after" <?php checked( $position, 'after' ); ?> />
										<span class="wpspeech-position-card-inner">
											<span class="wpspeech-position-preview">
												<span class="wpspeech-position-line wpspeech-position-line-full"></span>
												<span class="wpspeech-position-line wpspeech-position-line-full"></span>
												<span class="wpspeech-position-line wpspeech-position-line-short"></span>
												<span class="wpspeech-position-player-bar"></span>
											</span>
											<span class="wpspeech-position-label"><?php esc_html_e( 'After Content', 'wpspeech' ); ?></span>
										</span>
									</label>
								</div>
							</div>

							<!-- Toggles -->
							<div class="wpspeech-field">
								<h2><?php esc_html_e( 'Player Features', 'wpspeech' ); ?></h2>
								<div class="wpspeech-toggles-list">
									<label class="wpspeech-toggle-row">
										<span class="wpspeech-toggle-info">
											<span class="wpspeech-toggle-name"><?php esc_html_e( 'Progress Bar', 'wpspeech' ); ?></span>
											<span class="wpspeech-toggle-desc"><?php esc_html_e( 'Visual indicator showing reading progress below the controls', 'wpspeech' ); ?></span>
										</span>
										<span class="wpspeech-toggle-switch-wrap">
											<input type="checkbox" id="wpspeech-progress" name="<?php echo esc_attr( $opt_key ); ?>[show_progress_bar]" value="1" <?php checked( $progress_bar ); ?> />
											<span class="wpspeech-toggle-switch"><span class="wpspeech-toggle-knob"></span></span>
										</span>
									</label>
									<label class="wpspeech-toggle-row">
										<span class="wpspeech-toggle-info">
											<span class="wpspeech-toggle-name"><?php esc_html_e( 'Speed Control', 'wpspeech' ); ?></span>
											<span class="wpspeech-toggle-desc"><?php esc_html_e( 'Dropdown for visitors to change playback speed (0.75x - 2x)', 'wpspeech' ); ?></span>
										</span>
										<span class="wpspeech-toggle-switch-wrap">
											<input type="checkbox" id="wpspeech-speed" name="<?php echo esc_attr( $opt_key ); ?>[show_speed_control]" value="1" <?php checked( $speed_control ); ?> />
											<span class="wpspeech-toggle-switch"><span class="wpspeech-toggle-knob"></span></span>
										</span>
									</label>
									<label class="wpspeech-toggle-row">
										<span class="wpspeech-toggle-info">
											<span class="wpspeech-toggle-name"><?php esc_html_e( 'Sticky Player', 'wpspeech' ); ?></span>
											<span class="wpspeech-toggle-desc"><?php esc_html_e( 'Show a mini player at the bottom of the screen while scrolling during playback', 'wpspeech' ); ?></span>
										</span>
										<span class="wpspeech-toggle-switch-wrap">
											<input type="checkbox" id="wpspeech-sticky" name="<?php echo esc_attr( $opt_key ); ?>[sticky_player]" value="1" <?php checked( $sticky_player ); ?> />
											<span class="wpspeech-toggle-switch"><span class="wpspeech-toggle-knob"></span></span>
										</span>
									</label>
								</div>
							</div>

						</div>
					</div>

					<div class="wpspeech-save-row">
						<?php submit_button( __( 'Save Settings', 'wpspeech' ), 'primary wpspeech-save-btn', 'submit', false ); ?>
					</div>
				</div>

				<!-- Preview Tab -->
				<div class="wpspeech-tab-panel" data-panel="preview" role="tabpanel" id="wpspeech-panel-preview" aria-labelledby="wpspeech-tab-preview">
					<div class="wpspeech-card">
						<div class="wpspeech-card-header">
							<h2 class="wpspeech-card-title"><?php esc_html_e( 'Live Preview', 'wpspeech' ); ?></h2>
							<p class="wpspeech-card-desc"><?php esc_html_e( 'Test the current voice settings before saving. Type or paste any text below.', 'wpspeech' ); ?></p>
						</div>
						<div class="wpspeech-card-body">
							<div class="wpspeech-preview-area">
								<textarea id="wpspeech-preview-text" rows="4" class="wpspeech-textarea"
									placeholder="<?php esc_attr_e( 'Type something to hear it spoken aloud...', 'wpspeech' ); ?>"
								><?php esc_html_e( 'Welcome to Wpspeech. This plugin lets your visitors listen to articles with a single click. Try adjusting the speed, pitch, and volume in the Voice tab to find the perfect settings for your audience.', 'wpspeech' ); ?></textarea>

								<div class="wpspeech-preview-controls">
									<button type="button" id="wpspeech-preview-btn" class="wpspeech-btn-preview">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>
										<span id="wpspeech-preview-label"><?php esc_html_e( 'Play Preview', 'wpspeech' ); ?></span>
									</button>
									<button type="button" id="wpspeech-stop-preview-btn" class="wpspeech-btn-stop" style="display:none;">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><rect x="5" y="5" width="14" height="14" rx="2"/></svg>
										<?php esc_html_e( 'Stop', 'wpspeech' ); ?>
									</button>
									<div class="wpspeech-preview-wave" id="wpspeech-wave" style="display:none;">
										<span></span><span></span><span></span><span></span><span></span>
									</div>
								</div>
							</div>

							<!-- Player Mockup -->
							<div class="wpspeech-mockup">
								<p class="wpspeech-mockup-label"><?php esc_html_e( 'This is how the player looks on your site:', 'wpspeech' ); ?></p>
								<div class="wpspeech-player-mockup" style="--wpspeech-color: <?php echo esc_attr( $color ); ?>;">
									<div class="wpspeech-mockup-controls">
										<span class="wpspeech-mockup-play">
											<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>
											<?php esc_html_e( 'Listen to this article', 'wpspeech' ); ?>
										</span>
										<span class="wpspeech-mockup-stop">
											<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><rect x="5" y="5" width="14" height="14" rx="2"/></svg>
										</span>
										<?php if ( $speed_control ) : ?>
											<span class="wpspeech-mockup-speed">1x</span>
										<?php endif; ?>
										<span class="wpspeech-mockup-counter">0 / 12</span>
									</div>
									<?php if ( $progress_bar ) : ?>
										<div class="wpspeech-mockup-progress">
											<div class="wpspeech-mockup-progress-fill"></div>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- API Tab -->
				<div class="wpspeech-tab-panel" data-panel="api" role="tabpanel" id="wpspeech-panel-api" aria-labelledby="wpspeech-tab-api">
					<div class="wpspeech-card">
						<div class="wpspeech-card-header">
							<h2 class="wpspeech-card-title"><?php esc_html_e( 'REST API', 'wpspeech' ); ?></h2>
							<p class="wpspeech-card-desc"><?php esc_html_e( 'Enable REST API endpoints for React Native and mobile apps.', 'wpspeech' ); ?></p>
						</div>
						<div class="wpspeech-card-body">

							<!-- Enable Toggle -->
							<div class="wpspeech-field">
								<div class="wpspeech-toggles-list">
									<label class="wpspeech-toggle-row">
										<span class="wpspeech-toggle-info">
											<span class="wpspeech-toggle-name"><?php esc_html_e( 'Enable REST API', 'wpspeech' ); ?></span>
											<span class="wpspeech-toggle-desc"><?php esc_html_e( 'Expose public endpoints for fetching article text and TTS settings', 'wpspeech' ); ?></span>
										</span>
										<span class="wpspeech-toggle-switch-wrap">
											<input type="checkbox" id="wpspeech-rest-api" name="<?php echo esc_attr( $opt_key ); ?>[rest_api_enabled]" value="1" <?php checked( $rest_api ); ?> />
											<span class="wpspeech-toggle-switch"><span class="wpspeech-toggle-knob"></span></span>
										</span>
									</label>
								</div>
							</div>

							<!-- Endpoints (shown always for reference, but note they only work when enabled) -->
							<div class="wpspeech-api-endpoints-wrap" id="wpspeech-api-endpoints">

								<div class="wpspeech-api-endpoint">
									<div class="wpspeech-api-method">GET</div>
									<div class="wpspeech-api-details">
										<h3 class="wpspeech-api-title"><?php esc_html_e( 'Get Speech Data', 'wpspeech' ); ?></h3>
										<code class="wpspeech-api-url"><?php echo esc_html( $site_url ); ?>/wp-json/wpspeech/v1/speech/{post_id}</code>
										<p class="wpspeech-api-desc"><?php esc_html_e( 'Returns article title, plain text, sentences array, word count, estimated duration, and TTS settings.', 'wpspeech' ); ?></p>
									</div>
								</div>

								<div class="wpspeech-api-endpoint">
									<div class="wpspeech-api-method">GET</div>
									<div class="wpspeech-api-details">
										<h3 class="wpspeech-api-title"><?php esc_html_e( 'Get TTS Settings', 'wpspeech' ); ?></h3>
										<code class="wpspeech-api-url"><?php echo esc_html( $site_url ); ?>/wp-json/wpspeech/v1/settings</code>
										<p class="wpspeech-api-desc"><?php esc_html_e( 'Returns speech rate, pitch, volume, voice name, and enabled post types.', 'wpspeech' ); ?></p>
									</div>
								</div>

								<div class="wpspeech-api-endpoint">
									<div class="wpspeech-api-method">GET</div>
									<div class="wpspeech-api-details">
										<h3 class="wpspeech-api-title"><?php esc_html_e( 'List TTS Posts', 'wpspeech' ); ?></h3>
										<code class="wpspeech-api-url"><?php echo esc_html( $site_url ); ?>/wp-json/wpspeech/v1/posts?per_page=10&amp;page=1</code>
										<p class="wpspeech-api-desc"><?php esc_html_e( 'Paginated list of TTS-enabled posts with metadata and speech endpoint URLs.', 'wpspeech' ); ?></p>
									</div>
								</div>

								<div class="wpspeech-api-note">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
									<p><?php esc_html_e( 'All endpoints are public and require no authentication. Use expo-speech or react-native-tts in your app to play the returned sentences.', 'wpspeech' ); ?></p>
								</div>

							</div>

						</div>
					</div>

					<div class="wpspeech-save-row">
						<?php submit_button( __( 'Save Settings', 'wpspeech' ), 'primary wpspeech-save-btn', 'submit', false ); ?>
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

		$options        = get_option( WPSPEECH_OPTION_KEY, array() );
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
		<div class="wpspeech-admin-wrap">

			<!-- Header -->
			<div class="wpspeech-header">
				<div class="wpspeech-header-inner">
					<div class="wpspeech-header-left">
						<div class="wpspeech-logo">
							<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/><ellipse cx="12" cy="5" rx="9" ry="3"/></svg>
						</div>
						<div>
							<h1 class="wpspeech-header-title"><?php esc_html_e( 'Analytics', 'wpspeech' ); ?></h1>
							<p class="wpspeech-header-version"><?php esc_html_e( 'Usage overview for your TTS player', 'wpspeech' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<!-- Overview Stats -->
			<div class="wpspeech-card" style="border-radius: 0 0 12px 12px;">
				<div class="wpspeech-card-header">
					<h2 class="wpspeech-card-title"><?php esc_html_e( 'Overview', 'wpspeech' ); ?></h2>
				</div>
				<div class="wpspeech-card-body">
					<div class="wpspeech-stats-grid">
						<div class="wpspeech-stat-card">
							<div class="wpspeech-stat-icon" style="background: #eff6ff; color: #3b82f6;">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
							</div>
							<div class="wpspeech-stat-info">
								<span class="wpspeech-stat-value"><?php echo esc_html( $total_posts ); ?></span>
								<span class="wpspeech-stat-label"><?php esc_html_e( 'TTS-Enabled Posts', 'wpspeech' ); ?></span>
							</div>
						</div>

						<div class="wpspeech-stat-card">
							<div class="wpspeech-stat-icon" style="background: #f0fdf4; color: #22c55e;">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
							</div>
							<div class="wpspeech-stat-info">
								<span class="wpspeech-stat-value"><?php echo esc_html( count( $enabled_types ) ); ?></span>
								<span class="wpspeech-stat-label"><?php esc_html_e( 'Enabled Post Types', 'wpspeech' ); ?></span>
							</div>
						</div>

						<div class="wpspeech-stat-card">
							<div class="wpspeech-stat-icon" style="background: #fef3c7; color: #f59e0b;">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
							</div>
							<div class="wpspeech-stat-info">
								<span class="wpspeech-stat-value"><?php echo esc_html( number_format( $speech_rate, 1 ) ); ?>x</span>
								<span class="wpspeech-stat-label"><?php esc_html_e( 'Default Speed', 'wpspeech' ); ?></span>
							</div>
						</div>

						<div class="wpspeech-stat-card">
							<div class="wpspeech-stat-icon" style="background: <?php echo $rest_api ? '#f0fdf4' : '#fef2f2'; ?>; color: <?php echo $rest_api ? '#22c55e' : '#ef4444'; ?>;">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/></svg>
							</div>
							<div class="wpspeech-stat-info">
								<span class="wpspeech-stat-value"><?php echo $rest_api ? esc_html__( 'Enabled', 'wpspeech' ) : esc_html__( 'Disabled', 'wpspeech' ); ?></span>
								<span class="wpspeech-stat-label"><?php esc_html_e( 'REST API', 'wpspeech' ); ?></span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Feature Status -->
			<div class="wpspeech-card" style="margin-top: 20px;">
				<div class="wpspeech-card-header">
					<h2 class="wpspeech-card-title"><?php esc_html_e( 'Feature Status', 'wpspeech' ); ?></h2>
					<p class="wpspeech-card-desc"><?php esc_html_e( 'Current configuration of all player features.', 'wpspeech' ); ?></p>
				</div>
				<div class="wpspeech-card-body">
					<div class="wpspeech-feature-list">
						<?php
						$features = array(
							array(
								'name'    => __( 'Progress Bar', 'wpspeech' ),
								'enabled' => $progress_bar,
								'icon'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>',
							),
							array(
								'name'    => __( 'Speed Control', 'wpspeech' ),
								'enabled' => $speed_control,
								'icon'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
							),
							array(
								'name'    => __( 'Sticky Player', 'wpspeech' ),
								'enabled' => $sticky_player,
								'icon'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
							),
							array(
								'name'    => __( 'REST API', 'wpspeech' ),
								'enabled' => $rest_api,
								'icon'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/></svg>',
							),
						);
						foreach ( $features as $feature ) :
						?>
						<div class="wpspeech-feature-row">
							<div class="wpspeech-feature-left">
								<span class="wpspeech-feature-icon"><?php echo $feature['icon']; ?></span>
								<span class="wpspeech-feature-name"><?php echo esc_html( $feature['name'] ); ?></span>
							</div>
							<?php if ( $feature['enabled'] ) : ?>
								<span class="wpspeech-feature-badge wpspeech-feature-on"><?php esc_html_e( 'ON', 'wpspeech' ); ?></span>
							<?php else : ?>
								<span class="wpspeech-feature-badge wpspeech-feature-off"><?php esc_html_e( 'OFF', 'wpspeech' ); ?></span>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</div>

					<!-- Configuration Summary -->
					<div class="wpspeech-config-summary">
						<div class="wpspeech-config-item">
							<span class="wpspeech-config-label"><?php esc_html_e( 'Player Position', 'wpspeech' ); ?></span>
							<span class="wpspeech-config-value"><?php echo 'before' === $position ? esc_html__( 'Before Content', 'wpspeech' ) : esc_html__( 'After Content', 'wpspeech' ); ?></span>
						</div>
						<div class="wpspeech-config-item">
							<span class="wpspeech-config-label"><?php esc_html_e( 'Button Color', 'wpspeech' ); ?></span>
							<span class="wpspeech-config-value">
								<span class="wpspeech-config-color" style="background: <?php echo esc_attr( $button_color ); ?>;"></span>
								<?php echo esc_html( $button_color ); ?>
							</span>
						</div>
						<div class="wpspeech-config-item">
							<span class="wpspeech-config-label"><?php esc_html_e( 'Post Types', 'wpspeech' ); ?></span>
							<span class="wpspeech-config-value"><?php echo esc_html( implode( ', ', array_map( 'ucfirst', $enabled_types ) ) ); ?></span>
						</div>
					</div>
				</div>
			</div>

			<!-- Coming Soon -->
			<div class="wpspeech-card" style="margin-top: 20px;">
				<div class="wpspeech-card-body">
					<div class="wpspeech-api-note">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
						<p><?php esc_html_e( 'Detailed playback analytics (total plays, average listen duration, most-listened posts) are coming in a future update. Stay tuned!', 'wpspeech' ); ?></p>
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
		<div class="wpspeech-admin-wrap">

			<!-- Header -->
			<div class="wpspeech-header">
				<div class="wpspeech-header-inner">
					<div class="wpspeech-header-left">
						<div class="wpspeech-logo">
							<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
						</div>
						<div>
							<h1 class="wpspeech-header-title"><?php esc_html_e( 'Help & Support', 'wpspeech' ); ?></h1>
							<p class="wpspeech-header-version"><?php esc_html_e( 'Everything you need to get started', 'wpspeech' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<!-- FAQ -->
			<div class="wpspeech-card" style="border-radius: 0 0 12px 12px;">
				<div class="wpspeech-card-header">
					<h2 class="wpspeech-card-title"><?php esc_html_e( 'Frequently Asked Questions', 'wpspeech' ); ?></h2>
				</div>
				<div class="wpspeech-card-body">
					<div class="wpspeech-help-list">

						<div class="wpspeech-help-item">
							<h3 class="wpspeech-help-q"><?php esc_html_e( 'How does this plugin work?', 'wpspeech' ); ?></h3>
							<p class="wpspeech-help-a"><?php esc_html_e( 'Wpspeech uses the Web Speech API built into modern browsers. It reads the article text aloud using system voices on the visitor\'s device. No external API or server required - it\'s completely free.', 'wpspeech' ); ?></p>
						</div>

						<div class="wpspeech-help-item">
							<h3 class="wpspeech-help-q"><?php esc_html_e( 'Which browsers are supported?', 'wpspeech' ); ?></h3>
							<p class="wpspeech-help-a"><?php esc_html_e( 'Chrome, Edge, Safari, Firefox, and Opera all support the Web Speech API. If a visitor\'s browser doesn\'t support it, a friendly fallback message is shown instead of the player.', 'wpspeech' ); ?></p>
						</div>

						<div class="wpspeech-help-item">
							<h3 class="wpspeech-help-q"><?php esc_html_e( 'Why do voices sound different on different devices?', 'wpspeech' ); ?></h3>
							<p class="wpspeech-help-a"><?php esc_html_e( 'The Web Speech API uses voices installed on the visitor\'s operating system. Windows, macOS, Android, and iOS each have different built-in voices. The voice you select in settings is a preference - if it\'s not available on a visitor\'s device, their browser default is used.', 'wpspeech' ); ?></p>
						</div>

						<div class="wpspeech-help-item">
							<h3 class="wpspeech-help-q"><?php esc_html_e( 'Can I use this with custom post types?', 'wpspeech' ); ?></h3>
							<p class="wpspeech-help-a"><?php esc_html_e( 'Yes! Go to WP Speech > Settings > Display tab and enable any public post type. The player will appear on singular views for all enabled types.', 'wpspeech' ); ?></p>
						</div>

						<div class="wpspeech-help-item">
							<h3 class="wpspeech-help-q"><?php esc_html_e( 'Does this plugin slow down my site?', 'wpspeech' ); ?></h3>
							<p class="wpspeech-help-a"><?php esc_html_e( 'No. The plugin loads a single lightweight CSS and JS file only on pages where the player is displayed. There are no external API calls, no server-side processing, and no impact on page load speed.', 'wpspeech' ); ?></p>
						</div>

						<div class="wpspeech-help-item">
							<h3 class="wpspeech-help-q"><?php esc_html_e( 'How do I use the REST API?', 'wpspeech' ); ?></h3>
							<p class="wpspeech-help-a"><?php esc_html_e( 'The plugin provides public REST API endpoints for React Native and mobile apps. Enable it from WP Speech > Settings > API tab, then check the endpoint URLs and documentation. No authentication required.', 'wpspeech' ); ?></p>
						</div>

					</div>
				</div>
			</div>

			<!-- Quick Links -->
			<div class="wpspeech-card" style="margin-top: 20px;">
				<div class="wpspeech-card-header">
					<h2 class="wpspeech-card-title"><?php esc_html_e( 'Quick Links', 'wpspeech' ); ?></h2>
				</div>
				<div class="wpspeech-card-body">
					<div class="wpspeech-help-links">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpspeech' ) ); ?>" class="wpspeech-help-link">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
							<span><?php esc_html_e( 'Plugin Settings', 'wpspeech' ); ?></span>
						</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpspeech-analytics' ) ); ?>" class="wpspeech-help-link">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><ellipse cx="12" cy="5" rx="9" ry="3"/></svg>
							<span><?php esc_html_e( 'View Analytics', 'wpspeech' ); ?></span>
						</a>
						<a href="https://devshagor.com/" target="_blank" rel="noopener noreferrer" class="wpspeech-help-link">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
							<span><?php esc_html_e( 'Devshagor Website', 'wpspeech' ); ?></span>
						</a>
					</div>
				</div>
			</div>

		</div>
		<?php
	}
}
