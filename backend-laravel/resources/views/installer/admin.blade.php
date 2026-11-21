@extends('installer.layout')
@section('content')
<div class="space-y-6">
    <div><h2 class="text-xl font-extrabold text-navy-900">Langkah 4: Profil Madrasah & Akun Admin</h2><p class="text-sm text-textSecondary mt-1">Identitas instalasi dan kode tenant internal dibuat otomatis. Satu database CBT berdiri untuk satu madrasah.</p></div>
    <form action="{{ route('installer.admin.save') }}" method="POST" class="space-y-4">@csrf
        <div class="p-4 bg-contentBg border border-cardBorder rounded-xl space-y-4"><h3 class="text-sm font-bold text-navy-900">Profil Madrasah</h3>
            <div><label class="block text-xs font-bold text-navy-900 mb-1">Nama Resmi Madrasah</label><input type="text" name="madrasah_name" value="{{ old('madrasah_name') }}" required maxlength="255" placeholder="misal: MIN 15 Jakarta Selatan" class="w-full px-4 py-2.5 rounded-xl border border-cardBorder text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white"></div>
        </div>
        <div class="p-4 bg-contentBg border border-cardBorder rounded-xl space-y-4"><h3 class="text-sm font-bold text-navy-900">Akun Admin Madrasah Pertama</h3>
            <div><label class="block text-xs font-bold text-navy-900 mb-1">Nama Lengkap</label><input type="text" name="admin_name" value="{{ old('admin_name') }}" required maxlength="255" class="w-full px-4 py-2.5 rounded-xl border border-cardBorder text-sm bg-white"></div>
            <div><label class="block text-xs font-bold text-navy-900 mb-1">Username Admin</label><input type="text" name="admin_username" value="{{ old('admin_username') }}" required minlength="3" maxlength="80" pattern="[A-Za-z0-9._-]+" autocomplete="username" placeholder="misal: adminmin15" class="w-full px-4 py-2.5 rounded-xl border border-cardBorder text-sm bg-white"><p class="text-xs text-textSecondary mt-1">Username dipakai untuk login Admin. Email hanya untuk kontak/pemulihan.</p></div>
            <div><label class="block text-xs font-bold text-navy-900 mb-1">Email Kontak (opsional)</label><input type="email" name="admin_email" value="{{ old('admin_email') }}" maxlength="255" placeholder="admin@madrasah.sch.id" class="w-full px-4 py-2.5 rounded-xl border border-cardBorder text-sm bg-white"></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-xs font-bold text-navy-900 mb-1">Password (minimal 14 karakter)</label><input type="password" name="admin_password" required minlength="14" autocomplete="new-password" class="w-full px-4 py-2.5 rounded-xl border border-cardBorder text-sm bg-white"></div><div><label class="block text-xs font-bold text-navy-900 mb-1">Konfirmasi Password</label><input type="password" name="admin_password_confirmation" required minlength="14" autocomplete="new-password" class="w-full px-4 py-2.5 rounded-xl border border-cardBorder text-sm bg-white"></div></div>
        </div>
        <div class="pt-4 border-t border-cardBorder flex justify-end"><button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl">Simpan & Lanjutkan ke Aktivasi Lisensi</button></div>
    </form>
</div>
@endsection
