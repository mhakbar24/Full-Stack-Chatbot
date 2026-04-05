<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class StudentAuthController extends Controller
{
    public function register(Request $request)
    {
  
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:students,email',
            'password' => 'required|string',
        ]);

        $student = Student::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

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

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('students')->ignore($student->id),
            ],
            'password' => 'sometimes|required|string|confirmed'
        ]);

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }

        $student->update($validatedData);

        return response()->json([
            'message' => 'Data guru berhasil diupdate',
            'student' => $student->fresh() // Mengembalikan data terbaru
        ]);
    }
    
}
