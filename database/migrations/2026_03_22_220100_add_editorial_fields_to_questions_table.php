<?php

use App\Models\Question;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('format')->nullable()->after('module_id')->index();
            $table->string('status')->default(Question::STATUS_DRAFT)->after('difficulty')->index();
            $table->string('generation_mode')->default(Question::GENERATION_MODE_MANUAL)->after('status');
            $table->string('source_label')->nullable()->after('content');
            $table->string('source_url')->nullable()->after('source_label');
            $table->text('source_notes')->nullable()->after('source_url');
            $table->string('audio_source_type')->nullable()->after('source_notes');
            $table->foreignId('question_audio_asset_id')->nullable()->after('audio_source_type')->constrained('question_audio_assets')->nullOnDelete();
            $table->string('audio_external_url')->nullable()->after('question_audio_asset_id');
        });

        $questions = DB::table('questions')
            ->join('modules', 'modules.id', '=', 'questions.module_id')
            ->select('questions.id', 'questions.is_active', 'modules.slug')
            ->get();

        foreach ($questions as $question) {
            $format = str_contains((string) $question->slug, 'teil-2') ? 'shared_pool' : 'per_gap';

            DB::table('questions')
                ->where('id', $question->id)
                ->update([
                    'format' => $format,
                    'status' => $question->is_active ? Question::STATUS_PUBLISHED : Question::STATUS_DRAFT,
                    'generation_mode' => Question::GENERATION_MODE_MANUAL,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('question_audio_asset_id');
            $table->dropColumn([
                'format',
                'status',
                'generation_mode',
                'source_label',
                'source_url',
                'source_notes',
                'audio_source_type',
                'audio_external_url',
            ]);
        });
    }
};
