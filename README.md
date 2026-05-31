# Zertify

[![CI](https://github.com/vanobospace/zertify-oss/actions/workflows/ci.yml/badge.svg)](https://github.com/vanobospace/zertify-oss/actions/workflows/ci.yml)

Zertify is an open-source Laravel and Vue application for building German B2 exam-practice workflows. It combines a learner-facing interface with a Filament admin panel for managing exams, modules, questions, AI-assisted draft generation, listening transcripts, synthesized audio assets, and content catalogs.

The repository is an application, not a packaged library. The public version intentionally ships without proprietary training books, scanned pages, or third-party exam source material. Seeded questions and the content catalog are synthetic/internal examples meant to demonstrate the data model and workflow.

This public repository is a regularly updated, cleaned snapshot of active private development. History is squashed when publishing to keep proprietary content and local assistant artifacts out of the public tree.

## What is included

- Laravel 13 backend with Fortify authentication and Filament admin resources.
- Vue 3/Inertia frontend for the public learner experience.
- Question models for reading, listening, and language-structure modules.
- AI draft generation and validation services for structured B2-style exercises.
- Optional Gemini and Google Cloud Text-to-Speech integrations.
- Pest feature tests covering admin workflows, generators, audio handling, seeders, and content sync.
- GitHub Actions CI for PHP style checks, Laravel tests, and frontend production builds.

## What is not included

- API keys, service accounts, local environment files, or production secrets.
- Third-party books, official exam PDFs, page previews, or extracted proprietary examples.
- Production data or private deployment configuration.

## Requirements

- PHP 8.4 or newer
- Composer
- Node.js 22 or newer
- npm
- PostgreSQL by default, or another Laravel-supported database after updating `.env`
- Redis if you enable the Redis-backed runtime paths

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

For local development, run the Laravel server/queue/log watcher and Vite together:

```bash
composer run dev
```

## AI integrations

AI generation is optional. The application can run without external AI services. To enable generation, set the relevant values in `.env`:

```dotenv
GEMINI_API_KEY=
GEMINI_MODEL=gemini-3.1-flash-lite-preview
GOOGLE_CLOUD_SERVICE_ACCOUNT_JSON=
GOOGLE_CLOUD_SERVICE_ACCOUNT_JSON_PATH=
```

Never commit real API keys, service account JSON, generated audio from private runs, or local `.env` files.

The current generation backend is Google Gemini, with audio via Google Cloud Text-to-Speech and an optional Gemini Live native-audio path. Support for additional providers — including OpenAI models — is on the [roadmap](docs/roadmap.md) and contributions are welcome. The generation layer is provider-oriented (`SpeechSynthesisManager` already abstracts audio providers), so adding a backend is intended to be incremental.

## Testing

```bash
php artisan test
npm run build
```

The Composer test command also runs Laravel Pint in check mode:

```bash
composer test
```

## Project docs

- [Architecture](docs/architecture.md)
- [Roadmap](docs/roadmap.md)
- [Deployment notes](docs/deploy.md)

## Content and licensing note

Zertify can model exam-like B2 German tasks, but this repository is not affiliated with any exam provider. Do not add copyrighted books, PDFs, official test pages, extracted source examples, or screenshots unless you have explicit redistribution rights. Use synthetic examples for public fixtures and tests.

## License

Zertify is open-sourced under the MIT License. See [LICENSE](LICENSE).
