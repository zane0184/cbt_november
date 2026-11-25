<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Status Lisensi CBT Madrasah</title>
<style>body{font:15px/1.6 system-ui;background:#f4f7f8;color:#172536;margin:0;padding:28px}.card{max-width:680px;margin:7vh auto;background:white;border:1px solid #e4e9ef;border-radius:18px;padding:28px;box-shadow:0 12px 35px #10243a12}h1{margin:0 0 8px;color:#14243a}.muted{color:#64748b}.pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#e6f4f1;color:#0f6b58;font-weight:700;font-size:12px}.alert{background:#fff7ed;border:1px solid #fed7aa;padding:12px;border-radius:10px;margin:16px 0}.danger{background:#fef2f2;border-color:#fecaca;color:#991b1b}code{overflow-wrap:anywhere}</style></head>
<body><main class="card"><span class="pill">CBT MADRASAH STANDALONE</span><h1>Status Aktivasi</h1><p class="muted">Data akademik madrasah tetap tersimpan lokal. Perubahan status aktivasi tidak menghapus database, jawaban, atau hasil ujian.</p>
@if(session('license_error'))<div class="alert">Kode pemeriksaan: <code>{{ session('license_error') }}</code></div>@endif
@if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
@if(($installState['license_mode'] ?? null) === 'PRE_ACTIVATION_2026')
    @php
        $pilotExpiresAt = !empty($installState['pre_activation_expires_at']) ? \Illuminate\Support\Carbon::parse($installState['pre_activation_expires_at']) : null;
        $pilotExpired = !$pilotExpiresAt || $pilotExpiresAt->isPast();
    @endphp
    <p>Status: <strong>{{ $pilotExpired ? 'PRA-AKTIVASI KEDALUWARSA' : 'PRA-AKTIVASI 2026 AKTIF' }}</strong></p>
    <p>Mode: <strong>PRE_ACTIVATION_2026 (pilot sementara)</strong></p>
    <p>Mulai: {{ !empty($installState['pre_activation_issued_at']) ? \Illuminate\Support\Carbon::parse($installState['pre_activation_issued_at'])->format('d-m-Y H:i:s T') : 'Tidak tercatat' }}</p>
    <p>Berlaku sampai: <strong>{{ $pilotExpiresAt?->format('d-m-Y H:i:s T') ?? 'Tidak diketahui' }}</strong></p>
    <div class="alert {{ $pilotExpired ? 'danger' : '' }}">
        Mode ini bukan lisensi Publisher komersial. Masa pilot dibatasi maksimal 180 hari per instalasi dan penerbitan baru ditutup pada 31 Desember 2026. Sebelum masa berlaku habis, instalasi harus dialihkan ke aktivasi Publisher melalui prosedur resmi. Database dan jawaban ujian tidak dihapus ketika masa pilot berakhir.
    </div>
@elseif($state)
    <p>Status lokal: <strong>{{ $state->status }}</strong></p><p>Lisensi: <code>{{ $state->license_id }}</code></p><p>Revisi: {{ $state->license_revision }} / lease {{ $state->lease_revision }}</p><p>Berlaku sampai: {{ $state->license_expires_at?->format('d-m-Y H:i:s T') }}</p><p>Lease offline sampai: {{ $state->lease_expires_at?->format('d-m-Y H:i:s T') }}</p><p>Kode sinkronisasi terakhir: <code>{{ $state->last_error_code ?: '—' }}</code></p><p>Terakhir kontak Publisher: {{ $state->last_publisher_check_at?->format('d-m-Y H:i:s T') ?: 'Belum pernah' }}</p><form method="post" action="{{ route('license.refresh') }}" style="margin-top:18px">@csrf<button style="border:0;border-radius:10px;padding:11px 16px;background:#0F8B72;color:#fff;font-weight:700">Sinkronkan Lease dengan Publisher</button></form>
@else
    <p>Status: <strong>Belum diaktivasi</strong></p>
@endif
@if($identity)<p>Installation ID: <code>{{ $identity->installation_id }}</code></p><p>Domain: <code>{{ $identity->registered_domain }}</code></p>@endif
<p class="muted">Jangan menghapus file identitas, status lisensi, atau database untuk mengatasi masalah aktivasi. Hubungi pengelola CBT dengan kode pemeriksaan yang tampil.</p></main></body></html>
