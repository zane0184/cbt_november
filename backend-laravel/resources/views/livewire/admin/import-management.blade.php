<div class="space-y-6">
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-navy-900">Import Data Guru & Siswa</h1>
        <p class="mt-1 text-sm text-textSecondary">Unduh template sesuai data master madrasah, unggah, periksa hasil validasi, lalu konfirmasi import.</p>
    </div>

    @if($notice)<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">{{ $notice }}</div>@endif
    @error('upload')<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $message }}</div>@enderror

    <div class="grid gap-4 md:grid-cols-2">
        <a href="/admin/import/template/GURU" class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm hover:border-emerald-500">
            <div class="text-lg font-bold text-navy-900">Template Import Guru.xlsx</div>
            <p class="mt-1 text-sm text-textSecondary">Petunjuk, Data Guru, Penugasan Guru, dan referensi tenant.</p>
            <span class="mt-3 inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Unduh Template Guru</span>
        </a>
        <a href="/admin/import/template/SISWA" class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm hover:border-emerald-500">
            <div class="text-lg font-bold text-navy-900">Template Import Siswa.xlsx</div>
            <p class="mt-1 text-sm text-textSecondary">Petunjuk, Data Siswa, dan referensi rombel/tahun pelajaran.</p>
            <span class="mt-3 inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Unduh Template Siswa</span>
        </a>
    </div>

    <div class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm space-y-4">
        <h2 class="text-lg font-bold text-navy-900">Unggah Workbook</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold">Jenis Import</label>
                <select wire:model="type" class="w-full rounded-xl border border-cardBorder px-3 py-2">
                    <option value="GURU">Guru</option><option value="SISWA">Siswa</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">File .xlsx (maks. 10 MB)</label>
                <input type="file" wire:model="upload" accept=".xlsx" class="w-full rounded-xl border border-cardBorder p-2 text-sm">
                @error('upload')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
            </div>
        </div>
        <div wire:loading wire:target="upload,previewUpload" class="text-sm text-textSecondary">Memproses workbook...</div>
        <button wire:click="previewUpload" wire:loading.attr="disabled" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50">Validasi & Preview</button>
    </div>

    @if($batch)
        <div class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><h2 class="text-lg font-bold text-navy-900">Preview / Hasil Import</h2><p class="text-xs text-textSecondary">Batch {{ $batch->id }} · {{ $batch->original_filename }} · Status {{ $batch->status }}</p></div>
                @if($batch->status === 'PREVIEW')
                    <div class="flex gap-2"><button wire:click="discardPreview" class="rounded-xl border px-4 py-2 text-sm">Batalkan</button><button wire:click="confirmImport" wire:loading.attr="disabled" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white">Konfirmasi Import</button></div>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="rounded-xl bg-gray-50 p-3"><div class="text-xs text-textSecondary">Total baris</div><div class="text-xl font-bold">{{ $batch->total_rows }}</div></div>
                <div class="rounded-xl bg-emerald-50 p-3"><div class="text-xs text-emerald-800">Valid / berhasil</div><div class="text-xl font-bold">{{ $batch->valid_rows }}</div></div>
                <div class="rounded-xl bg-amber-50 p-3"><div class="text-xs text-amber-800">Dilewati</div><div class="text-xl font-bold">{{ $batch->skipped_rows }}</div></div>
                <div class="rounded-xl bg-red-50 p-3"><div class="text-xs text-red-800">Gagal</div><div class="text-xl font-bold">{{ $batch->failed_rows }}</div></div>
            </div>
            <div class="overflow-x-auto"><table class="min-w-full text-sm">
                <thead><tr class="border-b text-left"><th class="p-2">Sheet</th><th class="p-2">Baris</th><th class="p-2">Nama / Identitas</th><th class="p-2">Status</th><th class="p-2">Alasan</th></tr></thead>
                <tbody>@foreach($batch->rows as $row)<tr class="border-b align-top"><td class="p-2">{{ $row->sheet_name }}</td><td class="p-2">{{ $row->row_number }}</td><td class="p-2">{{ $row->row_summary['name'] ?? '—' }}<div class="text-xs text-textSecondary">••••{{ $row->row_summary['identity_suffix'] ?? '—' }}</div></td><td class="p-2 font-semibold">{{ $row->status }}</td><td class="p-2">{{ $row->reason ?: '—' }}</td></tr>@endforeach</tbody>
            </table></div>
        </div>
    @endif

    @if(count($activationLinks))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 space-y-3">
            <h2 class="font-bold text-amber-950">Tautan aktivasi satu kali — salin sekarang</h2>
            <p class="text-sm text-amber-900">Tautan berlaku 48 jam, hanya dapat digunakan sekali, dan tidak disimpan dalam riwayat. Bagikan tautan masing-masing secara pribadi kepada pemilik akun. Jangan kirim tautan secara massal ke grup umum.</p>
            @foreach($activationLinks as $link)
                <div class="rounded-xl bg-white p-3"><div class="text-sm font-semibold">{{ $link['name'] }} · {{ $link['identity'] }}</div><div class="mt-1 break-all text-xs">{{ $link['url'] }}</div><button type="button" onclick="navigator.clipboard.writeText(@js($link['url']))" class="mt-2 rounded-lg border px-3 py-1 text-xs">Salin tautan</button></div>
            @endforeach
        </div>
    @endif

    <div class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm">
        <h2 class="mb-3 text-lg font-bold text-navy-900">Riwayat Import Madrasah Ini</h2>
        <div class="overflow-x-auto"><table class="min-w-full text-sm">
            <thead><tr class="border-b text-left"><th class="p-2">Waktu</th><th class="p-2">Jenis</th><th class="p-2">File</th><th class="p-2">Status</th><th class="p-2">Berhasil</th><th class="p-2">Dilewati</th><th class="p-2">Gagal</th><th class="p-2"></th></tr></thead>
            <tbody>@forelse($history as $item)<tr class="border-b"><td class="p-2">{{ $item->created_at?->format('d/m/Y H:i') }}</td><td class="p-2">{{ $item->type }}</td><td class="p-2">{{ $item->original_filename }}</td><td class="p-2">{{ $item->status }}</td><td class="p-2">{{ $item->valid_rows }}</td><td class="p-2">{{ $item->skipped_rows }}</td><td class="p-2">{{ $item->failed_rows }}</td><td class="p-2"><button wire:click="showBatch('{{ $item->id }}')" class="text-emerald-700 underline">Detail</button></td></tr>@empty<tr><td colspan="8" class="p-4 text-center text-textSecondary">Belum ada riwayat import.</td></tr>@endforelse</tbody>
        </table></div>
    </div>
</div>
