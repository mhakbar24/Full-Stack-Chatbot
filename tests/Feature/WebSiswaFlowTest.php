<?php

namespace Tests\Feature;

use App\Models\Materi;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Student;
use App\Models\StudentMateriLog;
use App\Models\StudentQuizResult;
use App\Models\Teacher;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebSiswaFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->app->instance(GeminiService::class, new class extends GeminiService {
            public function generateText(string $prompt, ?string $systemInstruction = null, bool $forceJson = false): ?string
            {
                return 'Ringkasan: Fokus ulang konsep inti. Kekuatan: Konsistensi belajar sudah baik. Perlu Ditingkatkan: Perlu latihan terarah. Tindak Lanjut: Kerjakan latihan kecil hari ini.';
            }
        });
    }

    public function test_siswa_dashboard_materi_quiz_and_chatbot_flow(): void
    {
        [$user, $student] = $this->createSiswaUserAndProfile();
        $teacher = Teacher::create([
            'name' => 'Guru Flow',
            'email' => 'guru.flow@example.test',
            'password' => Hash::make('password123'),
        ]);

        $materi = Materi::create([
            'teacher_id' => $teacher->id,
            'title' => 'HTML Form Dasar',
            'category' => 'Frontend',
            'description' => 'Dasar form dan validasi input.',
            'image' => null,
        ]);

        StudentMateriLog::create([
            'student_id' => $student->id,
            'materi_id' => $materi->id,
            'accessed_at' => now()->subDay(),
        ]);

        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Quiz HTML Dasar',
            'description' => 'Evaluasi konsep form.',
        ]);

        Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Tag untuk form adalah?',
            'option_a' => '<form>',
            'option_b' => '<table>',
            'option_c' => '<input>',
            'option_d' => '<label>',
            'correct_answer' => 'A',
            'difficulty_level' => 'dasar',
            'competency_key' => 'html_semantic',
        ]);

        StudentQuizResult::create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'score' => 8,
        ]);

        $this->actingAs($user);

        $this->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('Chatbot Belajar Siswa');

        $this->get(route('siswa.materis'))
            ->assertOk()
            ->assertSee('Daftar Materi');

        $this->get(route('siswa.materis.show', $materi->id))
            ->assertOk()
            ->assertSee('Chatbot Pendamping Materi');

        $this->post(route('siswa.materis.chatbot', $materi->id), [
            'message' => 'Ringkas inti materi ini.',
        ])->assertRedirect(route('siswa.materis.show', $materi->id))
            ->assertSessionHas('chatbot_success')
            ->assertSessionHas('siswa_materi_chat_history_' . $materi->id);

        $this->post(route('siswa.chatbot'), [
            'message' => 'Apa latihan kecil untuk saya hari ini?',
        ])->assertRedirect(route('siswa.dashboard'))
            ->assertSessionHas('chatbot_success')
            ->assertSessionHas('siswa_chatbot_history');

        $this->get(route('siswa.quizzes'))
            ->assertOk()
            ->assertSee('Daftar Quiz');
    }

    private function createSiswaUserAndProfile(): array
    {
        $email = 'siswa.flow@example.test';

        $student = Student::create([
            'name' => 'Siswa Flow',
            'email' => $email,
            'password' => Hash::make('password123'),
        ]);

        $user = User::create([
            'name' => 'Siswa Flow',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => 'siswa',
        ]);

        return [$user, $student];
    }
}
