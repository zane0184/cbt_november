<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ganti Password — CBT Madrasah</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-lg">
        <h1 class="text-xl font-bold text-slate-900">Ganti Password Sementara</h1>
        <p class="mt-2 text-sm text-slate-600">Demi keamanan, ganti password sementara sebelum menggunakan fitur CBT Madrasah.</p>
        @if ($errors->any())
            <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('account.password.change.update') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="current_password" class="block text-sm font-medium text-slate-700">Password sementara</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password baru</label>
                <input id="password" name="password" type="password" minlength="14" autocomplete="new-password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                <p class="mt-1 text-xs text-slate-500">Minimal 14 karakter.</p>
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Ulangi password baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <button type="submit" class="w-full rounded-lg bg-blue-700 px-4 py-3 font-semibold text-white hover:bg-blue-800">Simpan Password Baru</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700">Keluar</button>
        </form>
    </main>
</body>
</html>
