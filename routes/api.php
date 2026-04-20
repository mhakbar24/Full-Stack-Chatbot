<?php
use App\Http\Controllers\TeacherAuthController;
use App\Http\Controllers\MateriController;
use App\Http\Controllers\StudentAuthController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StudentQuizController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\StudentProgressController;
use Illuminate\Support\Facades\Route;


// Rute Publik untuk Registrasi & Login
Route::post('teacher/register', [TeacherAuthController::class, 'register']);
Route::post('teacher/login', [TeacherAuthController::class, 'login']);
Route::post('student/register', [StudentAuthController::class, 'register']);
Route::post('student/login', [StudentAuthController::class, 'login']);
Route::post('chat', [ChatController::class, 'send']);


Route::middleware('auth:sanctum')->group(function () {
    // Auth guru
    Route::get('teacher/profile', [TeacherAuthController::class, 'profile']);
    Route::get('teacher/siswa', [TeacherAuthController::class, 'getStudents']);
    Route::delete('teacher/siswa/{studentId}', [TeacherAuthController::class, 'destroyStudent']);
    Route::get('teacher/siswa/{studentId}/progress-overview', [StudentProgressController::class, 'overview']);
    Route::post('teacher/siswa/{studentId}/progress-ai', [StudentProgressController::class, 'describe']);
    Route::get('teacher/siswa/{studentId}/progress-ai/history', [StudentProgressController::class, 'history']);
    Route::get('teacher/progress-ai/history/{id}', [StudentProgressController::class, 'historyDetail']);
    Route::put('teacher/update', [TeacherAuthController::class, 'update']);
    Route::post('teacher/logout', [TeacherAuthController::class, 'logout']);

    // Materi
    Route::get('materi/mine', [MateriController::class, 'myMateri']); // Lihat semua materi milik guru
    Route::get('materi', [MateriController::class, 'index']);  
    Route::post('materi', [MateriController::class, 'store']);       // Tambah materi baru
    Route::get('materi/{id}', [MateriController::class, 'show']);    // Lihat detail materi
    Route::post('materi/{id}/track', [MateriController::class, 'track']); // Catat akses materi oleh siswa
    Route::put('materi/{id}', [MateriController::class, 'update']);  // Update materi
    Route::delete('materi/{id}', [MateriController::class, 'destroy']); // Hapus materis

    // Auth siswa
    Route::get('student/profile', [StudentAuthController::class, 'profile']);
    Route::get('student/progress-overview', [StudentProgressController::class, 'studentOverview']);
    Route::post('student/progress-ai', [StudentProgressController::class, 'studentDescribe']);
    Route::get('student/progress-ai/history', [StudentProgressController::class, 'studentHistory']);
    Route::get('student/progress-ai/history/{id}', [StudentProgressController::class, 'studentHistoryDetail']);
    Route::put('student/update', [StudentAuthController::class, 'update']);
    Route::post('student/logout', [StudentAuthController::class, 'logout']);

    // QUIZ CRUD (pakai /quiz)
    Route::get('quiz', [QuizController::class, 'index']);
    Route::post('quiz', [QuizController::class, 'store']);
    Route::post('quiz/generate-ai', [QuizController::class, 'generateAi']);
    Route::get('quiz/{id}', [QuizController::class, 'show']);
    Route::put('quiz/{id}', [QuizController::class, 'update']);
    Route::delete('quiz/{id}', [QuizController::class, 'destroy']);
    Route::get('quiz/mine', [QuizController::class, 'myQuizzes']);

    // QUESTION CRUD (pakai /quiz/{quiz_id}/question)
    Route::get('quiz/{quiz_id}/question', [QuestionController::class, 'index']);
    Route::post('quiz/{quiz_id}/question', [QuestionController::class, 'store']);
    Route::get('question/{id}', [QuestionController::class, 'show']);
    Route::put('question/{id}', [QuestionController::class, 'update']);
    Route::delete('question/{id}', [QuestionController::class, 'destroy']);

    // Route siswa untuk mengerjakan quiz
    Route::get('quiz/{quizId}/questions', [StudentQuizController::class, 'getQuestions']);
    Route::post('quiz/{quizId}/submit', [StudentQuizController::class, 'submit']);
    Route::get('quiz/{quizId}/result', [StudentQuizController::class, 'getResult']);
    Route::get('student/quiz/results', [StudentQuizController::class, 'results']);
});