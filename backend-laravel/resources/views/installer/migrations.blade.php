@extends('installer.layout')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-xl font-extrabold text-navy-900">Langkah 3: Migrasi Skema Database</h2>
        <p class="text-sm text-textSecondary mt-1">Sistem hanya boleh membuat skema pada database MySQL baru yang kosong dan telah diverifikasi.</p>
    </div>

    @if (!$canRunMigrations)
        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 text-amber-900 text-sm rounded-r-xl shadow-sm space-y-2">
            <div class="font-bold">Proteksi Database Aktif — Migrasi Diblokir</div>
            <p class="text-xs">{{ $databaseStatus['message'] ?? 'Database belum diverifikasi.' }}</p>
            <p class="text-xs">Installer tidak akan menghapus, mengosongkan, atau menimpa tabel yang sudah ada. Pilih database baru yang kosong dan ulangi verifikasi.</p>
        </div>
        <div class="pt-4 border-t border-cardBorder">
            <a href="{{ route('installer.database') }}" class="inline-flex px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition shadow-md">
                Kembali ke Konfigurasi Database
            </a>
        </div>
    @else
        <div class="p-4 bg-contentBg border border-cardBorder rounded-xl space-y-3">
            <h3 class="text-sm font-bold text-navy-900">Target database terverifikasi kosong</h3>
            <p class="text-xs text-textSecondary">Migrasi akan membuat skema CBT Madrasah. Jika migrasi gagal atau terhenti, database dapat berada pada kondisi parsial dan perlu recovery manual; installer tidak akan mengulang atau menghapus tabel secara otomatis.</p>
        </div>
        <form action="{{ route('installer.migrations.run') }}" method="POST" class="pt-4 border-t border-cardBorder flex justify-end items-center">
            @csrf
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition shadow-md flex items-center gap-2">
                <span>Jalankan Migrasi Database</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>
    @endif
</div>
@endsection
