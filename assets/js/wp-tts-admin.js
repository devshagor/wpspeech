(function ( $ ) {
	'use strict';

	$( document ).ready( function () {

		// ---- Tab Navigation ----
		function activateTab( $tab ) {
			var tab = $tab.data( 'tab' );

			// Update ARIA on all tabs.
			$( '.wp-tts-tab' ).removeClass( 'wp-tts-tab-active' )
				.attr( 'aria-selected', 'false' )
				.attr( 'tabindex', '-1' );
			$tab.addClass( 'wp-tts-tab-active' )
				.attr( 'aria-selected', 'true' )
				.removeAttr( 'tabindex' );

			// Update panels.
			$( '.wp-tts-tab-panel' ).removeClass( 'wp-tts-tab-panel-active' );
			$( '.wp-tts-tab-panel[data-panel="' + tab + '"]' ).addClass( 'wp-tts-tab-panel-active' );
		}

		$( '.wp-tts-tab' ).on( 'click', function () {
			activateTab( $( this ) );
		});

		// Arrow key navigation between tabs.
		$( '.wp-tts-tab' ).on( 'keydown', function ( e ) {
			var $tabs = $( '.wp-tts-tab' );
			var index = $tabs.index( this );
			var $next;

			if ( e.key === 'ArrowRight' || e.key === 'ArrowDown' ) {
				e.preventDefault();
				$next = $tabs.eq( ( index + 1 ) % $tabs.length );
			} else if ( e.key === 'ArrowLeft' || e.key === 'ArrowUp' ) {
				e.preventDefault();
				$next = $tabs.eq( ( index - 1 + $tabs.length ) % $tabs.length );
			} else if ( e.key === 'Home' ) {
				e.preventDefault();
				$next = $tabs.first();
			} else if ( e.key === 'End' ) {
				e.preventDefault();
				$next = $tabs.last();
			}

			if ( $next && $next.length ) {
				activateTab( $next );
				$next.focus();
			}
		});

		// ---- Color Picker ----
		$( '.wp-tts-color-field' ).wpColorPicker( {
			change: function ( event, ui ) {
				// Update mockup live.
				var color = ui.color.toString();
				$( '.wp-tts-player-mockup' ).css( '--wp-tts-color', color );
			}
		});

		// ---- Range Sliders ----
		$( '#wp-tts-speech-rate' ).on( 'input', function () {
			var val = parseFloat( this.value ) + 'x';
			$( '#wp-tts-rate-val' ).text( val );
			this.setAttribute( 'aria-valuetext', val );
		});
		$( '#wp-tts-pitch' ).on( 'input', function () {
			var val = parseFloat( this.value );
			$( '#wp-tts-pitch-val' ).text( val );
			this.setAttribute( 'aria-valuetext', String( val ) );
		});
		$( '#wp-tts-volume' ).on( 'input', function () {
			var val = parseFloat( this.value );
			$( '#wp-tts-volume-val' ).text( val );
			this.setAttribute( 'aria-valuetext', String( val ) );
		});

		// ---- Voice Dropdown ----
		var voiceSelect = document.getElementById( 'wp-tts-voice-name' );
		var savedVoice  = document.getElementById( 'wp-tts-voice-saved' );

		function populateVoices() {
			if ( ! voiceSelect || typeof speechSynthesis === 'undefined' ) {
				return;
			}
			var voices = speechSynthesis.getVoices();
			if ( ! voices.length ) {
				return;
			}

			while ( voiceSelect.options.length > 1 ) {
				voiceSelect.remove( 1 );
			}

			voices.forEach( function ( voice ) {
				var option         = document.createElement( 'option' );
				option.value       = voice.name;
				option.textContent = voice.name + ' (' + voice.lang + ')';
				if ( savedVoice && savedVoice.value === voice.name ) {
					option.selected = true;
				}
				voiceSelect.appendChild( option );
			});
		}

		populateVoices();
		if ( typeof speechSynthesis !== 'undefined' && speechSynthesis.onvoiceschanged !== undefined ) {
			speechSynthesis.onvoiceschanged = populateVoices;
		}

		// ---- REST API Toggle ----
		var $apiToggle   = $( '#wp-tts-rest-api' );
		var $apiEndpoints = $( '#wp-tts-api-endpoints' );

		function updateApiEndpoints() {
			if ( $apiToggle.is( ':checked' ) ) {
				$apiEndpoints.removeClass( 'wp-tts-api-disabled' );
			} else {
				$apiEndpoints.addClass( 'wp-tts-api-disabled' );
			}
		}

		if ( $apiToggle.length ) {
			updateApiEndpoints();
			$apiToggle.on( 'change', updateApiEndpoints );
		}

		// ---- Preview ----
		var isPreviewPlaying = false;

		$( '#wp-tts-preview-btn' ).on( 'click', function () {
			var text = $( '#wp-tts-preview-text' ).val();
			if ( ! text ) {
				return;
			}

			if ( isPreviewPlaying ) {
				speechSynthesis.cancel();
				stopPreviewUI();
				return;
			}

			var utter    = new SpeechSynthesisUtterance( text );
			utter.rate   = parseFloat( $( '#wp-tts-speech-rate' ).val() || 1 );
			utter.pitch  = parseFloat( $( '#wp-tts-pitch' ).val() || 1 );
			utter.volume = parseFloat( $( '#wp-tts-volume' ).val() || 1 );

			var voiceName = voiceSelect ? voiceSelect.value : '';
			if ( voiceName ) {
				var match = speechSynthesis.getVoices().find( function ( v ) {
					return v.name === voiceName;
				});
				if ( match ) {
					utter.voice = match;
				}
			}

			utter.onstart = function () {
				startPreviewUI();
			};
			utter.onend = function () {
				stopPreviewUI();
			};
			utter.onerror = function () {
				stopPreviewUI();
			};

			speechSynthesis.cancel();
			speechSynthesis.speak( utter );
		});

		$( '#wp-tts-stop-preview-btn' ).on( 'click', function () {
			speechSynthesis.cancel();
			stopPreviewUI();
		});

		function startPreviewUI() {
			isPreviewPlaying = true;
			$( '#wp-tts-preview-label' ).text( 'Playing...' );
			$( '#wp-tts-preview-btn' ).css( 'background', 'linear-gradient(135deg, #d60017, #b50014)' );
			$( '#wp-tts-stop-preview-btn' ).show();
			$( '#wp-tts-wave' ).show();
		}

		function stopPreviewUI() {
			isPreviewPlaying = false;
			$( '#wp-tts-preview-label' ).text( 'Play Preview' );
			$( '#wp-tts-preview-btn' ).css( 'background', '' );
			$( '#wp-tts-stop-preview-btn' ).hide();
			$( '#wp-tts-wave' ).hide();
		}

	});
})( jQuery );
