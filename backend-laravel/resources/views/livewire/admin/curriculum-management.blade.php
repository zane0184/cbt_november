<div class="space-y-6">
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-navy-900">Kurikulum Terpadu — CP, TP & ATP</h1>
        <p class="text-xs text-textSecondary mt-1">Admin Madrasah dapat memantau referensi CP resmi dan TP/ATP yang dikembangkan Guru. Kurikulum Merdeka dan KBC/Panca Cinta terhubung dalam satu konteks pembelajaran.</p>
    </div>

    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900" role="status">
        <p class="font-semibold">Hak akses Admin: baca-saja</p>
        <p class="mt-1">CP induk resmi tidak boleh diedit Admin. TP dan ATP dibuat Guru melalui penugasan mengajar yang aktif. Perubahan versi, sumber, dan pemetaan harus mempertahankan histori serta snapshot ujian.</p>
    </div>

    <section class="bg-white border border-cardBorder rounded-2xl p-5 shadow-sm space-y-3">
        <h2 class="font-bold text-navy-900">Versi Kurikulum Terdaftar</h2>
        @forelse($registrations as $registration)
            <div class="rounded-xl border border-cardBorder p-3 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div>
                    <p class="font-semibold text-sm">{{ $registration->version?->title ?? 'Versi tidak tersedia' }}</p>
                    <p class="text-xs text-textSecondary">{{ $registration->version?->version_code ?? '-' }} · {{ $registration->version?->source?->document_number ?? 'Sumber belum terhubung' }}</p>
                </div>
                <span class="text-xs font-semibold rounded-full px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200">Terdaftar aktif</span>
            </div>
        @empty
            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl p-3">Belum ada versi kurikulum yang didaftarkan untuk instalasi madrasah ini. Admin dapat memeriksa menu Registry Kurikulum.</p>
        @endforelse
    </section>

    <section class="bg-white border border-cardBorder rounded-2xl p-5 shadow-sm space-y-3">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <h2 class="font-bold text-navy-900">Capaian Pembelajaran (CP) Induk</h2>
                <p class="text-xs text-textSecondary">CP resmi hanya ditampilkan bila tersedia pada versi yang terdaftar. Data tidak dibuat dari asumsi.</p>
            </div>
            <span class="text-xs font-semibold text-textSecondary">{{ $officialCps->total() }} CP resmi</span>
        </div>
        @forelse($officialCps as $cp)
            <article class="border border-cardBorder rounded-xl p-4 space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-xs font-bold rounded-md bg-navy-900 text-white px-2.5 py-1">{{ $cp->code }}</span>
                    <span class="text-xs font-semibold text-emerald-800">{{ $cp->version?->version_code }}</span>
                    <span class="text-xs text-textSecondary">{{ $cp->structure?->title ?? 'Struktur belum ditautkan' }}</span>
                </div>
                <p class="text-sm text-navy-900 whitespace-pre-line">{{ $cp->description }}</p>
                <div class="text-xs text-textSecondary">Sumber: {{ $cp->source?->document_number ?? 'Tidak tersedia' }} · Status sumber: {{ $cp->source?->verification_status ?? $cp->verification_status }}</div>
            </article>
        @empty
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-semibold">Konten CP resmi belum tersedia pada versi terdaftar.</p>
                <p class="mt-1">Registry sumber dokumen tidak sama dengan impor isi CP. CP tidak boleh direkayasa atau diisi dengan data contoh. Impor CP baru dapat dilakukan setelah artefak resmi dan checksum SHA-256 terverifikasi.</p>
            </div>
        @endforelse
        {{ $officialCps->links() }}
    </section>

    <section class="bg-white border border-cardBorder rounded-2xl p-5 shadow-sm space-y-3">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <h2 class="font-bold text-navy-900">Tujuan Pembelajaran (TP) & Alur Tujuan Pembelajaran (ATP)</h2>
                <p class="text-xs text-textSecondary">Data hasil pengembangan Guru, ditampilkan sesuai penugasan dan versi kurikulum.</p>
            </div>
            <span class="text-xs font-semibold text-textSecondary">{{ $teacherOutcomes->total() }} TP/ATP aktif</span>
        </div>
        @forelse($teacherOutcomes as $outcome)
            <article class="border border-cardBorder rounded-xl p-4 space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-xs font-bold rounded-md bg-emerald-700 text-white px-2.5 py-1">{{ $outcome->code }}</span>
                    <span class="text-xs font-semibold rounded-md border border-cardBorder px-2 py-1">{{ $outcome->outcome_type }}</span>
                    <span class="text-xs text-textSecondary">{{ $outcome->subject?->name ?? 'Mapel tidak tersedia' }}</span>
                </div>
                <h3 class="font-semibold text-sm text-navy-900">{{ $outcome->title }}</h3>
                @if($outcome->description)
                    <p class="text-sm text-textSecondary whitespace-pre-line">{{ $outcome->description }}</p>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-textSecondary">
                    <p>CP induk: {{ $outcome->parentCp?->code ?? 'Belum terhubung' }}</p>
                    <p>TP induk: {{ $outcome->parentTp?->code ?? '—' }}</p>
                    <p>Penugasan/Rombel: {{ $outcome->assignment?->rombel?->name ?? '—' }}</p>
                    <p>Periode: {{ $outcome->academicYear?->name ?? '—' }} · Semester {{ $outcome->semester?->term === 'GANJIL' ? 'Ganjil' : ($outcome->semester?->term === 'GENAP' ? 'Genap' : '—') }}</p>
                </div>
            </article>
        @empty
            <p class="text-sm text-textSecondary rounded-xl bg-gray-50 border border-cardBorder p-4">Belum ada TP/ATP yang dibuat Guru pada versi kurikulum terdaftar. Setelah CP resmi terverifikasi dan tersedia, Guru dapat mengembangkan TP/ATP melalui menu Pembelajaran & Kurikulum.</p>
        @endforelse
        {{ $teacherOutcomes->links() }}
    </section>

    <section class="bg-white border border-cardBorder rounded-2xl p-5 shadow-sm space-y-3">
        <h2 class="font-bold text-navy-900">Status Provenance Dokumen Kurikulum</h2>
        @foreach($curriculumSources as $source)
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 border-b border-cardBorder pb-2 last:border-0">
                <div>
                    <p class="text-sm font-semibold">{{ $source->document_number }}</p>
                    <p class="text-xs text-textSecondary">{{ $source->title }}</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $source->verification_status === 'ARTIFACT_CHECKSUM_VERIFIED' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">{{ $source->verification_status }}</span>
            </div>
        @endforeach
    </section>

    <section class="bg-white border border-cardBorder rounded-2xl p-5 shadow-sm space-y-3">
        <h2 class="font-bold text-navy-900">Referensi Kurikulum Legacy</h2>
        <p class="text-xs text-textSecondary">Bagian ini dipertahankan untuk membaca data lama dan tidak dianggap sebagai CP resmi terverifikasi.</p>
        @forelse($legacyOutcomes as $outcome)
            <article class="border border-cardBorder rounded-xl p-4 space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-xs font-bold rounded-md bg-gray-700 text-white px-2.5 py-1">{{ $outcome->code }}</span>
                    <span class="text-xs text-textSecondary">{{ $outcome->subject?->name ?? '-' }} · {{ $outcome->phase?->name ?? '-' }}</span>
                </div>
                @if($outcome->currentVersion)
                    <p class="text-sm">{{ $outcome->currentVersion->title ?? $outcome->currentVersion->description }}</p>
                @endif
                @foreach($outcome->objectives as $tp)
                    <p class="text-xs text-textSecondary">{{ $tp->code }} · {{ $tp->title }}</p>
                @endforeach
            </article>
        @empty
            <p class="text-xs text-textSecondary">Tidak ada data CP/TP legacy pada instalasi ini.</p>
        @endforelse
        {{ $legacyOutcomes->links() }}
    </section>
</div>
