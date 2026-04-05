<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Cloud\TextToSpeech\V1\Client\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SynthesizeSpeechRequest;
use Illuminate\Support\Facades\Storage;

class TtsController extends Controller
{
     public function synthesize(Request $request)
    {
        $text = $request->input('text', 'Halo, ini adalah tes suara guru pemrograman web dalam bahasa Indonesia.');

        $client = new TextToSpeechClient([
            'credentials' => base_path(env('GOOGLE_APPLICATION_CREDENTIALS')),
        ]);

        $inputText = (new SynthesisInput())
            ->setText($text);

        $voice = (new VoiceSelectionParams())
            ->setLanguageCode('id-ID')
            ->setName('id-ID-Wavenet-B'); // suara cowok, ada juga Wavenet-A (cewek)

        $audioConfig = (new AudioConfig())
            ->setAudioEncoding(AudioEncoding::MP3);

        // ✅ Bungkus semua dalam request
        $requestTTS = (new SynthesizeSpeechRequest())
            ->setInput($inputText)
            ->setVoice($voice)
            ->setAudioConfig($audioConfig);

        $response = $client->synthesizeSpeech($requestTTS);

        $audioContent = $response->getAudioContent();

        $fileName = 'guru_' . time() . '.mp3';
        Storage::disk('public')->put("tts/{$fileName}", $audioContent);

        $client->close();

        return response()->json([
            'success' => true,
            'file' => asset("storage/tts/{$fileName}")
        ]);
    }
}
