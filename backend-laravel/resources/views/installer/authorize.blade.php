@extends('installer.layout')

@section('title', 'Otorisasi Operator Installer')

@section('content')
<div class="installer-card">
    <h1>Otorisasi Operator</h1>
    <p>Installer CBT Madrasah dikunci sampai operator server memasukkan token bootstrap yang telah dikonfigurasi secara privat.</p>

    @if (!$tokenConfigured)
        <div class="alert alert-danger" role="alert">
            Token belum dikonfigurasi atau panjangnya kurang dari 32 karakter. Atur CBT_INSTALLER_BOOTSTRAP_TOKEN pada environment server, lalu muat ulang konfigurasi aplikasi.
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-warning" role="alert">{{ session('error') }}</div>
    @endif

    @if ($errors->has('bootstrap_token'))
        <div class="alert alert-danger" role="alert">{{ $errors->first('bootstrap_token') }}</div>
    @endif

    <form method="POST" action="{{ route('installer.authorize') }}" autocomplete="off">
        @csrf
        <label for="bootstrap_token">Token bootstrap operator</label>
        <input id="bootstrap_token" name="bootstrap_token" type="password" required minlength="32" maxlength="512" autocomplete="new-password" spellcheck="false">
        <button type="submit" @disabled(!$tokenConfigured)>Buka Installer</button>
    </form>
    <p class="text-muted">Otorisasi sesi berlaku maksimal 60 menit. Jangan membagikan token melalui chat, email, atau tangkapan layar.</p>
</div>
@endsection
