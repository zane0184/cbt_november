@extends('installer.layout')
@section('content')
<div class="space-y-6">
    <div><h2 class="text-xl font-extrabold text-navy-900">Langkah 5: Aktivasi Lisensi Publisher</h2><p class="text-sm text-textSecondary mt-1">Instalasi CBT Standalone harus terikat pada lisensi yang diterbitkan Publisher melalui HTTPS dan proof-of-possession.</p></div>
    <div class="p-4 bg-contentBg border border-cardBorder rounded-xl space-y-2"><div class="text-xs font-bold text-navy-900">Identitas instalasi</div><div class="text-xs text-textSecondary">Installation ID</div><code class="text-xs break-all">{{ $identity->installation_id }}</code><div class="text-xs text-textSecondary mt-2">Domain</div><code class="text-xs break-all">{{ $identity->registered_domain }}</code><div class="text-xs text-textSecondary mt-2">Public key fingerprint</div><code class="text-xs break-all">{{ $identity->key_fingerprint }}</code><p class="text-xs text-textSecondary mt-2">Private key instalasi disimpan terenkripsi di database lokal dan tidak dikirim ke Publisher.</p></div>
    @if($existingLicense)
        <div class="p-4 border border-cardBorder rounded-xl"><strong>Status lisensi lokal: {{ $existingLicense->status }}</strong><p class="text-xs text-textSecondary mt-1">License ID: {{ $existingLicense->license_id }} · Revisi {{ $existingLicense->license_revision }}</p><p class="text-xs text-textSecondary">Lisensi berakhir: {{ $existingLicense->license_expires_at?->format('d-m-Y H:i:s T') }} · Lease: {{ $existingLicense->lease_expires_at?->format('d-m-Y H:i:s T') }}</p><form method="post" action="{{ route('installer.license.refresh') }}" class="mt-3">@csrf<button class="px-5 py-2.5 bg-emerald-600 text-white font-bold text-sm rounded-xl">Sinkronkan / Perbarui Lease</button></form></div>
        <p class="text-xs text-textSecondary">Jika lisensi induk telah kedaluwarsa, Publisher harus memperpanjangnya terlebih dahulu. Sinkronisasi tidak memperpanjang masa lisensi komersial.</p>
    @else
        <div class="p-4 border border-amber-300 bg-amber-50 rounded-xl text-sm text-amber-900"><strong>Persiapan Publisher:</strong> pada VPS Publisher, jalankan <code>php artisan publisher:keys:generate license</code>. Salin <em>key_id</em> dan <em>public_key</em>. Jangan pernah menyalin private key.</div>
        <form action="{{ route('installer.license.activate') }}" method="POST" class="space-y-4">@csrf
            <div><label class="block text-xs font-bold text-navy-900 mb-1">Token Aktivasi</label><input type="password" name="activation_token" required minlength="30" maxlength="120" autocomplete="off" class="w-full px-4 py-3 rounded-xl border border-cardBorder text-sm bg-white" placeholder="Token satu kali dari Publisher"></div>
            <div><label class="block text-xs font-bold text-navy-900 mb-1">License Signing Key ID</label><input type="text" name="trusted_key_id" value="{{ old('trusted_key_id') }}" required maxlength="80" class="w-full px-4 py-3 rounded-xl border border-cardBorder text-sm bg-white" placeholder="license-YYYYMMDD-..."></div>
            <div><label class="block text-xs font-bold text-navy-900 mb-1">License Signing Public Key (Base64URL)</label><textarea name="trusted_public_key" required maxlength="80" rows="2" class="w-full px-4 py-3 rounded-xl border border-cardBorder text-sm bg-white font-mono" placeholder="Public key dari Publisher">{{ old('trusted_public_key') }}</textarea></div>
            <div class="pt-4 border-t border-cardBorder flex justify-end"><button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl">Verifikasi & Aktivasi Lisensi</button></div>
        </form>
    @endif
</div>
@endsection
