<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
class TeacherAuthController extends Controller
{
    public function profile(Request $request)
    {
    return response()->json($request->user());
    }
    public function update(Request $request)
    {   
        /** @var \App\Models\Teacher $teacher */
        $teacher = $request->user();

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('teachers')->ignore($teacher->id),
            ],
            'password' => 'sometimes|required|string|confirmed'
        ]);

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }

        $teacher->update($validatedData);

        return response()->json([
            'message' => 'Data guru berhasil diupdate',
            'teacher' => $teacher->fresh() // Mengembalikan data terbaru
        ]);
    }
     // Register guru
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:teachers',
            'password' => 'required|string|confirmed'
        ]);

        $teacher = Teacher::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => 'Register berhasil',
            'teacher' => $teacher
        ], 201);
    }

    // Login guru
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $teacher = Teacher::where('email', $request->email)->first();

        if (!$teacher || !Hash::check($request->password, $teacher->password)) {
            return response()->json(['message' => 'Login gagal'], 401);
        }

        // Buat token API
        $token = $teacher->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'teacher' => $teacher
        ], 200);
    }
    public function getStudents(Request $request)
    {
        $teacher = $request->user();
        if (!$teacher instanceof Teacher) {
            return response()->json([
                'ok' => false,
                'message' => 'Akses ditolak (khusus guru).'
            ], 403);
        }
        $students = Student::query()
            ->select(['id', 'name', 'email', 'created_at'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'ok' => true,
            'students' => $students
        ]);
    }
    public function logout(Request $request)
    {
    $request->user()->currentAccessToken()->delete();

    return response()->json(['message' => 'Logout berhasil']);
    }
}
