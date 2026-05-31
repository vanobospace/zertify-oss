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
        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();     // User who solved it
            $table->foreignId('question_id')->constrained()->cascadeOnDelete(); // Question solved
            $table->integer('score');      // Score achieved
            $table->integer('max_score');  // Maximum possible score
            $table->json('user_answers')->nullable(); // Store the answers (what was selected)
            $table->timestamps(); // When solved
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_results');
    }
};
