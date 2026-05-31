<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamExample extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_canonical_structure_source' => 'boolean',
            'is_generation_reference' => 'boolean',
            'normalized_payload' => 'array',
            'editorial_notes' => 'array',
            'tags' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ExamExampleSource, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ExamExampleSource::class, 'source_id');
    }
}
