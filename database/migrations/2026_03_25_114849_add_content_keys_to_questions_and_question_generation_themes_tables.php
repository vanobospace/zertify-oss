<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('content_key')->nullable()->after('seed_key');
            $table->unique('content_key');
        });

        Schema::table('question_generation_themes', function (Blueprint $table) {
            $table->string('content_key')->nullable()->after('module_slug');
            $table->unique('content_key');
        });
    }

    public function down(): void
    {
        Schema::table('question_generation_themes', function (Blueprint $table) {
            $table->dropUnique(['content_key']);
            $table->dropColumn('content_key');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique(['content_key']);
            $table->dropColumn('content_key');
        });
    }
};
