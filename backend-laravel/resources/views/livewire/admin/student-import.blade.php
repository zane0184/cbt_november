<div class="space-y-6">
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Import Data Siswa</h1>
            <p class="text-sm text-textSecondary mt-1">Unggah XLSX atau CSV. Rombel dapat dibuat otomatis dari metadata pada template setelah preview valid dan import dikonfirmasi.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="downloadXlsxTemplate" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow-sm">Unduh Template XLSX</button>
            <button type="button" wire:click="downloadCsvTemplate" class="px-4 py-2.5 rounded-xl border border-cardBorder bg-white text-navy-900 text-sm font-semibold hover:bg-gray-50">Template CSV (alternatif)</button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-4 text-sm">{{ session('message') }}</div>
    @endif

    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm space-y-4">
        <div>
            <label class="block text-sm font-semibold mb-2">Rombel fallback untuk template lama (opsional)</label>
            <select wire:model="selectedRombelId" class="w-full border border-cardBorder rounded-xl px-4 py-3 text-sm">
                <option value="">Tidak perlu dipilih jika template memuat metadata Rombel</option>
                @foreach ($rombels as $rombel)
                    <option value="{{ $rombel->id }}">{{ $rombel->name }} — {{ $rombel->gradeLevel?->name }} — {{ $rombel->academicYear?->name }}</option>
                @endforeach
            </select>
            @error('selectedRombelId') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold mb-2">File Siswa (XLSX/CSV, maksimal 10 MB)</label>
            <input type="file" wire:model="file" accept=".xlsx,.csv,.txt" class="w-full border border-cardBorder rounded-xl px-4 py-3 text-sm">
            @error('file') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            <p class="text-xs text-textSecondary mt-2">Kolom wajib: <strong>nama</strong>, <strong>nisn</strong>, <strong>rombel</strong>, <strong>tahun_pelajaran</strong>, dan <strong>kelas</strong>. Isi metadata Rombel yang sama pada seluruh baris dalam satu file. Contoh: rombel=Kelas 1-A, tahun_pelajaran=2026/2027, kelas=1. Jika Rombel belum ada, sistem akan membuatnya saat import dikonfirmasi; jika sudah ada dan cocok, sistem akan menggunakannya. Satu file hanya untuk satu Rombel. NISN harus berupa teks 10 digit. Kolom opsional: jenis_kelamin (L/P), tempat_lahir, tanggal_lahir (YYYY-MM-DD), nama_orang_tua, telepon, alamat.</p>
            <p class="text-xs text-textSecondary mt-1">Template XLSX hanya memiliki satu worksheet Data Siswa agar lolos pemeriksaan keamanan file. Jangan menambahkan sheet lain; petunjuk pengisian tersedia di halaman ini. Template memuat satu baris contoh 'Contoh Nama'; ganti atau hapus baris contoh sebelum mengunggah.</p>
            <p class="text-xs text-textSecondary mt-1">Password awal dibuat acak 8 karakter dan ditampilkan satu kali pada halaman Daftar Pengguna. Unduh Daftar Akun (.xlsx) sebelum menutup tampilan; password tidak dapat ditampilkan kembali.</p>
        </div>

        <div wire:loading wire:target="file" class="text-xs text-blue-700">Mengunggah file...</div>
        <div class="flex flex-wrap gap-3">
            <button type="button" wire:click="preview" wire:loading.attr="disabled" class="px-5 py-2.5 rounded-xl bg-blue-700 text-white text-sm font-bold">Buat Preview & Validasi</button>
            <button type="button" wire:click="import" wire:loading.attr="disabled" @disabled(empty($previewRows) || ($summary['invalid'] ?? 1) > 0) class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-bold disabled:opacity-40">Konfirmasi Import</button>
        </div>
    </div>

    @if (!empty($previewRows))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white border rounded-xl p-4"><div class="text-xs text-textSecondary">Total baris</div><div class="text-2xl font-bold">{{ $summary['total'] }}</div></div>
            <div class="bg-white border rounded-xl p-4"><div class="text-xs text-textSecondary">Valid</div><div class="text-2xl font-bold text-emerald-700">{{ $summary['valid'] }}</div></div>
            <div class="bg-white border rounded-xl p-4"><div class="text-xs text-textSecondary">Bermasalah</div><div class="text-2xl font-bold text-red-700">{{ $summary['invalid'] }}</div></div>
            <div class="bg-white border rounded-xl p-4"><div class="text-xs text-textSecondary">Rombel tujuan</div><div class="text-sm font-bold">{{ $summary['rombel_name'] }}</div><div class="text-xs mt-1 {{ !empty($summary['rombel_will_create']) ? 'text-amber-700' : 'text-emerald-700' }}">{{ !empty($summary['rombel_will_create']) ? 'Rombel akan dibuat saat konfirmasi import' : 'Rombel yang sudah ada akan digunakan' }}</div></div>
        </div>

        <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 border-b"><h2 class="font-bold">Preview seluruh baris</h2><p class="text-xs text-textSecondary">Baris dengan error tidak dapat disimpan. Jika transaksi gagal, seluruh batch dibatalkan.</p></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50"><tr><th class="p-3 text-left">Baris</th><th class="p-3 text-left">Nama</th><th class="p-3 text-left">NISN</th><th class="p-3 text-left">Jenis Kelamin</th><th class="p-3 text-left">Validasi</th></tr></thead>
                    <tbody class="divide-y">
                        @foreach ($previewRows as $row)
                            <tr>
                                <td class="p-3">{{ $row['row'] }}</td>
                                <td class="p-3">{{ $row['data']['name'] }}</td>
                                <td class="p-3 font-mono">{{ $row['data']['nisn'] }}</td>
                                <td class="p-3">{{ $row['data']['gender'] ?? '—' }}</td>
                                <td class="p-3">
                                    @if (count($row['errors']) === 0)
                                        <span class="text-emerald-700 font-semibold">Valid</span>
                                    @else
                                        <ul class="list-disc pl-4 text-red-700 text-xs space-y-1">@foreach ($row['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
