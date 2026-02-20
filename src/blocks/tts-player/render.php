<?php
/**
 * Server-side rendering for the TTS Player block.
 *
 * @package WP_Text_To_Speech
 * @since   1.1.0
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content (empty for dynamic blocks).
 * @var WP_Block $block      Block instance.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options       = get_option( WP_TTS_OPTION_KEY, array() );
$button_color  = isset( $options['button_color'] ) ? $options['button_color'] : '#d60017';
$show_progress = ! empty( $options['show_progress_bar'] );
$show_speed    = ! empty( $options['show_speed_control'] );
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<div class="wp-tts-player" role="region" aria-label="<?php esc_attr_e( 'Text to Speech Player', 'wp-text-to-speech' ); ?>"
		style="--wp-tts-color: <?php echo esc_attr( $button_color ); ?>;">

		<div class="wp-tts-header-row">
			<span class="wp-tts-headphone-icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
			</span>
			<div>
				<p class="wp-tts-player-title"><?php esc_html_e( 'Listen to this article', 'wp-text-to-speech' ); ?></p>
				<p class="wp-tts-player-subtitle"><?php esc_html_e( 'Powered by Text-to-Speech', 'wp-text-to-speech' ); ?></p>
			</div>
		</div>

		<div class="wp-tts-controls">
			<button type="button" class="wp-tts-btn wp-tts-play" aria-label="<?php esc_attr_e( 'Play', 'wp-text-to-speech' ); ?>">
				<svg class="wp-tts-icon wp-tts-icon-play" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false"><polygon points="5,3 19,12 5,21"/></svg>
				<svg class="wp-tts-icon wp-tts-icon-pause" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="display:none;" aria-hidden="true" focusable="false"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
				<span class="wp-tts-btn-label"><?php esc_html_e( 'Listen', 'wp-text-to-speech' ); ?></span>
			</button>

			<button type="button" class="wp-tts-btn wp-tts-stop" aria-label="<?php esc_attr_e( 'Stop', 'wp-text-to-speech' ); ?>" disabled>
				<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true" focusable="false"><rect x="5" y="5" width="14" height="14" rx="2"/></svg>
			</button>

			<div class="wp-tts-wave" aria-hidden="true">
				<span></span><span></span><span></span><span></span><span></span>
			</div>

			<?php if ( $show_speed ) : ?>
			<div class="wp-tts-speed-control">
				<label for="wp-tts-speed" class="screen-reader-text"><?php esc_html_e( 'Playback speed', 'wp-text-to-speech' ); ?></label>
				<select id="wp-tts-speed" class="wp-tts-speed-select">
					<option value="0.75">0.75x</option>
					<option value="1" selected>1x</option>
					<option value="1.25">1.25x</option>
					<option value="1.5">1.5x</option>
					<option value="2">2x</option>
				</select>
			</div>
			<?php endif; ?>

			<span class="wp-tts-time" role="status" aria-live="polite"></span>
		</div>

		<?php if ( $show_progress ) : ?>
		<div class="wp-tts-progress-bar" role="progressbar" aria-label="<?php esc_attr_e( 'Reading progress', 'wp-text-to-speech' ); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
			<div class="wp-tts-progress-fill"></div>
		</div>
		<?php endif; ?>
	</div>
</div>
