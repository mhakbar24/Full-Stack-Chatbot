# Full Stack Chatbot

Aplikasi chatbot berbasis web yang dibangun dengan **Laravel 12**, terintegrasi dengan **Gemini API (Google AI)** untuk membalas chat dan mengambil kesimpulan terhadap test hasil belajar siswa.

## Fitur

- 💬 Chat dasar — kirim pesan dan dapatkan balasan otomatis dari Gemini AI
- 🔐 Autentikasi API menggunakan Laravel Sanctum
- 📄 Dukungan export PDF (Laravel Dompdf)

## Tech Stack

| Komponen       | Teknologi              |
|----------------|-------------------------|
| Backend        | Laravel 12 (PHP ^8.2)   |
| AI Engine      | Gemini API (Google AI)  |
| Auth           | Laravel Sanctum         |
| PDF Export     | barryvdh/laravel-dompdf |
| Database       | MySQL                   |
| Deployment     | VPS — Nginx, PHP-FPM, SSL (Certbot) |

## Instalasi

1. Clone repository
   ```bash
   git clone https://github.com/mhakbar24/Full_Stack_Chatbot.git
   cd Full_Stack_Chatbot
   ```

2. Install dependencies
   ```bash
   composer install
   npm install
   ```

3. Konfigurasi environment
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Atur koneksi database dan API key Gemini di file `.env`
   ```env
   DB_CONNECTION=mysql
   DB_DATABASE=nama_database
   DB_USERNAME=root
   DB_PASSWORD=

   GEMINI_API_KEY=your_gemini_api_key_here
   ```

5. Jalankan migrasi database
   ```bash
   php artisan migrate
   ```

6. Jalankan server pengembangan
   ```bash
   composer run dev
   ```
   Perintah ini menjalankan server Laravel, queue listener, log viewer (Pail), dan Vite secara bersamaan.

7. Buka `http://localhost:8000` di browser.

## Deployment

Proyek ini dideploy ke VPS menggunakan stack **LEMP** (Nginx, PHP 8.2, MySQL) dengan SSL dari Certbot. Konfigurasi deployment tersedia di folder `deploy/`.

## Struktur Folder Penting

```
app/          # Logic aplikasi (Controller, Model, Service)
routes/       # Definisi route
resources/    # View, JS, CSS
database/     # Migration & seeder
deploy/       # Konfigurasi deployment VPS
scripts/      # Script pendukung
```

## Lisensi

Proyek ini menggunakan lisensi [MIT](https://opensource.org/licenses/MIT).
