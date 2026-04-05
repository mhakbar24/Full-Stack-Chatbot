<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Teacher;


class QuizController extends Controller
{
    // 🔹 Buat quiz baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $quiz = Quiz::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'teacher_id' => $request->user()->id, // guru yang login
        ]);

        return response()->json($quiz, 201);
    }


    // 🔹 Lihat semua quiz (beserta soalnya)
    public function index()
    {
        $quizzes = Quiz::with('questions')->get();

        return response()->json($quizzes);
    }

    // 🔹 Detail 1 quiz
    public function show($id)
    {
        $quiz = Quiz::with('questions')->findOrFail($id);

        return response()->json($quiz);
    }

    public function update(Request $request, $id)
    {
    $quiz = Quiz::findOrFail($id);

    $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
    ]);

    $quiz->update([
        'title' => $request->title,
        'description' => $request->description,
    ]);

    return response()->json([
        'message' => 'Quiz updated successfully',
        'data' => $quiz
    ], 200);
    }

    public function destroy($id)
    {
        $quiz = Quiz::findOrFail($id);
        $quiz->delete();

        return response()->json(['message' => 'Quiz deleted successfully']);
    }
}