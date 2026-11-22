<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-navy-900 via-navy-800 to-emerald-900 p-6 md:p-8 text-white shadow-sm">
        <div class="absolute -right-12 -top-16 h-56 w-56 rounded-full border border-white/10"></div>
        <div class="absolute -right-2 -bottom-24 h-64 w-64 rounded-full border border-white/10"></div>
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold text-emerald-100">
                    <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                    Portal Admin Madrasah
                </div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">{{ $tenant->name }}</h1>
                <p class="mt-2 text-sm text-slate-200">Dashboard administrasi, data akademik, pengguna, dan dukungan operasional madrasah.</p>
                <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-200">
                    <span>NSM: {{ $tenant->nsm ?: 'Belum diisi' }}</span>
                    <span>NPSN: {{ $tenant->npsn ?: 'Belum diisi' }}</span>
                    <span>{{ $tenant->email ?: 'Email belum diisi' }}</span>
                </div>
            </div>
            <div class="relative rounded-xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm lg:min-w-64">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-100">Periode Akademik Aktif</p>
                <p class="mt-1 text-lg font-bold">{{ $activeAcademicYear?->name ?? 'Belum ditetapkan' }}</p>
                <p class="mt-1 text-sm text-slate-200">
                    Semester {{ $activeSemester?->term ?? 'belum ditetapkan' }}
                </p>
            </div>
        </div>
        <div class="relative mt-6 flex flex-wrap gap-3">
            <a href="/admin/users" class="rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-navy-900 shadow-sm transition hover:bg-emerald-50">+ Tambah Pengguna</a>
            <a href="/admin/academic" class="rounded-xl border border-white/25 bg-white/10 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/20">Kelola Akademik</a>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        <article class="rounded-2xl border border-cardBorder bg-white p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-textSecondary">Akun Aktif</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-navy-900">{{ number_format($totalUsers) }}</p>
            <p class="mt-1 text-[11px] text-textSecondary">Tidak termasuk akun terhapus</p>
        </article>
        <article class="rounded-2xl border border-cardBorder bg-white p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-textSecondary">Siswa</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-emerald-700">{{ number_format($totalStudents) }}</p>
            <p class="mt-1 text-[11px] text-textSecondary">Dengan profil siswa</p>
        </article>
        <article class="rounded-2xl border border-cardBorder bg-white p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-textSecondary">Guru</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-indigo-700">{{ number_format($totalTeachers) }}</p>
            <p class="mt-1 text-[11px] text-textSecondary">Dengan profil Guru</p>
        </article>
        <article class="rounded-2xl border border-cardBorder bg-white p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-textSecondary">Rombel Aktif</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-navy-900">{{ number_format($totalRombels) }}</p>
            <p class="mt-1 text-[11px] text-textSecondary">Status aktif</p>
        </article>
        <article class="rounded-2xl border border-cardBorder bg-white p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-textSecondary">Mapel Aktif</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-amber-700">{{ number_format($totalSubjects) }}</p>
            <p class="mt-1 text-[11px] text-textSecondary">Termasuk mapel terdaftar</p>
        </article>
        <article class="rounded-2xl border border-cardBorder bg-white p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-textSecondary">Jadwal Ujian</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-purple-700">{{ number_format($totalExams) }}</p>
            <p class="mt-1 text-[11px] text-textSecondary">Daftar ujian tenant</p>
        </article>
    </section>

    <section>
        <div class="mb-3 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-navy-900">Akses Cepat Administrasi</h2>
                <p class="mt-1 text-xs text-textSecondary">Fitur yang tersedia sesuai kewenangan Admin Madrasah.</p>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <a href="/admin/users" class="group rounded-2xl border border-cardBorder bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-xl">👥</div>
                <h3 class="font-bold text-navy-900">Pengguna & Akun</h3>
                <p class="mt-1 text-xs leading-5 text-textSecondary">Kelola data akun Guru dan Siswa madrasah.</p>
            </a>
            <a href="/admin/academic" class="group rounded-2xl border border-cardBorder bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-xl">🏫</div>
                <h3 class="font-bold text-navy-900">Data Akademik</h3>
                <p class="mt-1 text-xs leading-5 text-textSecondary">Kelola tahun ajaran, kelas, Rombel, dan mata pelajaran.</p>
            </a>
            <a href="/admin/curriculum" class="group rounded-2xl border border-cardBorder bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-xl">📖</div>
                <h3 class="font-bold text-navy-900">Kurikulum</h3>
                <p class="mt-1 text-xs leading-5 text-textSecondary">Lihat dan kelola struktur CP/TP sesuai kewenangan.</p>
            </a>
            <a href="/admin/proctor-assignments" class="group rounded-2xl border border-cardBorder bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-xl">🛡️</div>
                <h3 class="font-bold text-navy-900">Guru Pengawas</h3>
                <p class="mt-1 text-xs leading-5 text-textSecondary">Tetapkan Guru pengawas sementara untuk ujian yang diterbitkan.</p>
            </a>
            <a href="/admin/madrasah-profile" class="group rounded-2xl border border-cardBorder bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-xl">🏛️</div>
                <h3 class="font-bold text-navy-900">Identitas Madrasah</h3>
                <p class="mt-1 text-xs leading-5 text-textSecondary">Perbarui nama, NSM, NPSN, alamat, kontak, dan logo resmi.</p>
            </a>
            <a href="/admin/academic-periods" class="group rounded-2xl border border-cardBorder bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-xl">📅</div>
                <h3 class="font-bold text-navy-900">Tahun Ajaran & Semester</h3>
                <p class="mt-1 text-xs leading-5 text-textSecondary">Atur periode aktif tanpa menghapus riwayat akademik sebelumnya.</p>
            </a>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <article class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-navy-900">Pemeriksaan Dasar Sistem</h2>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-textSecondary">Bukan health check penuh</span>
            </div>
            <div class="mt-4 space-y-3">
                <div class="flex items-center justify-between gap-3 text-sm">
                    <span class="text-textSecondary">Koneksi database</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $databaseHealth }}</span>
                </div>
                <div class="flex items-center justify-between gap-3 text-sm">
                    <span class="text-textSecondary">Direktori storage</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $storageHealth }}</span>
                </div>
            </div>
        </article>

        <article class="rounded-2xl border border-cardBorder bg-white shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between border-b border-cardBorder p-5">
                <div>
                    <h2 class="font-bold text-navy-900">Aktivitas Administratif Terbaru</h2>
                    <p class="mt-1 text-xs text-textSecondary">Ringkasan audit log pada madrasah ini.</p>
                </div>
            </div>
            <div class="divide-y divide-cardBorder">
                @forelse($recentActivity as $activity)
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-navy-900">{{ str_replace('_', ' ', $activity->action) }}</p>
                            <p class="mt-0.5 truncate text-xs text-textSecondary">{{ $activity->user?->name ?? 'Pengguna tidak tersedia' }}</p>
                        </div>
                        <time class="shrink-0 text-[11px] text-textSecondary">{{ $activity->created_at?->format('d/m/Y H:i') ?? '-' }}</time>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-textSecondary">Belum ada aktivitas administratif tercatat.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <article class="overflow-hidden rounded-2xl border border-cardBorder bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-cardBorder bg-slate-50/70 p-5">
                <h2 class="font-bold text-navy-900">Pengguna Terbaru</h2>
                <a href="/admin/users" class="text-xs font-bold text-emerald-700 hover:underline">Lihat semua</a>
            </div>
            <div class="divide-y divide-cardBorder">
                @forelse($recentUsers as $user)
                    <div class="flex items-center justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-navy-900">{{ $user->name }}</p>
                            <p class="truncate text-xs text-textSecondary">{{ $user->email }}</p>
                        </div>
                        <span class="shrink-0 rounded-lg border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-800">
                            {{ $user->roles->pluck('label')->map(fn ($label) => $label === 'Operator' ? 'Admin Madrasah' : $label)->join(', ') ?: 'Tanpa Role' }}
                        </span>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-textSecondary">Belum ada data pengguna.</div>
                @endforelse
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-cardBorder bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-cardBorder bg-slate-50/70 p-5">
                <h2 class="font-bold text-navy-900">Rombel Aktif</h2>
                <a href="/admin/academic" class="text-xs font-bold text-emerald-700 hover:underline">Lihat semua</a>
            </div>
            <div class="divide-y divide-cardBorder">
                @forelse($recentRombels as $rombel)
                    <div class="flex items-center justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-navy-900">{{ $rombel->name }}</p>
                            <p class="truncate text-xs text-textSecondary">{{ $rombel->academicYear?->name ?? 'Tahun ajaran belum ditentukan' }}</p>
                        </div>
                        <span class="shrink-0 rounded-lg border border-blue-100 bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-800">Aktif</span>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-textSecondary">Belum ada Rombel aktif.</div>
                @endforelse
            </div>
        </article>
    </section>
</div>
