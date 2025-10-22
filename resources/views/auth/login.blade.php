<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} – Login</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600" rel="stylesheet">
    <style>
        :root {
            color-scheme: light dark;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        body {
            margin: 0;
            display: grid;
            min-height: 100vh;
            place-items: center;
            background: #f8fafc;
        }
        .card {
            background: white;
            padding: 2.5rem;
            width: min(400px, 100%);
            border-radius: 1.5rem;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
            display: grid;
            gap: 1.5rem;
        }
        h1 {
            margin: 0;
            font-size: 1.75rem;
            color: #0f172a;
        }
        form {
            display: grid;
            gap: 1rem;
        }
        label {
            display: grid;
            gap: 0.5rem;
            font-weight: 500;
            color: #0f172a;
        }
        input {
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid #cbd5f5;
            font-size: 1rem;
        }
        button {
            padding: 0.85rem 1.25rem;
            border-radius: 999px;
            border: none;
            font-weight: 600;
            background: #2563eb;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background: #1d4ed8;
        }
        .error {
            background: rgba(220, 38, 38, 0.1);
            color: #b91c1c;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.95rem;
        }
        .meta {
            color: #475569;
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="card">
    <div>
        <h1>{{ config('app.name') }}</h1>
        <p class="meta">Melde dich an, um deine Prozesse zu verwalten.</p>
    </div>

    @if ($errors->any())
        <div class="error">
            {{ __('These credentials do not match our records.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label>
            {{ __('E-Mail') }}
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </label>
        <label>
            {{ __('Password') }}
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.95rem; color:#475569;">
            <input type="checkbox" name="remember" value="1" style="width:1rem; height:1rem;">
            <span>{{ __('Remember me') }}</span>
        </label>
        <button type="submit">{{ __('Sign in') }}</button>
    </form>
</div>
</body>
</html>
