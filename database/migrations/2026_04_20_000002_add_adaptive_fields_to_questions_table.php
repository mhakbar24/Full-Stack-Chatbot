<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('difficulty_level', 20)->default('menengah')->after('correct_answer');
            $table->string('competency_key', 100)->nullable()->after('difficulty_level');

            $table->index('difficulty_level');
            $table->index('competency_key');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['difficulty_level']);
            $table->dropIndex(['competency_key']);
            $table->dropColumn(['difficulty_level', 'competency_key']);
        });
    }
};
