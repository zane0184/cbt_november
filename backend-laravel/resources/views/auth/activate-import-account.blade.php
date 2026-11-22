<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="referrer" content="no-referrer">
<title>Aktivasi Akun CBT Madrasah</title>
<style>
body{font-family:Arial,sans-serif;background:#f1f5f9;margin:0;min-height:100vh;display:grid;place-items:center;padding:20px;box-sizing:border-box}
main{background:#fff;width:100%;max-width:440px;padding:32px;border-radius:18px;box-shadow:0 10px 35px #0f172a18;box-sizing:border-box}
h1{font-size:22px;color:#14243a;margin:0 0 10px}p{font-size:14px;line-height:1.6;color:#526174}
label{display:block;font-weight:700;font-size:13px;margin:16px 0 6px}
input{width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:10px;padding:12px;font-size:15px}
button{width:100%;margin-top:22px;border:0;border-radius:10px;background:#0f8b72;color:white;padding:13px;font-weight:700;font-size:14px;cursor:pointer}
.error{color:#b91c1c;margin-top:12px}
</style>
</head>
<body>
<main>
<h1>Aktivasi Akun CBT Madrasah</h1>
<p>Buat password pribadi minimal 14 karakter. Tautan ini hanya dapat digunakan sekali dan berlaku terbatas.</p>
<form method="POST" action="{{ $action }}">@csrf
<label for="password">Password baru</label>
<input id="password" name="password" type="password" minlength="14" required autocomplete="new-password">
<label for="password_confirmation">Ulangi password</label>
<input id="password_confirmation" name="password_confirmation" type="password" minlength="14" required autocomplete="new-password">
<button type="submit">Aktifkan Akun</button>
</form>
@if($errors->any())<p class="error">{{ $errors->first() }}</p>@endif
</main>
</body>
</html>
