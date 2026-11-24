<div class="space-y-6">
    <div class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-navy-900">Logo & Favicon Madrasah</h1>
        <p class="mt-1 text-sm text-textSecondary">Unggah aset identitas visual untuk instalasi madrasah ini. File logo dan favicon tersedia sebagai aset branding publik; penyimpanan tetap terpisah pada storage instalasi madrasah ini.</p>
    </div>

    @if(session()->has('message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('message') }}</div>
    @endif
    @error('uploads')<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $message }}</div>@enderror

    <form wire:submit="save" enctype="multipart/form-data" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm space-y-4">
                <div><h2 class="font-bold text-navy-900">Logo aplikasi</h2><p class="mt-1 text-xs text-textSecondary">PNG, JPG, JPEG, atau WebP · maksimal 2 MB · minimal 64 × 64 px.</p></div>
                <div class="flex min-h-32 items-center justify-center rounded-xl border border-dashed border-cardBorder bg-contentBg p-4">
                    @if($appLogoUrl)<img src="{{ $appLogoUrl }}" alt="Logo aplikasi {{ $tenant->name }}" class="max-h-28 max-w-full object-contain">@else<span class="text-xs text-textSecondary">Belum ada logo aplikasi</span>@endif
                </div>
                <input type="file" wire:model="appLogoUpload" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm">
                @if($appLogoUpload)<p class="text-xs text-textSecondary">Dipilih: {{ $appLogoUpload->getClientOriginalName() }}</p>@endif
                @error('appLogoUpload')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                @if($tenant->logo_path)<button type="button" wire:click="removeAppLogo" wire:confirm="Hapus logo aplikasi dari profil madrasah?" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-700">Hapus logo aplikasi</button>@endif
            </section>

            <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm space-y-4">
                <div><h2 class="font-bold text-navy-900">Logo kop dokumen</h2><p class="mt-1 text-xs text-textSecondary">Logo terpisah untuk kop laporan dan dokumen resmi · PNG/JPG/WebP · maksimal 2 MB.</p></div>
                <div class="flex min-h-32 items-center justify-center rounded-xl border border-dashed border-cardBorder bg-contentBg p-4">
                    @if($documentLogoUrl)<img src="{{ $documentLogoUrl }}" alt="Logo kop {{ $tenant->name }}" class="max-h-28 max-w-full object-contain">@else<span class="text-xs text-textSecondary">Belum ada logo kop dokumen</span>@endif
                </div>
                <input type="file" wire:model="documentLogoUpload" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm">
                @if($documentLogoUpload)<p class="text-xs text-textSecondary">Dipilih: {{ $documentLogoUpload->getClientOriginalName() }}</p>@endif
                @error('documentLogoUpload')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                @if($tenant->document_logo_path)<button type="button" wire:click="removeDocumentLogo" wire:confirm="Hapus logo kop dokumen dari profil madrasah?" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-700">Hapus logo kop</button>@endif
            </section>

            <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm space-y-4 lg:col-span-2">
                <div><h2 class="font-bold text-navy-900">Favicon browser</h2><p class="mt-1 text-xs text-textSecondary">PNG atau WebP persegi · 32–256 px · maksimal 512 KB.</p></div>
                <div class="flex items-center gap-4">
                    <div class="flex h-20 w-20 items-center justify-center rounded-xl border border-dashed border-cardBorder bg-contentBg p-2">
                        @if($faviconUpload)<img src="{{ $faviconUpload->temporaryUrl() }}" alt="Pratinjau favicon {{ $tenant->name }}" class="h-14 w-14 object-contain"><span class="sr-only">Pratinjau favicon</span>@elseif($faviconUrl)<img src="{{ $faviconUrl }}" alt="Favicon {{ $tenant->name }}" class="h-14 w-14 object-contain">@else<span class="text-xs text-textSecondary">Belum ada</span>@endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <input type="file" wire:model="faviconUpload" accept="image/png,image/webp" class="block w-full text-sm">
                        @if($faviconUpload)<p class="mt-1 text-xs text-textSecondary">Dipilih: {{ $faviconUpload->getClientOriginalName() }}</p>@endif
                        @error('faviconUpload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        @if($tenant->favicon_path)<button type="button" wire:click="removeFavicon" wire:confirm="Hapus favicon dari profil madrasah?" class="mt-2 rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-700">Hapus favicon</button>@endif
                    </div>
                </div>
            </section>
        </div>

        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Simpan Aset Branding</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
