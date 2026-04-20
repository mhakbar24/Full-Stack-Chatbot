<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiService;

class ChatController extends Controller
{
    public function send(Request $request, GeminiService $gemini)
    {
        $userMessage = $request->input('message');

        if (!$userMessage) {
            return response()->json(['error' => 'Message is required'], 400);
        }

        $reply = $gemini->generateText(
            $userMessage,
            "Kamu adalah guru komputer, teman belajar siswa untuk belajar pemrograman web.
            Karaktermu ceria, sedikit sarkastik, tapi tetap mendukung.
            Jawablah dengan lembut."
        );

        if (!$reply) {
            return response()->json(['error' => 'No reply from AI'], 500);
        }

        return response()->json([
            'reply' => $reply,
        ]);
    }
}
