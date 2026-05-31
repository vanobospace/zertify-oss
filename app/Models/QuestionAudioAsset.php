<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class QuestionAudioAsset extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'duration_seconds' => 'integer',
        'is_active' => 'boolean',
        'generation_metadata' => 'array',
        'generated_at' => 'datetime',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'question_audio_asset_id');
    }

    public function getDisplayNameAttribute(): string
    {
        if (filled($this->label)) {
            return (string) $this->label;
        }

        if (filled($this->original_name)) {
            return (string) $this->original_name;
        }

        return basename((string) $this->path);
    }

    public function getPublicUrlAttribute(): ?string
    {
        if (blank($this->path)) {
            return null;
        }

        $disk = (string) ($this->disk ?: 'public');
        $path = (string) $this->path;

        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        return Storage::disk($disk)->url($path);
    }
}
