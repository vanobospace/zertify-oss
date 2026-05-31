<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_example_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('source_key')->unique();
            $table->string('exam_family');
            $table->string('exam_code');
            $table->string('variant')->nullable();
            $table->string('level')->nullable();
            $table->string('source_type');
            $table->string('title');
            $table->string('author_or_publisher')->nullable();
            $table->string('source_path')->nullable();
            $table->string('language', 8)->default('de');
            $table->boolean('is_canonical_structure_source')->default(false);
            $table->boolean('is_generation_reference')->default(true);
            $table->boolean('do_not_publish_directly')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['exam_family', 'exam_code', 'variant', 'level'], 'exam_example_sources_exam_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_example_sources');
    }
};
