<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VerifyController extends Controller
{
   public function index(Request $request)
    {
        $certificates = [
            "1212070002T1CO23" => [
                "name" => "COY OCTO BRANDON DAULAY",
                "birth_place" => "KOTA PINANG",
                "birth_date" => "04 October 1995",
                "gender" => "Laki - laki",
                "status" => "AKTIF",
                "certificate_name" => "Ahli Teknik Kapal Penangkap Ikan Tingkat I",
                "training_center" => "Sinar Poseidon Gupita Training Center",
                "valid_until" => "03-07-2030"
            ]
        ];

        // Ambil nomor dari input form, atau gunakan default dummy agar langsung muncul saat dibuka
        $number = $request->input('number', '1212070002T1CO23'); 
        
        $data = $certificates[$number] ?? null;
        $age = null;

        // Jika data ditemukan, hitung umur secara native menggunakan Carbon
       

        // Lempar data ke view verify.blade.php
        return view('verify', compact('data', 'number', 'age'));
    }
}