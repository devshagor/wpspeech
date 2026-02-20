# Wpspeech

A WordPress plugin that adds text-to-speech to your posts and pages. Works on the web via the **Web Speech API** and exposes a **REST API** for React Native and other mobile apps to use native TTS engines.

---

## Features

### Web (Browser)
- Play / Pause / Stop controls with SVG icons
- Adjustable playback speed (0.75x - 2x)
- Progress bar with sentence counter
- Voice, pitch, volume, and speed settings from admin dashboard
- Enable/disable per post type
- Customizable button color
- Mobile-optimized touch targets (44px min)
- Chrome Android keep-alive workaround

### REST API (React Native / Mobile Apps)
- `GET /wp-json/wpspeech/v1/speech/{id}` - Get article text split into sentences
- `GET /wp-json/wpspeech/v1/settings` - Get TTS configuration
- `GET /wp-json/wpspeech/v1/posts` - List TTS-enabled posts with metadata
- Clean plain text extraction (HTML stripped, entities decoded)
- Estimated reading duration per article
- Works with `expo-speech`, `react-native-tts`, or any native TTS library

---

## Installation

1. Upload `wpspeech` folder to `/wp-content/plugins/`
2. Activate in **WP Admin > Plugins**
3. Configure at **Settings > WP Speech**

---

## Admin Settings

**Settings > WP Speech**

### Voice Settings

| Setting | Default | Range |
|---------|---------|-------|
| Voice | Browser Default | Varies by device |
| Speed | 1.0x | 0.5 - 2.0 |
| Pitch | 1.0 | 0.0 - 2.0 |
| Volume | 1.0 | 0.0 - 1.0 |

### Display Settings

| Setting | Default | Options |
|---------|---------|---------|
| Enable on | Posts | Any public post type |
| Button Color | #d60017 | Any hex color |
| Button Position | Before content | Before / After |
| Progress Bar | Enabled | On / Off |
| Speed Control | Enabled | On / Off |

---

## REST API Reference

All endpoints are public (no authentication required). Base URL: `https://yoursite.com/wp-json/wpspeech/v1`

---

### GET `/speech/{id}`

Returns article content as clean plain text split into sentences, ready for native TTS.

**Parameters:**

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | Yes | WordPress post ID |

**Response:**

```json
{
  "post_id": 42,
  "title": "How to Set Up BOGO Deals in WooCommerce",
  "plain_text": "Setting up buy one get one deals is easy with Disco. First, navigate to your WordPress dashboard...",
  "sentences": [
    "Setting up buy one get one deals is easy with Disco.",
    "First, navigate to your WordPress dashboard.",
    "Click on Disco in the sidebar menu.",
    "..."
  ],
  "sentence_count": 28,
  "word_count": 450,
  "estimated_duration_seconds": 180,
  "tts_settings": {
    "speech_rate": 1.0,
    "pitch": 1.0,
    "volume": 1.0,
    "voice_name": ""
  },
  "excerpt": "Setting up buy one get one deals is easy with Disco. First, navigate to your WordPress dashboard...",
  "featured_image": "https://yoursite.com/wp-content/uploads/2025/01/bogo-deals.jpg",
  "author": "John Doe",
  "date": "2025-06-15T10:30:00+00:00"
}
```

**Errors:**

| Status | Code | When |
|--------|------|------|
| 404 | `post_not_found` | Post doesn't exist or isn't published |
| 403 | `tts_not_enabled` | TTS is disabled for this post type |

---

### GET `/settings`

Returns TTS configuration set by the admin. Use these values to configure your native TTS engine.

**Response:**

```json
{
  "tts_settings": {
    "speech_rate": 1.0,
    "pitch": 1.0,
    "volume": 1.0,
    "voice_name": ""
  },
  "enabled_post_types": ["post", "page"]
}
```

---

### GET `/posts`

Returns a paginated list of posts that have TTS enabled.

**Parameters:**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `post_type` | string | `post` | Post type to query |
| `per_page` | integer | `10` | Results per page (max 50) |
| `page` | integer | `1` | Page number |
| `search` | string | `""` | Search query |

**Response:**

```json
{
  "posts": [
    {
      "id": 42,
      "title": "How to Set Up BOGO Deals",
      "excerpt": "Setting up buy one get one deals is easy...",
      "word_count": 450,
      "estimated_duration_seconds": 180,
      "featured_image": "https://yoursite.com/wp-content/uploads/bogo.jpg",
      "author": "John Doe",
      "date": "2025-06-15T10:30:00+00:00",
      "speech_endpoint": "https://yoursite.com/wp-json/wpspeech/v1/speech/42"
    }
  ],
  "total": 25,
  "total_pages": 3,
  "page": 1,
  "per_page": 10
}
```

---

## React Native Integration

### Using `expo-speech` (Expo)

```bash
npx expo install expo-speech
```

```jsx
import * as Speech from 'expo-speech';
import { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, ActivityIndicator } from 'react-native';

const SITE_URL = 'https://yoursite.com';

export function ArticlePlayer({ postId }) {
  const [sentences, setSentences] = useState([]);
  const [settings, setSettings] = useState({});
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isPlaying, setIsPlaying] = useState(false);
  const [loading, setLoading] = useState(true);
  const [title, setTitle] = useState('');

  // Fetch speech data from WordPress
  useEffect(() => {
    fetch(`${SITE_URL}/wp-json/wpspeech/v1/speech/${postId}`)
      .then(res => res.json())
      .then(data => {
        setSentences(data.sentences);
        setSettings(data.tts_settings);
        setTitle(data.title);
        setLoading(false);
      })
      .catch(err => {
        console.error('Failed to fetch speech data:', err);
        setLoading(false);
      });
  }, [postId]);

  const speakFrom = (index) => {
    if (index >= sentences.length) {
      setIsPlaying(false);
      setCurrentIndex(0);
      return;
    }

    setCurrentIndex(index);
    setIsPlaying(true);

    Speech.speak(sentences[index], {
      rate: settings.speech_rate || 1.0,
      pitch: settings.pitch || 1.0,
      volume: settings.volume || 1.0,
      onDone: () => speakFrom(index + 1),
      onError: () => {
        setIsPlaying(false);
        setCurrentIndex(0);
      },
    });
  };

  const handlePlay = () => {
    if (isPlaying) {
      Speech.stop();
      setIsPlaying(false);
    } else {
      speakFrom(currentIndex);
    }
  };

  const handleStop = () => {
    Speech.stop();
    setIsPlaying(false);
    setCurrentIndex(0);
  };

  if (loading) return <ActivityIndicator />;

  const progress = sentences.length
    ? Math.round((currentIndex / sentences.length) * 100)
    : 0;

  return (
    <View style={styles.player}>
      <Text style={styles.title}>{title}</Text>

      <View style={styles.controls}>
        <TouchableOpacity onPress={handlePlay} style={styles.playBtn}>
          <Text style={styles.playBtnText}>
            {isPlaying ? '⏸ Pause' : '▶ Listen'}
          </Text>
        </TouchableOpacity>

        <TouchableOpacity onPress={handleStop} style={styles.stopBtn}>
          <Text>⏹ Stop</Text>
        </TouchableOpacity>

        <Text style={styles.counter}>
          {currentIndex} / {sentences.length}
        </Text>
      </View>

      <View style={styles.progressBar}>
        <View style={[styles.progressFill, { width: `${progress}%` }]} />
      </View>
    </View>
  );
}

const styles = {
  player: {
    padding: 16,
    backgroundColor: '#f8f9fa',
    borderRadius: 10,
    marginVertical: 12,
  },
  title: { fontSize: 14, color: '#666', marginBottom: 8 },
  controls: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  playBtn: {
    backgroundColor: '#d60017',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 8,
  },
  playBtnText: { color: '#fff', fontWeight: '600', fontSize: 15 },
  stopBtn: {
    backgroundColor: '#e2e4e7',
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderRadius: 8,
  },
  counter: { marginLeft: 'auto', color: '#757575', fontSize: 13 },
  progressBar: {
    height: 6,
    backgroundColor: '#e2e4e7',
    borderRadius: 3,
    marginTop: 10,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    backgroundColor: '#d60017',
    borderRadius: 3,
  },
};
```

---

### Using `react-native-tts` (Bare React Native)

```bash
npm install react-native-tts
cd ios && pod install
```

```jsx
import Tts from 'react-native-tts';
import { useState, useEffect } from 'react';

const SITE_URL = 'https://yoursite.com';

export function useArticleTTS(postId) {
  const [sentences, setSentences] = useState([]);
  const [settings, setSettings] = useState({});
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isPlaying, setIsPlaying] = useState(false);

  useEffect(() => {
    fetch(`${SITE_URL}/wp-json/wpspeech/v1/speech/${postId}`)
      .then(res => res.json())
      .then(data => {
        setSentences(data.sentences);
        setSettings(data.tts_settings);

        // Apply WordPress TTS settings to native engine.
        Tts.setDefaultRate(data.tts_settings.speech_rate || 1.0);
        Tts.setDefaultPitch(data.tts_settings.pitch || 1.0);
      });

    // Listen for sentence completion.
    const onFinish = Tts.addEventListener('tts-finish', () => {
      setCurrentIndex(prev => {
        const next = prev + 1;
        if (next < sentences.length) {
          Tts.speak(sentences[next]);
          return next;
        }
        setIsPlaying(false);
        return 0;
      });
    });

    return () => onFinish.remove();
  }, [postId]);

  const play = () => {
    setIsPlaying(true);
    Tts.speak(sentences[currentIndex]);
  };

  const pause = () => {
    Tts.stop();
    setIsPlaying(false);
  };

  const stop = () => {
    Tts.stop();
    setIsPlaying(false);
    setCurrentIndex(0);
  };

  return {
    play,
    pause,
    stop,
    isPlaying,
    currentIndex,
    sentenceCount: sentences.length,
    progress: sentences.length
      ? Math.round((currentIndex / sentences.length) * 100)
      : 0,
  };
}
```

---

### Fetching the Posts List

```jsx
// Show all TTS-enabled posts with estimated listen time
const [posts, setPosts] = useState([]);

useEffect(() => {
  fetch(`${SITE_URL}/wp-json/wpspeech/v1/posts?per_page=20`)
    .then(res => res.json())
    .then(data => setPosts(data.posts));
}, []);

// Each post has:
// - post.title
// - post.estimated_duration_seconds  (e.g., 180 = "3 min listen")
// - post.speech_endpoint             (direct link to speech data)
// - post.featured_image
```

---

## How It Works

### Web (Browser)
1. `the_content` filter injects player HTML on single post views
2. JavaScript extracts article text and splits into sentences
3. Each sentence is spoken via `SpeechSynthesisUtterance`, chained via `onend`
4. Sentence-by-sentence playback avoids Chrome's 15-second utterance timeout

### Mobile App (React Native)
1. App calls `GET /wp-json/wpspeech/v1/speech/{id}`
2. API returns clean plain text + sentences array + TTS settings
3. App feeds sentences to `expo-speech` or `react-native-tts`
4. Native TTS engine handles speech using device voices (Siri, Google TTS, Samsung TTS)

**No extra WordPress settings needed.** The same admin settings (speed, pitch, volume) apply to both web and API responses. The React Native app reads these settings and applies them to the native TTS engine.

---

## File Structure

```
wpspeech/
├── wpspeech.php            # Plugin bootstrap
├── uninstall.php                     # Cleanup on delete
├── README.md                         # This file
├── includes/
│   ├── class-wpspeech-admin.php        # Admin settings (WordPress Settings API)
│   ├── class-wpspeech-frontend.php     # Web frontend: the_content filter
│   └── class-wpspeech-rest-api.php     # REST API for React Native / mobile apps
└── assets/
    ├── js/
    │   ├── wpspeech-admin.js           # Admin: voice dropdown, preview
    │   └── wpspeech-frontend.js        # Web: Speech API player
    └── css/
        ├── wpspeech-admin.css          # Admin styles
        └── wpspeech-frontend.css       # Player styles (responsive)
```

---

## Browser Support (Web)

| Feature | Chrome 33+ | Safari 7+ | Firefox 49+ | Edge 14+ |
|---------|-----------|-----------|-------------|----------|
| Speech Synthesis | Yes | Yes | Yes | Yes |
| Pause/Resume | Yes | iOS: No | Yes | Yes |

## Mobile App Support (API)

| Library | Platform | Voices |
|---------|----------|--------|
| `expo-speech` | iOS + Android | Device native voices |
| `react-native-tts` | iOS + Android | Device native voices |
| iOS `AVSpeechSynthesizer` | iOS | Siri voices |
| Android `TextToSpeech` | Android | Google TTS / Samsung TTS |

---

## Troubleshooting

### API returns 404 for a post
The post is either not published or the ID is wrong. Only published posts are returned.

### API returns 403 `tts_not_enabled`
The post type isn't enabled. Go to **Settings > WP Speech** and check the post type under "Enable on."

### No sound in React Native
- **Expo**: Ensure `expo-speech` is installed and the app has audio permissions
- **Bare RN**: Run `cd ios && pod install` after installing `react-native-tts`
- **Android**: Check that Google TTS or Samsung TTS is installed in device settings

### Web player shows "not supported"
The browser doesn't support Web Speech API. This doesn't affect the REST API.

---

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Modern browser for web player
- `expo-speech` or `react-native-tts` for React Native apps

---

## License

GPL v2 or later.
