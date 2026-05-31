<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.1-flash-lite-preview'),
        'connect_timeout_seconds' => env('GEMINI_CONNECT_TIMEOUT_SECONDS', 10),
        'request_timeout_seconds' => env('GEMINI_REQUEST_TIMEOUT_SECONDS', 60),
        'explanation_timeout_seconds' => env('GEMINI_EXPLANATION_TIMEOUT_SECONDS', 45),
        'request_budget_seconds' => env('GEMINI_REQUEST_BUDGET_SECONDS', 120),
        'max_generation_attempts' => env('GEMINI_MAX_GENERATION_ATTEMPTS', 3),
    ],

    'speech' => [
        'storage_disk' => env('SPEECH_STORAGE_DISK', 'public'),
        'default_provider' => env('SPEECH_DEFAULT_PROVIDER', 'google_cloud_tts'),
        'default_output_format' => env('SPEECH_DEFAULT_OUTPUT_FORMAT', 'wav'),
        'voice_presets' => [
            'google_cloud_tts' => [
                'news_female' => [
                    'voice' => 'de-DE-Chirp3-HD-Kore',
                    'speaking_rate' => 1.0,
                    'pitch' => 0.0,
                ],
                'news_male' => [
                    'voice' => 'de-DE-Chirp3-HD-Puck',
                    'speaking_rate' => 1.0,
                    'pitch' => 0.0,
                ],
                'neutral_female' => [
                    'voice' => 'de-DE-Chirp3-HD-Leda',
                    'speaking_rate' => 0.98,
                    'pitch' => -0.2,
                ],
                'neutral_male' => [
                    'voice' => 'de-DE-Chirp3-HD-Orus',
                    'speaking_rate' => 0.98,
                    'pitch' => -0.4,
                ],
                'anchor_female' => [
                    'voice' => 'de-DE-Chirp3-HD-Kore',
                    'speaking_rate' => 0.94,
                    'pitch' => -0.8,
                ],
                'anchor_male' => [
                    'voice' => 'de-DE-Chirp3-HD-Orus',
                    'speaking_rate' => 0.93,
                    'pitch' => -1.0,
                ],
                'reporter_female' => [
                    'voice' => 'de-DE-Chirp3-HD-Leda',
                    'speaking_rate' => 1.07,
                    'pitch' => 0.5,
                ],
                'reporter_male' => [
                    'voice' => 'de-DE-Chirp3-HD-Puck',
                    'speaking_rate' => 1.06,
                    'pitch' => 0.2,
                ],
            ],
            'gemini_live_native_audio' => [
                'news_female' => env('SPEECH_GEMINI_NATIVE_PRESET_NEWS_FEMALE_VOICE', 'Kore'),
                'news_male' => env('SPEECH_GEMINI_NATIVE_PRESET_NEWS_MALE_VOICE', 'Puck'),
                'neutral_female' => env('SPEECH_GEMINI_NATIVE_PRESET_NEUTRAL_FEMALE_VOICE', 'Leda'),
                'neutral_male' => env('SPEECH_GEMINI_NATIVE_PRESET_NEUTRAL_MALE_VOICE', 'Orus'),
            ],
        ],
        'dialogue_voice_pairs' => [
            'dialog_mf' => [
                'interviewer_voice_preset' => 'anchor_male',
                'guest_voice_preset' => 'reporter_female',
            ],
            'dialog_fm' => [
                'interviewer_voice_preset' => 'anchor_female',
                'guest_voice_preset' => 'reporter_male',
            ],
            'dialog_mm' => [
                'interviewer_voice_preset' => 'anchor_male',
                'guest_voice_preset' => 'reporter_male',
            ],
            'dialog_ff' => [
                'interviewer_voice_preset' => 'anchor_female',
                'guest_voice_preset' => 'reporter_female',
            ],
        ],
        'audio_style_presets' => [
            'clean' => [
                'normalize_target_peak' => 0.92,
                'compression_threshold' => 0.98,
                'compression_ratio' => 1.0,
                'highpass_alpha' => 0.0,
                'highpass_mix' => 0.0,
                'lowpass_alpha' => 0.0,
                'lowpass_mix' => 0.0,
                'dry_mix' => 1.0,
                'wet_mix' => 0.0,
                'makeup_gain' => 1.0,
                'saturation_drive' => 1.0,
                'noise_level' => 0.0,
                'sample_hold' => 1.0,
            ],
            'news_polish' => [
                'normalize_target_peak' => 0.88,
                'compression_threshold' => 0.52,
                'compression_ratio' => 4.2,
                'highpass_alpha' => 0.983,
                'highpass_mix' => 0.38,
                'lowpass_alpha' => 0.15,
                'lowpass_mix' => 0.12,
                'dry_mix' => 0.62,
                'wet_mix' => 0.38,
                'makeup_gain' => 1.16,
                'saturation_drive' => 1.35,
                'noise_level' => 0.0015,
                'sample_hold' => 1.0,
            ],
            'radio_light' => [
                'normalize_target_peak' => 0.82,
                'compression_threshold' => 0.40,
                'compression_ratio' => 7.2,
                'highpass_alpha' => 0.991,
                'highpass_mix' => 0.56,
                'lowpass_alpha' => 0.38,
                'lowpass_mix' => 0.30,
                'dry_mix' => 0.36,
                'wet_mix' => 0.64,
                'makeup_gain' => 1.28,
                'saturation_drive' => 1.62,
                'noise_level' => 0.0024,
                'sample_hold' => 1.0,
            ],
            'radio_heavy' => [
                'normalize_target_peak' => 0.82,
                'compression_threshold' => 0.30,
                'compression_ratio' => 10.5,
                'highpass_alpha' => 0.993,
                'highpass_mix' => 0.62,
                'lowpass_alpha' => 0.46,
                'lowpass_mix' => 0.36,
                'dry_mix' => 0.24,
                'wet_mix' => 0.76,
                'makeup_gain' => 1.34,
                'saturation_drive' => 1.90,
                'noise_level' => 0.0032,
                'sample_hold' => 1.0,
            ],
            'phone_hotline' => [
                'normalize_target_peak' => 0.80,
                'compression_threshold' => 0.28,
                'compression_ratio' => 11.0,
                'highpass_alpha' => 0.996,
                'highpass_mix' => 0.70,
                'lowpass_alpha' => 0.60,
                'lowpass_mix' => 0.56,
                'dry_mix' => 0.14,
                'wet_mix' => 0.75,
                'makeup_gain' => 1.30,
                'saturation_drive' => 2.05,
                'noise_level' => 0.0036,
                'sample_hold' => 2.0,
            ],
            'podcast_warm' => [
                'normalize_target_peak' => 0.88,
                'compression_threshold' => 0.56,
                'compression_ratio' => 3.6,
                'highpass_alpha' => 0.978,
                'highpass_mix' => 0.26,
                'lowpass_alpha' => 0.10,
                'lowpass_mix' => 0.10,
                'dry_mix' => 0.72,
                'wet_mix' => 0.28,
                'makeup_gain' => 1.08,
                'saturation_drive' => 1.28,
                'noise_level' => 0.0008,
                'sample_hold' => 1.0,
            ],
            'fm_clean' => [
                'normalize_target_peak' => 0.86,
                'compression_threshold' => 0.48,
                'compression_ratio' => 5.6,
                'highpass_alpha' => 0.986,
                'highpass_mix' => 0.46,
                'lowpass_alpha' => 0.26,
                'lowpass_mix' => 0.22,
                'dry_mix' => 0.54,
                'wet_mix' => 0.46,
                'makeup_gain' => 1.18,
                'saturation_drive' => 1.55,
                'noise_level' => 0.0020,
                'sample_hold' => 1.0,
            ],
            'pa_speaker' => [
                'normalize_target_peak' => 0.84,
                'compression_threshold' => 0.34,
                'compression_ratio' => 8.8,
                'highpass_alpha' => 0.994,
                'highpass_mix' => 0.74,
                'lowpass_alpha' => 0.54,
                'lowpass_mix' => 0.48,
                'dry_mix' => 0.20,
                'wet_mix' => 0.80,
                'makeup_gain' => 1.22,
                'saturation_drive' => 1.95,
                'noise_level' => 0.0028,
                'sample_hold' => 1.0,
            ],
            'room_light' => [
                'normalize_target_peak' => 0.88,
                'compression_threshold' => 0.62,
                'compression_ratio' => 2.8,
                'highpass_alpha' => 0.976,
                'highpass_mix' => 0.18,
                'lowpass_alpha' => 0.08,
                'lowpass_mix' => 0.08,
                'dry_mix' => 0.80,
                'wet_mix' => 0.20,
                'makeup_gain' => 1.06,
                'saturation_drive' => 1.14,
                'noise_level' => 0.0006,
                'sample_hold' => 1.0,
            ],
        ],
        'real_teil1' => [
            'pause_milliseconds' => (int) env('SPEECH_REAL_TEIL1_PAUSE_MS', 500),
            'default_intro_voice_profile' => 'anchor_main',
            'default_segment_voice_profile' => 'news_main',
            'voice_profiles' => [
                'anchor_main' => [
                    'voice' => env('SPEECH_REAL_TEIL1_ANCHOR_VOICE', env('SPEECH_REAL_TEIL1_NEWS_MAIN_VOICE', 'de-DE-Chirp3-HD-Kore')),
                ],
                'news_main' => [
                    'voice' => env('SPEECH_REAL_TEIL1_NEWS_MAIN_VOICE', 'de-DE-Chirp3-HD-Kore'),
                ],
                'news_a' => [
                    'voice' => env('SPEECH_REAL_TEIL1_NEWS_A_VOICE', env('SPEECH_REAL_TEIL1_NEWS_MAIN_VOICE', 'de-DE-Chirp3-HD-Kore')),
                ],
                'news_b' => [
                    'voice' => env('SPEECH_REAL_TEIL1_NEWS_B_VOICE', env('SPEECH_REAL_TEIL1_NEWS_MAIN_VOICE', 'de-DE-Chirp3-HD-Kore')),
                ],
                'news_c' => [
                    'voice' => env('SPEECH_REAL_TEIL1_NEWS_C_VOICE', env('SPEECH_REAL_TEIL1_NEWS_MAIN_VOICE', 'de-DE-Chirp3-HD-Kore')),
                ],
                'news_d' => [
                    'voice' => env('SPEECH_REAL_TEIL1_NEWS_D_VOICE', env('SPEECH_REAL_TEIL1_NEWS_MAIN_VOICE', 'de-DE-Chirp3-HD-Kore')),
                ],
                'news_e' => [
                    'voice' => env('SPEECH_REAL_TEIL1_NEWS_E_VOICE', env('SPEECH_REAL_TEIL1_NEWS_MAIN_VOICE', 'de-DE-Chirp3-HD-Kore')),
                ],
            ],
            'segment_voice_cycle' => ['news_main', 'news_main', 'news_main', 'news_main', 'news_main'],
        ],
        'hoeren_teil1_effects' => [
            'enabled' => (bool) env('SPEECH_HOEREN_TEIL1_EFFECTS_ENABLED', true),
            'intro_signal_enabled' => (bool) env('SPEECH_HOEREN_TEIL1_INTRO_SIGNAL_ENABLED', true),
            'final_gong_enabled' => (bool) env('SPEECH_HOEREN_TEIL1_FINAL_GONG_ENABLED', true),
            'segment_pause_ms' => (int) env('SPEECH_HOEREN_TEIL1_SEGMENT_PAUSE_MS', 520),
            'effects_gain_db' => (float) env('SPEECH_HOEREN_TEIL1_EFFECTS_GAIN_DB', -3.5),
            'speech_target_lufs' => (float) env('SPEECH_HOEREN_TEIL1_SPEECH_TARGET_LUFS', -18.5),
        ],
        'hoeren_teil2_dialogue' => [
            'pause_ms' => (int) env('SPEECH_HOEREN_TEIL2_DIALOGUE_PAUSE_MS', 420),
        ],
        'google_cloud_tts' => [
            'endpoint' => env('GOOGLE_CLOUD_TTS_ENDPOINT', 'https://texttospeech.googleapis.com/v1/text:synthesize'),
            'token_uri' => env('GOOGLE_CLOUD_TOKEN_URI', 'https://oauth2.googleapis.com/token'),
            'service_account_json' => env('GOOGLE_CLOUD_SERVICE_ACCOUNT_JSON'),
            'service_account_json_path' => env('GOOGLE_CLOUD_SERVICE_ACCOUNT_JSON_PATH'),
            'language_code' => env('GOOGLE_CLOUD_TTS_LANGUAGE_CODE', 'de-DE'),
            'voice_name' => env('GOOGLE_CLOUD_TTS_VOICE_NAME', 'de-DE-Chirp3-HD-Kore'),
            'speaking_rate' => (float) env('GOOGLE_CLOUD_TTS_SPEAKING_RATE', 1.0),
            'pitch' => (float) env('GOOGLE_CLOUD_TTS_PITCH', 0.0),
        ],
        'gemini_live_native_audio' => [
            'enabled_for_hoeren_teil1' => (bool) env('SPEECH_GEMINI_NATIVE_ENABLED_FOR_HOEREN_TEIL1', false),
            'endpoint' => env('SPEECH_GEMINI_NATIVE_ENDPOINT', ''),
            'model' => env('SPEECH_GEMINI_NATIVE_MODEL', 'gemini-2.5-flash-preview-tts'),
            'api_key' => env('SPEECH_GEMINI_NATIVE_API_KEY'),
            'bearer_token' => env('SPEECH_GEMINI_NATIVE_BEARER_TOKEN'),
            'mime_type' => env('SPEECH_GEMINI_NATIVE_MIME_TYPE', 'audio/wav'),
            'connect_timeout_seconds' => (int) env('SPEECH_GEMINI_NATIVE_CONNECT_TIMEOUT_SECONDS', 10),
            'request_timeout_seconds' => (int) env('SPEECH_GEMINI_NATIVE_REQUEST_TIMEOUT_SECONDS', 90),
            'temperature' => (float) env('SPEECH_GEMINI_NATIVE_TEMPERATURE', 0.6),
            'soft_cap_prompt_tokens' => (int) env('SPEECH_GEMINI_NATIVE_SOFT_CAP_PROMPT_TOKENS', 3500),
            'voice_profiles' => [
                'anchor_main' => [
                    'voice' => env('SPEECH_GEMINI_NATIVE_ANCHOR_MAIN_VOICE', 'Kore'),
                    'style_instruction' => 'Stimme eines neutralen Nachrichtensprechers. Klar, ruhig, ohne Hintergrundgeräusche.',
                ],
                'news_main' => [
                    'voice' => env('SPEECH_GEMINI_NATIVE_NEWS_MAIN_VOICE', 'Kore'),
                    'style_instruction' => 'Nachrichtenstil, klar artikuliert, natürlicher Fluss, ohne Musik und ohne Umgebungsgeräusche.',
                ],
                'news_a' => [
                    'voice' => env('SPEECH_GEMINI_NATIVE_NEWS_A_VOICE', env('SPEECH_GEMINI_NATIVE_NEWS_MAIN_VOICE', 'Kore')),
                    'style_instruction' => 'Kurzer Nachrichtentext, nüchtern und deutlich, ohne Hintergrundeffekte.',
                ],
                'news_b' => [
                    'voice' => env('SPEECH_GEMINI_NATIVE_NEWS_B_VOICE', env('SPEECH_GEMINI_NATIVE_NEWS_MAIN_VOICE', 'Kore')),
                    'style_instruction' => 'Kurzer Nachrichtentext, sachlich und gut verständlich, ohne Hintergrundeffekte.',
                ],
                'news_c' => [
                    'voice' => env('SPEECH_GEMINI_NATIVE_NEWS_C_VOICE', env('SPEECH_GEMINI_NATIVE_NEWS_MAIN_VOICE', 'Kore')),
                    'style_instruction' => 'Nachrichtenlesung in normalem Tempo, klare Aussprache, ohne Hintergrundgeräusche.',
                ],
                'news_d' => [
                    'voice' => env('SPEECH_GEMINI_NATIVE_NEWS_D_VOICE', env('SPEECH_GEMINI_NATIVE_NEWS_MAIN_VOICE', 'Kore')),
                    'style_instruction' => 'Sachliche Radiomeldung, natuerlich, ohne Soundeffekte und ohne Musik.',
                ],
                'news_e' => [
                    'voice' => env('SPEECH_GEMINI_NATIVE_NEWS_E_VOICE', env('SPEECH_GEMINI_NATIVE_NEWS_MAIN_VOICE', 'Kore')),
                    'style_instruction' => 'Einzelne Nachricht im Prüfungsstil, deutliche Pausen und klare Artikulation.',
                ],
            ],
        ],
    ],

];
