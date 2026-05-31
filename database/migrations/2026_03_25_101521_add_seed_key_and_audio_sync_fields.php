<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('seed_key')->nullable()->after('module_id');
        });

        $seedKeyByModuleAndOrder = [
            'hoeren-teil-1' => [
                10 => 'hoeren-teil-1.reference.1',
                20 => 'hoeren-teil-1.reference.2',
            ],
            'hoeren-teil-2' => [
                10 => 'hoeren-teil-2.reference.1',
                20 => 'hoeren-teil-2.reference.2',
            ],
            'hoeren-teil-3' => [
                10 => 'hoeren-teil-3.reference.1',
                20 => 'hoeren-teil-3.reference.2',
            ],
        ];

        $questions = DB::table('questions')
            ->join('modules', 'modules.id', '=', 'questions.module_id')
            ->select('questions.id', 'questions.order', 'questions.seed_key', 'modules.slug')
            ->whereIn('modules.slug', array_keys($seedKeyByModuleAndOrder))
            ->whereNull('questions.seed_key')
            ->get();

        foreach ($questions as $question) {
            $seedKey = $seedKeyByModuleAndOrder[$question->slug][$question->order] ?? null;

            if ($seedKey === null) {
                continue;
            }

            DB::table('questions')
                ->where('id', $question->id)
                ->update(['seed_key' => $seedKey]);
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->unique('seed_key');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique(['seed_key']);
            $table->dropColumn('seed_key');
        });
    }
};
