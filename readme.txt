=== WP Text to Speech ===
Contributors: webappick
Donate link: https://webappick.com/
Tags: text-to-speech, tts, accessibility, audio, speech
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a text-to-speech player to your posts and pages using the browser's built-in Web Speech API. Free, no API keys required.

== Description ==

WP Text to Speech adds a play button to your articles that reads the content aloud using the browser's built-in speech synthesis engine. No external API keys, no usage costs, no third-party services.

**Features:**

* Play / Pause / Stop controls
* Adjustable playback speed (0.75x - 2x)
* Progress bar with sentence counter
* Voice, pitch, volume, and speed settings from the admin dashboard
* Enable/disable per post type (Posts, Pages, Products, etc.)
* Customizable button color with live preview
* Gutenberg block — place the player anywhere in your content
* Sticky mini-player that follows users while scrolling
* Mobile-optimized with large touch targets
* Accessible: ARIA labels, roles, keyboard navigation, and live regions
* Optional REST API for React Native and mobile app integration (disabled by default)
* Zero external dependencies — no third-party scripts, fonts, or services

**Gutenberg Block:**

Search for "Text to Speech Player" in the block inserter to place the player at any position in your content. The block automatically disables global auto-insertion for that post to prevent duplicates.

**REST API for Mobile Apps (opt-in):**

The plugin includes an optional REST API that must be explicitly enabled from the settings page. Once enabled it provides read-only endpoints that return published article text split into sentences, ready for native TTS engines:

* `GET /wp-json/wp-tts/v1/speech/{id}` — Get article text for TTS
* `GET /wp-json/wp-tts/v1/settings` — Get TTS configuration
* `GET /wp-json/wp-tts/v1/posts` — List TTS-enabled posts

All endpoints are read-only and only expose publicly available published content. Works with `expo-speech`, `react-native-tts`, or any native TTS library.

**How It Works:**

1. The plugin filters post content to inject a player bar (or use the Gutenberg block)
2. JavaScript extracts article text and splits it into sentences
3. Each sentence is spoken using the Web Speech API
4. Sentence-by-sentence playback avoids Chrome's known timeout bug

== Installation ==

1. Upload the `wp-text-to-speech` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Text to Speech > Settings to configure voice and display options
4. Visit any single post to see the player

== Frequently Asked Questions ==

= Does this plugin require an API key? =

No. It uses the browser's built-in Web Speech API which is completely free.

= Which browsers are supported? =

Chrome 33+, Safari 7+, Firefox 49+, Edge 14+, and most modern mobile browsers.

= Can I place the player anywhere I want? =

Yes. Use the "Text to Speech Player" Gutenberg block to place the player at any position within your content. When the block is used, global auto-insertion is automatically skipped for that post.

= Can I use this with my React Native app? =

Yes. Enable the REST API from Text to Speech > Settings > API tab, then use the endpoints to fetch article text for native TTS playback.

= Why do voices sound different on different devices? =

Voices are provided by the operating system, not the plugin. Available voices vary by OS (macOS uses Siri voices, Windows uses Microsoft voices, Android uses Google TTS, etc.).

= Can I choose which post types show the player? =

Yes. Go to Text to Speech > Settings > Display tab and select which post types should show the player.

= Does the player appear on archive pages? =

No. The player only appears on single post/page views, never on archives, search results, or the homepage.

= Will this slow down my site? =

No. The plugin only loads its small CSS and JS files on singular pages where TTS is enabled. On all other pages the frontend class is not even loaded. No external scripts, fonts, or services are used.

= Is the REST API enabled by default? =

No. The REST API is disabled by default and must be explicitly enabled by an administrator from Text to Speech > Settings > API tab.

== Screenshots ==

1. Admin settings page — Voice tab with speed, pitch, and volume sliders
2. Admin settings page — Display tab with post type chips and position cards
3. Admin settings page — Preview tab with live player mockup
4. Frontend player bar at the top of an article
5. Player in active state with progress bar and sticky mini-player
6. Gutenberg block in the editor with duplicate detection notice

== Changelog ==

= 1.0.0 =
* Initial release
* Browser-based TTS using Web Speech API
* Admin settings dashboard under Text to Speech menu
* Voice, speed, pitch, and volume controls
* Enable/disable per post type
* Customizable button color and position (before/after content)
* Progress bar and speed control toggles
* Sticky mini-player during playback
* Gutenberg block for manual player placement
* Analytics and Help submenu pages
* Optional REST API endpoints for mobile app integration (disabled by default)
* Mobile-optimized touch interface
* Accessible markup with ARIA attributes and keyboard navigation
* Performance-optimized: frontend assets only loaded where needed

== Upgrade Notice ==

= 1.0.0 =
Initial release.
