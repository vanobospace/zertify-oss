<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_audio_assets', function (Blueprint $table) {
            $table->string('transcript_hash', 64)->nullable()->after('original_name');
            $table->json('generation_metadata')->nullable()->after('transcript_hash');
            $table->timestamp('generated_at')->nullable()->after('generation_metadata');
        });
    }

    public function down(): void
    {
        Schema::table('question_audio_assets', function (Blueprint $table) {
            $table->dropColumn([
                'transcript_hash',
                'generation_metadata',
                'generated_at',
            ]);
        });
    }
};
