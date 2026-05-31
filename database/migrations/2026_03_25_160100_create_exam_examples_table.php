<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_examples', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->constrained('exam_example_sources')->cascadeOnDelete();
            $table->string('example_key')->unique();
            $table->string('exam_family');
            $table->string('exam_code');
            $table->string('variant')->nullable();
            $table->string('level')->nullable();
            $table->string('module_slug');
            $table->string('part_key')->nullable();
            $table->string('task_shape');
            $table->string('source_type');
            $table->string('source_title');
            $table->string('source_author_or_publisher')->nullable();
            $table->string('source_path')->nullable();
            $table->unsignedInteger('source_page_from')->nullable();
            $table->unsignedInteger('source_page_to')->nullable();
            $table->string('language', 8)->default('de');
            $table->boolean('is_canonical_structure_source')->default(false);
            $table->boolean('is_generation_reference')->default(true);
            $table->string('title');
            $table->longText('raw_text');
            $table->longText('search_text');
            $table->json('normalized_payload');
            $table->json('editorial_notes')->nullable();
            $table->string('rights_note')->nullable();
            $table->json('tags')->nullable();
            $table->string('corpus_hash', 64);
            $table->timestamps();

            $table->index(['exam_family', 'exam_code', 'variant', 'level'], 'exam_examples_exam_idx');
            $table->index(['module_slug', 'part_key', 'task_shape'], 'exam_examples_module_idx');
            $table->index(['is_generation_reference', 'is_canonical_structure_source'], 'exam_examples_flags_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_examples');
    }
};
