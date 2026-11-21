<div class="space-y-6">
    <section class="flex flex-col justify-between gap-5 rounded-2xl border border-cardBorder bg-white p-6 shadow-sm lg:flex-row lg:items-center">
        <div class="min-w-0">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Dashboard Eksekutif</p>
            <h1 class="text-2xl font-bold tracking-tight text-navy-900 md:text-3xl">Selamat datang, {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-sm text-textSecondary">{{ $tenant->name }} <span class="mx-1 text-gray-300">•</span> Administrasi madrasah dalam satu ringkasan.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/admin/profile" class="rounded-xl border border-cardBorder bg-white px-4 py-2.5 text-sm font-semibold text-navy-900 transition hover:bg-gray-50">Profil Madrasah</a>
            <a href="/admin/users" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">Kelola Guru & Siswa</a>
            <a href="/admin/academic" class="rounded-xl border border-cardBorder bg-white px-4 py-2.5 text-sm font-semibold text-navy-900 transition hover:bg-gray-50">Data Akademik</a>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-textSecondary">Siswa Terdaftar</p>
            <p class="mt-3 text-3xl font-bold tabular-nums text-navy-900">{{ number_format($totalStudents, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-textSecondary">Profil siswa pada madrasah ini</p>
        </article>
        <article class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-textSecondary">Guru Terdaftar</p>
            <p class="mt-3 text-3xl font-bold tabular-nums text-emerald-700">{{ number_format($totalTeachers, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-textSecondary">Profil Guru pada madrasah ini</p>
        </article>
        <article class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-textSecondary">Rombel Aktif</p>
            <p class="mt-3 text-3xl font-bold tabular-nums text-navy-900">{{ number_format($totalRombels, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-textSecondary">Rombongan belajar berstatus aktif</p>
        </article>
        <article class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-textSecondary">Mata Pelajaran Aktif</p>
            <p class="mt-3 text-3xl font-bold tabular-nums text-amber-600">{{ number_format($totalSubjects, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-textSecondary">Master mata pelajaran madrasah</p>
        </article>
    </section>

    <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <article class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-bold text-navy-900">Periode Akademik</h2>
                    <p class="mt-1 text-xs text-textSecondary">Periode aktif ditetapkan Admin; sistem tidak memilih atau mengaktifkan periode secara otomatis.</p>
                </div>
                <a href="/admin/academic" class="text-xs font-semibold text-emerald-700 hover:underline">Kelola periode</a>
            </div>
            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold text-textSecondary">Tahun Ajaran Aktif</p>
                    <p class="mt-2 text-lg font-bold text-navy-900">{{ $activeYearLabel }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold text-textSecondary">Semester Aktif</p>
                    <p class="mt-2 text-lg font-bold text-navy-900">{{ $activeSemesterLabel }}</p>
                </div>
            </div>
        </article>
        <article class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
            <h2 class="font-bold text-navy-900">Kesehatan Instalasi</h2>
            <p class="mt-1 text-xs text-textSecondary">Pemeriksaan ringkas tanpa menampilkan detail rahasia server.</p>
            <div class="mt-5 space-y-3 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-textSecondary">Database</span>
                    <span class="{{ $databaseHealthy ? 'text-emerald-700' : 'text-red-600' }} font-semibold">{{ $databaseHealthy ? 'Terhubung' : 'Perlu Diperiksa' }}</span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-textSecondary">Penyimpanan aplikasi</span>
                    <span class="{{ $storageWritable ? 'text-emerald-700' : 'text-red-600' }} font-semibold">{{ $storageWritable ? 'Dapat ditulis' : 'Perlu Diperiksa' }}</span>
                </div>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <article class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
            <div>
                <h2 class="font-bold text-navy-900">Distribusi Siswa per Kelas</h2>
                <p class="mt-1 text-xs text-textSecondary">Berdasarkan rombel aktif pada tahun ajaran aktif.</p>
            </div>
            @if($studentDistribution->isNotEmpty())
                <div class="mt-5 space-y-4">
                    @foreach($studentDistribution as $row)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <span class="font-medium text-navy-900">{{ $row['label'] }}</span>
                                <span class="font-semibold tabular-nums text-textSecondary">{{ number_format($row['students'], 0, ',', '.') }}</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-emerald-600" style="width: {{ (int) round(($row['students'] / $maxDistributionCount) * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-5 rounded-xl border border-dashed border-cardBorder bg-slate-50 p-6 text-center text-sm text-textSecondary">
                    Distribusi belum tersedia. Tetapkan satu tahun ajaran aktif dan lengkapi data rombel.
                </div>
            @endif
        </article>

        <article class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-bold text-navy-900">Aktivitas Administratif Terbaru</h2>
                    <p class="mt-1 text-xs text-textSecondary">Hanya aktivitas administrasi; tidak memuat data operasional ujian.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-textSecondary">{{ $recentActivities->count() }} aktivitas</span>
            </div>
            <div class="mt-4 divide-y divide-cardBorder">
                @forelse($recentActivities as $activity)
                    <div class="py-3 first:pt-0 last:pb-0">
                        <p class="text-sm font-semibold text-navy-900">{{ \Illuminate\Support\Str::headline($activity->action) }}</p>
                        <div class="mt-1 flex flex-wrap items-center justify-between gap-2 text-xs text-textSecondary">
                            <span>{{ $activity->user?->name ?? 'Sistem' }} · {{ $activity->entity_type }}</span>
                            <span>{{ $activity->created_at?->format('d M Y H:i') ?? '-' }}</span>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-textSecondary">Belum ada aktivitas administratif yang tercatat.</p>
                @endforelse
            </div>
        </article>
    </section>
</div>
