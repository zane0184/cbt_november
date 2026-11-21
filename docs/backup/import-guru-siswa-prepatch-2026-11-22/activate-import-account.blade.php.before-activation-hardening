<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Aktivasi Akun CBT Madrasah</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
<div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
<h1 class="text-xl font-bold text-slate-900">Aktivasi Akun CBT Madrasah</h1>
<p class="mt-2 text-sm text-slate-600">Buat password pribadi minimal 14 karakter. Tautan ini hanya dapat digunakan sekali.</p>
<form method="POST" action="{{ $action }}" class="mt-6 space-y-4">@csrf
<div><label class="block text-sm font-semibold">Password baru</label><input name="password" type="password" minlength="14" required autocomplete="new-password" class="mt-1 w-full rounded-xl border p-3"></div>
<div><label class="block text-sm font-semibold">Ulangi password</label><input name="password_confirmation" type="password" minlength="14" required autocomplete="new-password" class="mt-1 w-full rounded-xl border p-3"></div>
<button class="w-full rounded-xl bg-emerald-700 p-3 font-bold text-white">Aktifkan Akun</button>
</form>
@if($errors->any())<p class="mt-3 text-sm text-red-600">{{ $errors->first() }}</p>@endif
</div></body></html>
