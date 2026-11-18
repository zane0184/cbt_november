@extends('installer.layout')

@section('content')
<div class="space-y-6 text-center py-6">
    <div class="w-16 h-16 bg-amber-100 text-amber-700 rounded-full flex items-center justify-center mx-auto shadow-md">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0Z"/></svg>
    </div>

    <div>
        <h2 class="text-2xl font-extrabold text-navy-900">Verifikasi Akhir Instalasi CBT Madrasah</h2>
        <p class="text-sm text-textSecondary mt-2 max-w-lg mx-auto">
            Tenant dan akun Admin Madrasah telah dibuat. Installer belum dikunci sampai pemeriksaan migration, tenant, role, permission, dan konfigurasi selesai.
        </p>
        <p class="text-xs font-semibold text-amber-700 mt-3">Status saat ini: {{ $status['state'] }}</p>
    </div>

    <div class="p-4 bg-contentBg border border-cardBorder rounded-xl max-w-md mx-auto text-left text-xs space-y-2">
        <p class="font-bold text-navy-900">Pemeriksaan yang dilakukan saat finalisasi:</p>
        <ul class="list-disc list-inside text-textSecondary space-y-1">
            <li>Seluruh migration tercatat berhasil.</li>
            <li>Tenant dan Admin Madrasah berada pada tenant yang sama.</li>
            <li>Akun awal hanya memiliki role ADMIN_MADRASAH, bukan SUPER_ADMIN.</li>
            <li>Permission struktur kurikulum tersedia sesuai prinsip least privilege.</li>
            <li>Konfigurasi database dan APP_KEY dapat diverifikasi.</li>
        </ul>
    </div>

    <form method="POST" action="{{ route('installer.finalize') }}" class="pt-4">
        @csrf
        <button type="submit" class="inline-flex items-center gap-2 px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition shadow-lg">
            <span>Verifikasi Akhir dan Kunci Installer</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </button>
    </form>
</div>
@endsection
