<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
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
        $originalEmail = $teacher->email;

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('teachers')->ignore($teacher->id),
            ],
            'password' => 'sometimes|required|string|confirmed',
            'profile_photo' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }

        if ($request->hasFile('profile_photo')) {
            if ($teacher->profile_photo_path) {
                Storage::disk('public')->delete($teacher->profile_photo_path);
            }

            $validatedData['profile_photo_path'] = $request->file('profile_photo')
                ->store('profile-photos/teachers', 'public');
        }

        unset($validatedData['profile_photo']);

        $teacher->update($validatedData);

        $userPayload = [
            'name' => $teacher->name,
            'role' => 'guru',
        ];

        if (!empty($validatedData['password'])) {
            $userPayload['password'] = $validatedData['password'];
        }

        User::updateOrCreate(
            ['email' => $teacher->email],
            $userPayload
        );

        if ($originalEmail !== $teacher->email) {
            User::where('email', $originalEmail)->delete();
        }

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
            'password' => 'required|string|confirmed',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $profilePhotoPath = null;
        if ($request->hasFile('profile_photo')) {
            $profilePhotoPath = $request->file('profile_photo')->store('profile-photos/teachers', 'public');
        }

        $teacher = Teacher::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_photo_path' => $profilePhotoPath,
        ]);

        User::updateOrCreate(
            ['email' => $teacher->email],
            [
                'name' => $teacher->name,
                'password' => Hash::make($request->password),
                'role' => 'guru',
            ]
        );

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

    public function destroyStudent(Request $request, int $studentId)
    {
        $teacher = $request->user();
        if (!$teacher instanceof Teacher) {
            return response()->json([
                'ok' => false,
                'message' => 'Akses ditolak (khusus guru).'
            ], 403);
        }

        $student = Student::find($studentId);
        if (!$student) {
            return response()->json([
                'ok' => false,
                'message' => 'Siswa tidak ditemukan.'
            ], 404);
        }

        $studentEmail = $student->email;
        $photoPath = $student->profile_photo_path;

        DB::transaction(function () use ($student, $studentEmail) {
            User::where('email', $studentEmail)->delete();
            $student->delete();
        });

        if ($photoPath) {
            Storage::disk('public')->delete($photoPath);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Siswa berhasil dihapus.'
        ]);
    }

    public function logout(Request $request)
    {
    $request->user()->currentAccessToken()->delete();

    return response()->json(['message' => 'Logout berhasil']);
    }
}
