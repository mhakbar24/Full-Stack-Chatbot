<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; 
use App\Http\Controllers\TtsController;

class ChatController extends Controller
{
   public function send(Request $request, TtsController $tts)
    {
        $userMessage = $request->input('message');

        if (!$userMessage) {
            return response()->json(['error' => 'Message is required'], 400);
        }

        // ✅ Panggil Gemini API
        $response = Http::withHeaders([
            'Content-Type'   => 'application/json',
            'X-goog-api-key' => env('GEMINI_API_KEY'),
        ])->post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
            [
                "systemInstruction" => [
                    "parts" => [
                        [
                            "text" => "Kamu adalah guru komputer, teman belajar siswa untuk belajar pemrograman web. 
                            Karaktermu ceria, sedikit sarkastik, tapi tetap mendukung. 
                            Jawablah dengan lembut."
                        ]
                    ]
                ],
                "contents" => [
                    [
                        "parts" => [
                            [ "text" => $userMessage ]
                        ]
                    ]
                ]
            ]
        );

        $data = $response->json();
        $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$reply) {
            return response()->json(['error' => 'No reply from AI'], 500);
        }

        // ✅ Panggil TTSController untuk buat audio dari balasan
        $ttsRequest = new Request(['text' => $reply]);
        $ttsResponse = $tts->synthesize($ttsRequest)->getData();

        return response()->json([
            'reply'     => $reply,
            'audio_url' => $ttsResponse->file ?? null,
        ]);
    }
}
