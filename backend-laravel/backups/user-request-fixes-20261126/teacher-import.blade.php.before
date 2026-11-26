<div class="space-y-6">
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Import Data Guru</h1>
            <p class="text-sm text-textSecondary mt-1">Unggah XLSX atau CSV, validasi seluruh data Guru, lalu konfirmasi pembuatan akun dan profil Guru.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/admin/users" class="px-4 py-2.5 rounded-xl border border-cardBorder bg-white text-navy-900 text-sm font-semibold hover:bg-gray-50">Kembali ke Pengguna</a>
            <button type="button" wire:click="downloadXlsxTemplate" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold">Unduh Template XLSX</button>
            <button type="button" wire:click="downloadCsvTemplate" class="px-4 py-2.5 rounded-xl border border-cardBorder bg-white text-navy-900 text-sm font-semibold hover:bg-gray-50">Template CSV</button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-4 text-sm">{{ session('message') }}</div>
    @endif

    <div class="bg-blue-50 border border-blue-200 text-blue-900 rounded-xl p-4 text-sm space-y-1">
        <p><strong>Ketentuan identitas login Guru:</strong> PNS/PPPK wajib menggunakan NIP 18 digit, sedangkan Honorer wajib menggunakan NIK 16 digit. NUPTK hanya data tambahan.</p>
        <p>Setelah import berhasil, identitas login dan password awal ditampilkan satu kali pada halaman Daftar Pengguna. Unduh Daftar Akun (.xlsx) sebelum menutup tampilan; password tidak dapat ditampilkan kembali.</p>
    </div>

    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm space-y-4">
        <div>
            <label class="block text-sm font-semibold mb-2">File Guru (XLSX/CSV, maksimal 10 MB)</label>
            <input type="file" wire:model="file" accept=".xlsx,.csv,.txt" class="w-full border border-cardBorder rounded-xl px-4 py-3 text-sm">
            @error('file') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            <p class="text-xs text-textSecondary mt-2">Kolom wajib: nama, status_kepegawaian. Kolom lain: nip, nik, nuptk, jenis_kelamin, telepon. Maksimal 5.000 Guru per batch.</p>
        </div>
        <div wire:loading wire:target="file" class="text-xs text-blue-700">Mengunggah file...</div>
        <div class="flex flex-wrap gap-3">
            <button type="button" wire:click="preview" wire:loading.attr="disabled" class="px-5 py-2.5 rounded-xl bg-blue-700 text-white text-sm font-bold">Buat Preview & Validasi</button>
            <button type="button" wire:click="import" wire:loading.attr="disabled" @disabled(empty($previewRows) || ($summary['invalid'] ?? 1) > 0) class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-bold disabled:opacity-40">Konfirmasi Import</button>
        </div>
    </div>

    @if (!empty($previewRows))
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <div class="bg-white border rounded-xl p-4"><div class="text-xs text-textSecondary">Total Guru</div><div class="text-2xl font-bold">{{ $summary['total'] }}</div></div>
            <div class="bg-white border rounded-xl p-4"><div class="text-xs text-textSecondary">Valid</div><div class="text-2xl font-bold text-emerald-700">{{ $summary['valid'] }}</div></div>
            <div class="bg-white border rounded-xl p-4"><div class="text-xs text-textSecondary">Bermasalah</div><div class="text-2xl font-bold text-red-700">{{ $summary['invalid'] }}</div></div>
        </div>
        <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 border-b"><h2 class="font-bold">Preview seluruh baris</h2><p class="text-xs text-textSecondary">Tidak ada akun dibuat sebelum seluruh baris valid dan Admin menekan Konfirmasi Import. Jika transaksi gagal, seluruh batch dibatalkan.</p></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50"><tr><th class="p-3 text-left">Baris</th><th class="p-3 text-left">Nama</th><th class="p-3 text-left">Status</th><th class="p-3 text-left">Identitas Login</th><th class="p-3 text-left">NUPTK</th><th class="p-3 text-left">Validasi</th></tr></thead>
                    <tbody class="divide-y">
                        @foreach ($previewRows as $row)
                            <tr>
                                <td class="p-3">{{ $row['row'] }}</td>
                                <td class="p-3">{{ $row['data']['name'] }}</td>
                                <td class="p-3">{{ $row['data']['employment_status'] }}</td>
                                <td class="p-3 font-mono">{{ $row['data']['employee_identifier'] ?? '—' }}</td>
                                <td class="p-3 font-mono">{{ $row['data']['nuptk'] ?? '—' }}</td>
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
