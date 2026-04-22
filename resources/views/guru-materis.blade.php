<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Materi Guru</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body{margin:0;background:#f8fcf7;color:#1b3a2f;font-family:'Space Grotesk',sans-serif;}
        .wrap{max-width:1120px;margin:0 auto;padding:22px;}
        .card{background:#fff;border:1px solid #d8eadf;border-radius:14px;padding:16px;margin-top:14px;}
        .actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
        .filters{display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;align-items:end;}
        .btn,button{border:0;border-radius:10px;padding:10px 12px;background:#2f9e44;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;}
        .btn-alt{background:#2d4f78;}
        input,textarea{width:100%;padding:10px;border:1px solid #c4dccf;border-radius:10px;font:inherit;}
        table{width:100%;border-collapse:collapse;}th,td{padding:10px 8px;text-align:left;border-bottom:1px solid #eaf4ee;vertical-align:top;}
        .thumb{width:56px;height:56px;border-radius:8px;object-fit:cover;border:1px solid #d7e7dd;}
        .thumb-btn{border:0;background:transparent;padding:0;cursor:pointer;}
        .modal-backdrop{position:fixed;inset:0;background:rgba(9,20,14,.72);display:none;align-items:center;justify-content:center;padding:18px;z-index:9999;}
        .modal-backdrop.show{display:flex;}
        .modal-card{position:relative;max-width:min(92vw,980px);max-height:86vh;}
        .modal-image{max-width:100%;max-height:86vh;display:block;border-radius:14px;border:2px solid #d8eadf;box-shadow:0 24px 60px rgba(0,0,0,.35);background:#fff;}
        .modal-close{position:absolute;top:-12px;right:-12px;width:38px;height:38px;border-radius:999px;border:0;background:#fff;color:#244639;font-size:20px;font-weight:700;cursor:pointer;box-shadow:0 8px 20px rgba(0,0,0,.22);}
        @media (max-width:980px){ .filters{grid-template-columns:1fr 1fr;} }
        @media (max-width:720px){table,thead,tbody,tr,th,td{display:block;}th{display:none;}td{padding:7px 0;}}
    </style>
</head>
<body>
<div class="wrap">
    <h1 style="margin:0;">Manajemen Materi Guru</h1>
    <div>Login sebagai {{ $user->name }} ({{ strtoupper($user->role) }})</div>

    <div class="actions" style="margin-top:12px;">
        <a class="btn btn-alt" href="{{ route('guru.dashboard') }}">Kembali Dashboard</a>
        <a class="btn btn-alt" href="{{ route('guru.quizzes') }}">Kelola Quiz</a>
        <form action="{{ route('web.logout') }}" method="POST">@csrf<button type="submit" style="background:#1f3a5c;">Logout</button></form>
    </div>

    <div class="card">
        <h3>Filter Materi</h3>
        <form method="GET" class="filters">
            <div>
                <label>Cari Judul</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Contoh: HTML Dasar">
            </div>
            <div>
                <label>Kategori</label>
                <input type="text" name="category" value="{{ $filters['category'] ?? '' }}" placeholder="Contoh: Frontend">
            </div>
            <div>
                <label>Dari</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div>
                <label>Sampai</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div class="actions">
                <button type="submit" class="btn-alt">Terapkan</button>
                <a class="btn" style="background:#9bb9aa;" href="{{ route('guru.materis') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        @if (session('status'))
            <div style="color:#17633a;font-weight:700;">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div style="color:#b0233f;font-weight:700;">{{ $errors->first() }}</div>
        @endif
        @if (session('materi_ai_rubric'))
            @php $rubric = session('materi_ai_rubric'); @endphp
            <div style="margin-top:10px;padding:10px;border-radius:10px;background:#f4fbff;border:1px solid #d6e8f4;color:#1f4a74;">
                <strong>Rubrik Kualitas Materi AI</strong>
                <div style="margin-top:5px;font-size:.9rem;">Kejelasan: {{ $rubric['clarity'] }}/5 • Kelengkapan: {{ $rubric['completeness'] }}/5 • Selaras CP/ATP: {{ $rubric['alignment'] }}/5 • Total: <strong>{{ number_format($rubric['total'], 2) }}/5</strong></div>
                <div style="margin-top:4px;font-size:.88rem;">{{ $rubric['recommendation'] }}</div>
            </div>
        @endif

        <h3>Tambah Materi</h3>
        <form method="POST" action="{{ route('guru.materis.store') }}" enctype="multipart/form-data" style="display:grid;gap:10px;">
            @csrf
            <input name="title" placeholder="Judul materi" required>
            <input name="category" placeholder="Kategori (opsional)">
            <textarea name="description" rows="3" placeholder="Isi materi"></textarea>
            <label>Icon materi (opsional)</label>
            <input type="file" name="icon" accept="image/*">
            <label>Gambar materi (opsional)</label>
            <input type="file" name="image" accept="image/*">
            <div><button type="submit">Simpan Materi</button></div>
        </form>

        <hr style="border:0;border-top:1px solid #eaf4ee;margin:14px 0;">

        <h3>Generate Materi dengan AI (CP/ATP Fase F)</h3>
        <form method="POST" action="{{ route('guru.materis.generate-ai') }}" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end;">
            @csrf
            <div>
                <label>Topik Materi</label>
                <select name="topic_key" required style="width:100%;padding:10px;border:1px solid #c4dccf;border-radius:10px;font:inherit;">
                    <option value="">Pilih topik materi</option>
                    @foreach (($topicOptions ?? []) as $topic)
                        <option value="{{ $topic['key'] }}" @selected(old('topic_key') === $topic['key'])>{{ $topic['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Kompetensi CP/ATP</label>
                <select name="competency_key" style="width:100%;padding:10px;border:1px solid #c4dccf;border-radius:10px;font:inherit;">
                    <option value="">Auto-detect</option>
                    @foreach (($competencyOptions ?? []) as $opt)
                        <option value="{{ $opt['key'] }}">{{ $opt['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Kedalaman</label>
                <select name="depth" style="width:100%;padding:10px;border:1px solid #c4dccf;border-radius:10px;font:inherit;">
                    <option value="dasar">Dasar</option>
                    <option value="menengah" selected>Menengah</option>
                    <option value="lanjut">Lanjut</option>
                </select>
            </div>
            <div>
                <button type="submit" style="background:#2d4f78;">Generate AI</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Daftar Materi Saya</h3>
        <table>
            <thead>
                <tr><th>Icon</th><th>Gambar</th><th>Judul</th><th>Kategori</th><th>Isi Materi</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            @forelse ($materis as $materi)
                <tr>
                    <td>
                        @if ($materi->icon)
                            <button type="button" class="thumb-btn" data-preview-src="{{ asset('storage/' . $materi->icon) }}" data-preview-alt="Icon {{ $materi->title }}">
                                <img class="thumb" src="{{ asset('storage/' . $materi->icon) }}" alt="Icon {{ $materi->title }}">
                            </button>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($materi->image)
                            <button type="button" class="thumb-btn" data-preview-src="{{ asset('storage/' . $materi->image) }}" data-preview-alt="{{ $materi->title }}">
                                <img class="thumb" src="{{ asset('storage/' . $materi->image) }}" alt="{{ $materi->title }}">
                            </button>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $materi->title }}</td>
                    <td>{{ $materi->category ?: '-' }}</td>
                    <td>{{ $materi->description ?: '-' }}</td>
                    <td>
                        <form method="POST" action="{{ route('guru.materis.update', $materi->id) }}" enctype="multipart/form-data" style="display:grid;gap:8px;max-width:320px;">
                            @csrf @method('PUT')
                            <input name="title" value="{{ $materi->title }}" required>
                            <input name="category" value="{{ $materi->category }}">
                            <textarea name="description" rows="2">{{ $materi->description }}</textarea>
                            <label>Icon materi</label>
                            <input type="file" name="icon" accept="image/*">
                            <label>Gambar materi</label>
                            <input type="file" name="image" accept="image/*">
                            <div class="actions">
                                <button type="submit" style="background:#2d4f78;">Update</button>
                        </form>
                                <form method="POST" action="{{ route('guru.materis.destroy', $materi->id) }}" onsubmit="return confirm('Hapus materi ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="background:#b0233f;">Hapus</button>
                                </form>
                            </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada materi.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:12px;">{{ $materis->links() }}</div>
    </div>
</div>

<div id="materiPreviewModal" class="modal-backdrop" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-label="Preview gambar materi">
        <button type="button" class="modal-close" id="closeMateriPreview" aria-label="Tutup">×</button>
        <img id="materiPreviewImage" class="modal-image" src="" alt="Preview gambar materi">
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('materiPreviewModal');
        const modalImage = document.getElementById('materiPreviewImage');
        const closeBtn = document.getElementById('closeMateriPreview');
        const triggers = document.querySelectorAll('[data-preview-src]');

        function openModal(src, altText) {
            modalImage.src = src;
            modalImage.alt = altText || 'Preview gambar materi';
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            modalImage.src = '';
            document.body.style.overflow = '';
        }

        triggers.forEach(function (el) {
            el.addEventListener('click', function () {
                openModal(el.getAttribute('data-preview-src'), el.getAttribute('data-preview-alt'));
            });
        });

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                closeModal();
            }
        });
    })();
</script>
</body>
</html>
