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
        Schema::create('student_progress_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->unsignedInteger('total_quiz');
            $table->decimal('average_score', 8, 2);
            $table->unsignedInteger('best_score');
            $table->unsignedInteger('latest_score');
            $table->text('teacher_context')->nullable();
            $table->longText('analysis');
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
            $table->index(['teacher_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_progress_analyses');
    }
};
