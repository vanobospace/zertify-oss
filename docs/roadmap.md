# Roadmap

This public snapshot focuses on making the core application understandable, runnable, and safe to extend. Planned work is intentionally practical and contributor-friendly.

## Near term

- Add more synthetic seed questions across reading, listening, and language-structure modules.
- Expand CI coverage with frontend type checks and targeted browser smoke tests.
- Document the admin generation workflow with screenshots from a local demo instance.
- Add smaller service-level tests around prompt construction and validation edge cases.

## Later

- Add OpenAI models as an alternative generation backend alongside the existing Gemini integration.
- Provide a demo mode with fully synthetic content and disabled external AI calls.
- Improve import/export tooling for legally redistributable public content packs.
- Add more explicit review states for AI-generated tasks.
- Document deployment recipes for common Laravel hosting targets.

## Contribution guidelines

Contributions should keep public fixtures synthetic unless redistribution rights are explicit. Do not add proprietary exam pages, extracted book examples, private datasets, generated audio from private runs, or real service credentials.
