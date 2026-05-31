<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->decimal('score', 5, 1)->change();
            $table->decimal('max_score', 5, 1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->integer('score')->change();
            $table->integer('max_score')->change();
        });
    }
};
