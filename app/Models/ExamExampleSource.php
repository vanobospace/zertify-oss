<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamExampleSource extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_canonical_structure_source' => 'boolean',
            'is_generation_reference' => 'boolean',
            'do_not_publish_directly' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * @return HasMany<ExamExample, $this>
     */
    public function examples(): HasMany
    {
        return $this->hasMany(ExamExample::class, 'source_id');
    }
}
