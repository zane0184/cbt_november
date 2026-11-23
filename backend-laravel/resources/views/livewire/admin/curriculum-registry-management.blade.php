<div class="space-y-6">
    <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-navy-900">Registry Kurikulum & Referensi Resmi</h1>
                <p class="mt-1 text-sm text-textSecondary">Pendaftaran versi kurikulum resmi dan pemetaan mata pelajaran lokal pada instalasi madrasah ini.</p>
            </div>
            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">Lingkup instalasi lokal</span>
        </div>
    </section>

    @if (session()->has('message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('message') }}</div>
    @endif

    <section class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900" role="status">
        <p class="font-semibold">Batas verifikasi CP resmi</p>
        <p class="mt-1">Registry sumber dan struktur fase/mata pelajaran telah dicatat beserta provenance. Konten CP per elemen belum boleh dianggap lengkap sampai artefak CP resmi, versi, dan checksum diverifikasi. Admin tidak dapat mengubah CP induk; Guru mengembangkan TP/ATP setelah CP yang sah tersedia.</p>
    </section>

    <section class="space-y-3">
        <div>
            <h2 class="text-lg font-bold text-navy-900">Versi kurikulum resmi yang tersedia</h2>
            <p class="text-xs text-textSecondary">Hanya versi resmi read-only yang dapat didaftarkan. Pendaftaran mengikat versi dan pemilik sumber untuk tenant ini.</p>
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            @forelse($versions as $version)
                @php
                    $registered = $registrations->first(fn ($registration) => $registration->multi_curriculum_version_id === $version->id && $registration->curriculum_owner_id === $version->tenant_id && $registration->status === 'ACTIVE');
                @endphp
                <article class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-bold text-navy-900">{{ $version->title }}</h3>
                            <p class="mt-1 text-xs text-textSecondary">{{ $version->version_code }} · Tahun efektif {{ $version->effective_year }}</p>
                        </div>
                        <span class="rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $version->source?->verification_status === 'ARTIFACT_CHECKSUM_VERIFIED' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
                            {{ $version->source?->verification_status ?? 'SOURCE MISSING' }}
                        </span>
                    </div>
                    <p class="mt-3 text-xs text-textSecondary">{{ $version->source?->document_number }} — {{ $version->source?->title }}</p>
                    <p class="mt-2 text-xs text-textSecondary">Status versi: {{ $version->status }} · Official: {{ $version->is_official ? 'Ya' : 'Tidak' }} · Dapat diedit: {{ $version->is_editable ? 'Ya' : 'Tidak' }}</p>
                    <div class="mt-4">
                        @if($registered)
                            <span class="inline-flex rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800">Sudah terdaftar pada instalasi ini</span>
                        @else
                            <button type="button" wire:click="registerVersion('{{ $version->id }}')" wire:loading.attr="disabled" class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800 disabled:opacity-50">
                                Daftarkan versi ini
                            </button>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-cardBorder bg-white p-8 text-center text-sm text-textSecondary">Belum ada versi resmi pada registry lokal.</div>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm space-y-4">
        <div>
            <h2 class="text-lg font-bold text-navy-900">Pemetaan mata pelajaran madrasah</h2>
            <p class="mt-1 text-xs text-textSecondary">Hubungkan mata pelajaran lokal ke node mata pelajaran pada versi kurikulum yang telah didaftarkan. Pemetaan ini menjadi konteks resmi bagi penugasan Guru.</p>
        </div>

        @if($registrations->where('status', 'ACTIVE')->isNotEmpty())
            <div class="max-w-xl">
                <label class="mb-1 block text-xs font-bold text-navy-900">Versi kurikulum terdaftar</label>
                <select wire:model.live="selectedRegistrationId" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                    <option value="">Pilih versi terdaftar...</option>
                    @foreach($registrations->where('status', 'ACTIVE') as $registration)
                        <option value="{{ $registration->id }}">{{ $registration->version?->title }} ({{ $registration->version?->version_code }})</option>
                    @endforeach
                </select>
            </div>

            @if($selectedRegistration)
                <form wire:submit.prevent="mapSubject" class="grid gap-3 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-bold text-navy-900">Mata pelajaran lokal</label>
                        <select wire:model="selectedSubjectId" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                            <option value="">Pilih mapel lokal...</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->code }})</option>
                            @endforeach
                        </select>
                        @error('selectedSubjectId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-navy-900">Node mapel resmi</label>
                        <select wire:model="selectedStructureId" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                            <option value="">Pilih mapel pada kurikulum...</option>
                            @foreach($availableSubjectNodes as $node)
                                <option value="{{ $node->id }}">
                                    {{ $node->title }} — {{ $node->metadata['subject_code'] ?? $node->code }}
                                </option>
                            @endforeach
                        </select>
                        @error('selectedStructureId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <button type="submit" wire:loading.attr="disabled" class="w-full rounded-lg bg-navy-900 px-4 py-2.5 text-xs font-bold text-white hover:bg-navy-800 disabled:opacity-50">Simpan pemetaan</button>
                    </div>
                </form>

                <div class="overflow-x-auto rounded-xl border border-cardBorder">
                    <table class="min-w-full divide-y divide-cardBorder text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Mapel lokal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Referensi resmi</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Sumber</th>
                                <th class="px-4 py-3 text-right text-xs font-bold uppercase text-textSecondary">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cardBorder bg-white">
                            @forelse($subjectMappings as $mapping)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-navy-900">{{ $mapping->subject?->name }} <span class="text-xs font-normal text-textSecondary">({{ $mapping->subject?->code }})</span></td>
                                    <td class="px-4 py-3 text-navy-900">{{ $mapping->curriculumStructure?->title }}</td>
                                    <td class="px-4 py-3 text-xs text-textSecondary">{{ $mapping->curriculumStructure?->source?->document_number }} · {{ $mapping->curriculumStructure?->verification_status }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if($mapping->status === 'ACTIVE')
                                            <button type="button" wire:click="retireMapping('{{ $mapping->id }}')" wire:confirm="Nonaktifkan pemetaan ini? Histori tetap disimpan." class="text-xs font-semibold text-red-700 hover:underline">Nonaktifkan</button>
                                        @else
                                            <span class="text-xs text-textSecondary">Tidak aktif</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-textSecondary">Belum ada pemetaan mapel untuk versi ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <p class="rounded-xl border border-dashed border-cardBorder bg-gray-50 p-5 text-sm text-textSecondary">Daftarkan versi kurikulum terlebih dahulu untuk mengaktifkan pemetaan mapel lokal.</p>
        @endif
    </section>

    <section class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-navy-900">Provenance sumber referensi</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full divide-y divide-cardBorder text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Dokumen</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Penerbit</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Status verifikasi</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Sumber</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cardBorder">
                    @foreach($sources as $source)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-navy-900">{{ $source->document_number }}</p>
                                <p class="mt-0.5 text-xs text-textSecondary">{{ $source->title }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-textSecondary">{{ $source->issuer }}</td>
                            <td class="px-4 py-3 text-xs font-semibold">{{ $source->verification_status }}</td>
                            <td class="px-4 py-3 text-xs"><a href="{{ $source->source_uri }}" target="_blank" rel="noopener noreferrer" class="text-emerald-700 hover:underline">Buka dokumen/rujukan</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-textSecondary">METADATA_VERIFIED bukan berarti file telah diarsipkan dan checksum SHA-256 diverifikasi. Jangan menganggap CP resmi siap dipakai sebelum status artefak dan isi CP diverifikasi.</p>
    </section>
</div>
