<?php
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\TextToSpeech\V1\Client\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SynthesizeSpeechRequest;

// Path JSON service account
$credentialsPath = __DIR__ . '/storage/app/myappforai-19a180edf790.json'; 
putenv("GOOGLE_APPLICATION_CREDENTIALS=$credentialsPath");

echo "🔑 Using credentials from: $credentialsPath\n";

try {
    $client = new TextToSpeechClient();

    $inputText = (new SynthesisInput())
        ->setText("Halo, ini adalah tes suara guru pemrograman web dalam bahasa Indonesia.");

    $voice = (new VoiceSelectionParams())
        ->setLanguageCode("id-ID")
        ->setName("id-ID-Wavenet-B");

    $audioConfig = (new AudioConfig())
        ->setAudioEncoding(AudioEncoding::MP3);

    $response = $client->synthesizeSpeech($inputText, $voice, $audioConfig);

    file_put_contents("tts_test.mp3", $response->getAudioContent());

    echo "✅ Berhasil! File tts_test.mp3 sudah dibuat.\n";

    $client->close();
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}