<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top left, #ffe7d4, transparent 45%), linear-gradient(140deg, #f8fbff, #eaf4ff 40%, #fdf8ef);
            color: #15314f;
        }

        .card {
            width: min(430px, 92vw);
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid #dbe8f6;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 20px 40px rgba(17, 49, 87, 0.12);
        }

        h1 { margin: 0 0 8px; font-size: 1.6rem; }
        p { margin: 0 0 18px; color: #4d6783; }

        label { display: block; margin: 12px 0 6px; font-weight: 600; }
        input {
            width: 100%;
            border: 1px solid #c8daef;
            border-radius: 10px;
            padding: 11px 12px;
            font: inherit;
        }

        .err { color: #b0243e; font-size: .9rem; margin-top: 8px; }

        button {
            margin-top: 16px;
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 12px;
            font: inherit;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(120deg, #ff6b35, #ff8744);
            cursor: pointer;
        }

        .hint { margin-top: 12px; font-size: .86rem; color: #60718a; }
    </style>
</head>
<body>
    <form method="POST" action="{{ route('web.login') }}" class="card">
        @csrf
        <h1>Masuk Dashboard</h1>
        <p>Login sebagai user dengan role siswa, guru, atau admin.</p>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>

        @if ($errors->any())
            <div class="err">{{ $errors->first() }}</div>
        @endif

        <button type="submit">Login</button>
        <div class="hint">Tips: role user menentukan diarahkan ke dashboard Siswa, Guru, atau Admin.</div>
    </form>
</body>
</html>
