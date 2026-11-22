<div class="mx-auto max-w-5xl space-y-6">
    <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm md:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Administrasi Madrasah</p>
                <h1 class="mt-1 text-2xl font-bold text-navy-900">Identitas Madrasah</h1>
                <p class="mt-2 text-sm text-textSecondary">Perbarui identitas resmi yang digunakan pada dashboard dan kop laporan madrasah.</p>
            </div>
            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800">Tenant: {{ $tenant->code }}</span>
        </div>
    </section>

    @if(session()->has('madrasahProfileMessage'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('madrasahProfileMessage') }}</div>
    @endif

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="font-bold text-navy-900">Data Resmi Madrasah</h2>
            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="madrasah-name" class="mb-1.5 block text-xs font-bold text-navy-900">Nama Madrasah</label>
                    <input id="madrasah-name" wire:model="name" type="text" maxlength="255" required class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="madrasah-nsm" class="mb-1.5 block text-xs font-bold text-navy-900">NSM</label>
                    <input id="madrasah-nsm" wire:model="nsm" type="text" inputmode="numeric" maxlength="12" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    @error('nsm') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="madrasah-npsn" class="mb-1.5 block text-xs font-bold text-navy-900">NPSN</label>
                    <input id="madrasah-npsn" wire:model="npsn" type="text" inputmode="numeric" maxlength="8" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    @error('npsn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="madrasah-address" class="mb-1.5 block text-xs font-bold text-navy-900">Alamat</label>
                    <textarea id="madrasah-address" wire:model="address" rows="3" maxlength="2000" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
                    @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="madrasah-phone" class="mb-1.5 block text-xs font-bold text-navy-900">Telepon</label>
                    <input id="madrasah-phone" wire:model="phone" type="text" maxlength="30" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="madrasah-email" class="mb-1.5 block text-xs font-bold text-navy-900">Email Resmi</label>
                    <input id="madrasah-email" wire:model="email" type="email" maxlength="255" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <aside class="space-y-4">
            <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm">
                <h2 class="font-bold text-navy-900">Logo Madrasah</h2>
                <div class="mt-4 flex min-h-36 items-center justify-center rounded-xl border border-dashed border-cardBorder bg-slate-50 p-4">
                    @if($logo)
                        <img src="{{ $logo->temporaryUrl() }}" alt="Pratinjau logo madrasah" class="max-h-28 max-w-full object-contain">
                    @elseif($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo madrasah" class="max-h-28 max-w-full object-contain">
                    @else
                        <div class="text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-2xl font-bold text-emerald-700">{{ strtoupper(substr($tenant->name, 0, 1)) }}</div>
                            <p class="mt-2 text-xs text-textSecondary">Belum ada logo</p>
                        </div>
                    @endif
                </div>
                <label for="madrasah-logo" class="mt-4 block text-xs font-bold text-navy-900">Unggah logo baru</label>
                <input id="madrasah-logo" wire:model="logo" type="file" accept=".jpg,.jpeg,.png,.webp" class="mt-2 block w-full text-xs text-textSecondary file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:font-semibold file:text-emerald-800">
                <p class="mt-2 text-[11px] leading-5 text-textSecondary">Format JPG, PNG, atau WebP. Maksimal 2 MB. Logo lama tidak dihapus otomatis agar histori file tetap aman.</p>
                @error('logo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <div wire:loading wire:target="logo" class="mt-2 text-xs text-emerald-700">Mengunggah dan memeriksa file…</div>
            </section>
            <section class="rounded-2xl border border-cardBorder bg-slate-50 p-4">
                <p class="text-xs font-bold text-navy-900">Keamanan perubahan</p>
                <p class="mt-1 text-xs leading-5 text-textSecondary">Setiap perubahan identitas dicatat dengan aktor, tenant, waktu, dan nilai sebelum/sesudah. Kode tenant dan status instalasi tidak dapat diubah dari halaman ini.</p>
            </section>
            <button type="submit" wire:loading.attr="disabled" class="w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Simpan Identitas Madrasah</span>
                <span wire:loading wire:target="save">Menyimpan…</span>
            </button>
        </aside>
    </form>
</div>
