(function ( $ ) {
	'use strict';

	$( document ).ready( function () {

		// ---- Tab Navigation ----
		function activateTab( $tab ) {
			var tab = $tab.data( 'tab' );

			// Update ARIA on all tabs.
			$( '.wpspeech-tab' ).removeClass( 'wpspeech-tab-active' )
				.attr( 'aria-selected', 'false' )
				.attr( 'tabindex', '-1' );
			$tab.addClass( 'wpspeech-tab-active' )
				.attr( 'aria-selected', 'true' )
				.removeAttr( 'tabindex' );

			// Update panels.
			$( '.wpspeech-tab-panel' ).removeClass( 'wpspeech-tab-panel-active' );
			$( '.wpspeech-tab-panel[data-panel="' + tab + '"]' ).addClass( 'wpspeech-tab-panel-active' );
		}

		$( '.wpspeech-tab' ).on( 'click', function () {
			activateTab( $( this ) );
		});

		// Arrow key navigation between tabs.
		$( '.wpspeech-tab' ).on( 'keydown', function ( e ) {
			var $tabs = $( '.wpspeech-tab' );
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
		$( '.wpspeech-color-field' ).wpColorPicker( {
			change: function ( event, ui ) {
				// Update mockup live.
				var color = ui.color.toString();
				$( '.wpspeech-player-mockup' ).css( '--wpspeech-color', color );
			}
		});

		// ---- Range Sliders ----
		$( '#wpspeech-speech-rate' ).on( 'input', function () {
			var val = parseFloat( this.value ) + 'x';
			$( '#wpspeech-rate-val' ).text( val );
			this.setAttribute( 'aria-valuetext', val );
		});
		$( '#wpspeech-pitch' ).on( 'input', function () {
			var val = parseFloat( this.value );
			$( '#wpspeech-pitch-val' ).text( val );
			this.setAttribute( 'aria-valuetext', String( val ) );
		});
		$( '#wpspeech-volume' ).on( 'input', function () {
			var val = parseFloat( this.value );
			$( '#wpspeech-volume-val' ).text( val );
			this.setAttribute( 'aria-valuetext', String( val ) );
		});

		// ---- Voice Dropdown ----
		var voiceSelect = document.getElementById( 'wpspeech-voice-name' );
		var savedVoice  = document.getElementById( 'wpspeech-voice-saved' );

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
		var $apiToggle   = $( '#wpspeech-rest-api' );
		var $apiEndpoints = $( '#wpspeech-api-endpoints' );

		function updateApiEndpoints() {
			if ( $apiToggle.is( ':checked' ) ) {
				$apiEndpoints.removeClass( 'wpspeech-api-disabled' );
			} else {
				$apiEndpoints.addClass( 'wpspeech-api-disabled' );
			}
		}

		if ( $apiToggle.length ) {
			updateApiEndpoints();
			$apiToggle.on( 'change', updateApiEndpoints );
		}

		// ---- Preview ----
		var isPreviewPlaying = false;

		$( '#wpspeech-preview-btn' ).on( 'click', function () {
			var text = $( '#wpspeech-preview-text' ).val();
			if ( ! text ) {
				return;
			}

			if ( isPreviewPlaying ) {
				speechSynthesis.cancel();
				stopPreviewUI();
				return;
			}

			var utter    = new SpeechSynthesisUtterance( text );
			utter.rate   = parseFloat( $( '#wpspeech-speech-rate' ).val() || 1 );
			utter.pitch  = parseFloat( $( '#wpspeech-pitch' ).val() || 1 );
			utter.volume = parseFloat( $( '#wpspeech-volume' ).val() || 1 );

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

		$( '#wpspeech-stop-preview-btn' ).on( 'click', function () {
			speechSynthesis.cancel();
			stopPreviewUI();
		});

		function startPreviewUI() {
			isPreviewPlaying = true;
			$( '#wpspeech-preview-label' ).text( 'Playing...' );
			$( '#wpspeech-preview-btn' ).css( 'background', 'linear-gradient(135deg, #d60017, #b50014)' );
			$( '#wpspeech-stop-preview-btn' ).show();
			$( '#wpspeech-wave' ).show();
		}

		function stopPreviewUI() {
			isPreviewPlaying = false;
			$( '#wpspeech-preview-label' ).text( 'Play Preview' );
			$( '#wpspeech-preview-btn' ).css( 'background', '' );
			$( '#wpspeech-stop-preview-btn' ).hide();
			$( '#wpspeech-wave' ).hide();
		}

	});
})( jQuery );
