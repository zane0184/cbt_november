<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Administrasi Madrasah</p>
        <h1 class="mt-1 text-2xl font-bold text-navy-900">Profil Madrasah</h1>
        <p class="mt-2 text-sm text-textSecondary">Perbarui identitas dasar madrasah pada instalasi ini. Kode instalasi dan status lisensi dikelola oleh sistem, bukan dari halaman ini.</p>
    </div>

    @if (session()->has('message'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6 rounded-2xl border border-cardBorder bg-white p-5 shadow-sm sm:p-7">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="madrasah-name" class="mb-1.5 block text-sm font-semibold text-navy-900">Nama resmi madrasah <span class="text-red-600">*</span></label>
                <input id="madrasah-name" type="text" wire:model="name" maxlength="255" required class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="madrasah-nsm" class="mb-1.5 block text-sm font-semibold text-navy-900">NSM</label>
                <input id="madrasah-nsm" type="text" wire:model="nsm" maxlength="12" inputmode="numeric" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                @error('nsm') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="madrasah-npsn" class="mb-1.5 block text-sm font-semibold text-navy-900">NPSN</label>
                <input id="madrasah-npsn" type="text" wire:model="npsn" maxlength="8" inputmode="numeric" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                @error('npsn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="madrasah-address" class="mb-1.5 block text-sm font-semibold text-navy-900">Alamat</label>
                <textarea id="madrasah-address" wire:model="address" rows="3" maxlength="5000" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
                @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="madrasah-phone" class="mb-1.5 block text-sm font-semibold text-navy-900">Telepon</label>
                <input id="madrasah-phone" type="tel" wire:model="phone" maxlength="30" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="madrasah-email" class="mb-1.5 block text-sm font-semibold text-navy-900">Email</label>
                <input id="madrasah-email" type="email" wire:model="email" maxlength="255" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-cardBorder pt-5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-textSecondary">Perubahan dicatat pada audit log instalasi ini.</p>
            <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Simpan Profil</span>
                <span wire:loading wire:target="save">Menyimpan…</span>
            </button>
        </div>
    </form>

    <div class="rounded-2xl border border-dashed border-cardBorder bg-gray-50 p-5">
        <h2 class="text-sm font-bold text-navy-900">Identitas instalasi</h2>
        <dl class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-xs text-textSecondary">Kode instalasi</dt>
                <dd class="mt-1 font-semibold text-navy-900">{{ $tenant->code }}</dd>
            </div>
            <div>
                <dt class="text-xs text-textSecondary">Status data lokal</dt>
                <dd class="mt-1 font-semibold text-navy-900">{{ $tenant->status }}</dd>
            </div>
        </dl>
        <p class="mt-3 text-xs text-textSecondary">Kode dan status hanya ditampilkan. Keduanya tidak dapat diubah dari portal Admin Madrasah.</p>
    </div>
</div>
