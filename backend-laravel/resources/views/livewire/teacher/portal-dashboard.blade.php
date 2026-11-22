<div class="space-y-6">
    <section class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest portal-muted">Ruang kerja</p>
            <h1 class="mt-1 text-2xl font-extrabold md:text-3xl">Dashboard Guru</h1>
            <p class="mt-2 text-sm portal-muted">Ringkasan penugasan akademik dan aktivitas pembelajaran Anda.</p>
        </div>
        <a href="{{ route('teacher.learning') }}" class="portal-button">Lihat Penugasan</a>
    </section>

    @if (session()->has('message'))
        <div class="portal-card p-4 text-sm font-semibold">{{ session('message') }}</div>
    @endif

    <section class="grid gap-4 lg:grid-cols-[1.3fr_1fr]">
        <div class="portal-card flex items-center gap-4 p-5">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-xl font-extrabold" style="background:var(--surface-soft);color:var(--accent)">
                {{ strtoupper(substr($profile->full_name ?: $user->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wider portal-muted">Selamat datang</p>
                <h2 class="mt-1 truncate text-lg font-extrabold">{{ $profile->full_name ?: $user->name }}</h2>
                <p class="mt-1 text-sm portal-muted">{{ $tenant?->name ?? 'Identitas madrasah belum tersedia' }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @if($profile->nip)<span class="portal-badge">NIP {{ $profile->nip }}</span>@endif
                    @if($profile->nik)<span class="portal-badge">NIK {{ $profile->nik }}</span>@endif
                    @if($profile->nuptk)<span class="portal-badge">NUPTK {{ $profile->nuptk }}</span>@endif
                </div>
            </div>
        </div>
        <div class="portal-card p-5">
            <div class="flex items-center justify-between gap-2">
                <h2 class="font-bold">Periode Akademik</h2>
                <span class="portal-badge">{{ $period['status'] }}</span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="portal-soft rounded-xl p-3">
                    <p class="text-xs portal-muted">Tahun Pelajaran</p>
                    <p class="mt-1 font-bold">{{ $period['academic_year']?->name ?? 'Belum Ditetapkan' }}</p>
                </div>
                <div class="portal-soft rounded-xl p-3">
                    <p class="text-xs portal-muted">Semester</p>
                    <p class="mt-1 font-bold">{{ $period['semester']?->term ?? 'Belum Ditetapkan' }}</p>
                </div>
            </div>
            <p class="mt-3 text-xs portal-muted">Periode mengikuti penetapan Admin Madrasah. Portal Guru tidak memilih semester secara otomatis.</p>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Mata Pelajaran', 'value' => $stats['subjects'], 'icon' => '▤'],
            ['label' => 'Rombel Diampu', 'value' => $stats['rombels'], 'icon' => '▦'],
            ['label' => 'Jumlah Siswa', 'value' => $stats['students'], 'icon' => '♙'],
            ['label' => 'Penugasan Aktif', 'value' => $stats['assignments'], 'icon' => '✓'],
        ] as $stat)
            <div class="portal-card p-4 md:p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium portal-muted">{{ $stat['label'] }}</span>
                    <span class="text-lg" style="color:var(--accent)">{{ $stat['icon'] }}</span>
                </div>
                <p class="mt-3 text-3xl font-extrabold">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="grid gap-4 md:grid-cols-2">
        <div class="portal-card p-5">
            <h2 class="font-bold">Aktivitas Asesmen & Ujian</h2>
            <p class="mt-1 text-xs portal-muted">Dihitung dari asesmen yang dibuat Guru pada lingkup penugasan aktif.</p>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="portal-soft rounded-xl p-4">
                    <p class="text-xs portal-muted">Asesmen Saya</p>
                    <p class="mt-1 text-2xl font-extrabold">{{ $activity['assessments'] }}</p>
                </div>
                <div class="portal-soft rounded-xl p-4">
                    <p class="text-xs portal-muted">Ujian Terkait</p>
                    <p class="mt-1 text-2xl font-extrabold">{{ $activity['exams'] }}</p>
                </div>
            </div>
        </div>
        <div class="portal-card p-5">
            <h2 class="font-bold">Agenda & Notifikasi</h2>
            <div class="mt-4 rounded-xl border border-dashed portal-border p-5 text-center">
                <p class="font-semibold">Belum ada agenda terhubung</p>
                <p class="mt-1 text-sm portal-muted">Jadwal pembelajaran, asesmen, ujian, dan penugasan Proktor akan ditampilkan setelah modul terkait tersedia.</p>
            </div>
        </div>
    </section>

    <section class="portal-card overflow-hidden">
        <div class="flex flex-col justify-between gap-2 border-b portal-border p-5 sm:flex-row sm:items-center">
            <div>
                <h2 class="font-bold">Penugasan Aktif Saya</h2>
                <p class="mt-1 text-xs portal-muted">Hanya penugasan Guru pada tenant ini yang masih berlaku hari ini.</p>
            </div>
            <a href="{{ route('teacher.learning') }}" class="text-sm font-bold" style="color:var(--accent)">Lihat semua penugasan →</a>
        </div>
        @if(count($activeAssignments))
            <div class="overflow-x-auto">
                <table class="portal-table w-full min-w-[680px] text-sm">
                    <thead><tr><th>Mata Pelajaran</th><th>Rombel</th><th>Tahun / Semester</th><th>Siswa</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($activeAssignments as $row)
                        @php($assignment = $row['assignment'])
                        <tr>
                            <td><p class="font-bold">{{ $assignment->subject->name }}</p><p class="text-xs portal-muted">{{ $assignment->subject->code }}</p></td>
                            <td><p class="font-semibold">{{ $assignment->rombel->name }}</p><p class="text-xs portal-muted">{{ $assignment->rombel->gradeLevel?->name ?? 'Kelas belum tersedia' }}</p></td>
                            <td>{{ $assignment->academicYear->name }}<p class="text-xs portal-muted">{{ $assignment->semester->term }}</p></td>
                            <td class="font-bold">{{ $row['students_count'] }}</td>
                            <td><span class="portal-badge">{{ $row['status_label'] }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-8 text-center">
                <p class="font-semibold">Belum ada penugasan aktif</p>
                <p class="mt-1 text-sm portal-muted">Hubungi Admin Madrasah jika penugasan mapel atau rombel Anda belum ditetapkan.</p>
            </div>
        @endif
    </section>

    <section class="portal-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-bold">Kurikulum Merdeka Terintegrasi KBC</h2>
                <p class="mt-1 text-xs portal-muted">Ringkasan referensi kurikulum yang terdaftar untuk tenant Anda.</p>
            </div>
            <a href="{{ route('teacher.learning') }}#kurikulum" class="text-sm font-bold" style="color:var(--accent)">Buka konteks kurikulum →</a>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="portal-soft rounded-xl p-3"><p class="text-xs portal-muted">Versi Terdaftar</p><p class="mt-1 text-2xl font-extrabold">{{ $curriculum['version_count'] }}</p></div>
            <div class="portal-soft rounded-xl p-3"><p class="text-xs portal-muted">CP Tersimpan</p><p class="mt-1 text-2xl font-extrabold">{{ $curriculum['counts']['CP'] }}</p></div>
            <div class="portal-soft rounded-xl p-3"><p class="text-xs portal-muted">TP Tersimpan</p><p class="mt-1 text-2xl font-extrabold">{{ $curriculum['counts']['TP'] }}</p></div>
            <div class="portal-soft rounded-xl p-3"><p class="text-xs portal-muted">ATP Tersimpan</p><p class="mt-1 text-2xl font-extrabold">{{ $curriculum['counts']['ATP'] }}</p></div>
        </div>
        <p class="mt-3 text-xs portal-muted">{{ $curriculum['official_cp_note'] }}</p>
    </section>
</div>
