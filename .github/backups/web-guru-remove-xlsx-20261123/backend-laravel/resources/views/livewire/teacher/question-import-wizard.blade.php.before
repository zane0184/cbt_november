<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-textSecondary">Bank Soal / Impor</p>
            <h1 class="mt-1 text-2xl font-extrabold text-navy-900">Impor Soal dari XLSX</h1>
            <p class="mt-1 text-sm text-textSecondary">Unggah template resmi, periksa hasil parsing, lalu konfirmasi satu batch secara atomik.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('teacher.questions.template') }}" class="rounded-xl border border-cardBorder bg-white px-4 py-2 text-sm font-bold text-navy-900 hover:bg-slate-50">Unduh Template</a>
            <a href="{{ route('teacher.questions.index') }}" class="rounded-xl border border-cardBorder bg-white px-4 py-2 text-sm font-bold text-navy-900 hover:bg-slate-50">Kembali ke Bank Soal</a>
        </div>
    </div>

    @if(session()->has('message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('message') }}</div>
    @endif

    <section class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm space-y-4">
        <div>
            <h2 class="text-base font-bold text-navy-900">1. Pilih workbook</h2>
            <p class="mt-1 text-xs text-textSecondary">Format XLSX, maksimal 10 MB. Jangan mengubah nama sheet atau urutan header template.</p>
        </div>

        <form wire:submit.prevent="previewImport" class="space-y-3">
            <input type="file" wire:model="workbook" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="block w-full rounded-xl border border-cardBorder bg-slate-50 p-3 text-sm">
            @error('workbook') <p class="text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            <div wire:loading wire:target="workbook" class="text-xs text-indigo-700">Mengunggah file sementara…</div>
            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" wire:target="previewImport,workbook" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 disabled:opacity-50">Validasi & Preview</button>
            </div>
        </form>
    </section>

    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 space-y-2">
        <h2 class="text-sm font-bold text-amber-900">Batas kontrak import saat ini</h2>
        <p class="text-xs leading-5 text-amber-900">Jenis soal yang didukung: MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, dan ESSAY. SHORT_ANSWER tetap BLOCKED.</p>
        <p class="text-xs leading-5 text-amber-900">Kolom Tahun Ajaran Ref, Materi/Topik, KBC Ref, Elemen KBC Ref, Indikator, Sumber/Referensi, dan Media Ref harus dikosongkan sampai penyimpanan versi dan snapshot mendukungnya. Media dapat dipasang setelah import melalui editor soal.</p>
        <p class="text-xs leading-5 text-amber-900">CP Ref dan TP Ref harus UUID aktif pada tenant dan mata pelajaran yang ditugaskan kepada Guru. Kelas Ref harus UUID kelas aktif pada tenant.</p>
    </section>

    @if($totalRows > 0 || $previewErrors !== [])
        <section class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-navy-900">2. Hasil validasi</h2>
                    <p class="mt-1 text-xs text-textSecondary">Preview tidak menyimpan data. Workbook akan dibaca ulang saat konfirmasi.</p>
                </div>
                <div class="flex gap-4 text-sm">
                    <div><p class="text-xs text-textSecondary">Baris soal</p><p class="font-extrabold text-navy-900">{{ $totalRows }}</p></div>
                    <div><p class="text-xs text-textSecondary">Baris valid</p><p class="font-extrabold text-navy-900">{{ $validRows }}</p></div>
                </div>
            </div>

            @if($previewErrors !== [])
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 space-y-2">
                    <h3 class="text-sm font-bold text-red-800">Kesalahan yang harus diperbaiki ({{ count($previewErrors) }})</h3>
                    <ul class="max-h-64 space-y-2 overflow-y-auto text-xs text-red-800">
                        @foreach($previewErrors as $error)
                            <li class="rounded-lg bg-white/70 px-3 py-2">
                                <span class="font-bold">Baris {{ $error['row'] ?? 0 }} · {{ $error['field'] ?? 'Workbook' }}:</span>
                                {{ $error['error'] ?? 'Kesalahan tidak diketahui.' }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @elseif($previewReady)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">Semua baris lolos validasi awal. Belum ada data yang disimpan.</div>
            @endif

            @if($previewRows !== [])
                <div class="overflow-x-auto rounded-xl border border-cardBorder">
                    <table class="min-w-full divide-y divide-cardBorder text-left text-xs">
                        <thead class="bg-slate-50 text-textSecondary">
                            <tr>
                                <th class="px-3 py-2 font-bold">No.</th>
                                <th class="px-3 py-2 font-bold">Kode Soal</th>
                                <th class="px-3 py-2 font-bold">Jenis</th>
                                <th class="px-3 py-2 font-bold">Pertanyaan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cardBorder bg-white">
                            @foreach($previewRows as $row)
                                <tr>
                                    <td class="px-3 py-2">{{ $row['number'] }}</td>
                                    <td class="px-3 py-2">{{ $row['code'] ?: '-' }}</td>
                                    <td class="px-3 py-2 font-semibold">{{ $row['type'] }}</td>
                                    <td class="px-3 py-2">{{ $row['stem'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="confirmImport" wire:loading.attr="disabled" @disabled(!$previewReady) class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40">3. Konfirmasi & Impor</button>
            </div>
        </section>
    @endif
</div>
