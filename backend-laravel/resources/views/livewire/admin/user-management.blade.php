<div class="space-y-6">
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div><h1 class="text-2xl font-bold text-navy-900">Manajemen Guru & Siswa</h1><p class="text-xs text-textSecondary mt-1">Kelola akun Guru dan Siswa pada madrasah ini. Akun Admin dan role legacy tidak dapat dibuat atau dikelola dari halaman ini.</p></div>
        <button wire:click="openCreateModal" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-xl text-xs">+ Tambah Pengguna</button>
    </div>
    @if (session()->has('message'))<div class="bg-emerald-50 border-l-4 border-emerald-600 text-emerald-800 p-4 rounded-xl text-sm">{{ session('message') }}</div>@endif
    <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden">
        <div class="p-4 md:p-6 border-b border-cardBorder bg-gray-50/50 flex flex-col md:flex-row gap-3"><input wire:model.live="search" type="text" placeholder="Cari nama, username, atau email..." class="border border-cardBorder rounded-xl px-4 py-2 text-sm flex-1"><select wire:model.live="roleFilter" class="border border-cardBorder rounded-xl px-4 py-2 text-sm md:w-56"><option value="">Semua Guru & Siswa</option><option value="GURU">Guru</option><option value="SISWA">Siswa</option></select><select wire:model.live="statusFilter" class="border border-cardBorder rounded-xl px-4 py-2 text-sm md:w-48"><option value="">Semua Status Akun</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-cardBorder"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-bold uppercase">Nama & Kontak</th><th class="px-6 py-3 text-left text-xs font-bold uppercase">Identitas Login</th><th class="px-6 py-3 text-left text-xs font-bold uppercase">Peran</th><th class="px-6 py-3 text-left text-xs font-bold uppercase">Status Akun</th><th class="px-6 py-3 text-left text-xs font-bold uppercase">Dibuat</th><th class="px-6 py-3 text-right text-xs font-bold uppercase">Aksi</th></tr></thead><tbody class="bg-white divide-y divide-cardBorder">
        @forelse($users as $user)<tr class="hover:bg-gray-50"><td class="px-6 py-4"><div class="text-sm font-bold text-navy-900">{{ $user->name }}</div><div class="text-xs text-textSecondary">{{ $user->email ?: 'Email belum diisi' }}</div></td><td class="px-6 py-4 text-xs"><div>Username tambahan: <strong>{{ $user->username ?: '—' }}</strong></div>@if($user->teacherProfile)<div>Kategori: <strong>{{ $user->teacherProfile->employment_type ?: 'Belum ditetapkan' }}</strong></div><div>Identitas login: <strong>{{ $user->teacherProfile->employment_type === 'ASN' ? ($user->teacherProfile->nip ?: 'NIP belum diisi') : ($user->teacherProfile->employment_type === 'HONORER' ? ($user->teacherProfile->nik ?: 'NIK belum diisi') : 'Belum ditetapkan') }}</strong></div>@elseif($user->studentProfile)<div>Login NISN: <strong>{{ $user->studentProfile->nisn }}</strong></div><div>Status akademik: <strong>{{ $user->studentProfile->academic_status }}</strong></div><div>Tahun masuk: <strong>{{ $user->studentProfile->entryAcademicYear?->name ?? 'Belum tercatat' }}</strong></div>@endif</td><td class="px-6 py-4">@foreach($user->roles as $role)<span class="px-2.5 py-1 text-xs font-bold rounded-md border bg-blue-50 text-blue-700 border-blue-200">{{ $role->name }}</span>@endforeach</td><td class="px-6 py-4">@if($user->is_active)<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Aktif</span>@else<span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700">Nonaktif</span>@endif</td><td class="px-6 py-4 text-xs font-mono">{{ $user->created_at?->format('Y-m-d H:i') ?: '—' }}</td><td class="px-6 py-4 text-right whitespace-nowrap"><button type="button" wire:click="openEditModal('{{ $user->id }}')" class="text-blue-700 hover:underline text-xs font-bold mr-3">Edit</button><button type="button" wire:click="openResetPasswordModal('{{ $user->id }}')" class="text-amber-700 hover:underline text-xs font-bold mr-3">Reset Password</button><button type="button" wire:click="openAccountStatusModal('{{ $user->id }}', {{ $user->is_active ? 'false' : 'true' }})" class="{{ $user->is_active ? 'text-red-700' : 'text-emerald-700' }} hover:underline text-xs font-bold">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></td></tr>
        @empty<tr><td colspan="6" class="px-6 py-12 text-center text-textSecondary text-sm">Tidak ada data pengguna.</td></tr>@endforelse
        </tbody></table></div><div class="p-4 border-t border-cardBorder">{{ $users->links() }}</div>
    </div>
    @if($showCreateModal)<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60"><div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full p-6 space-y-4 border border-cardBorder max-h-[90vh] overflow-y-auto"><h3 class="text-lg font-bold text-navy-900">Tambah Pengguna Baru</h3><form wire:submit.prevent="createUser" class="space-y-4">
        <div><label class="block text-xs font-bold mb-1">Nama Lengkap</label><input wire:model="name" type="text" required class="w-full border rounded-xl px-3.5 py-2 text-sm">@error('name')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
        <div><label class="block text-xs font-bold mb-1">Peran</label><select wire:model.live="selectedRole" required class="w-full border rounded-xl px-3.5 py-2 text-sm"><option value="">Pilih Peran</option>@foreach($roles as $role)<option value="{{ $role->name }}">{{ $role->label }}</option>@endforeach</select>@error('selectedRole')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
        <div><label class="block text-xs font-bold mb-1">Username tambahan (opsional; bukan identitas login Guru/Siswa)</label><input wire:model="username" type="text" autocomplete="off" class="w-full border rounded-xl px-3.5 py-2 text-sm" placeholder="akun.guru">@error('username')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
        @if($selectedRole === 'GURU')
            <div><label class="block text-xs font-bold mb-1">Kategori Kepegawaian</label><select wire:model.live="employmentType" required class="w-full border rounded-xl px-3.5 py-2 text-sm"><option value="">Pilih kategori</option><option value="ASN">ASN (login menggunakan NIP)</option><option value="HONORER">Honorer (login menggunakan NIK)</option></select>@error('employmentType')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
            @if($employmentType === 'ASN')
                <div><label class="block text-xs font-bold mb-1">NIP (18 digit) — identitas login wajib</label><input wire:model="nip" type="text" inputmode="numeric" maxlength="18" required class="w-full border rounded-xl px-3.5 py-2 text-sm">@error('nip')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
            @elseif($employmentType === 'HONORER')
                <div><label class="block text-xs font-bold mb-1">NIK (16 digit) — identitas login wajib</label><input wire:model="nik" type="text" inputmode="numeric" maxlength="16" required class="w-full border rounded-xl px-3.5 py-2 text-sm">@error('nik')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
            @endif
            <p class="text-xs text-textSecondary">NIP hanya digunakan untuk Guru ASN. NIK hanya digunakan untuk Guru Honorer. Keduanya tidak saling menggantikan; NUPTK bukan identitas login.</p>
        @endif
        @if($selectedRole === 'SISWA')
            <div><label class="block text-xs font-bold mb-1">NISN (10 digit) — identitas login wajib</label><input wire:model="nisn" type="text" inputmode="numeric" maxlength="10" required class="w-full border rounded-xl px-3.5 py-2 text-sm">@error('nisn')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
            <div><label class="block text-xs font-bold mb-1">Tahun Masuk</label><select wire:model="entryAcademicYearId" required class="w-full border rounded-xl px-3.5 py-2 text-sm"><option value="">Pilih tahun pelajaran aktif</option>@foreach($activeAcademicYears as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach</select>@error('entryAcademicYearId')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                @if($activeAcademicYears->isEmpty())<span class="mt-1 block text-xs text-amber-700">Aktifkan tahun pelajaran terlebih dahulu pada Data Akademik.</span>@endif
            </div>
        @endif
        <div><label class="block text-xs font-bold mb-1">Email Kontak / Pemulihan (opsional)</label><input wire:model="email" type="email" class="w-full border rounded-xl px-3.5 py-2 text-sm">@error('email')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
        <div><label class="block text-xs font-bold mb-1">Password (minimal 14 karakter)</label><input wire:model="password" type="password" minlength="14" required autocomplete="new-password" class="w-full border rounded-xl px-3.5 py-2 text-sm">@error('password')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
        <div class="flex justify-end gap-3 pt-3 border-t border-cardBorder"><button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-xs font-bold rounded-xl bg-gray-100">Batal</button><button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 rounded-xl">Simpan Pengguna</button></div>
    </form></div></div>@endif

    @if($showEditModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
        <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full p-6 space-y-4 border border-cardBorder max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-navy-900">Edit Data Pengguna</h3>
            <form wire:submit.prevent="updateUser" class="space-y-3">
                <div><label class="block text-xs font-bold mb-1">Nama Lengkap</label><input wire:model="editName" type="text" required class="w-full border rounded-xl px-3.5 py-2 text-sm">@error('editName')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
                <div><label class="block text-xs font-bold mb-1">Username tambahan (opsional; bukan identitas login Guru/Siswa)</label><input wire:model="editUsername" type="text" autocomplete="off" class="w-full border rounded-xl px-3.5 py-2 text-sm">@error('editUsername')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
                <div><label class="block text-xs font-bold mb-1">Email Kontak / Pemulihan (opsional)</label><input wire:model="editEmail" type="email" class="w-full border rounded-xl px-3.5 py-2 text-sm">@error('editEmail')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
                @if($editingRole === 'GURU')
                    <div><label class="block text-xs font-bold mb-1">Kategori Kepegawaian</label><select wire:model.live="editEmploymentType" required class="w-full border rounded-xl px-3.5 py-2 text-sm"><option value="">Pilih kategori</option><option value="ASN">ASN (login menggunakan NIP)</option><option value="HONORER">Honorer (login menggunakan NIK)</option></select>@error('editEmploymentType')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
                    @if($editEmploymentType === 'ASN')
                        <div><label class="block text-xs font-bold mb-1">NIP (18 digit) — identitas login wajib</label><input wire:model="editNip" type="text" inputmode="numeric" maxlength="18" required class="w-full border rounded-xl px-3.5 py-2 text-sm">@error('editNip')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
                    @elseif($editEmploymentType === 'HONORER')
                        <div><label class="block text-xs font-bold mb-1">NIK (16 digit) — identitas login wajib</label><input wire:model="editNik" type="text" inputmode="numeric" maxlength="16" required class="w-full border rounded-xl px-3.5 py-2 text-sm">@error('editNik')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
                    @endif
                    <p class="text-xs text-textSecondary">Identitas login mengikuti kategori kepegawaian dan tidak dapat dipertukarkan.</p>
                @else
                    <div><label class="block text-xs font-bold mb-1">NISN</label><input wire:model="editNisn" type="text" maxlength="10" required class="w-full border rounded-xl px-3.5 py-2 text-sm">@error('editNisn')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
                @endif
                <div class="flex justify-end gap-3 pt-3 border-t border-cardBorder"><button type="button" wire:click="$set('showEditModal', false)" class="px-4 py-2 text-xs font-bold rounded-xl bg-gray-100">Batal</button><button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-blue-700 rounded-xl">Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
    @endif
    @if($showAccountStatusModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-cardBorder">
            <h3 class="text-lg font-bold text-navy-900">{{ $statusTarget ? 'Aktifkan Akun' : 'Nonaktifkan Akun' }}</h3>
            <p class="text-xs text-textSecondary">{{ $statusTarget ? 'Akun akan dapat login kembali. Sesi dan token lama tidak dipulihkan.' : 'Akun akan ditolak saat login berikutnya dan seluruh token API akan dicabut. Sesi Web yang masih terbuka akan ditolak pada request berikutnya.' }}</p>
            <form wire:submit.prevent="updateAccountStatus" class="space-y-3">
                <div><label class="block text-xs font-bold mb-1">Alasan perubahan (wajib)</label><textarea wire:model="statusReason" rows="3" minlength="10" maxlength="1000" required class="w-full border rounded-xl px-3.5 py-2 text-sm"></textarea>@error('statusReason')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
                <div class="flex justify-end gap-3 pt-3 border-t border-cardBorder"><button type="button" wire:click="$set('showAccountStatusModal', false)" class="px-4 py-2 text-xs font-bold rounded-xl bg-gray-100">Batal</button><button type="submit" class="px-4 py-2 text-xs font-bold text-white {{ $statusTarget ? 'bg-emerald-700' : 'bg-red-700' }} rounded-xl">{{ $statusTarget ? 'Aktifkan Akun' : 'Nonaktifkan Akun' }}</button></div>
            </form>
        </div>
    </div>
    @endif
    @if($showResetPasswordModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-cardBorder">
            <h3 class="text-lg font-bold text-navy-900">Reset Password Pengguna</h3>
            <p class="text-xs text-textSecondary">Password baru minimal 14 karakter. Password lama tidak ditampilkan dan tidak dicatat ke audit log.</p>
            <form wire:submit.prevent="resetUserPassword" class="space-y-3">
                <div><label class="block text-xs font-bold mb-1">Password Baru</label><input wire:model="resetPassword" type="password" minlength="14" required autocomplete="new-password" class="w-full border rounded-xl px-3.5 py-2 text-sm">@error('resetPassword')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror</div>
                <div class="flex justify-end gap-3 pt-3 border-t border-cardBorder"><button type="button" wire:click="$set('showResetPasswordModal', false)" class="px-4 py-2 text-xs font-bold rounded-xl bg-gray-100">Batal</button><button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-amber-700 rounded-xl">Reset Password</button></div>
            </form>
        </div>
    </div>
    @endif
</div>
