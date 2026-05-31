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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            // Relationship with exams table. If exam is deleted, modules will be deleted as well.
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();

            $table->string('name'); // "Sprachbausteine Teil 1"
            $table->string('slug'); // "sprachbausteine-teil-1"
            $table->string('type'); // "gap_fill", "reading"...
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
