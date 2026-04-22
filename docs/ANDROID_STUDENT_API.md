# Android API Siswa

Dokumen ini khusus untuk aplikasi Android versi siswa.

## 1. Base URL
- Production: https://fansnime.my.id/api


## 2. Autentikasi
- Gunakan Laravel Sanctum token dari endpoint login siswa.
- Header untuk endpoint protected:
  - Authorization: Bearer {token}
  - Accept: application/json

## 3. Endpoint Public

### 3.1 Register Siswa
- Method: POST
- Path: /student/register
- Content-Type:
  - application/json (tanpa foto)
  - multipart/form-data (dengan foto)
- Body:
  - name (required)
  - email (required)
  - password (required)
  - profile_photo (optional file: jpg/jpeg/png/webp max 2MB)
- Response sukses: message, student

### 3.2 Login Siswa
- Method: POST
- Path: /student/login
- Body:
  - email (required)
  - password (required)
- Response sukses:
  - student
  - token

### 3.3 Chat
- Method: POST
- Path: /chat
- Catatan: endpoint ini saat ini public.

## 4. Endpoint Protected (Wajib Bearer Token)

## 4.1 Profil Siswa

### Get Profil
- Method: GET
- Path: /student/profile

### Update Profil
- Method: PUT
- Path: /student/update
- Content-Type:
  - application/json (tanpa foto)
  - multipart/form-data (dengan foto)
- Body opsional:
  - name
  - email
  - password
  - password_confirmation (wajib jika password diisi)
  - profile_photo (file)

### Logout
- Method: POST
- Path: /student/logout

## 4.2 Materi

### List Materi
- Method: GET
- Path: /materi

### Detail Materi
- Method: GET
- Path: /materi/{id}

### Track Akses Materi
- Method: POST
- Path: /materi/{id}/track
- Tujuan: mencatat aktivitas baca materi oleh siswa.

## 4.3 Quiz Siswa

### List Quiz
- Method: GET
- Path: /quiz

### Ambil Soal Quiz
- Method: GET
- Path: /quiz/{quizId}/questions

### Submit Jawaban Quiz
- Method: POST
- Path: /quiz/{quizId}/submit
- Body: sesuai struktur jawaban dari backend StudentQuizController.

### Lihat Hasil Quiz Tertentu
- Method: GET
- Path: /quiz/{quizId}/result

### Riwayat Hasil Quiz Siswa
- Method: GET
- Path: /student/quiz/results

## 4.4 Progress Belajar Siswa

### Progress Overview
- Method: GET
- Path: /student/progress-overview

### Generate Analisis AI
- Method: POST
- Path: /student/progress-ai

### Riwayat Analisis AI
- Method: GET
- Path: /student/progress-ai/history

### Detail Riwayat Analisis AI
- Method: GET
- Path: /student/progress-ai/history/{id}

## 5. Rekomendasi Alur Implementasi Android
1. Splash
2. Login atau Register
3. Simpan token ke secure storage
4. Home siswa: ringkasan progress + menu materi + menu quiz
5. Materi:
   - list materi
   - detail materi
   - panggil track saat detail dibuka
6. Quiz:
   - list quiz
   - ambil soal
   - submit jawaban
   - tampilkan hasil
7. Progress AI:
   - overview
   - generate analisis
   - buka riwayat
8. Profil:
   - tampilkan profil
   - update data/foto
   - logout

## 6. Catatan Integrasi Penting
- Untuk upload file, gunakan multipart/form-data.
- Jangan set Content-Type multipart secara manual, biarkan library HTTP yang isi boundary.
- Selalu kirim Accept: application/json agar error response konsisten JSON.
- Jika token expired atau invalid, backend akan balas 401 dan aplikasi harus kembali ke layar login.

## 7. Daftar Endpoint Ringkas
- POST /student/register
- POST /student/login
- POST /chat
- GET /student/profile
- PUT /student/update
- POST /student/logout
- GET /materi
- GET /materi/{id}
- POST /materi/{id}/track
- GET /quiz
- GET /quiz/{quizId}/questions
- POST /quiz/{quizId}/submit
- GET /quiz/{quizId}/result
- GET /student/quiz/results
- GET /student/progress-overview
- POST /student/progress-ai
- GET /student/progress-ai/history
- GET /student/progress-ai/history/{id}

## 8. Contoh Request dan Response JSON

Catatan:
- Semua contoh menggunakan base URL `https://fansnime.my.id/api`.
- Untuk endpoint protected, tambahkan header:
  - `Authorization: Bearer {token}`
  - `Accept: application/json`

### 8.1 POST /student/register

Request (JSON tanpa foto):
```json
{
  "name": "Siswa A",
  "email": "siswaa@example.com",
  "password": "rahasia123"
}
```

Response 201:
```json
{
  "message": "Student registered successfully",
  "student": {
    "id": 12,
    "name": "Siswa A",
    "email": "siswaa@example.com",
    "profile_photo_path": null,
    "profile_photo_url": null,
    "created_at": "2026-04-22T10:15:00.000000Z",
    "updated_at": "2026-04-22T10:15:00.000000Z"
  }
}
```

Response 422 (contoh):
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": [
      "The email has already been taken."
    ]
  }
}
```

### 8.2 POST /student/login

Request:
```json
{
  "email": "siswaa@example.com",
  "password": "rahasia123"
}
```

Response 200:
```json
{
  "student": {
    "id": 12,
    "name": "Siswa A",
    "email": "siswaa@example.com",
    "profile_photo_path": "profile-photos/students/abc.jpg",
    "profile_photo_url": "https://fansnime.my.id/storage/profile-photos/students/abc.jpg"
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxx"
}
```

Response 401:
```json
{
  "message": "Invalid login details"
}
```

### 8.3 POST /chat

Request:
```json
{
  "message": "Jelaskan perbedaan HTML dan CSS"
}
```

Response 200:
```json
{
  "reply": "HTML itu kerangka halaman, CSS itu yang mengatur tampilan..."
}
```

Response 400:
```json
{
  "error": "Message is required"
}
```

### 8.4 GET /student/profile

Response 200:
```json
{
  "id": 12,
  "name": "Siswa A",
  "email": "siswaa@example.com",
  "profile_photo_path": "profile-photos/students/abc.jpg",
  "profile_photo_url": "https://fansnime.my.id/storage/profile-photos/students/abc.jpg"
}
```

### 8.5 PUT /student/update

Request (JSON):
```json
{
  "name": "Siswa A Update",
  "email": "siswaupdate@example.com"
}
```

Response 200:
```json
{
  "message": "Data guru berhasil diupdate",
  "student": {
    "id": 12,
    "name": "Siswa A Update",
    "email": "siswaupdate@example.com",
    "profile_photo_url": "https://fansnime.my.id/storage/profile-photos/students/abc.jpg"
  }
}
```

### 8.6 POST /student/logout

Response 200:
```json
{
  "message": "Logged out successfully"
}
```

### 8.7 GET /materi

Response 200:
```json
{
  "data": [
    {
      "id": 3,
      "title": "Dasar HTML",
      "category": "Frontend",
      "isi_materi": "Materi lengkap...",
      "description": "Materi lengkap...",
      "image": "https://fansnime.my.id/storage/materi_images/a.jpg",
      "teacher": {
        "id": 2,
        "name": "Guru A",
        "email": "guru@example.com"
      },
      "created_at": "2026-04-22 10:20:00"
    }
  ]
}
```

### 8.8 GET /materi/{id}

Response 200:
```json
{
  "data": {
    "id": 3,
    "title": "Dasar HTML",
    "category": "Frontend",
    "isi_materi": "Materi lengkap...",
    "description": "Materi lengkap...",
    "image": "https://fansnime.my.id/storage/materi_images/a.jpg",
    "teacher": {
      "id": 2,
      "name": "Guru A",
      "email": "guru@example.com"
    },
    "created_at": "2026-04-22 10:20:00"
  }
}
```

### 8.9 POST /materi/{id}/track

Response 200:
```json
{
  "ok": true,
  "message": "Aktivitas materi tercatat.",
  "data": {
    "id": 55,
    "student_id": 12,
    "materi_id": 3,
    "accessed_at": "2026-04-22T10:30:00.000000Z"
  }
}
```

### 8.10 GET /quiz

Response 200:
```json
[
  {
    "id": 9,
    "title": "Quiz HTML Dasar",
    "description": "Soal dasar HTML",
    "teacher_id": 2,
    "questions": [
      {
        "id": 91,
        "quiz_id": 9,
        "question_text": "Tag untuk paragraf adalah..."
      }
    ]
  }
]
```

### 8.11 GET /quiz/{quizId}/questions

Response 200:
```json
{
  "quiz": "Quiz HTML Dasar",
  "questions": [
    {
      "id": 91,
      "question_text": "Tag untuk paragraf adalah...",
      "options": {
        "A": "<p>",
        "B": "<h1>",
        "C": "<ul>",
        "D": "<div>"
      }
    }
  ]
}
```

### 8.12 POST /quiz/{quizId}/submit

Request:
```json
{
  "quiz_id": 9,
  "answers": {
    "91": "A",
    "92": "C"
  }
}
```

Response 200:
```json
{
  "message": "Quiz submitted successfully",
  "score": 1,
  "total": 2,
  "result": {
    "id": 100,
    "student_id": 12,
    "quiz_id": 9,
    "score": 1,
    "created_at": "2026-04-22T10:35:00.000000Z"
  }
}
```

### 8.13 GET /quiz/{quizId}/result

Response 200:
```json
{
  "ok": true,
  "data": {
    "id": 100,
    "student_id": 12,
    "quiz_id": 9,
    "score": 1,
    "quiz": {
      "id": 9,
      "title": "Quiz HTML Dasar"
    }
  }
}
```

Response 404:
```json
{
  "ok": false,
  "message": "Hasil quiz belum ada. Silakan submit quiz dulu."
}
```

### 8.14 GET /student/quiz/results

Response 200:
```json
{
  "results": [
    {
      "id": 100,
      "student_id": 12,
      "quiz_id": 9,
      "score": 1,
      "quiz": {
        "id": 9,
        "title": "Quiz HTML Dasar"
      }
    }
  ]
}
```

### 8.15 GET /student/progress-overview

Response 200 (dipersingkat):
```json
{
  "ok": true,
  "student": {
    "id": 12,
    "name": "Siswa A",
    "email": "siswaa@example.com"
  },
  "quiz_progress": {
    "total_attempts": 5,
    "average_score": 78.4,
    "best_score": 95,
    "latest_score": 80,
    "trend": "meningkat"
  },
  "materi_progress": {
    "total_materi_available": 20,
    "unique_materi_accessed": 8,
    "coverage_percent": 40,
    "recent_accesses": []
  }
}
```

### 8.16 POST /student/progress-ai

Request:
```json
{
  "context": "Saya ingin fokus ke latihan DOM dan event handling"
}
```

Response 200 (dipersingkat):
```json
{
  "ok": true,
  "student": {
    "id": 12,
    "name": "Siswa A",
    "email": "siswaa@example.com"
  },
  "stats": {
    "total_quiz": 5,
    "average_score": 78.4,
    "best_score": 95,
    "latest_score": 80,
    "materi_coverage_percent": 40
  },
  "analysis": "Ringkasan: ...",
  "analysis_sections": {
    "ringkasan": "...",
    "kekuatan": "...",
    "perlu_ditingkatkan": "...",
    "tindak_lanjut": "..."
  },
  "quality_badge": {
    "key": "cukup",
    "label": "Cukup"
  },
  "history_id": 77
}
```

Response 422 (jika belum pernah mengerjakan quiz):
```json
{
  "ok": false,
  "message": "Belum ada data hasil quiz untuk dianalisis."
}
```

### 8.17 GET /student/progress-ai/history

Response 200 (dipersingkat):
```json
{
  "ok": true,
  "student": {
    "id": 12,
    "name": "Siswa A",
    "email": "siswaa@example.com"
  },
  "count": 2,
  "history": [
    {
      "id": 77,
      "total_quiz": 5,
      "average_score": 78.4,
      "analysis": "Ringkasan: ...",
      "created_at": "2026-04-22T10:50:00.000000Z"
    }
  ]
}
```

### 8.18 GET /student/progress-ai/history/{id}

Response 200:
```json
{
  "ok": true,
  "data": {
    "id": 77,
    "student_id": 12,
    "analysis": "Ringkasan: ...",
    "created_at": "2026-04-22T10:50:00.000000Z"
  },
  "analysis_sections": {
    "ringkasan": "...",
    "kekuatan": "...",
    "perlu_ditingkatkan": "...",
    "tindak_lanjut": "..."
  },
  "quality_badge": {
    "key": "baik",
    "label": "Baik"
  }
}
```

Response 404:
```json
{
  "ok": false,
  "message": "Histori feedback AI tidak ditemukan."
}
```
