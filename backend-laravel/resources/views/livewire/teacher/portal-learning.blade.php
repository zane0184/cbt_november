<div class="space-y-6">
    <section>
        <p class="text-xs font-bold uppercase tracking-widest portal-muted">Penugasan akademik</p>
        <h1 class="mt-1 text-2xl font-extrabold md:text-3xl">Pembelajaran & Kurikulum</h1>
        <p class="mt-2 text-sm portal-muted">Daftar penugasan Anda dan konteks Kurikulum Merdeka yang terintegrasi Kurikulum Berbasis Cinta.</p>
    </section>

    <section class="portal-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold">Periode Akademik Madrasah</h2>
                <p class="mt-1 text-xs portal-muted">Periode aktif hanya mengikuti penetapan Admin; Guru tidak dapat menggantinya.</p>
            </div>
            <span class="portal-badge">{{ $period['status'] }}</span>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div class="portal-soft rounded-xl p-4"><p class="text-xs portal-muted">Tahun Pelajaran Aktif</p><p class="mt-1 text-lg font-bold">{{ $period['academic_year']?->name ?? 'Belum Ditetapkan' }}</p></div>
            <div class="portal-soft rounded-xl p-4"><p class="text-xs portal-muted">Semester Aktif</p><p class="mt-1 text-lg font-bold">{{ $period['semester']?->term ?? 'Belum Ditetapkan' }}</p></div>
        </div>
    </section>

    <section class="portal-card overflow-hidden">
        <div class="border-b portal-border p-5">
            <h2 class="font-bold">Penugasan Saya</h2>
            <p class="mt-1 text-xs portal-muted">{{ $active_assignment_count }} penugasan aktif pada tanggal hari ini. Riwayat tetap ditampilkan tanpa mengubah statusnya.</p>
        </div>
        @if(count($assignments))
            <div class="overflow-x-auto">
                <table class="portal-table w-full min-w-[850px] text-sm">
                    <thead><tr><th>Mata Pelajaran</th><th>Rombel / Kelas</th><th>Tahun Ajaran</th><th>Semester</th><th>Periode Penugasan</th><th>Siswa</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($assignments as $row)
                        @php($assignment = $row['assignment'])
                        <tr>
                            <td><p class="font-bold">{{ $assignment->subject->name }}</p><p class="text-xs portal-muted">{{ $assignment->subject->code }}</p></td>
                            <td><p class="font-semibold">{{ $assignment->rombel->name }}</p><p class="text-xs portal-muted">{{ $assignment->rombel->gradeLevel?->name ?? 'Kelas belum tersedia' }}</p></td>
                            <td>{{ $assignment->academicYear->name }}</td>
                            <td>{{ $assignment->semester->term }}</td>
                            <td>{{ $assignment->starts_at?->format('d/m/Y') ?? 'Tanpa tanggal mulai' }}<br><span class="portal-muted">s.d. {{ $assignment->ends_at?->format('d/m/Y') ?? 'Tidak dibatasi' }}</span></td>
                            <td class="font-bold">{{ $row['students_count'] }}</td>
                            <td><span class="portal-badge">{{ $row['status_label'] }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-8 text-center"><p class="font-semibold">Belum ada penugasan Guru</p><p class="mt-1 text-sm portal-muted">Penugasan mapel dan rombel ditetapkan oleh Admin Madrasah.</p></div>
        @endif
    </section>

    <section id="kurikulum" class="space-y-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest portal-muted">Kurikulum dan akademik</p>
            <h2 class="mt-1 text-xl font-extrabold">Kurikulum Merdeka Terintegrasi KBC</h2>
        </div>

        @if($curriculum['has_registered_reference'])
            <div class="grid gap-3 md:grid-cols-2">
                @foreach($curriculum['versions'] as $version)
                    <div class="portal-card p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div><p class="font-bold">{{ $version['curriculum_name'] }}</p><p class="mt-1 text-sm portal-muted">{{ $version['version_title'] }}</p></div>
                            <span class="portal-badge">{{ $version['owner_type'] === 'GLOBAL' ? 'Referensi global' : 'Referensi tenant' }}</span>
                        </div>
                        <p class="mt-3 text-xs portal-muted">{{ $version['curriculum_code'] }} · {{ $version['version_code'] }} · Tahun efektif {{ $version['effective_year'] ?? 'Belum ditetapkan' }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <div class="portal-card p-5">
                <p class="font-semibold">Belum ada versi kurikulum yang terdaftar untuk madrasah ini.</p>
                <p class="mt-1 text-sm portal-muted">Portal Guru tidak membuat referensi kurikulum otomatis. Admin perlu mendaftarkan versi yang sah terlebih dahulu.</p>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach([
                ['CP', $curriculum['counts']['CP']],
                ['TP', $curriculum['counts']['TP']],
                ['ATP', $curriculum['counts']['ATP']],
                ['Pemetaan KBC', $curriculum['counts']['KBC_CINTA']],
            ] as [$label, $count])
                <div class="portal-card p-4"><p class="text-xs portal-muted">{{ $label }}</p><p class="mt-2 text-2xl font-extrabold">{{ $count }}</p><p class="mt-1 text-[11px] portal-muted">Referensi tersimpan</p></div>
            @endforeach
        </div>

        <div class="portal-warning text-sm">{{ $curriculum['official_cp_note'] }}</div>

        <div class="portal-card p-5">
            <h3 class="font-bold">Panca Cinta</h3>
            <p class="mt-1 text-xs portal-muted">Kerangka integrasi yang ditetapkan dalam proyek. Daftar ini bukan klaim bahwa pemetaan data setiap unsur sudah tersedia.</p>
            <ol class="mt-4 grid gap-2 sm:grid-cols-2">
                @foreach($curriculum['panca_cinta'] as $item)
                    <li class="portal-soft rounded-xl p-3 text-sm font-semibold">{{ $loop->iteration }}. {{ $item }}</li>
                @endforeach
            </ol>
            <p class="mt-3 text-xs portal-muted">Struktur baseline mengenal node DIMENSI_CINTA dan outcome KBC_CINTA; hubungan rinci ke lima unsur Panca Cinta masih perlu referensi tervalidasi.</p>
        </div>

        <div class="grid gap-3 md:grid-cols-2">
            <div class="portal-card p-5">
                <h3 class="font-bold">Pembelajaran Mendalam</h3>
                <span class="mt-3 inline-flex portal-badge">Belum terintegrasi</span>
                <p class="mt-2 text-sm portal-muted">Belum ditemukan referensi/model khusus Pembelajaran Mendalam pada baseline sumber yang digunakan.</p>
            </div>
            <div class="portal-card p-5">
                <h3 class="font-bold">Delapan Dimensi Profil Lulusan</h3>
                <span class="mt-3 inline-flex portal-badge">Referensi belum tersedia</span>
                <p class="mt-2 text-sm portal-muted">Portal tidak menampilkan nama atau pemetaan dimensi berdasarkan asumsi. Referensi resmi perlu ditambahkan melalui modul kurikulum.</p>
            </div>
        </div>

        <div class="portal-card overflow-hidden">
            <div class="border-b portal-border p-5">
                <h3 class="font-bold">CP, TP, ATP, dan KBC yang Tersimpan</h3>
                <p class="mt-1 text-xs portal-muted">Maksimal 100 entri ditampilkan dari versi yang terdaftar. Guru belum dapat mengubah referensi pada tahap fondasi.</p>
            </div>
            @if(count($curriculum['outcomes']))
                <div class="overflow-x-auto">
                    <table class="portal-table w-full min-w-[650px] text-sm">
                        <thead><tr><th>Jenis</th><th>Kode</th><th>Deskripsi</th><th>Versi</th></tr></thead>
                        <tbody>
                        @foreach($curriculum['outcomes'] as $outcome)
                            <tr><td><span class="portal-badge">{{ $outcome['type'] }}</span></td><td class="font-bold">{{ $outcome['code'] }}</td><td class="max-w-xl">{{ $outcome['description'] }}</td><td>{{ $outcome['version_title'] }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-6 text-center text-sm portal-muted">Belum ada CP/TP/ATP/KBC aktif pada versi kurikulum yang terdaftar.</div>
            @endif
        </div>
    </section>

    <section class="portal-card p-5">
        <h2 class="font-bold">Jadwal Pembelajaran</h2>
        <p class="mt-2 text-sm portal-muted">Belum ada model jadwal mengajar yang dapat digunakan sebagai sumber data. Portal tidak membuat jadwal contoh.</p>
    </section>
</div>
