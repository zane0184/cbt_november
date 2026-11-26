<div class="space-y-6">
    <section class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-emerald-800 via-emerald-700 to-emerald-600 px-6 py-7 text-white md:px-8">
            <p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-100">E-Learning Madrasah · Ruang Guru</p>
            <div class="mt-2 flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <h1 class="text-2xl font-extrabold md:text-3xl">Selamat datang, {{ $profile->full_name ?: $user->name }}</h1>
                    <p class="mt-2 max-w-2xl text-sm text-emerald-50">Pilih kelas dan mata pelajaran untuk melanjutkan kegiatan pembelajaran dan asesmen.</p>
                    <p class="mt-1 text-xs text-emerald-100">{{ $tenant?->name ?? 'Madrasah' }}</p>
                </div>
                <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm backdrop-blur">
                    <p class="text-xs text-emerald-100">Tahun Pelajaran</p>
                    <p class="mt-1 font-bold">{{ $period['academic_year']?->name ?? 'Belum ditetapkan' }}</p>
                    <p class="mt-2 text-xs text-emerald-100">Semester</p>
                    <p class="mt-1 font-bold">{{ $period['semester']?->term ?? 'Belum ditetapkan' }}</p>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-2 divide-x divide-emerald-100 bg-emerald-50/70 md:grid-cols-4">
            <div class="px-5 py-4"><p class="text-xs font-semibold text-emerald-800">Mata Pelajaran</p><p class="mt-1 text-2xl font-extrabold text-emerald-950">{{ $stats['subjects'] }}</p></div>
            <div class="px-5 py-4"><p class="text-xs font-semibold text-emerald-800">Rombel Diampu</p><p class="mt-1 text-2xl font-extrabold text-emerald-950">{{ $stats['rombels'] }}</p></div>
            <div class="px-5 py-4"><p class="text-xs font-semibold text-emerald-800">Jumlah Siswa</p><p class="mt-1 text-2xl font-extrabold text-emerald-950">{{ $stats['students'] }}</p></div>
            <div class="px-5 py-4"><p class="text-xs font-semibold text-emerald-800">Penugasan Aktif</p><p class="mt-1 text-2xl font-extrabold text-emerald-950">{{ $stats['assignments'] }}</p></div>
        </div>
    </section>

    @if (session()->has('message'))
        <div class="portal-card p-4 text-sm font-semibold">{{ session('message') }}</div>
    @endif

    <section class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest portal-muted">Kelas online saya</p>
            <h2 class="mt-1 text-xl font-extrabold md:text-2xl">Pilih Kelas / Mata Pelajaran</h2>
            <p class="mt-1 text-sm portal-muted">Kartu berikut berasal dari penugasan mengajar aktif yang ditetapkan Admin Madrasah.</p>
        </div>
        <a href="{{ route('teacher.assignments') }}" class="text-sm font-bold text-emerald-800 hover:text-emerald-950">Lihat seluruh penugasan →</a>
    </section>

    @if(count($activeAssignments))
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($activeAssignments as $row)
                @php($assignment = $row['assignment'])
                <article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                    <div class="h-2 bg-gradient-to-r from-emerald-800 to-emerald-500"></div>
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-xl font-black text-emerald-800">▤</div>
                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800">{{ $row['status_label'] }}</span>
                        </div>
                        <p class="mt-4 text-xs font-bold uppercase tracking-wider text-emerald-700">{{ $assignment->subject->code }}</p>
                        <h3 class="mt-1 text-xl font-extrabold text-slate-900">{{ $assignment->rombel->name }} <span class="text-emerald-700">·</span> {{ $assignment->subject->name }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $assignment->rombel->gradeLevel?->phase?->name ?? 'Fase belum tersedia' }} · {{ $assignment->rombel->gradeLevel?->name ?? 'Kelas belum tersedia' }}</p>
                        <div class="mt-4 grid grid-cols-2 gap-2 rounded-xl bg-slate-50 p-3 text-xs">
                            <div><p class="text-slate-500">Tahun Pelajaran</p><p class="mt-1 font-bold text-slate-800">{{ $assignment->academicYear->name }}</p></div>
                            <div><p class="text-slate-500">Semester</p><p class="mt-1 font-bold text-slate-800">{{ $assignment->semester->term }}</p></div>
                            <div class="col-span-2 border-t border-slate-200 pt-2"><p class="text-slate-500">Peserta didik</p><p class="mt-1 font-bold text-slate-800">{{ $row['students_count'] }} siswa</p></div>
                        </div>
                        <div class="mt-auto grid grid-cols-2 gap-2 pt-5">
                            <a href="{{ route('teacher.questions.index') }}" class="inline-flex items-center justify-center rounded-lg border border-emerald-700 px-3 py-2.5 text-xs font-bold text-emerald-800 transition hover:bg-emerald-50">Bank Soal</a>
                            <a href="{{ route('teacher.exams.index') }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-700 px-3 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-800">Ujian CBT</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>
    @else
        <section class="portal-card p-8 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-2xl text-amber-700">▦</div>
            <h3 class="mt-3 font-bold">Belum ada kelas yang ditugaskan</h3>
            <p class="mx-auto mt-1 max-w-xl text-sm portal-muted">Kelas dan mata pelajaran akan tampil setelah Admin Madrasah menetapkan penugasan mengajar Anda.</p>
        </section>
    @endif

    <section class="grid gap-4 lg:grid-cols-2">
        <div class="portal-card p-5">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h2 class="font-bold">Aktivitas Asesmen & Ujian</h2>
                    <p class="mt-1 text-xs portal-muted">Aktivitas pada lingkup penugasan Anda.</p>
                </div>
                <span class="text-2xl text-emerald-700">▣</span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="portal-soft rounded-xl p-4"><p class="text-xs portal-muted">Asesmen Saya</p><p class="mt-1 text-2xl font-extrabold">{{ $activity['assessments'] }}</p></div>
                <div class="portal-soft rounded-xl p-4"><p class="text-xs portal-muted">Ujian Terkait</p><p class="mt-1 text-2xl font-extrabold">{{ $activity['exams'] }}</p></div>
            </div>
            <a href="{{ route('teacher.exams.index') }}" class="mt-4 inline-flex text-sm font-bold text-emerald-800">Kelola asesmen dan ujian →</a>
        </div>
        <div class="portal-card p-5">
            <h2 class="font-bold">Identitas Guru</h2>
            <p class="mt-1 text-xs portal-muted">Informasi profil yang terdaftar pada madrasah.</p>
            <p class="mt-4 text-lg font-extrabold">{{ $profile->full_name ?: $user->name }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @if($profile->nip)<span class="portal-badge">NIP {{ $profile->nip }}</span>@endif
                @if($profile->nuptk)<span class="portal-badge">NUPTK {{ $profile->nuptk }}</span>@endif
            </div>
            <a href="{{ route('teacher.profile') }}" class="mt-4 inline-flex text-sm font-bold text-emerald-800">Lihat profil →</a>
        </div>
    </section>

    <section class="portal-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-bold">Kurikulum Merdeka Terintegrasi KBC</h2>
                <p class="mt-1 text-xs portal-muted">Konteks kurikulum tetap terhubung dengan penugasan, pembelajaran, dan asesmen.</p>
            </div>
            <a href="{{ route('teacher.learning') }}" class="text-sm font-bold text-emerald-800">Buka pembelajaran & kurikulum →</a>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="portal-soft rounded-xl p-3"><p class="text-xs portal-muted">Versi Terdaftar</p><p class="mt-1 text-2xl font-extrabold">{{ $curriculum['counts']['registered_versions'] ?? 0 }}</p></div>
            <div class="portal-soft rounded-xl p-3"><p class="text-xs portal-muted">CP Tersimpan</p><p class="mt-1 text-2xl font-extrabold">{{ $curriculum['counts']['cp_artifact_verified'] ?? 0 }}</p></div>
            <div class="portal-soft rounded-xl p-3"><p class="text-xs portal-muted">Panca Cinta</p><p class="mt-1 text-2xl font-extrabold">{{ $curriculum['counts']['panca_cinta'] ?? 0 }}</p></div>
            <div class="portal-soft rounded-xl p-3"><p class="text-xs portal-muted">Dimensi Profil Lulusan</p><p class="mt-1 text-2xl font-extrabold">{{ $curriculum['counts']['graduate_dimension'] ?? 0 }}</p></div>
        </div>
        <p class="mt-3 text-xs portal-muted">{{ $curriculum['notice'] ?? 'CP resmi belum dapat dipilih sampai sumber dan checksum terverifikasi.' }}</p>
    </section>
</div>
