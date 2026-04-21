---
name: ingat-pekerjaan
description: 'Lacak apa yang sudah dikerjakan selama sesi coding: tujuan, langkah, keputusan, blocker, dan hasil akhir. Gunakan saat menangani tugas multi-langkah agar progres konsisten dan mudah dilanjutkan.'
argument-hint: 'Jelaskan tujuan tugas dan batasan utamanya'
user-invocable: true
---

# Skill Ingat Pekerjaan

Skill ini membantu agent menjaga ingatan kerja selama sesi: apa yang diminta, apa yang sudah dicoba, apa yang berhasil/gagal, dan apa hasil akhirnya.

## Kapan Digunakan
- Tugas lebih dari satu langkah.
- Ada pencarian konteks lintas beberapa file.
- Ada risiko lupa keputusan teknis di tengah implementasi.
- Perlu ringkasan hasil kerja yang jelas di akhir.

## Outcome
- Catatan progres yang konsisten selama eksekusi.
- Keputusan penting terdokumentasi beserta alasan singkat.
- Ringkasan akhir yang bisa dipakai untuk melanjutkan pekerjaan berikutnya.

## Prosedur
1. Tetapkan tujuan utama dari prompt pengguna dalam 1-2 kalimat.
2. Pecah tugas menjadi langkah kecil yang bisa diverifikasi.
3. Kumpulkan konteks secara bertahap (file, error, konfigurasi) sebelum mengubah kode.
4. Setelah tiap 3-5 aksi, tulis update singkat: apa yang sudah dilakukan, apa hasilnya, dan langkah berikutnya.
5. Saat mengambil keputusan teknis, catat alasan ringkas dan dampaknya.
6. Jika ada blocker, catat blocker, hipotesis penyebab, dan percobaan yang sudah dilakukan.
7. Setelah implementasi, verifikasi hasil (lint/test/run/error check) sesuai kebutuhan tugas.
8. Tutup dengan ringkasan: perubahan, status verifikasi, dan next steps paling relevan.

## Decision Points
- Jika workflow pengguna tidak jelas: minta klarifikasi hasil yang diinginkan sebelum implementasi besar.
- Jika perubahan berisiko tinggi: validasi tambahan sebelum menyimpulkan selesai.
- Jika hasil verifikasi gagal: kembali ke langkah diagnosis, lalu ulangi verifikasi.

## Quality Checks
- Tujuan tugas tercermin di hasil akhir.
- Tidak ada langkah penting yang hilang dari alur kerja.
- Keputusan utama memiliki alasan yang bisa ditinjau ulang.
- Status verifikasi eksplisit: lulus, gagal, atau belum dijalankan.

## Completion Criteria
- Permintaan utama pengguna sudah terpenuhi.
- Perubahan sudah diverifikasi semampunya di lingkungan saat ini.
- Ringkasan akhir mencakup apa yang diubah dan kondisi akhirnya.
