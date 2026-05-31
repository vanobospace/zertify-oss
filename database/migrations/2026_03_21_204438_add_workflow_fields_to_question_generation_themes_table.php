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
        Schema::table('question_generation_themes', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('notes');
            $table->json('last_preview_payload')->nullable()->after('status');
            $table->timestamp('last_previewed_at')->nullable()->after('last_preview_payload');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_generation_themes', function (Blueprint $table) {
            $table->dropColumn(['status', 'last_preview_payload', 'last_previewed_at']);
        });
    }
};
