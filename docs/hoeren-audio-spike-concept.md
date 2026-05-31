# Hören Audio Spike Concept

## Goal

The goal of this spike is not to build a final production-grade speech studio.

The goal is to answer one practical product question:

Can synthetic audio sound natural enough to train `Hören` effectively in Zertify?

This is an experiment-first phase:

- no YouTube ingestion
- no dependency on human voice actors
- no automatic learner-facing publishing
- no promise of "indistinguishable from a human"
- focus on "natural enough for exam practice"

## Product conclusion

The agreed product direction is:

- `Hören` should be trained with audio that feels natural enough for practice
- it does **not** need to be indistinguishable from a live person
- the system should stay fast, cheap, and scalable
- the first viable path is `synthetic-first`, not `human-recording-first`

This means the product should optimize for:

- clear comprehension
- stable pacing
- exam-fit structure
- audible evidence for answers
- repeatable content production

not for:

- cinematic realism
- studio-level voice acting
- expensive manual production

## Why not YouTube as the main strategy

Using YouTube as the core source of listening content is a weak product strategy.

Problems:

- legal risk around reuse/downloading
- poor control over difficulty and structure
- poor alignment between audio and answer logic
- transcripts are often noisy or inconsistent
- content is not designed around exam tasks
- distractors and evidence have to be reverse-engineered after the fact

YouTube can still be useful as:

- a reference source
- a benchmark for pacing and naturalness
- inspiration for task patterns

but not as the core ingestion pipeline for Zertify.

## Core content model

Listening content should remain `script-first`.

The system should generate a full structured draft before synthesizing audio:

- `audio.title`
- `audio_notes`
- `transcript`
- `statements`
- `correct`
- `explanation`

This keeps task logic under control before speech generation happens.

## Recommended architecture

Two different AI layers should be used:

### 1. LLM layer

Purpose:

- generate the listening task
- generate the transcript/script
- generate the true/false statements
- generate the correct answers
- generate explanations and evidence

Recommended current choice:

- `Gemini`

Reason:

- it is already integrated into the project
- it can stay responsible for the structured task payload

### 2. TTS layer

Purpose:

- turn the transcript into playable audio

Recommended first provider:

- `Google Cloud Text-to-Speech`

Recommended backup / comparison provider:

- `OpenAI TTS`

Reason:

- Google Cloud is a stronger starting point for low-cost experimentation
- OpenAI is useful for direct quality comparison if needed

## Provider decision

### Primary

`Google Cloud TTS`

Why:

- strong German support
- better experimentation economics
- trial/free path is better suited for early spikes
- supports natural-sounding modern voices

### Secondary

`OpenAI TTS`

Why:

- useful as a quality benchmark
- relatively simple API
- worth testing on the same transcripts if Google output feels weak

### Not primary for this spike

`Groq`

Why not:

- not the strongest current candidate for German TTS in this use case
- better treated as optional future tooling, not the default voice pipeline

## Format strategy

`Hören` parts should not be treated as the same production problem.

### Hören Teil 1

Format:

- short bulletin / local news / service updates
- 5 true/false statements
- one voice is enough

Assessment:

- best candidate for synthetic audio
- should be first to validate

### Hören Teil 3

Format:

- short practical info blocks / culture / service / announcements
- 5 true/false statements
- one voice is enough

Assessment:

- also a good synthetic-first candidate
- similar risk profile to Teil 1

### Hören Teil 2

Format:

- long report or interview
- 10 true/false statements
- either:
  - one-voice report / radio feature
  - two-voice moderator + guest

Assessment:

- highest realism risk
- hardest format for TTS to make convincing
- should be tested in both one-voice and two-voice variants

## Recommended experimental pipeline

### Stage 1: Generate draft content

Generate:

- transcript
- audio title
- audio notes
- statements
- answers
- explanations

Output is a listening draft question in the current Zertify schema.

### Stage 2: Synthesize audio

Take the transcript and synthesize audio with the selected provider.

Store the output as a `QuestionAudioAsset`.

Attach the asset to the question.

### Stage 3: Human listening QA

For the spike, audio must be listened to manually in admin.

Checklist:

- clear pronunciation
- acceptable pacing
- no broken prosody
- transcript roughly matches what is spoken
- answer evidence is clearly audible

### Stage 4: Keep or reject

If the sample sounds good enough:

- keep it attached to the draft

If not:

- regenerate content
- or regenerate audio with another provider/voice

## First benchmark set

Generate and review:

- 3 samples for `Teil 1`
- 3 samples for `Teil 3`
- 2 samples for `Teil 2` one-voice
- 2 samples for `Teil 2` two-voice

This should be enough to decide whether synthetic-first listening is viable.

## Technical implementation view

### Scope for the spike

Add one internal admin-only experiment flow:

- choose an existing listening draft
- synthesize audio from the transcript
- save it as an audio asset
- attach it to the question

This should **not** become a learner-facing auto-publish flow yet.

### Config

Add one speech config block with:

- default provider
- storage disk
- provider-specific credentials
- default voice settings

### Services

Implement a listening audio synthesis service that:

- reads the current question transcript
- selects the configured provider
- requests TTS generation
- stores the generated file
- creates a `QuestionAudioAsset`
- attaches it to the question

### Admin action

Add one admin action for listening drafts:

- `Generate audio`

Behavior:

- available only for listening formats
- available only when a transcript is present
- creates and attaches an audio asset
- shows success/error notification

## Success criteria

The spike is successful if:

- `Teil 1` sounds good enough with synthetic audio
- `Teil 3` sounds good enough with synthetic audio
- `Teil 2` has at least one acceptable mode:
  - one voice
  - or two voices
- generated audio attaches correctly through the current asset pipeline
- playback works in existing learner UI without special frontend work

## What this spike does not decide yet

This spike does not yet decide:

- final provider lock-in
- automatic mass generation
- learner-facing instant publishing
- whether every `Teil 2` should use two voices
- whether premium mock exams need a different audio quality tier

## Recommended next implementation order

1. Extend listening draft generation in the existing AI flow.
2. Add the speech provider config.
3. Implement the TTS synthesis service.
4. Add the admin action for listening drafts.
5. Generate the benchmark set and listen manually.
6. Decide whether `Teil 2` should stay one-voice, mixed, or be constrained further.

---

## Review & assessment

### Strengths

- **Clear product framing.** The spike answers one practical question — "is synthetic audio good enough for exam practice?" — without overcommitting to production scope. This prevents scope creep.
- **Script-first architecture.** Generating the full structured draft (transcript, statements, answers, evidence) before synthesizing audio gives full control over task quality before spending money on TTS. If the script is bad, no audio is wasted.
- **Format-aware prioritization.** Teil 1 and Teil 3 (single voice, short blocks) are the right starting candidates. Teil 2 (long, potentially two voices) is correctly deferred as the highest-risk format.
- **YouTube rejection is well-reasoned.** Legal risk, poor content-to-task alignment, and noisy transcripts make it a weak core strategy. Keeping it as a reference source only is correct.
- **Industry-standard approach.** Script-first + TTS is how Duolingo, Busuu, and most language edtech platforms scale audio content. This is not experimental — it is the proven path.

### Risks & gaps

- **Prompt engineering for listening is the hardest part.** The document mentions extending the AI flow but does not estimate the complexity. For listening, the LLM prompt must generate natural monologues/dialogues with embedded audible evidence for each statement. This is significantly harder than Sprachbausteine prompts and will likely require multiple iteration cycles.
- **Google Cloud TTS vs Gemini TTS confusion.** The document says "Google Cloud TTS" but the existing config uses `gemini-2.5-flash-tts` as the model — these are different products with different APIs, pricing, and quality characteristics. This needs to be resolved before implementation.
- **Two-voice Teil 2 is a separate engineering problem.** Multi-speaker synthesis requires either SSML role markup (classic Google TTS), two separate API calls with audio concatenation, or a multi-speaker model. This should be treated as its own mini-spike, not bundled with the initial experiment.
- **No duration control strategy.** Teil 1 is ~150 words / 2-3 minutes, Teil 2 is ~400-500 words / 5-7 minutes. Long transcripts may produce TTS artifacts or hit API limits. The pipeline should enforce length constraints or implement chunking.
- **Manual QA is fine for 10 samples but does not scale.** If the spike succeeds, a semi-automated QA step (e.g. STT back-transcription + diff against original transcript) should be considered for the next phase.

### Alternative approaches considered

| Approach | Pros | Cons | Verdict |
|---|---|---|---|
| **Human recordings** | Maximum quality, real exam feel | Expensive, slow, does not scale | Overkill for a training platform |
| **Audio-first (YouTube/podcasts → tasks)** | Real speech, diverse accents | Content not exam-aligned, legal risk, evidence is reverse-engineered | Weak product strategy, correctly rejected |
| **Script-first + TTS (this plan)** | Full content control, cheap, scalable | Synthetic sound, prompt complexity | Best fit for Zertify's goals |

### Conclusion

The approach is sound and follows industry best practices for edtech audio content. The main risk is not in the architecture but in **prompt engineering quality** — the listening draft prompt must produce transcripts with natural flow and clearly audible evidence for each true/false statement. TTS is a commodity layer that will work; the differentiator is task quality.

**Recommendation:** Start with Teil 1 only, 3 samples, single voice. Validate prompt quality and TTS naturalness. If successful — extend to Teil 3, then Teil 2 one-voice. Treat Teil 2 two-voice as a separate spike.
