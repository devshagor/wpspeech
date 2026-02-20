( function ( blocks, element, blockEditor, components, data, i18n ) {
	'use strict';

	var el          = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var useSelect   = data.useSelect;
	var Fragment    = element.Fragment;
	var PanelBody   = components.PanelBody;
	var Notice      = components.Notice;

	// Settings passed from PHP via wp_localize_script.
	var config = window.wpTtsBlockEditor || {};

	blocks.registerBlockType( 'wpspeech/player', {

		edit: function ( props ) {
			var blockProps = useBlockProps( {
				className: 'wpspeech-block-preview',
			} );

			// Get the current post type.
			var postType = useSelect( function ( select ) {
				return select( 'core/editor' ).getCurrentPostType();
			}, [] );

			// Check if global auto-insertion is active for this post type.
			var enabledTypes    = config.enabledPostTypes || [];
			var isGlobalEnabled = enabledTypes.indexOf( postType ) !== -1;
			var buttonColor     = config.buttonColor || '#d60017';

			return el( Fragment, {},

				// Inspector sidebar notice.
				el( InspectorControls, {},
					el( PanelBody, { title: i18n.__( 'TTS Player', 'wpspeech' ), initialOpen: true },
						el( 'p', { style: { fontSize: '13px', color: '#475569' } },
							i18n.__( 'This block renders the TTS player at this exact position. Global auto-insertion is automatically skipped for posts that contain this block.', 'wpspeech' )
						),
						isGlobalEnabled && el( Notice, {
								status: 'info',
								isDismissible: false,
								style: { margin: '12px 0 0' },
							},
							i18n.__( 'Global auto-insertion is active for this post type. It will be skipped because this block is present.', 'wpspeech' )
						)
					)
				),

				// Block preview in editor.
				el( 'div', blockProps,

					// Info banner when global is also active.
					isGlobalEnabled && el( 'div', { className: 'wpspeech-block-notice' },
						el( 'svg', { width: 16, height: 16, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round', strokeLinejoin: 'round' },
							el( 'circle', { cx: 12, cy: 12, r: 10 } ),
							el( 'line', { x1: 12, y1: 16, x2: 12, y2: 12 } ),
							el( 'line', { x1: 12, y1: 8, x2: 12.01, y2: 8 } )
						),
						el( 'span', {},
							i18n.__( 'Global auto-insert is on for this post type — it will be replaced by this block\'s position.', 'wpspeech' )
						)
					),

					// Player mockup.
					el( 'div', { className: 'wpspeech-block-player', style: { '--wpspeech-color': buttonColor } },

						// Header row.
						el( 'div', { className: 'wpspeech-block-header-row' },
							el( 'svg', { width: 18, height: 18, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round', strokeLinejoin: 'round', className: 'wpspeech-block-headphone' },
								el( 'path', { d: 'M3 18v-6a9 9 0 0 1 18 0v6' } ),
								el( 'path', { d: 'M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z' } )
							),
							el( 'div', {},
								el( 'div', { className: 'wpspeech-block-title' }, i18n.__( 'Listen to this article', 'wpspeech' ) ),
								el( 'div', { className: 'wpspeech-block-subtitle' }, i18n.__( 'Powered by WP Speech', 'wpspeech' ) )
							)
						),

						// Controls row.
						el( 'div', { className: 'wpspeech-block-controls' },
							el( 'span', { className: 'wpspeech-block-play-btn' },
								el( 'svg', { width: 16, height: 16, viewBox: '0 0 24 24', fill: 'currentColor' },
									el( 'polygon', { points: '5,3 19,12 5,21' } )
								),
								i18n.__( 'Listen', 'wpspeech' )
							),
							el( 'span', { className: 'wpspeech-block-stop-btn' },
								el( 'svg', { width: 12, height: 12, viewBox: '0 0 24 24', fill: 'currentColor' },
									el( 'rect', { x: 5, y: 5, width: 14, height: 14, rx: 2 } )
								)
							),
							el( 'span', { className: 'wpspeech-block-speed' }, '1x' ),
							el( 'span', { className: 'wpspeech-block-counter' }, '0 / 12' )
						),

						// Progress bar.
						el( 'div', { className: 'wpspeech-block-progress' },
							el( 'div', { className: 'wpspeech-block-progress-fill' } )
						)
					)
				)
			);
		},

		save: function () {
			// Server-side rendered — save returns null.
			return null;
		},
	} );

} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.data,
	window.wp.i18n
);
