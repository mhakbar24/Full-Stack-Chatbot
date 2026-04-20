<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_intervention_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('risk_level', 20)->default('sedang');
            $table->string('title');
            $table->text('action_items')->nullable();
            $table->unsignedInteger('target_score')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('belum_mulai');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'student_id']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_intervention_plans');
    }
};
