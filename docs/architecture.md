# Architecture

Zertify is a Laravel application with an Inertia/Vue public interface and a Filament administration panel. The backend owns the exam model, question lifecycle, content catalog, AI draft generation, validation, and optional audio synthesis.

## Main areas

- **Public learner interface**: Vue 3 and Inertia pages under `resources/js/Pages`.
- **Admin workflows**: Filament resources for exams, modules, questions, content examples, and generation themes.
- **Content model**: Laravel models for exams, modules, questions, structured content, generated drafts, explanations, and optional audio metadata.
- **AI generation**: services that build prompts, request structured drafts, validate returned content, and surface review warnings instead of silently accepting weak output.
- **Listening audio**: optional synthesis paths for transcript-based listening exercises. Generated audio files are intentionally not committed.
- **Seed and catalog sync**: public-safe synthetic seed data plus catalog commands for repeatable local development and tests.

## Question generation pipeline

AI generation is optional — the app runs fully without an API key. When enabled, a question draft flows through:

```
Filament admin action
  └─ GenerateQuestionAiJob (queued)
       └─ QuestionGenerationThemeSelector        # picks a topic/theme blueprint
       └─ GeminiService                          # builds the prompt + requests a structured draft
            └─ QuestionGenerationQualityValidator # validates shape, options, answer keys, distractors
       └─ Question (status: draft)               # persisted with review warnings, not silently accepted
  └─ Maintainer reviews → publishes
  └─ ContentCatalogService (content:export-catalog) # exports a public-safe snapshot to database/content/catalog.json
```

- **Prompt construction**: `GeminiService` assembles a blueprint-constrained prompt per module subtype (Sprachbausteine, Lesen, Hören) and requests a strict JSON payload (task draft, answer key, explanation pack).
- **Validation**: `QuestionGenerationQualityValidator` enforces the per-format contracts (gap counts, option counts, unique answer mappings) documented in [`b2-allgemein-content-contracts.md`](b2-allgemein-content-contracts.md). Usable-but-weak output produces review warnings rather than a hard failure.
- **Catalog**: `ContentCatalogService` exports the canonical, public-safe content subset; `content:export-catalog` / `content:refresh-from-catalog` keep local dev and tests repeatable.

## Listening audio pipeline

```
Published listening question
  └─ GenerateListeningAudioJob (queued)
       └─ ListeningQuestionAudioSynthesisService
            └─ SpeechSynthesisManager            # selects the configured provider
                 ├─ GoogleCloudTextToSpeechService   (SPEECH_DEFAULT_PROVIDER=google_cloud_tts)
                 └─ GeminiLiveNativeAudioService     (optional native multi-speaker audio)
            └─ Teil-specific assembly (segments / dialogue) + ListeningAudioPostProcessor + WaveAudioAssembler
       └─ stored on the SPEECH_STORAGE_DISK as a wav file
       └─ QuestionAudioAsset (DB metadata; the audio file itself is gitignored)
```

Provider selection and voice presets are configured through `config/services.php` (see the `SPEECH_*` and `GOOGLE_CLOUD_TTS_*` variables in `.env.example`).

## Data flow

1. A maintainer creates or selects an exam module.
2. The admin panel creates a draft question manually or through an AI generation action.
3. Generation services normalize and validate the structured payload.
4. Review warnings are shown in the admin UI when content is usable but needs attention.
5. Approved questions become available to the learner-facing interface; listening questions can additionally get synthesized audio.

## Public repository boundary

The public repository includes the application code, tests, documentation, and synthetic fixtures. It intentionally excludes proprietary source pages, private extracted examples, generated listening audio, production data, local assistant notes, and secrets.
