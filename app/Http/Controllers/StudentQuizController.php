<?php

namespace App\Http\Controllers;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\QuizAnswer;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentQuizResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentQuizController extends Controller
{
    public function getQuestions($quizId)
    {
          // 1. Ambil semua pertanyaan dalam quiz
        $quiz = Quiz::with('questions')->findOrFail($quizId);

        return response()->json([
            'quiz' => $quiz->title,
            'questions' => $quiz->questions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'question_text' => $q->question_text,
                    'options' => [
                        'A' => $q->option_a,
                        'B' => $q->option_b,
                        'C' => $q->option_c,
                        'D' => $q->option_d,
                    ]
                ];
            })
        ]);
    }
     // 2. Siswa submit jawaban
    public function submit(Request $request, $quizId)
    {
        $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'answers' => 'required|array',
        ]);

        $quiz = Quiz::findOrFail($request->quiz_id);
        $answers = $request->answers;

        $questions = Question::where('quiz_id', $quiz->id)->get();

        $score = 0;
        foreach ($questions as $question) {
            if (
                isset($answers[$question->id]) &&
                $answers[$question->id] === $question->correct_answer
            ) {
                $score++;
            }
        }

        $result = StudentQuizResult::create([
            'student_id' => Auth::id(),
            'quiz_id'    => $quiz->id,
            'score'      => $score,
        ]);

        return response()->json([
            'message' => 'Quiz submitted successfully',
            'score'   => $score,
            'total'   => $questions->count(),
            'result'  => $result,
        ]);
    }
    
     // 3. Lihat hasil quiz siswa
    public function getResult($quizId)
{
    $studentId = auth()->id();

    $result = StudentQuizResult::with(['quiz', 'student'])
        ->where('quiz_id', $quizId)
        ->where('student_id', $studentId)
        ->latest()
        ->first();

    if (!$result) {
        return response()->json([
            'ok' => false,
            'message' => 'Hasil quiz belum ada. Silakan submit quiz dulu.'
        ], 404);
    }

    return response()->json([
        'ok' => true,
        'data' => $result
    ], 200);
}

    public function results()
    {
        $results = StudentQuizResult::with('quiz')
            ->where('student_id', Auth::id())
            ->get();

        return response()->json([
            'results' => $results
        ]);
    }

}
