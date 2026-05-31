<?php

namespace App\Models;

use App\Support\ListeningTeilOneSegmentedContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    public const COMPREHENSION_PART_MAX_POINTS = 25.0;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const GENERATION_MODE_MANUAL = 'manual';

    public const GENERATION_MODE_AI_DRAFT = 'ai_draft';

    public const GENERATION_MODE_AI_EDITED = 'ai_edited';

    public const GENERATION_MODE_AI_GENERATING = 'ai_generating';

    public const GENERATION_MODE_AI_AUDIO_GENERATING = 'ai_audio_generating';

    public const AUDIO_SOURCE_EXTERNAL = 'external';

    public const AUDIO_SOURCE_ASSET = 'asset';

    public const AUDIO_VOICE_PRESET_NEWS_MALE = 'news_male';

    public const AUDIO_VOICE_PRESET_NEWS_FEMALE = 'news_female';

    public const AUDIO_VOICE_PRESET_NEUTRAL_MALE = 'neutral_male';

    public const AUDIO_VOICE_PRESET_NEUTRAL_FEMALE = 'neutral_female';

    public const AUDIO_VOICE_PRESET_ANCHOR_FEMALE = 'anchor_female';

    public const AUDIO_VOICE_PRESET_ANCHOR_MALE = 'anchor_male';

    public const AUDIO_VOICE_PRESET_REPORTER_FEMALE = 'reporter_female';

    public const AUDIO_VOICE_PRESET_REPORTER_MALE = 'reporter_male';

    public const AUDIO_VOICE_PRESET_DIALOG_MF = 'dialog_mf';

    public const AUDIO_VOICE_PRESET_DIALOG_FM = 'dialog_fm';

    public const AUDIO_VOICE_PRESET_DIALOG_MM = 'dialog_mm';

    public const AUDIO_VOICE_PRESET_DIALOG_FF = 'dialog_ff';

    public const AUDIO_STYLE_PRESET_CLEAN = 'clean';

    public const AUDIO_STYLE_PRESET_NEWS_POLISH = 'news_polish';

    public const AUDIO_STYLE_PRESET_RADIO_LIGHT = 'radio_light';

    public const AUDIO_STYLE_PRESET_RADIO_HEAVY = 'radio_heavy';

    public const AUDIO_STYLE_PRESET_PHONE_HOTLINE = 'phone_hotline';

    public const AUDIO_STYLE_PRESET_PODCAST_WARM = 'podcast_warm';

    public const AUDIO_STYLE_PRESET_FM_CLEAN = 'fm_clean';

    public const AUDIO_STYLE_PRESET_PA_SPEAKER = 'pa_speaker';

    public const AUDIO_STYLE_PRESET_ROOM_LIGHT = 'room_light';

    // Allow filling all fields (avoid fillable for each)
    protected $guarded = [];

    // MAGIC HERE:
    // Tell Laravel: "content field is an array, unpack it automatically".
    protected $casts = [
        'content' => 'array',
        'points' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(QuestionAudioAsset::class, 'question_audio_asset_id');
    }

    /**
     * @return HasMany<TestResult, Question>
     */
    public function testResults(): HasMany
    {
        return $this->hasMany(TestResult::class);
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PUBLISHED => 'Published',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function generationModeOptions(): array
    {
        return [
            self::GENERATION_MODE_MANUAL => 'Manual',
            self::GENERATION_MODE_AI_DRAFT => 'AI draft',
            self::GENERATION_MODE_AI_EDITED => 'AI edited',
            self::GENERATION_MODE_AI_GENERATING => 'AI generating',
            self::GENERATION_MODE_AI_AUDIO_GENERATING => 'AI audio generating',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function audioSourceOptions(): array
    {
        return [
            self::AUDIO_SOURCE_EXTERNAL => 'External URL',
            self::AUDIO_SOURCE_ASSET => 'Uploaded asset',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function audioVoicePresetOptions(): array
    {
        return [
            self::AUDIO_VOICE_PRESET_NEWS_MALE => 'News Male',
            self::AUDIO_VOICE_PRESET_NEWS_FEMALE => 'News Female',
            self::AUDIO_VOICE_PRESET_NEUTRAL_MALE => 'Neutral Male',
            self::AUDIO_VOICE_PRESET_NEUTRAL_FEMALE => 'Neutral Female',
            self::AUDIO_VOICE_PRESET_ANCHOR_FEMALE => 'Anchor Female',
            self::AUDIO_VOICE_PRESET_ANCHOR_MALE => 'Anchor Male',
            self::AUDIO_VOICE_PRESET_REPORTER_FEMALE => 'Reporter Female',
            self::AUDIO_VOICE_PRESET_REPORTER_MALE => 'Reporter Male',
            self::AUDIO_VOICE_PRESET_DIALOG_MF => 'Dialog M/F',
            self::AUDIO_VOICE_PRESET_DIALOG_FM => 'Dialog F/M',
            self::AUDIO_VOICE_PRESET_DIALOG_MM => 'Dialog M/M',
            self::AUDIO_VOICE_PRESET_DIALOG_FF => 'Dialog F/F',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function audioStylePresetOptions(): array
    {
        return [
            self::AUDIO_STYLE_PRESET_CLEAN => 'Clean',
            self::AUDIO_STYLE_PRESET_NEWS_POLISH => 'News Polish',
            self::AUDIO_STYLE_PRESET_RADIO_LIGHT => 'Radio Light',
            self::AUDIO_STYLE_PRESET_RADIO_HEAVY => 'Radio Heavy',
            self::AUDIO_STYLE_PRESET_PHONE_HOTLINE => 'Phone Hotline',
            self::AUDIO_STYLE_PRESET_PODCAST_WARM => 'Podcast Warm',
            self::AUDIO_STYLE_PRESET_FM_CLEAN => 'FM Clean',
            self::AUDIO_STYLE_PRESET_PA_SPEAKER => 'PA Speaker',
            self::AUDIO_STYLE_PRESET_ROOM_LIGHT => 'Room Light',
        ];
    }

    /**
     * Attribute: Calculate gaps count automatically
     */
    public function getGapsCountAttribute(): int
    {
        if (empty($this->content['text'])) {
            return 0;
        }

        preg_match_all('/{{gap_\d+}}/', $this->content['text'], $matches);

        return count($matches[0]);
    }

    /**
     * Attribute: Calculate scorable answer count for any task type.
     */
    public function getAnswerCountAttribute(): int
    {
        $correct = $this->content['correct'] ?? null;

        if (is_array($correct) && $correct !== []) {
            return count($correct);
        }

        return $this->gaps_count;
    }

    public function usesFixedComprehensionScoring(): bool
    {
        return in_array($this->resolveFormat(), [
            'reading_matching_headlines',
            'reading_article_mc',
            'reading_situations_matching',
            'listening_segmented_true_false',
            'listening_short_true_false',
            'listening_long_true_false',
        ], true);
    }

    public function getPointsPerAnswerAttribute(): float
    {
        if ($this->usesFixedComprehensionScoring()) {
            $answerCount = max($this->answer_count, 1);

            return self::COMPREHENSION_PART_MAX_POINTS / $answerCount;
        }

        return (float) ($this->module->default_points ?? 1.0);
    }

    /**
     * Attribute: Maximum points for the WHOLE task (Price * Gaps Count)
     */
    public function getTotalMaxPointsAttribute(): float|int
    {
        if ($this->usesFixedComprehensionScoring()) {
            return self::COMPREHENSION_PART_MAX_POINTS;
        }

        return $this->answer_count * $this->points_per_answer;
    }

    public function resolveFormat(): ?string
    {
        if (is_string($this->format) && $this->format !== '') {
            return $this->format;
        }

        $contentFormat = $this->content['format'] ?? null;

        if (is_string($contentFormat) && $contentFormat !== '') {
            return $contentFormat;
        }

        return $this->module?->slug !== null && str_contains($this->module->slug, 'teil-2')
            ? 'shared_pool'
            : 'per_gap';
    }

    public function usesListeningAudio(): bool
    {
        return in_array($this->resolveFormat(), [
            'listening_segmented_true_false',
            'listening_short_true_false',
            'listening_long_true_false',
        ], true);
    }

    public function resolveCatalogKey(): ?string
    {
        if (filled($this->seed_key)) {
            return (string) $this->seed_key;
        }

        if (filled($this->content_key)) {
            return (string) $this->content_key;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedContent(): array
    {
        $content = is_array($this->content) ? $this->content : [];

        if (! $this->usesListeningAudio()) {
            return $content;
        }

        $resolvedAudioUrl = $this->resolveAudioUrl();
        $audio = is_array($content['audio'] ?? null) ? $content['audio'] : [];

        if ($resolvedAudioUrl === null) {
            unset($audio['url']);
            $content['audio'] = $audio;

            return $content;
        }

        $audio['url'] = $resolvedAudioUrl;
        $content['audio'] = $audio;

        return $content;
    }

    public function resolveAudioUrl(): ?string
    {
        if ($this->audio_source_type === self::AUDIO_SOURCE_ASSET && $this->audioAsset !== null) {
            if ($this->hasStaleListeningAudioAsset()) {
                return null;
            }

            return $this->audioAsset->public_url;
        }

        if ($this->audio_source_type === self::AUDIO_SOURCE_EXTERNAL && filled($this->audio_external_url)) {
            return (string) $this->audio_external_url;
        }

        $embeddedAudioUrl = $this->content['audio']['url'] ?? null;

        return is_string($embeddedAudioUrl) && $embeddedAudioUrl !== '' ? $embeddedAudioUrl : null;
    }

    public function resolveListeningTranscriptForAudio(): ?string
    {
        if (! $this->usesListeningAudio()) {
            return null;
        }

        $content = is_array($this->content) ? $this->content : [];

        if ($this->resolveFormat() === ListeningTeilOneSegmentedContent::FORMAT) {
            $content = ListeningTeilOneSegmentedContent::normalize($content);
        }

        $transcript = trim((string) ($content['transcript'] ?? ''));

        return $transcript !== '' ? $transcript : null;
    }

    public function currentListeningTranscriptHash(): ?string
    {
        $transcript = $this->resolveListeningTranscriptForAudio();

        if ($transcript === null) {
            return null;
        }

        return hash('sha256', $transcript);
    }

    public function hasFreshListeningAudioAsset(): bool
    {
        if (! $this->usesListeningAudio() || $this->audio_source_type !== self::AUDIO_SOURCE_ASSET || $this->audioAsset === null) {
            return false;
        }

        $assetTranscriptHash = trim((string) ($this->audioAsset->transcript_hash ?? ''));

        if ($assetTranscriptHash === '') {
            return true;
        }

        return hash_equals($assetTranscriptHash, (string) $this->currentListeningTranscriptHash());
    }

    public function hasStaleListeningAudioAsset(): bool
    {
        if (! $this->usesListeningAudio() || $this->audio_source_type !== self::AUDIO_SOURCE_ASSET || $this->audioAsset === null) {
            return false;
        }

        $assetTranscriptHash = trim((string) ($this->audioAsset->transcript_hash ?? ''));

        if ($assetTranscriptHash === '') {
            return false;
        }

        return ! hash_equals($assetTranscriptHash, (string) $this->currentListeningTranscriptHash());
    }
}
