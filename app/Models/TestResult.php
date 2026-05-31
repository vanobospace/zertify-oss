<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_id',
        'score',
        'max_score',
        'user_answers',
    ];

    protected $casts = [
        'user_answers' => 'array', // Automatically cast JSON to array
    ];

    /**
     * Relationship: Result belongs to a User
     *
     * @return BelongsTo<User, TestResult>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Result belongs to a Question
     *
     * @return BelongsTo<Question, TestResult>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
