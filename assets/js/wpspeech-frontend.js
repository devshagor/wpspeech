(function () {
	'use strict';

	// Bail if Web Speech API is not supported.
	if ( ! ( 'speechSynthesis' in window ) ) {
		var player = document.querySelector( '.wpspeech-player' );
		if ( player ) {
			var msg = ( window.wpTtsSettings && window.wpTtsSettings.i18n && window.wpTtsSettings.i18n.unsupported )
			? window.wpTtsSettings.i18n.unsupported
			: 'Text-to-speech is not supported in this browser.';
		player.innerHTML = '<p class="wpspeech-unsupported">' + msg + '</p>';
		}
		return;
	}

	var synth    = window.speechSynthesis;
	var settings = window.wpTtsSettings || {};
	var i18n     = settings.i18n || {};

	// State.
	var utterance    = null;
	var sentences    = [];
	var currentIndex = 0;
	var isPlaying    = false;
	var isPaused     = false;

	// DOM references.
	var playerEl, playBtn, stopBtn, playIcon, pauseIcon, btnLabel;
	var progressBar, progressFill, timeDisplay, speedSelect;
	var contentEl;

	// Sticky mini-player references.
	var stickyEl, stickyPlayBtn, stickyStopBtn, stickyProgressFill, stickyCounter, stickyPlayIcon, stickyPauseIcon;
	var playerVisible = true;

	document.addEventListener( 'DOMContentLoaded', init );

	function init() {
		playerEl     = document.querySelector( '.wpspeech-player' );
		playBtn      = document.querySelector( '.wpspeech-play' );
		stopBtn      = document.querySelector( '.wpspeech-stop' );
		playIcon     = document.querySelector( '.wpspeech-icon-play' );
		pauseIcon    = document.querySelector( '.wpspeech-icon-pause' );
		btnLabel     = document.querySelector( '.wpspeech-btn-label' );
		progressFill = document.querySelector( '.wpspeech-progress-fill' );
		progressBar  = document.querySelector( '.wpspeech-progress-bar' );
		timeDisplay  = document.querySelector( '.wpspeech-time' );
		speedSelect  = document.querySelector( '.wpspeech-speed-select' );

		// Find article content - try theme-specific then generic selectors.
		contentEl = document.querySelector( '.single-post-body' )
				 || document.querySelector( '.entry-content' )
				 || document.querySelector( '.post-content' )
				 || document.querySelector( 'article' );

		if ( ! playBtn || ! contentEl ) {
			return;
		}

		// Extract text: clone content and remove the player before reading.
		var contentClone = contentEl.cloneNode( true );
		var playerInClone = contentClone.querySelector( '.wpspeech-player' );
		if ( playerInClone ) {
			playerInClone.remove();
		}
		var rawText = contentClone.innerText || contentClone.textContent;
		sentences   = splitIntoSentences( rawText );

		// Event listeners.
		playBtn.addEventListener( 'click', togglePlayPause );
		stopBtn.addEventListener( 'click', stopSpeech );

		if ( speedSelect ) {
			speedSelect.value = settings.speechRate || 1;
			speedSelect.addEventListener( 'change', onSpeedChange );
		}

		// Setup sticky mini-player if enabled.
		if ( settings.stickyPlayer ) {
			setupStickyPlayer();
		}

		// Keep speech alive on mobile (Chrome Android pauses after ~15s idle).
		setupMobileKeepAlive();
	}

	function splitIntoSentences( text ) {
		return text
			.replace( /\s+/g, ' ' )
			.split( /(?<=[.!?])\s+/ )
			.map( function ( s ) { return s.trim(); } )
			.filter( function ( s ) { return s.length > 0; } );
	}

	function togglePlayPause() {
		if ( isPaused ) {
			synth.resume();
			isPaused  = false;
			isPlaying = true;
			updateUI();
			return;
		}
		if ( isPlaying ) {
			synth.pause();
			isPaused  = true;
			isPlaying = false;
			updateUI();
			return;
		}
		speakFrom( currentIndex );
	}

	function speakFrom( index ) {
		if ( index >= sentences.length ) {
			resetPlayer();
			return;
		}

		currentIndex = index;
		isPlaying    = true;
		isPaused     = false;

		utterance        = new SpeechSynthesisUtterance( sentences[ index ] );
		utterance.rate   = parseFloat( speedSelect ? speedSelect.value : ( settings.speechRate || 1 ) );
		utterance.pitch  = parseFloat( settings.pitch || 1 );
		utterance.volume = parseFloat( settings.volume || 1 );

		// Set voice if configured.
		var voices = synth.getVoices();
		if ( settings.voiceName ) {
			var match = voices.find( function ( v ) { return v.name === settings.voiceName; } );
			if ( match ) {
				utterance.voice = match;
			}
		}

		utterance.onend = function () {
			currentIndex++;
			updateProgress();
			if ( currentIndex < sentences.length ) {
				speakFrom( currentIndex );
			} else {
				resetPlayer();
			}
		};

		utterance.onerror = function ( e ) {
			if ( e.error !== 'canceled' ) {
				console.warn( 'WP TTS error:', e.error );
			}
			resetPlayer();
		};

		synth.cancel();
		synth.speak( utterance );
		updateUI();
		updateProgress();
	}

	function stopSpeech() {
		synth.cancel();
		resetPlayer();
	}

	function onSpeedChange() {
		if ( isPlaying || isPaused ) {
			synth.cancel();
			isPaused = false;
			speakFrom( currentIndex );
		}
	}

	function updateUI() {
		if ( isPlaying ) {
			playIcon.style.display  = 'none';
			pauseIcon.style.display = 'inline';
			btnLabel.textContent    = i18n.pause || 'Pause';
			playBtn.setAttribute( 'aria-label', i18n.pause || 'Pause' );
			stopBtn.disabled        = false;
			playerEl.classList.add( 'wpspeech-active' );
		} else if ( isPaused ) {
			playIcon.style.display  = 'inline';
			pauseIcon.style.display = 'none';
			btnLabel.textContent    = i18n.resume || 'Resume';
			playBtn.setAttribute( 'aria-label', i18n.resume || 'Resume' );
			stopBtn.disabled        = false;
			playerEl.classList.add( 'wpspeech-active' );
		} else {
			playIcon.style.display  = 'inline';
			pauseIcon.style.display = 'none';
			btnLabel.textContent    = i18n.listen || 'Listen';
			playBtn.setAttribute( 'aria-label', i18n.listen || 'Listen' );
			stopBtn.disabled        = true;
			playerEl.classList.remove( 'wpspeech-active' );
		}
		updateStickyUI();
	}

	function updateProgress() {
		if ( ! sentences.length ) {
			return;
		}
		var pct = Math.round( ( currentIndex / sentences.length ) * 100 );
		if ( progressFill ) {
			progressFill.style.width = pct + '%';
		}
		if ( progressBar ) {
			progressBar.setAttribute( 'aria-valuenow', pct );
		}
		if ( timeDisplay ) {
			timeDisplay.textContent = currentIndex + ' / ' + sentences.length;
		}
		// Sync sticky progress.
		if ( stickyProgressFill ) {
			stickyProgressFill.style.width = pct + '%';
		}
		if ( stickyCounter ) {
			stickyCounter.textContent = currentIndex + ' / ' + sentences.length;
		}
	}

	function resetPlayer() {
		isPlaying    = false;
		isPaused     = false;
		currentIndex = 0;
		updateUI();
		if ( progressFill ) {
			progressFill.style.width = '0%';
		}
		if ( progressBar ) {
			progressBar.setAttribute( 'aria-valuenow', 0 );
		}
		if ( timeDisplay ) {
			timeDisplay.textContent = '';
		}
		// Reset sticky.
		if ( stickyProgressFill ) {
			stickyProgressFill.style.width = '0%';
		}
		if ( stickyCounter ) {
			stickyCounter.textContent = '';
		}
	}

	// ---- Sticky Mini Player ----

	function setupStickyPlayer() {
		createStickyDOM();

		// Use IntersectionObserver to detect when the main player leaves the viewport.
		if ( 'IntersectionObserver' in window ) {
			var observer = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					playerVisible = entry.isIntersecting;
					updateStickyVisibility();
				});
			}, { threshold: 0 } );
			observer.observe( playerEl );
		}
	}

	function createStickyDOM() {
		var color = settings.buttonColor || '#d60017';

		stickyEl = document.createElement( 'div' );
		stickyEl.className = 'wpspeech-sticky';
		stickyEl.setAttribute( 'role', 'region' );
		stickyEl.setAttribute( 'aria-label', i18n.listen || 'WP Speech Player' );
		stickyEl.style.cssText = '--wpspeech-color: ' + color + ';';

		stickyEl.innerHTML =
			'<button type="button" class="wpspeech-sticky-play" aria-label="' + ( i18n.pause || 'Pause' ) + '">' +
				'<svg class="wpspeech-sticky-icon-play" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg>' +
				'<svg class="wpspeech-sticky-icon-pause" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" style="display:none;"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>' +
			'</button>' +
			'<div class="wpspeech-sticky-info">' +
				'<span class="wpspeech-sticky-title">' + ( i18n.listen || 'Listen' ) + '</span>' +
				'<div class="wpspeech-sticky-progress"><div class="wpspeech-sticky-progress-fill"></div></div>' +
			'</div>' +
			'<div class="wpspeech-sticky-wave"><span></span><span></span><span></span><span></span></div>' +
			'<span class="wpspeech-sticky-counter"></span>' +
			'<button type="button" class="wpspeech-sticky-stop" aria-label="' + 'Stop' + '">' +
				'<svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><rect x="5" y="5" width="14" height="14" rx="2"/></svg>' +
			'</button>';

		document.body.appendChild( stickyEl );

		// Cache references.
		stickyPlayBtn      = stickyEl.querySelector( '.wpspeech-sticky-play' );
		stickyStopBtn      = stickyEl.querySelector( '.wpspeech-sticky-stop' );
		stickyProgressFill = stickyEl.querySelector( '.wpspeech-sticky-progress-fill' );
		stickyCounter      = stickyEl.querySelector( '.wpspeech-sticky-counter' );
		stickyPlayIcon     = stickyEl.querySelector( '.wpspeech-sticky-icon-play' );
		stickyPauseIcon    = stickyEl.querySelector( '.wpspeech-sticky-icon-pause' );

		// Mirror events to the main player controls.
		stickyPlayBtn.addEventListener( 'click', togglePlayPause );
		stickyStopBtn.addEventListener( 'click', stopSpeech );
	}

	function updateStickyVisibility() {
		if ( ! stickyEl ) {
			return;
		}
		// Show sticky only when playing/paused AND original player is out of view.
		if ( ( isPlaying || isPaused ) && ! playerVisible ) {
			stickyEl.classList.add( 'wpspeech-sticky-visible' );
		} else {
			stickyEl.classList.remove( 'wpspeech-sticky-visible' );
		}
	}

	function updateStickyUI() {
		if ( ! stickyEl ) {
			return;
		}
		var stickyTitle = stickyEl.querySelector( '.wpspeech-sticky-title' );
		var stickyWave  = stickyEl.querySelector( '.wpspeech-sticky-wave' );

		if ( isPlaying ) {
			stickyPlayIcon.style.display  = 'none';
			stickyPauseIcon.style.display = 'inline';
			stickyPlayBtn.setAttribute( 'aria-label', i18n.pause || 'Pause' );
			if ( stickyTitle ) { stickyTitle.textContent = i18n.pause || 'Playing'; }
			if ( stickyWave ) { stickyWave.style.display = 'flex'; }
		} else if ( isPaused ) {
			stickyPlayIcon.style.display  = 'inline';
			stickyPauseIcon.style.display = 'none';
			stickyPlayBtn.setAttribute( 'aria-label', i18n.resume || 'Resume' );
			if ( stickyTitle ) { stickyTitle.textContent = i18n.resume || 'Paused'; }
			if ( stickyWave ) { stickyWave.style.display = 'none'; }
		} else {
			stickyPlayIcon.style.display  = 'inline';
			stickyPauseIcon.style.display = 'none';
			stickyPlayBtn.setAttribute( 'aria-label', i18n.listen || 'Listen' );
			if ( stickyTitle ) { stickyTitle.textContent = i18n.listen || 'Listen'; }
			if ( stickyWave ) { stickyWave.style.display = 'none'; }
		}
		updateStickyVisibility();
	}

	// Mobile: Chrome Android stops speech synthesis after ~15s if no interaction.
	// This workaround pauses/resumes periodically to keep it alive.
	function setupMobileKeepAlive() {
		var isMobile = /Android|iPhone|iPad|iPod/i.test( navigator.userAgent );
		if ( ! isMobile ) {
			return;
		}

		setInterval( function () {
			if ( synth.speaking && ! synth.paused ) {
				synth.pause();
				synth.resume();
			}
		}, 10000 );
	}

})();
