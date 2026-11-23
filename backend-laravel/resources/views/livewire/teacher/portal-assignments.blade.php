<div class="space-y-6">
    <section>
        <p class="text-xs font-bold uppercase tracking-widest portal-muted">Ruang lingkup mengajar</p>
        <h1 class="mt-1 text-2xl font-extrabold md:text-3xl">Penugasan Saya</h1>
        <p class="mt-2 text-sm portal-muted">Daftar penugasan mapel dan rombel yang ditetapkan Admin Madrasah, termasuk riwayat yang sudah tidak aktif.</p>
    </section>
    <section class="portal-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold">Periode Akademik Madrasah</h2>
                <p class="mt-1 text-xs portal-muted">Periode akademik ditetapkan Admin Madrasah dan tidak dapat diubah dari Portal Guru.</p>
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
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-bold">Daftar Penugasan dan Riwayat</h2>
                    <p class="mt-1 text-xs portal-muted">Menampilkan {{ count($assignments) }} penugasan; {{ $active_assignment_count }} masih aktif sesuai tanggal server.</p>
                </div>
                <span class="portal-badge">{{ $active_assignment_count }} Aktif</span>
            </div>
        </div>
        @if(count($assignments))
            <div class="overflow-x-auto">
                <table class="portal-table w-full min-w-[850px] text-sm">
                    <thead><tr><th>Mata Pelajaran</th><th>Fase / Rombel / Kelas</th><th>Tahun Ajaran</th><th>Semester</th><th>Periode Penugasan</th><th>Konteks Kurikulum</th><th>Siswa</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($assignments as $row)
                        @php($assignment = $row['assignment'])
                        @php($curriculum = $row['curriculum_context'] ?? [])
                        <tr>
                            <td><p class="font-bold">{{ $assignment->subject->name }}</p><p class="text-xs portal-muted">{{ $assignment->subject->code }}</p></td>
                            <td><p class="font-semibold">{{ $assignment->rombel->name }}</p><p class="text-xs portal-muted">{{ $assignment->rombel->gradeLevel?->phase?->name ?? 'Fase belum tersedia' }} · {{ $assignment->rombel->gradeLevel?->name ?? 'Kelas belum tersedia' }}</p></td>
                            <td>{{ $assignment->academicYear->name }}</td>
                            <td>{{ $assignment->semester->term }}</td>
                            <td>{{ $assignment->starts_at?->format('d/m/Y') ?? 'Tanpa tanggal mulai' }}<p class="text-xs portal-muted">s.d. {{ $assignment->ends_at?->format('d/m/Y') ?? 'Tidak dibatasi' }}</p></td>
                            <td>
                                <p class="font-semibold">{{ $curriculum['assignment']['version_code'] ?? 'Belum dipatok' }}</p>
                                <p class="text-xs portal-muted">{{ $curriculum['assignment']['phase'] ?? 'Fase belum tersedia' }} · {{ $curriculum['assignment']['grade'] ?? 'Kelas belum tersedia' }}</p>
                                <p class="text-xs portal-muted">{{ $curriculum['assignment']['official_subject'] ?? 'Mapel resmi belum dipetakan' }}</p>
                                <span class="mt-1 inline-flex portal-badge">{{ $curriculum['status'] ?? 'CONTEXT_UNKNOWN' }}</span>
                            </td>
                            <td class="font-bold">{{ $row['students_count'] }}</td>
                            <td><span class="portal-badge">{{ $row['status_label'] }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-8 text-center"><p class="font-semibold">Belum ada penugasan Guru</p><p class="mt-1 text-sm portal-muted">Penugasan mapel dan rombel ditetapkan oleh Admin Madrasah. Hubungi Admin jika data Anda belum sesuai.</p></div>
        @endif
    </section>
    <section class="portal-warning text-sm">Portal Guru hanya menampilkan penugasan. Penambahan, perubahan, pengaktifan, dan pencabutan penugasan tetap menjadi kewenangan Admin Madrasah.</section>
</div>
