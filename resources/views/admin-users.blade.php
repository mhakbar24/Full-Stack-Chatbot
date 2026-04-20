<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen User</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body{margin:0;font-family:'Manrope',sans-serif;background:#0f172a;color:#142a43;}
        .wrap{max-width:1150px;margin:0 auto;padding:20px;}
        .head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;color:#e3f3ff;}
        .card{margin-top:14px;background:#fff;border-radius:14px;padding:16px;border:1px solid #dce7f5;}
        .row{display:grid;grid-template-columns:2fr 2fr 1fr 1fr;gap:10px;align-items:end;}
        input,select{width:100%;padding:10px;border:1px solid #c8d8ee;border-radius:10px;font:inherit;}
        button,.btn{border:0;border-radius:10px;padding:10px 12px;background:#ff7a3b;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;}
        table{width:100%;border-collapse:collapse;} th,td{padding:10px 8px;text-align:left;border-bottom:1px solid #ecf2fb;vertical-align:top;}
        .actions{display:flex;gap:8px;align-items:center;}
        .ok{color:#0c7a5a;font-weight:700;} .err{color:#b0233f;font-weight:700;}
        @media (max-width:900px){ .row{grid-template-columns:1fr 1fr;} }
        @media (max-width:620px){ .row{grid-template-columns:1fr;} }
    </style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <div>
            <h1 style="margin:0;">Manajemen User Dashboard</h1>
            <div>Login sebagai {{ $user->name }} ({{ strtoupper($user->role) }})</div>
        </div>
        <div class="actions">
            <a href="{{ route('admin.dashboard') }}" class="btn" style="background:#2d4f78;">Kembali Dashboard</a>
            <form action="{{ route('web.logout') }}" method="POST">@csrf<button type="submit" style="background:#7ee4c5;color:#10334f;">Logout</button></form>
        </div>
    </div>

    <div class="card">
        @if (session('status'))
            <div class="ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="err">{{ $errors->first() }}</div>
        @endif

        <form method="GET" style="margin:10px 0 16px; display:flex; gap:8px;">
            <input type="text" name="q" placeholder="Cari nama/email/role" value="{{ $search }}">
            <button type="submit" style="background:#1f3a5c;">Cari</button>
        </form>

        <h3>Tambah User Baru</h3>
        <form method="POST" action="{{ route('admin.users.store') }}" class="row">
            @csrf
            <div>
                <label>Nama</label>
                <input name="name" required>
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div>
                <label>Role</label>
                <select name="role" required>
                    <option value="guru">Guru</option>
                    <option value="siswa">Siswa</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div>
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div>
                <label>Konfirmasi Password</label>
                <input type="password" name="password_confirmation" required>
            </div>
            <div style="display:flex;align-items:end;">
                <button type="submit">Tambah User</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Daftar User</h3>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($users as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->email }}</td>
                    <td>{{ strtoupper($item->role) }}</td>
                    <td>{{ $item->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="actions" style="align-items:flex-start; flex-direction:column;">
                        <form method="POST" action="{{ route('admin.users.update', $item->id) }}" class="actions">
                            @csrf
                            @method('PUT')
                            <input name="name" value="{{ $item->name }}" required style="max-width:150px;">
                            <select name="role" style="max-width:120px;">
                                <option value="guru" @selected($item->role === 'guru')>Guru</option>
                                <option value="siswa" @selected($item->role === 'siswa')>Siswa</option>
                                <option value="admin" @selected($item->role === 'admin')>Admin</option>
                            </select>
                            <button type="submit" style="background:#1f3a5c;">Simpan</button>
                        </form>

                        <form method="POST" action="{{ route('admin.users.reset-password', $item->id) }}" class="actions" style="margin-top:6px;">
                            @csrf
                            <input name="new_password" type="password" placeholder="Password baru" required style="max-width:170px;">
                            <button type="submit" style="background:#ff7a3b;">Reset</button>
                        </form>

                        <form method="POST" action="{{ route('admin.users.destroy', $item->id) }}" onsubmit="return confirm('Yakin hapus user ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="background:#b0233f;">Delete</button>
                        </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Tidak ada user.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div style="margin-top:12px;">{{ $users->links() }}</div>
    </div>
</div>
</body>
</html>
