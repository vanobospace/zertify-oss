# B2 Allgemein Content Contracts

This document defines the task data shapes for `B2 Allgemein` modules in Zertify.

## Scope

These contracts cover:

- `Sprachbausteine Teil 1`: gap-fill with 3 options per gap
- `Sprachbausteine Teil 2`: gap-fill with one shared pool of 15 options
- `Lesen Teil 1`: 5 texts + 10 headings, matching
- `Lesen Teil 2`: 1 article + 5 multiple-choice questions
- `Lesen Teil 3`: 10 situations + 12 short texts, matching with `X`
- `Hören Teil 1`: 5 short audio items, true/false
- `Hören Teil 2`: 1 long interview/story + 10 true/false statements
- `Hören Teil 3`: 5 short audio items, true/false

## Shared conventions

- `format` is the discriminator for the task renderer.
- All task content remains in German.
- UI chrome, review text, and explanations are localizable.
- `correct` always maps stable item ids to a stable answer id/value.
- `explanation` stores per-item review content.
- `explanation_translations` caches translated review content by UI locale.
- `source` records editorial provenance for manual and AI-assisted content.
- `Sprachbausteine` uses gap ids like `gap_1`.
- `Lesen/Hören` uses item ids like `text_1`, `question_1`, `situation_1`, `statement_1`.
- `B2 Allgemein Lesen/Hören` uses fixed part scoring:
  each full task is worth `25` points, divided by the number of scorable items in that task.

## B2 Allgemein scoring rules

- `Lesen Teil 1` = `25 / 5 = 5` points per item
- `Lesen Teil 2` = `25 / 5 = 5` points per item
- `Lesen Teil 3` = `25 / 10 = 2.5` points per item
- `Hören Teil 1` = `25 / 5 = 5` points per item
- `Hören Teil 2` = `25 / 10 = 2.5` points per item
- `Hören Teil 3` = `25 / 5 = 5` points per item
- This scoring rule is specific to `B2 Allgemein Lesen/Hören` and does not replace the existing `Sprachbausteine` per-gap scoring.

## Shared support objects

### Source metadata

```json
{
    "label": "Internal Zertify seed set",
    "url": "",
    "notes": "AI-generated B2 Allgemein seed task."
}
```

### Comprehension explanation

```json
{
    "correct_answer": "heading_h",
    "reason": "Text 1 beschreibt sowohl gesundheitliche Vorteile als auch Risiken einer vegetarischen Ernährung.",
    "evidence": "Viele Studien zeigen Vorteile, zugleich wird vor Nährstoffmangel gewarnt.",
    "wrong_answer_reason": "Andere Überschriften greifen nur einen Teilaspekt auf und passen deshalb nicht vollständig.",
    "strategy_hint": "Suche nach dem Kerngedanken des ganzen Textes, nicht nach einzelnen Wörtern."
}
```

### Sprachbausteine explanation

```json
{
    "answer": "dass",
    "answer_translation": "что",
    "rule_type": "Konjunktion",
    "reason": "Hier folgt ein Nebensatz, daher steht das konjugierte Verb am Ende.",
    "pattern": "",
    "contrast": "„weil“ würde einen kausalen Zusammenhang ausdrücken, der hier nicht gemeint ist.",
    "example": "Ich denke, dass das stimmt."
}
```

## Implemented Sprachbausteine formats

The following formats are already implemented in the current Zertify codebase.

### Product length rules

- Do not reduce the current generation length baselines for `Sprachbausteine`.
- `Teil 1` keeps the current project minimum of `220` words.
- `Teil 2` keeps the current project minimum of `250` words.
- These are intentional product constraints based on real offline course and exam preparation experience, not arbitrary defaults.
- Shorter texts should not be treated as acceptable baseline generation output for `B2 Allgemein`.

## Format: `per_gap`

Used for `Sprachbausteine Teil 1`.

```json
{
    "text": "Sehr geehrte Damen und Herren, ... {{gap_1}} ... {{gap_10}} ...",
    "options": {
        "gap_1": ["gebucht", "gemacht", "begonnen"],
        "gap_2": ["hieß es", "stand es", "schrieb es"]
    },
    "correct": {
        "gap_1": "gebucht",
        "gap_2": "hieß es"
    },
    "explanation": {
        "gap_1": {
            "answer": "gebucht",
            "rule_type": "Kollokation",
            "reason": "Einen Kurs buchen ist die feste Verbindung.",
            "pattern": "",
            "contrast": "„machen“ klingt hier zu umgangssprachlich.",
            "example": "Ich habe den Intensivkurs im Juli gebucht."
        }
    },
    "explanation_translations": {}
}
```

Validation rules:

- exactly 10 gaps
- every gap has exactly 3 options
- every `correct` answer must exist inside the same gap option list
- `text` uses `{{gap_n}}` markers
- expected text style: letter / email
- generation baseline: target around `250` words, but never below `220`

## Format: `shared_pool`

Used for `Sprachbausteine Teil 2`.

```json
{
    "format": "shared_pool",
    "text": "Sachtext mit {{gap_1}} ... {{gap_10}} ...",
    "options_pool": [
        "dass",
        "ob",
        "weil",
        "jedoch",
        "daher",
        "obwohl",
        "wenn",
        "denn",
        "zwar",
        "dabei",
        "bereits",
        "indem",
        "sodass",
        "wobei",
        "als"
    ],
    "correct": {
        "gap_1": "dass",
        "gap_2": "ob"
    },
    "explanation": {
        "gap_1": {
            "answer": "dass",
            "rule_type": "Konjunktion",
            "reason": "Nach dem Verb folgt hier ein Nebensatz.",
            "pattern": "",
            "contrast": "„ob“ würde eine indirekte Entscheidungsfrage markieren.",
            "example": "Ich glaube, dass er heute kommt."
        }
    },
    "explanation_translations": {}
}
```

Validation rules:

- exactly 10 gaps
- exactly 15 shared-pool options
- every `correct` answer must exist in `options_pool`
- shared pool may contain distractors that stay unused
- `text` uses `{{gap_n}}` markers
- expected text style: article / Sachtext
- generation baseline: target around `300` words, but never below `250`

## Planned B2 Allgemein Lesen / Hören formats

## Format: `reading_matching_headlines`

Used for `Lesen Teil 1`.

```json
{
    "format": "reading_matching_headlines",
    "instructions": "Lesen Sie zuerst die zehn Überschriften. Lesen Sie dann die fünf Texte und entscheiden Sie, welche Überschrift am besten zu welchem Text passt.",
    "headings": [
        {
            "id": "heading_a",
            "label": "a",
            "text": "Vegetarische Ernährung – Eine gesunde Alternative?"
        }
    ],
    "texts": [
        {
            "id": "text_1",
            "title": "Text 1",
            "body": "Immer mehr Menschen entscheiden sich..."
        }
    ],
    "correct": {
        "text_1": "heading_a"
    },
    "explanation": {
        "text_1": {
            "correct_answer": "heading_a",
            "reason": "Der Text behandelt Vorteile und Risiken der vegetarischen Ernährung.",
            "evidence": "Er nennt gesundheitliche Vorteile und warnt zugleich vor möglichem Nährstoffmangel."
        }
    },
    "explanation_translations": {},
    "source": {
        "label": "Internal Zertify seed set",
        "url": ""
    }
}
```

Validation rules:

- exactly 10 headings
- exactly 5 texts
- exactly 5 `correct` mappings
- each text maps to one unique heading

## Format: `reading_article_mc`

Used for `Lesen Teil 2`.

```json
{
    "format": "reading_article_mc",
    "instructions": "Lesen Sie den Text und entscheiden Sie, welche Lösung a, b oder c richtig ist.",
    "article": {
        "title": "Handwerkliche Kaffeeröstereien in Deutschland",
        "body": "In den letzten Jahren hat sich..."
    },
    "questions": [
        {
            "id": "question_1",
            "prompt": "Warum kaufen viele Menschen lieber Kaffee in kleinen Röstereien?",
            "options": [
                {
                    "id": "option_a",
                    "label": "a",
                    "text": "Weil er meist günstiger ist."
                },
                {
                    "id": "option_b",
                    "label": "b",
                    "text": "Weil sie Qualität und Herkunft schätzen."
                },
                {
                    "id": "option_c",
                    "label": "c",
                    "text": "Weil Supermärkte keinen Kaffee mehr anbieten."
                }
            ]
        }
    ],
    "correct": {
        "question_1": "option_b"
    },
    "explanation": {
        "question_1": {
            "correct_answer": "option_b",
            "reason": "Im Text wird die Wertschätzung von Qualität und Transparenz betont.",
            "evidence": "Die Kundschaft achtet auf Herkunft, Röstung und Beratung."
        }
    },
    "explanation_translations": {},
    "source": {
        "label": "Internal Zertify seed set",
        "url": ""
    }
}
```

Validation rules:

- exactly 5 questions
- each question has exactly 3 options
- every `correct` answer points to an option in the same question

## Format: `reading_situations_matching`

Used for `Lesen Teil 3`.

```json
{
    "format": "reading_situations_matching",
    "instructions": "Lesen Sie die zehn Situationen und dann die zwölf Texte. Finden Sie für jede Situation den passenden Text. Markieren Sie X, wenn es keine Lösung gibt.",
    "situations": [
        {
            "id": "situation_1",
            "number": 1,
            "text": "Sie möchten umweltfreundlich einkaufen..."
        }
    ],
    "texts": [
        {
            "id": "text_a",
            "label": "a",
            "title": "Lastenräder in Linz",
            "body": "Ab März können kostenlos..."
        }
    ],
    "extra_answer": {
        "id": "no_match",
        "label": "X",
        "text": "Keine passende Lösung"
    },
    "correct": {
        "situation_1": "text_a"
    },
    "explanation": {
        "situation_1": {
            "correct_answer": "text_a",
            "reason": "Der Text beschreibt genau eine nachhaltige Lösung für größere Einkäufe ohne Auto.",
            "evidence": "Es geht um Lastenräder, Ausleihstationen und umweltfreundliches Einkaufen."
        }
    },
    "explanation_translations": {},
    "source": {
        "label": "Internal Zertify seed set",
        "url": ""
    }
}
```

Validation rules:

- exactly 10 situations
- exactly 12 texts
- `correct` may point to a text id or `no_match`
- texts may be unused

## Format: `listening_short_true_false`

Used for `Hören Teil 1` and `Hören Teil 3`.

```json
{
    "format": "listening_short_true_false",
    "instructions": "Sie hören die Nachrichten nur einmal. Entscheiden Sie, ob die Aussagen richtig oder falsch sind.",
    "audio": {
        "title": "Nachrichten aus verschiedenen Lebensbereichen",
        "url": "/storage/audio/b2-allgemein/hoeren-teil-1-example.mp3",
        "duration_seconds": 152,
        "audio_notes": "Kurze Nachrichtensendung, neutral gelesen"
    },
    "transcript": "Guten Morgen. Zuerst ein Blick auf die aktuellen Meldungen aus der Stadt und der Region. ...",
    "statements": [
        {
            "id": "statement_1",
            "number": 1,
            "text": "Die wirtschaftliche Lage wird pessimistisch eingeschätzt."
        }
    ],
    "correct": {
        "statement_1": "true"
    },
    "explanation": {
        "statement_1": {
            "correct_answer": "true",
            "reason": "Im Audio wird die Lage als sehr ernst und enttäuschend beschrieben.",
            "evidence": "Der Wirtschaftsklimaindex sinkt und Prognosen wurden gesenkt."
        }
    },
    "explanation_translations": {},
    "source": {
        "label": "Internal Zertify seed set",
        "url": ""
    }
}
```

Validation rules:

- exactly 5 statements
- every `correct` value is `true` or `false`
- `audio.title` is required
- `audio_notes` and `transcript` are optional but strongly recommended for editorial QA
- published tasks require a real audio source via uploaded asset or external URL

## Format: `listening_long_true_false`

Used for `Hören Teil 2`.

```json
{
    "format": "listening_long_true_false",
    "instructions": "Sie hören ein Radiointerview. Entscheiden Sie, ob die Aussagen richtig oder falsch sind.",
    "audio": {
        "title": "Nora Feldmann – Schreiben, Erinnern, Verstehen",
        "url": "/storage/audio/b2-allgemein/hoeren-teil-2-example.mp3",
        "duration_seconds": 428,
        "audio_notes": "Radiointerview mit Moderatorin und Gast"
    },
    "transcript": "Moderatorin: Willkommen im Studio. Heute sprechen wir mit Nora Feldmann uber Schreiben und Erinnern. ...",
    "context": {
        "speaker": "Radiointerview",
        "replay_limit": 1
    },
    "statements": [
        {
            "id": "statement_1",
            "number": 1,
            "text": "Nora Feldmann freut sich über die Ehrung durch die Universität Utrecht."
        }
    ],
    "correct": {
        "statement_1": "true"
    },
    "explanation": {
        "statement_1": {
            "correct_answer": "true",
            "reason": "Sie sagt ausdrücklich, dass sie sich über die Ehrung freut.",
            "evidence": "Im Interview fällt die Formulierung „Natürlich freue ich mich sehr...“."
        }
    },
    "explanation_translations": {},
    "source": {
        "label": "Internal Zertify seed set",
        "url": ""
    }
}
```

Validation rules:

- exactly 10 statements
- every `correct` value is `true` or `false`
- `audio.title` is required
- `audio_notes` and `transcript` are optional but strongly recommended for editorial QA
- `context.speaker` and `context.replay_limit` are optional but useful
- published tasks require a real audio source via uploaded asset or external URL

## Answer storage convention

User answers should be stored as:

```json
{
    "text_1": "heading_a",
    "question_1": "option_b",
    "situation_1": "text_a",
    "statement_1": "true"
}
```

This keeps the scoring pipeline consistent across matching, MC, and true/false tasks.

For implemented Sprachbausteine tasks, user answers follow the same map pattern:

```json
{
    "gap_1": "gebucht",
    "gap_2": "hieß es"
}
```

## Next implementation step

After these contracts are accepted, the next step is:

1. add runtime validation in admin and backend
2. add learner renderers for each `format`
3. add admin forms and AI-generation prompts per format
