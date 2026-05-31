<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('question_generation_themes', function (Blueprint $table) {
            $table->id();
            $table->string('exam_slug');
            $table->string('module_slug');
            $table->string('title');
            $table->text('prompt_seed');
            $table->string('source_label')->nullable();
            $table->string('source_url')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['exam_slug', 'module_slug', 'is_active']);
            $table->unique(['exam_slug', 'module_slug', 'title']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_generation_themes');
    }
};
