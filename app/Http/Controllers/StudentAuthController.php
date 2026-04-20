<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class StudentAuthController extends Controller
{
    public function register(Request $request)
    {
  
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:students,email',
            'password' => 'required|string',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $profilePhotoPath = null;
        if ($request->hasFile('profile_photo')) {
            $profilePhotoPath = $request->file('profile_photo')->store('profile-photos/students', 'public');
        }

        $student = Student::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'profile_photo_path' => $profilePhotoPath,
        ]);

        User::updateOrCreate(
            ['email' => $student->email],
            [
                'name' => $student->name,
                'password' => Hash::make($request->password),
                'role' => 'siswa',
            ]
        );

        return response()->json([
            'message' => 'Student registered successfully',
            'student' => $student
        ], 201);
    }
    

    public function login(Request $request)
    {
        $student = Student::where('email', $request->email)->first();

        if (!$student || !Hash::check($request->password, $student->password)) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        $token = $student->createToken('student-token')->plainTextToken;

        return response()->json([
            'student' => $student,
            'token'   => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function profile(Request $request)
{
    return response()->json($request->user());
}
public function update(Request $request)
    {   
        /** @var \App\Models\Student $student */
        $student = $request->user();
        $originalEmail = $student->email;

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('students')->ignore($student->id),
            ],
            'password' => 'sometimes|required|string|confirmed',
            'profile_photo' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }

        if ($request->hasFile('profile_photo')) {
            if ($student->profile_photo_path) {
                Storage::disk('public')->delete($student->profile_photo_path);
            }

            $validatedData['profile_photo_path'] = $request->file('profile_photo')
                ->store('profile-photos/students', 'public');
        }

        unset($validatedData['profile_photo']);

        $student->update($validatedData);

        $userPayload = [
            'name' => $student->name,
            'role' => 'siswa',
        ];

        if (!empty($validatedData['password'])) {
            $userPayload['password'] = $validatedData['password'];
        }

        User::updateOrCreate(
            ['email' => $student->email],
            $userPayload
        );

        if ($originalEmail !== $student->email) {
            User::where('email', $originalEmail)->delete();
        }

        return response()->json([
            'message' => 'Data guru berhasil diupdate',
            'student' => $student->fresh() // Mengembalikan data terbaru
        ]);
    }
    
}
