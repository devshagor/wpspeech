<?php
/**
 * REST API endpoints for WP Text to Speech.
 *
 * Provides speech data for React Native and other mobile apps.
 *
 * @package WP_Text_To_Speech
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_TTS_REST_API
 *
 * Registers read-only REST API routes under the wp-tts/v1 namespace.
 * All endpoints are intentionally public (no authentication required) because
 * they only expose published post content — the same data already visible to
 * any site visitor. This allows mobile apps and third-party clients to fetch
 * article text for native TTS playback without requiring API keys.
 *
 * The REST API is disabled by default and must be explicitly enabled by an
 * administrator via Text to Speech > Settings > API tab.
 *
 * @since 1.0.0
 */
class WP_TTS_REST_API {

	/**
	 * REST API namespace.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const REST_NAMESPACE = 'wp-tts/v1';

	/**
	 * Constructor. Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all REST API routes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes() {

		// GET /wp-json/wp-tts/v1/speech/{id}.
		// Public: returns only published post content (same as front-end).
		register_rest_route(
			self::REST_NAMESPACE,
			'/speech/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_speech_data' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'description'       => __( 'Post ID.', 'wp-text-to-speech' ),
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && (int) $param > 0;
						},
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// GET /wp-json/wp-tts/v1/settings.
		// Public: returns only non-sensitive TTS playback settings.
		register_rest_route(
			self::REST_NAMESPACE,
			'/settings',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_tts_settings' ),
				'permission_callback' => '__return_true',
			)
		);

		// GET /wp-json/wp-tts/v1/posts.
		// Public: returns only published posts (same as front-end archives).
		register_rest_route(
			self::REST_NAMESPACE,
			'/posts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_tts_enabled_posts' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'post_type' => array(
						'default'           => 'post',
						'type'              => 'string',
						'description'       => __( 'Post type slug.', 'wp-text-to-speech' ),
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => function ( $param ) {
							return post_type_exists( sanitize_key( $param ) );
						},
					),
					'per_page'  => array(
						'default'           => 10,
						'type'              => 'integer',
						'description'       => __( 'Results per page (max 50).', 'wp-text-to-speech' ),
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && (int) $param > 0 && (int) $param <= 50;
						},
					),
					'page'      => array(
						'default'           => 1,
						'type'              => 'integer',
						'description'       => __( 'Page number.', 'wp-text-to-speech' ),
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && (int) $param > 0;
						},
					),
					'search'    => array(
						'default'           => '',
						'type'              => 'string',
						'description'       => __( 'Search query.', 'wp-text-to-speech' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * GET /wp-json/wp-tts/v1/speech/{id}
	 *
	 * Returns cleaned article text split into sentences, ready for native TTS.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_speech_data( $request ) {
		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			return new WP_Error(
				'wp_tts_post_not_found',
				__( 'Post not found or not published.', 'wp-text-to-speech' ),
				array( 'status' => 404 )
			);
		}

		// Check if TTS is enabled for this post type.
		$options       = get_option( WP_TTS_OPTION_KEY, array() );
		$enabled_types = isset( $options['enabled_post_types'] ) ? (array) $options['enabled_post_types'] : array( 'post' );

		if ( ! in_array( $post->post_type, $enabled_types, true ) ) {
			return new WP_Error(
				'wp_tts_not_enabled',
				__( 'Text-to-speech is not enabled for this post type.', 'wp-text-to-speech' ),
				array( 'status' => 403 )
			);
		}

		// Get content and strip to plain text.
		$content    = apply_filters( 'the_content', $post->post_content );
		$plain_text = $this->html_to_plain_text( $content );
		$sentences  = $this->split_into_sentences( $plain_text );

		// Get TTS settings.
		$tts_settings = $this->format_settings( $options );

		$excerpt = $post->post_excerpt
			? wp_strip_all_tags( $post->post_excerpt )
			: wp_trim_words( $plain_text, 30, '...' );

		return rest_ensure_response( array(
			'post_id'                    => (int) $post_id,
			'title'                      => get_the_title( $post ),
			'plain_text'                 => $plain_text,
			'sentences'                  => $sentences,
			'sentence_count'             => count( $sentences ),
			'word_count'                 => str_word_count( $plain_text ),
			'estimated_duration_seconds' => $this->estimate_duration( $plain_text, $tts_settings['speech_rate'] ),
			'tts_settings'               => $tts_settings,
			'excerpt'                    => $excerpt,
			'featured_image'             => get_the_post_thumbnail_url( $post_id, 'medium' ) ?: null,
			'author'                     => get_the_author_meta( 'display_name', $post->post_author ),
			'date'                       => get_the_date( 'c', $post ),
		) );
	}

	/**
	 * GET /wp-json/wp-tts/v1/settings
	 *
	 * Returns TTS settings for the mobile app to configure its TTS engine.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object.
	 */
	public function get_tts_settings( $request ) {
		$options = get_option( WP_TTS_OPTION_KEY, array() );

		return rest_ensure_response( array(
			'tts_settings'       => $this->format_settings( $options ),
			'enabled_post_types' => isset( $options['enabled_post_types'] ) ? (array) $options['enabled_post_types'] : array( 'post' ),
		) );
	}

	/**
	 * GET /wp-json/wp-tts/v1/posts
	 *
	 * Returns a paginated list of posts that have TTS enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_tts_enabled_posts( $request ) {
		$options       = get_option( WP_TTS_OPTION_KEY, array() );
		$enabled_types = isset( $options['enabled_post_types'] ) ? (array) $options['enabled_post_types'] : array( 'post' );
		$post_type     = $request->get_param( 'post_type' );
		$per_page      = min( (int) $request->get_param( 'per_page' ), 50 );
		$page          = (int) $request->get_param( 'page' );
		$search        = $request->get_param( 'search' );

		if ( ! in_array( $post_type, $enabled_types, true ) ) {
			return new WP_Error(
				'wp_tts_not_enabled',
				__( 'Text-to-speech is not enabled for this post type.', 'wp-text-to-speech' ),
				array( 'status' => 403 )
			);
		}

		$query_args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
		);

		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		$query = new WP_Query( $query_args );
		$posts = array();

		foreach ( $query->posts as $post ) {
			$plain_text = $this->html_to_plain_text( apply_filters( 'the_content', $post->post_content ) );
			$settings   = $this->format_settings( $options );

			$excerpt = $post->post_excerpt
				? wp_strip_all_tags( $post->post_excerpt )
				: wp_trim_words( $plain_text, 30, '...' );

			$posts[] = array(
				'id'                         => (int) $post->ID,
				'title'                      => get_the_title( $post ),
				'excerpt'                    => $excerpt,
				'word_count'                 => str_word_count( $plain_text ),
				'estimated_duration_seconds' => $this->estimate_duration( $plain_text, $settings['speech_rate'] ),
				'featured_image'             => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null,
				'author'                     => get_the_author_meta( 'display_name', $post->post_author ),
				'date'                       => get_the_date( 'c', $post ),
				'speech_endpoint'            => rest_url( self::REST_NAMESPACE . '/speech/' . $post->ID ),
			);
		}

		wp_reset_postdata();

		return rest_ensure_response( array(
			'posts'       => $posts,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
			'page'        => $page,
			'per_page'    => $per_page,
		) );
	}

	/**
	 * Convert HTML content to clean plain text.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html HTML content to convert.
	 * @return string Plain text suitable for TTS.
	 */
	private function html_to_plain_text( $html ) {
		// Remove the TTS player HTML if present.
		$html = preg_replace( '/<div class="wp-tts-player"[^>]*>.*?<\/div>\s*<\/div>/s', '', $html );

		// Remove script and style tags with content.
		$html = preg_replace( '/<(script|style)[^>]*>.*?<\/\1>/si', '', $html );

		// Convert block-level elements to periods for sentence separation.
		$html = preg_replace( '/<\/(p|div|h[1-6]|li|blockquote|tr)>/i', ".\n", $html );
		$html = preg_replace( '/<br\s*\/?>/i', '. ', $html );

		// Strip remaining HTML tags.
		$text = wp_strip_all_tags( $html );

		// Decode HTML entities.
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

		// Clean up whitespace and repeated punctuation.
		$text = preg_replace( '/\.{2,}/', '.', $text );
		$text = preg_replace( '/\s+/', ' ', $text );

		return trim( $text );
	}

	/**
	 * Split text into sentences.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Plain text to split.
	 * @return array Array of sentence strings.
	 */
	private function split_into_sentences( $text ) {
		$sentences = preg_split( '/(?<=[.!?])\s+/', $text );
		$sentences = array_map( 'trim', $sentences );
		$sentences = array_filter(
			$sentences,
			function ( $s ) {
				return strlen( $s ) > 0;
			}
		);
		return array_values( $sentences );
	}

	/**
	 * Estimate speech duration in seconds.
	 *
	 * Uses an average speaking rate of 150 words per minute at 1x speed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text  The text to estimate duration for.
	 * @param float  $rate  Speech rate multiplier.
	 * @return int Estimated duration in seconds.
	 */
	private function estimate_duration( $text, $rate = 1.0 ) {
		$word_count = str_word_count( $text );
		$wpm        = 150 * (float) $rate;

		if ( $wpm <= 0 ) {
			$wpm = 150;
		}

		return (int) round( ( $word_count / $wpm ) * 60 );
	}

	/**
	 * Format settings for API response.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options Raw plugin options.
	 * @return array Formatted TTS settings.
	 */
	private function format_settings( $options ) {
		return array(
			'speech_rate' => isset( $options['speech_rate'] ) ? (float) $options['speech_rate'] : 1.0,
			'pitch'       => isset( $options['pitch'] ) ? (float) $options['pitch'] : 1.0,
			'volume'      => isset( $options['volume'] ) ? (float) $options['volume'] : 1.0,
			'voice_name'  => isset( $options['voice_name'] ) ? $options['voice_name'] : '',
		);
	}
}
