<div class="space-y-6">
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-navy-900">Profil Madrasah</h1>
                <p class="mt-1 text-sm text-textSecondary">Kelola identitas resmi madrasah untuk portal dan dokumen. Perubahan hanya berlaku pada instalasi ini.</p>
            </div>
            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">Instalasi lokal</span>
        </div>
    </div>

    @if(session()->has('message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('message') }}</div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm space-y-5">
            <div>
                <h2 class="text-base font-bold text-navy-900">Identitas resmi</h2>
                <p class="mt-1 text-xs text-textSecondary">Kode instalasi tidak dapat diubah dari halaman ini.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="name" class="mb-1 block text-xs font-bold text-textSecondary">Nama resmi madrasah *</label>
                    <input id="name" wire:model.blur="name" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm" maxlength="255">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="shortName" class="mb-1 block text-xs font-bold text-textSecondary">Nama singkat</label>
                    <input id="shortName" wire:model.blur="shortName" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm" maxlength="120">
                    @error('shortName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="level" class="mb-1 block text-xs font-bold text-textSecondary">Jenjang *</label>
                    <select id="level" wire:model="level" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm">
                        <option value="">Pilih jenjang</option><option value="MI">MI</option><option value="MTs">MTs</option><option value="MA">MA</option><option value="RA">RA</option>
                    </select>
                    @error('level')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="nsm" class="mb-1 block text-xs font-bold text-textSecondary">NSM (12 digit)</label>
                    <input id="nsm" inputmode="numeric" wire:model.blur="nsm" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm" maxlength="12">
                    @error('nsm')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="npsn" class="mb-1 block text-xs font-bold text-textSecondary">NPSN (8 digit)</label>
                    <input id="npsn" inputmode="numeric" wire:model.blur="npsn" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm" maxlength="8">
                    @error('npsn')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="ownershipStatus" class="mb-1 block text-xs font-bold text-textSecondary">Status madrasah *</label>
                    <select id="ownershipStatus" wire:model="ownershipStatus" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm">
                        <option value="">Pilih status</option><option value="NEGERI">Negeri</option><option value="SWASTA">Swasta</option>
                    </select>
                    @error('ownershipStatus')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="principalName" class="mb-1 block text-xs font-bold text-textSecondary">Nama Kepala Madrasah</label>
                    <input id="principalName" wire:model.blur="principalName" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm" maxlength="180">
                    @error('principalName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm space-y-5">
            <div><h2 class="text-base font-bold text-navy-900">Alamat dan kontak</h2><p class="mt-1 text-xs text-textSecondary">Informasi ini dapat digunakan pada kop dokumen setelah pengaturan dokumen diaktifkan.</p></div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="address" class="mb-1 block text-xs font-bold text-textSecondary">Alamat lengkap *</label>
                    <textarea id="address" wire:model.blur="address" rows="3" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm"></textarea>
                    @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div><label for="village" class="mb-1 block text-xs font-bold text-textSecondary">Desa/Kelurahan</label><input id="village" wire:model.blur="village" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm">@error('village')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="district" class="mb-1 block text-xs font-bold text-textSecondary">Kecamatan</label><input id="district" wire:model.blur="district" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm">@error('district')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="city" class="mb-1 block text-xs font-bold text-textSecondary">Kabupaten/Kota</label><input id="city" wire:model.blur="city" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm">@error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="province" class="mb-1 block text-xs font-bold text-textSecondary">Provinsi</label><input id="province" wire:model.blur="province" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm">@error('province')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="postalCode" class="mb-1 block text-xs font-bold text-textSecondary">Kode pos (5 digit)</label><input id="postalCode" inputmode="numeric" wire:model.blur="postalCode" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm" maxlength="5">@error('postalCode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="phone" class="mb-1 block text-xs font-bold text-textSecondary">Telepon</label><input id="phone" wire:model.blur="phone" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm" maxlength="25">@error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="email" class="mb-1 block text-xs font-bold text-textSecondary">Email</label><input id="email" type="email" wire:model.blur="email" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm" maxlength="255">@error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="website" class="mb-1 block text-xs font-bold text-textSecondary">Website</label><input id="website" type="url" wire:model.blur="website" placeholder="https://" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm" maxlength="255">@error('website')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            </div>
        </section>

        <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm space-y-3">
            <h2 class="text-base font-bold text-navy-900">Informasi tambahan</h2>
            <textarea wire:model.blur="additionalInfo" rows="4" maxlength="5000" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm" placeholder="Informasi tambahan yang diperlukan pada profil madrasah"></textarea>
            @error('additionalInfo')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        </section>

        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Simpan Profil Madrasah</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
