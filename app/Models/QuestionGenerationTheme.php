<?php

namespace App\Models;

use Database\Factories\QuestionGenerationThemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionGenerationTheme extends Model
{
    /** @use HasFactory<QuestionGenerationThemeFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_APPROVED = 'approved';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'last_preview_payload' => 'array',
        'last_previewed_at' => 'datetime',
    ];

    public function resolveCatalogKey(): ?string
    {
        if (filled($this->content_key)) {
            return (string) $this->content_key;
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_APPROVED => 'Approved',
        ];
    }
}
