<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerifyController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\WebDashboardController;
use App\Http\Controllers\WebGuruQuizController;
use App\Http\Controllers\WebGuruMateriController;

Route::get('/', function () {
	return redirect()->route('login');
});

Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('web.login');
Route::post('/logout', [WebAuthController::class, 'logout'])->middleware('auth')->name('web.logout');

Route::middleware(['auth', 'role:guru'])->group(function () {
	Route::get('/guru', [WebDashboardController::class, 'guru'])->name('guru.dashboard');
	Route::post('/guru/students/{id}/progress-ai', [WebDashboardController::class, 'guruStudentsGenerateAi'])->name('guru.students.progress-ai');
	Route::get('/guru/students/{id}/progress', [WebDashboardController::class, 'guruStudentProgress'])->name('guru.students.progress');
	Route::get('/guru/students/{id}/progress/pdf', [WebDashboardController::class, 'guruStudentProgressPdf'])->name('guru.students.progress.pdf');
	Route::post('/guru/students/{id}/interventions', [WebDashboardController::class, 'guruStudentInterventionsStore'])->name('guru.students.interventions.store');
	Route::put('/guru/interventions/{id}', [WebDashboardController::class, 'guruStudentInterventionsUpdate'])->name('guru.interventions.update');
	Route::delete('/guru/interventions/{id}', [WebDashboardController::class, 'guruStudentInterventionsDestroy'])->name('guru.interventions.destroy');
	Route::get('/guru/early-warning/pdf', [WebDashboardController::class, 'guruEarlyWarningPdf'])->name('guru.early-warning.pdf');
	Route::delete('/guru/students/{id}', [WebDashboardController::class, 'guruStudentsDestroy'])->name('guru.students.destroy');
	Route::get('/guru/history', [WebDashboardController::class, 'guruHistory'])->name('guru.history');
	Route::get('/guru/history/{id}', [WebDashboardController::class, 'guruHistoryDetail'])->name('guru.history.detail');

	Route::get('/guru/quizzes', [WebGuruQuizController::class, 'index'])->name('guru.quizzes');
	Route::post('/guru/quizzes', [WebGuruQuizController::class, 'store'])->name('guru.quizzes.store');
	Route::post('/guru/quizzes/generate-ai', [WebGuruQuizController::class, 'generateAi'])->name('guru.quizzes.generate-ai');
	Route::get('/guru/quizzes/{id}', [WebGuruQuizController::class, 'show'])->name('guru.quizzes.show');
	Route::put('/guru/quizzes/{id}', [WebGuruQuizController::class, 'update'])->name('guru.quizzes.update');
	Route::delete('/guru/quizzes/{id}', [WebGuruQuizController::class, 'destroy'])->name('guru.quizzes.destroy');
	Route::post('/guru/quizzes/{quizId}/questions', [WebGuruQuizController::class, 'storeQuestion'])->name('guru.quizzes.questions.store');
	Route::put('/guru/questions/{id}', [WebGuruQuizController::class, 'updateQuestion'])->name('guru.questions.update');
	Route::delete('/guru/questions/{id}', [WebGuruQuizController::class, 'destroyQuestion'])->name('guru.questions.destroy');

	Route::get('/guru/materis', [WebGuruMateriController::class, 'index'])->name('guru.materis');
	Route::post('/guru/materis', [WebGuruMateriController::class, 'store'])->name('guru.materis.store');
	Route::post('/guru/materis/generate-ai', [WebGuruMateriController::class, 'generateAi'])->name('guru.materis.generate-ai');
	Route::put('/guru/materis/{id}', [WebGuruMateriController::class, 'update'])->name('guru.materis.update');
	Route::delete('/guru/materis/{id}', [WebGuruMateriController::class, 'destroy'])->name('guru.materis.destroy');
});

Route::middleware(['auth', 'role:siswa'])->group(function () {
	Route::get('/siswa', [WebDashboardController::class, 'siswa'])->name('siswa.dashboard');
	Route::post('/siswa/chatbot', [WebDashboardController::class, 'siswaChatbot'])->name('siswa.chatbot');
	Route::get('/siswa/progress', [WebDashboardController::class, 'siswaProgress'])->name('siswa.progress');
	Route::get('/siswa/progress/overview', [WebDashboardController::class, 'siswaProgressOverview'])->name('siswa.progress.overview');
	Route::post('/siswa/progress/overview/ai', [WebDashboardController::class, 'siswaProgressOverviewAi'])->name('siswa.progress.overview.ai');
	Route::get('/siswa/history/ai', [WebDashboardController::class, 'siswaHistoryAi'])->name('siswa.history.ai');
	Route::get('/siswa/history/ai/{id}', [WebDashboardController::class, 'siswaHistoryAiDetail'])->name('siswa.history.ai.detail');
	Route::get('/siswa/progress/overview/pdf', [WebDashboardController::class, 'siswaProgressOverviewPdf'])->name('siswa.progress.overview.pdf');
	Route::get('/siswa/materis', [WebDashboardController::class, 'siswaMateris'])->name('siswa.materis');
	Route::get('/siswa/materis/{id}', [WebDashboardController::class, 'siswaMateriShow'])->name('siswa.materis.show');
	Route::post('/siswa/materis/{id}/chatbot', [WebDashboardController::class, 'siswaMateriChatbot'])->name('siswa.materis.chatbot');
	Route::get('/siswa/quizzes', [WebDashboardController::class, 'siswaQuizzes'])->name('siswa.quizzes');
	Route::get('/siswa/quizzes/{id}', [WebDashboardController::class, 'siswaQuizShow'])->name('siswa.quizzes.show');
	Route::post('/siswa/quizzes/{quizId}/questions/{questionId}/hint', [WebDashboardController::class, 'siswaQuizHint'])->name('siswa.quizzes.questions.hint');
	Route::post('/siswa/quizzes/{id}/submit', [WebDashboardController::class, 'siswaQuizSubmit'])->name('siswa.quizzes.submit');
	Route::get('/siswa/quizzes/{id}/result', [WebDashboardController::class, 'siswaQuizResult'])->name('siswa.quizzes.result');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
	Route::get('/admin', [WebDashboardController::class, 'admin'])->name('admin.dashboard');
	Route::get('/admin/guru-siswa', [WebDashboardController::class, 'adminGuruSiswa'])->name('admin.guru-siswa');
	Route::get('/admin/users', [WebDashboardController::class, 'adminUsers'])->name('admin.users');
	Route::post('/admin/users', [WebDashboardController::class, 'adminUsersStore'])->name('admin.users.store');
	Route::put('/admin/users/{id}', [WebDashboardController::class, 'adminUsersUpdate'])->name('admin.users.update');
	Route::post('/admin/users/{id}/reset-password', [WebDashboardController::class, 'adminUsersResetPassword'])->name('admin.users.reset-password');
	Route::delete('/admin/users/{id}', [WebDashboardController::class, 'adminUsersDestroy'])->name('admin.users.destroy');
});

Route::get('/verify', [VerifyController::class, 'index']);
Route::get('/check', [VerifyController::class, 'check']);
