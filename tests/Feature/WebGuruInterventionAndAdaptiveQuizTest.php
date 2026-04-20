<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Student;
use App\Models\StudentInterventionPlan;
use App\Models\StudentQuizResult;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebGuruInterventionAndAdaptiveQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_can_create_update_and_delete_intervention_plan(): void
    {
        [$guruUser, $teacher] = $this->createGuruUserAndProfile();
        [$siswaUser, $student] = $this->createSiswaUserAndProfile();

        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Quiz Intervensi',
            'description' => 'Quiz untuk progress siswa.',
        ]);

        StudentQuizResult::create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'score' => 5,
        ]);

        $this->actingAs($guruUser);

        $this->get(route('guru.students.progress', $student->id))
            ->assertOk()
            ->assertSee('Workflow Tindak Lanjut Guru');

        $this->post(route('guru.students.interventions.store', $student->id), [
            'title' => 'Pendampingan HTML Mingguan',
            'risk_level' => 'sedang',
            'action_items' => 'Latihan form dan validasi setiap hari.',
            'target_score' => 75,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'belum_mulai',
            'notes' => 'Mulai dari konsep dasar.',
        ])->assertRedirect(route('guru.students.progress', $student->id));

        $plan = StudentInterventionPlan::query()
            ->where('teacher_id', $teacher->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $this->assertDatabaseHas('student_intervention_plans', [
            'id' => $plan->id,
            'title' => 'Pendampingan HTML Mingguan',
            'status' => 'belum_mulai',
        ]);

        $this->put(route('guru.interventions.update', $plan->id), [
            'title' => 'Pendampingan HTML Mingguan',
            'risk_level' => 'sedang',
            'action_items' => 'Latihan form dan validasi setiap hari.',
            'target_score' => 80,
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'berjalan',
            'notes' => 'Monitoring setiap 2 hari.',
        ])->assertRedirect(route('guru.students.progress', $student->id));

        $this->assertDatabaseHas('student_intervention_plans', [
            'id' => $plan->id,
            'status' => 'berjalan',
            'target_score' => 80,
        ]);

        $this->delete(route('guru.interventions.destroy', $plan->id))
            ->assertRedirect(route('guru.students.progress', $student->id));

        $this->assertDatabaseMissing('student_intervention_plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_siswa_quiz_detail_orders_questions_adaptively(): void
    {
        [$guruUser, $teacher] = $this->createGuruUserAndProfile('guru.adaptive@example.test');
        [$siswaUser, $student] = $this->createSiswaUserAndProfile('siswa.adaptive@example.test');

        $quizHtml = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'HTML Dasar Form',
            'description' => 'Latihan elemen html dan form',
        ]);

        $quizJs = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'JavaScript DOM Event',
            'description' => 'Latihan manipulasi dom',
        ]);

        $targetQuiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Target Adaptive Quiz',
            'description' => 'Campuran topik.',
        ]);

        StudentQuizResult::create([
            'student_id' => $student->id,
            'quiz_id' => $quizHtml->id,
            'score' => 2,
        ]);

        StudentQuizResult::create([
            'student_id' => $student->id,
            'quiz_id' => $quizJs->id,
            'score' => 9,
        ]);

        StudentQuizResult::create([
            'student_id' => $student->id,
            'quiz_id' => $targetQuiz->id,
            'score' => 7,
        ]);

        Question::create([
            'quiz_id' => $targetQuiz->id,
            'question_text' => 'Q1 Menengah HTML',
            'option_a' => 'A1',
            'option_b' => 'B1',
            'option_c' => 'C1',
            'option_d' => 'D1',
            'correct_answer' => 'A',
            'difficulty_level' => 'menengah',
            'competency_key' => 'html_semantic',
        ]);

        Question::create([
            'quiz_id' => $targetQuiz->id,
            'question_text' => 'Q2 Menengah JS',
            'option_a' => 'A2',
            'option_b' => 'B2',
            'option_c' => 'C2',
            'option_d' => 'D2',
            'correct_answer' => 'B',
            'difficulty_level' => 'menengah',
            'competency_key' => 'javascript_dom',
        ]);

        Question::create([
            'quiz_id' => $targetQuiz->id,
            'question_text' => 'Q3 Dasar HTML',
            'option_a' => 'A3',
            'option_b' => 'B3',
            'option_c' => 'C3',
            'option_d' => 'D3',
            'correct_answer' => 'C',
            'difficulty_level' => 'dasar',
            'competency_key' => 'html_semantic',
        ]);

        Question::create([
            'quiz_id' => $targetQuiz->id,
            'question_text' => 'Q4 Lanjut HTML',
            'option_a' => 'A4',
            'option_b' => 'B4',
            'option_c' => 'C4',
            'option_d' => 'D4',
            'correct_answer' => 'D',
            'difficulty_level' => 'lanjut',
            'competency_key' => 'html_semantic',
        ]);

        $this->actingAs($siswaUser);

        $response = $this->get(route('siswa.quizzes.show', $targetQuiz->id));

        $response->assertOk();
        $response->assertSee('Fokus kompetensi saat ini');
        $response->assertSeeInOrder([
            'Q1 Menengah HTML',
            'Q2 Menengah JS',
            'Q3 Dasar HTML',
            'Q4 Lanjut HTML',
        ]);
    }

    private function createGuruUserAndProfile(string $email = 'guru.intervention@example.test'): array
    {
        $teacher = Teacher::create([
            'name' => 'Guru Test',
            'email' => $email,
            'password' => Hash::make('password123'),
        ]);

        $user = User::create([
            'name' => 'Guru Test',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => 'guru',
        ]);

        return [$user, $teacher];
    }

    private function createSiswaUserAndProfile(string $email = 'siswa.intervention@example.test'): array
    {
        $student = Student::create([
            'name' => 'Siswa Test',
            'email' => $email,
            'password' => Hash::make('password123'),
        ]);

        $user = User::create([
            'name' => 'Siswa Test',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => 'siswa',
        ]);

        return [$user, $student];
    }
}
