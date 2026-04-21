<?php

namespace App\Http\Controllers;

use App\Http\Resources\MateriResource;
use Illuminate\Http\Request;
use App\Models\Materi;
use App\Models\Student;
use App\Models\StudentMateriLog;
use App\Models\Teacher;
use Illuminate\Support\Facades\Storage;

class MateriController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $materi = Materi::with('teacher')->get();
        return MateriResource::collection($materi);
    }

    public function myMateri(Request $request)
    {
        $teacher = $request->user();

        if (!$teacher instanceof Teacher) {
            return response()->json(['message' => 'Akses ditolak. Endpoint ini khusus guru.'], 403);
        }

        // Ambil materi milik teacher yang sedang login
        $materis = $teacher->materis()->with('teacher')->get();
        return MateriResource::collection($materis);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $teacher = $request->user();
        if (!$teacher instanceof Teacher) {
            return response()->json(['message' => 'Akses ditolak. Endpoint ini khusus guru.'], 403);
        }

        $request->validate([
        'title' => 'required|string|max:255',
        'category' => 'nullable|string',
        'description' => 'nullable|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

    $imagePath = null;
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('materi_images', 'public');
        }

    $materi = Materi::create([
        'title' => $request->title,
        'category' => $request->category,
        'description' => $request->description,
        'teacher_id' => $teacher->id,
        'image' => $imagePath
        ]);

    return (new MateriResource($materi))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $materi = Materi::with('teacher:id,name,email')->findOrFail($id);

        $user = $request->user();
        if ($user instanceof Student) {
            StudentMateriLog::create([
                'student_id' => $user->id,
                'materi_id' => $materi->id,
                'accessed_at' => now(),
            ]);
        }

        return new MateriResource($materi);
    }

    public function track(Request $request, string $id)
    {
        $student = $request->user();
        if (!$student instanceof Student) {
            return response()->json(['message' => 'Akses ditolak. Endpoint ini khusus siswa.'], 403);
        }

        $materi = Materi::findOrFail($id);

        $log = StudentMateriLog::create([
            'student_id' => $student->id,
            'materi_id' => $materi->id,
            'accessed_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Aktivitas materi tercatat.',
            'data' => [
                'id' => $log->id,
                'student_id' => $log->student_id,
                'materi_id' => $log->materi_id,
                'accessed_at' => $log->accessed_at,
            ],
        ]);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $teacher = $request->user();
        if (!$teacher instanceof Teacher) {
            return response()->json(['message' => 'Akses ditolak. Endpoint ini khusus guru.'], 403);
        }

        // Ambil materi milik guru yang login
        $materi = $teacher->materis()->findOrFail($id);

        // Validasi input
        $validatedData = $request->validate([
            'title' => 'sometimes|string|max:255',
            'category' => 'sometimes|string',
            'description' => 'sometimes|string',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        // Update gambar jika ada file baru
        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($materi->image) {
                Storage::disk('public')->delete($materi->image);
            }
            // Simpan gambar baru dan update path
            $validatedData['image'] = $request->file('image')->store('materi_images', 'public');
        }

        // Update data materi dengan data yang sudah divalidasi
        $materi->update($validatedData);
        // Muat ulang relasi teacher untuk memastikan data ter-update pada respons
        $materi->load('teacher');
        return new MateriResource($materi);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $teacher = $request->user();
        if (!$teacher instanceof Teacher) {
            return response()->json(['message' => 'Akses ditolak. Endpoint ini khusus guru.'], 403);
        }

        $materi = $teacher->materis()->findOrFail($id);

        $materi->delete();

        return response()->json(['message' => 'Materi berhasil dihapus']);
    }
}
