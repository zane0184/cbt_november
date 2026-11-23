<div class="space-y-6">
    <section>
        <p class="text-xs font-bold uppercase tracking-widest portal-muted">Akun dan identitas</p>
        <h1 class="mt-1 text-2xl font-extrabold md:text-3xl">Profil Guru</h1>
        <p class="mt-2 text-sm portal-muted">Informasi profil ditampilkan sesuai data kepegawaian dan akun yang terdaftar pada madrasah.</p>
    </section>

    @if (session()->has('message'))
        <div class="portal-card p-4 text-sm font-semibold">{{ session('message') }}</div>
    @endif

    <section class="grid gap-5 lg:grid-cols-[1fr_1.2fr]">
        <div class="portal-card p-5">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl text-2xl font-extrabold" style="background:var(--surface-soft);color:var(--accent)">
                    {{ strtoupper(substr($profile->full_name ?: $user->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-extrabold">{{ $profile->full_name ?: $user->name }}</h2>
                    <p class="mt-1 text-sm portal-muted">{{ $tenant?->name ?? 'Madrasah belum tersedia' }}</p>
                    <span class="mt-2 inline-flex portal-badge">Akun terautentikasi</span>
                </div>
            </div>
            <div class="portal-warning mt-5 text-xs">
                Skema tabel akun pada baseline ini belum memiliki kolom status aktif/nonaktif tersendiri. Status di atas menunjukkan sesi akun yang sedang terautentikasi, bukan pengganti status kepegawaian.
            </div>
            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="portal-muted">Tenant</dt><dd class="text-right font-semibold">{{ $tenant?->code ?? $user->tenant_id }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="portal-muted">Role portal</dt><dd class="font-semibold">GURU</dd></div>
                <div class="flex justify-between gap-4"><dt class="portal-muted">Email akun</dt><dd class="break-all text-right font-semibold">{{ $user->email }}</dd></div>
            </dl>
        </div>

        <div class="portal-card p-5">
            <h2 class="text-lg font-bold">Identitas Kepegawaian</h2>
            <p class="mt-1 text-sm portal-muted">Identitas inti dan relasi penugasan dikelola oleh Admin Madrasah.</p>
            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-xs portal-muted">Nama Guru</dt><dd class="mt-1 font-semibold">{{ $profile->full_name ?: $user->name }}</dd></div>
                <div><dt class="text-xs portal-muted">Employee Identifier</dt><dd class="mt-1 font-semibold">{{ $profile->employee_identifier ?: 'Belum tersedia' }}</dd></div>
                <div><dt class="text-xs portal-muted">NIP</dt><dd class="mt-1 font-semibold">{{ $profile->nip ?: 'Belum tersedia' }}</dd></div>
                <div><dt class="text-xs portal-muted">NIK</dt><dd class="mt-1 font-semibold">{{ $profile->nik ?: 'Belum tersedia' }}</dd></div>
                <div><dt class="text-xs portal-muted">NUPTK</dt><dd class="mt-1 font-semibold">{{ $profile->nuptk ?: 'Belum tersedia' }}</dd></div>
                <div><dt class="text-xs portal-muted">Jenis Kelamin</dt><dd class="mt-1 font-semibold">{{ $profile->gender ?: 'Belum tersedia' }}</dd></div>
            </dl>
        </div>
    </section>

    <section class="portal-card max-w-2xl p-5">
        <h2 class="text-lg font-bold">Informasi Kontak</h2>
        <p class="mt-1 text-sm portal-muted">Pada fondasi ini Guru hanya dapat memperbarui nomor kontak. Email dan identitas kepegawaian tetap read-only sampai tersedia alur verifikasi/perubahan resmi.</p>
        <form wire:submit="savePhone" class="mt-5 space-y-3">
            <label for="teacher-phone" class="block text-sm font-semibold">Nomor Kontak</label>
            <input id="teacher-phone" type="text" wire:model="phone" class="portal-input" maxlength="30" autocomplete="tel" placeholder="Masukkan nomor kontak">
            @error('phone') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <button type="submit" class="portal-button" wire:loading.attr="disabled">Simpan Nomor Kontak</button>
        </form>
    </section>
</div>
