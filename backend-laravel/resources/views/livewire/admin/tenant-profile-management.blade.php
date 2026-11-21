<div class="space-y-6">
    <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Administrasi Madrasah</p>
        <h1 class="mt-2 text-2xl font-bold text-navy-900">Profil & Identitas Madrasah</h1>
        <p class="mt-1 text-sm text-textSecondary">Perbarui identitas institusi untuk tampilan portal, dokumen, dan informasi akademik madrasah.</p>
    </section>
    @if(session()->has('message'))
        <div class="rounded-xl border-l-4 border-emerald-600 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('message') }}</div>
    @endif
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm xl:col-span-2">
            <form wire:submit.prevent="saveProfile" class="space-y-5">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-navy-900">Nama Madrasah</label>
                    <input wire:model="name" type="text" maxlength="255" required class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-navy-900">NSM (12 digit)</label>
                        <input wire:model="nsm" type="text" inputmode="numeric" maxlength="12" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm">
                        @error('nsm') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-navy-900">NPSN (8 digit)</label>
                        <input wire:model="npsn" type="text" inputmode="numeric" maxlength="8" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm">
                        @error('npsn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-navy-900">Alamat</label>
                    <textarea wire:model="address" rows="3" maxlength="2000" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm"></textarea>
                    @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-navy-900">Nomor Telepon</label>
                        <input wire:model="phone" type="text" maxlength="30" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm">
                        @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-navy-900">Email Madrasah</label>
                        <input wire:model="email" type="email" maxlength="255" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm">
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="border-t border-cardBorder pt-5">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-navy-900">Logo Madrasah</label>
                    <input wire:model="logo" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-textSecondary">
                    <p class="mt-1 text-xs text-textSecondary">PNG/JPG/WEBP, maksimal 2 MB. File disimpan pada direktori tenant madrasah.</p>
                    @error('logo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end border-t border-cardBorder pt-4">
                    <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50">
                        <span wire:loading.remove>Simpan Profil Madrasah</span><span wire:loading>Menyimpan...</span>
                    </button>
                </div>
            </form>
        </section>
        <aside class="space-y-4">
            <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm">
                <h2 class="font-bold text-navy-900">Pratinjau Identitas</h2>
                <div class="mt-4 flex min-h-36 flex-col items-center justify-center rounded-xl bg-slate-50 p-5 text-center">
                    @if($logo)
                        <img src="{{ $logo->temporaryUrl() }}" alt="Pratinjau logo" class="mb-3 h-20 w-20 rounded-xl object-contain">
                    @elseif($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo madrasah" class="mb-3 h-20 w-20 rounded-xl object-contain">
                    @else
                        <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-2xl font-black text-emerald-800">{{ strtoupper(substr($name ?: 'M', 0, 1)) }}</div>
                    @endif
                    <p class="font-bold text-navy-900">{{ $name ?: 'Nama Madrasah' }}</p>
                    <p class="mt-1 text-xs text-textSecondary">NSM: {{ $nsm ?: '—' }} · NPSN: {{ $npsn ?: '—' }}</p>
                </div>
                <dl class="mt-4 space-y-2 text-xs">
                    <div class="flex justify-between gap-3"><dt class="text-textSecondary">Kode instalasi</dt><dd class="font-semibold text-navy-900">{{ $tenant->code }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-textSecondary">Status tenant</dt><dd class="font-semibold text-navy-900">{{ $tenant->status }}</dd></div>
                </dl>
            </section>
            <section class="rounded-2xl border border-blue-100 bg-blue-50 p-5 text-xs leading-relaxed text-blue-900">
                Kode instalasi dan status tenant tidak dapat diubah dari halaman ini. Pengaturan tersebut berada pada kewenangan sistem/penerbit, bukan Admin Madrasah.
            </section>
        </aside>
    </div>
</div>
