@extends('installer.layout')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-xl font-extrabold text-navy-900">Langkah 1: Pemeriksaan Persyaratan Sistem</h2>
        <p class="text-sm text-textSecondary mt-1">Sistem melakukan verifikasi kesiapan server, ekstensi PHP, dan izin folder sebelum proses instalasi dilanjutkan.</p>
    </div>

    <!-- PHP Version -->
    <div class="border border-cardBorder rounded-xl p-4 bg-contentBg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $requirements['php']['satisfied'] ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-navy-900">Versi PHP Server</h3>
                    <p class="text-xs text-textSecondary">Dibutuhkan min. PHP {{ $requirements['php']['required'] }} (Terpasang: {{ $requirements['php']['current'] }})</p>
                </div>
            </div>
            <span class="text-xs font-bold px-3 py-1 rounded-full {{ $requirements['php']['satisfied'] ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                {{ $requirements['php']['satisfied'] ? 'MEMENUHI' : 'TIDAK MEMENUHI' }}
            </span>
        </div>
    </div>

    <!-- Required PHP Extensions -->
    <div>
        <h3 class="text-sm font-bold text-navy-900 mb-3">Ekstensi PHP Wajib</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            @foreach ($requirements['extensions']['list'] as $ext => $loaded)
                <div class="flex items-center justify-between p-3 border border-cardBorder rounded-xl bg-white shadow-2xs">
                    <span class="text-xs font-semibold text-navy-900">{{ $ext }}</span>
                    <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full {{ $loaded ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                        {{ $loaded ? 'ADA' : 'HILANG' }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Folder Permissions -->
    <div>
        <h3 class="text-sm font-bold text-navy-900 mb-3">Izin Akses Tulis Direktori (Write Permission)</h3>
        <div class="space-y-2">
            @foreach ($requirements['permissions']['list'] as $key => $perm)
                <div class="flex items-center justify-between p-3 border border-cardBorder rounded-xl bg-white">
                    <div class="truncate mr-3">
                        <p class="text-xs font-bold text-navy-900">{{ $key }}</p>
                        <p class="text-[11px] text-textSecondary truncate">{{ $perm['path'] }}</p>
                    </div>
                    <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-full shrink-0 {{ $perm['writable'] ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                        {{ $perm['writable'] ? 'DAPAT DITULIS (775)' : 'TIDAK DAPAT DITULIS' }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="border border-cardBorder rounded-xl p-4 bg-contentBg flex items-center justify-between">
        <div><h3 class="text-sm font-bold text-navy-900">APP_KEY Enkripsi Instalasi</h3><p class="text-xs text-textSecondary">Wajib sebelum pembuatan kunci identitas instalasi. Jika belum ada, jalankan <code>php artisan key:generate</code> melalui terminal hosting.</p></div>
        <span class="text-xs font-bold px-3 py-1 rounded-full {{ $requirements['app_key']['configured'] ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">{{ $requirements['app_key']['configured'] ? 'TERPASANG' : 'BELUM ADA' }}</span>
    </div>

    <!-- Environment Limits -->
    <div class="border border-cardBorder rounded-xl p-4 bg-gray-50 text-xs space-y-2">
        <h3 class="font-bold text-navy-900">Info Batasan Lingkungan Web Host</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-textSecondary">
            <div><span class="font-bold text-navy-900">HTTPS Status:</span> {{ $requirements['environment']['https'] ? 'Aktif (Terenkripsi)' : 'Non-HTTPS (HTTP)' }}</div>
            <div><span class="font-bold text-navy-900">Upload Max:</span> {{ $requirements['environment']['upload_max'] }}</div>
            <div><span class="font-bold text-navy-900">Post Max:</span> {{ $requirements['environment']['post_max'] }}</div>
            <div><span class="font-bold text-navy-900">Memory Limit:</span> {{ $requirements['environment']['memory_limit'] }}</div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="pt-4 border-t border-cardBorder flex justify-between items-center">
        <p class="text-xs text-textSecondary">
            @if ($requirements['is_ready'])
                Seluruh persyaratan sistem terpenuhi. Silakan lanjut ke pengaturan database.
            @else
                Beberapa persyaratan belum terpenuhi. Harap perbaiki konfigurasi server Anda.
            @endif
        </p>

        @if ($requirements['is_ready'])
            <a href="{{ route('installer.database') }}" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition shadow-md flex items-center gap-2">
                <span>Lanjut ke Database</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        @else
            <button disabled class="px-6 py-2.5 bg-gray-300 text-gray-500 font-bold text-sm rounded-xl cursor-not-allowed">
                Lanjut ke Database (Terkunci)
            </button>
        @endif
    </div>
</div>
@endsection
