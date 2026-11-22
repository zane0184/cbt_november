<div class="mx-auto max-w-6xl space-y-6">
    <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm md:p-8">
        <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Administrasi Akademik</p>
        <h1 class="mt-1 text-2xl font-bold text-navy-900">Tahun Ajaran & Semester</h1>
        <p class="mt-2 text-sm text-textSecondary">Atur periode akademik aktif dengan histori yang tetap dipertahankan. Perubahan periode diserialisasi dan dicatat dalam audit log.</p>
        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-textSecondary">Tahun Ajaran Aktif</p>
                <p class="mt-1 text-lg font-bold text-navy-900">{{ $activeYear?->name ?? 'Belum ditetapkan' }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-textSecondary">Semester Aktif</p>
                <p class="mt-1 text-lg font-bold text-navy-900">{{ $activeSemester ? $activeSemester->term.' · '.$activeSemester->academicYear?->name : 'Belum ditetapkan' }}</p>
            </div>
        </div>
    </section>

    @if(session()->has('academicPeriodMessage'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('academicPeriodMessage') }}</div>
    @endif

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm">
            <h2 class="font-bold text-navy-900">Tambah Tahun Ajaran</h2>
            <p class="mt-1 text-xs leading-5 text-textSecondary">Format tahun ajaran: YYYY/YYYY. Tahun baru dibuat nonaktif sampai Admin mengaktifkannya.</p>
            <form wire:submit="createAcademicYear" class="mt-4 space-y-3">
                <div>
                    <label for="year-name" class="mb-1 block text-xs font-bold text-navy-900">Tahun Ajaran</label>
                    <input id="year-name" wire:model="yearName" type="text" placeholder="2026/2027" maxlength="9" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    @error('yearName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-800">Tambah Tahun Ajaran</button>
            </form>
        </div>

        <div class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="font-bold text-navy-900">Tambah Semester</h2>
            <p class="mt-1 text-xs leading-5 text-textSecondary">Semester hanya dapat diaktifkan pada tahun ajaran yang sedang aktif. Hanya satu semester aktif untuk satu tenant.</p>
            <form wire:submit="createSemester" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3 sm:items-end">
                <div class="sm:col-span-2">
                    <label for="period-year" class="mb-1 block text-xs font-bold text-navy-900">Tahun Ajaran</label>
                    <select id="period-year" wire:model="selectedYearId" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        <option value="">Pilih tahun ajaran</option>
                        @foreach($years as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}{{ $year->is_active ? ' (Aktif)' : '' }}</option>
                        @endforeach
                    </select>
                    @error('selectedYearId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="period-semester" class="mb-1 block text-xs font-bold text-navy-900">Semester</label>
                    <select id="period-semester" wire:model="semesterTerm" class="w-full rounded-xl border border-cardBorder px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        <option value="GANJIL">Ganjil</option>
                        <option value="GENAP">Genap</option>
                    </select>
                </div>
                <div class="sm:col-span-3">
                    @error('semesterTerm') <p class="mb-2 text-xs text-red-600">{{ $message }}</p> @enderror
                    <button type="submit" class="rounded-xl bg-navy-900 px-4 py-3 text-sm font-bold text-white hover:bg-navy-800">Tambah Semester</button>
                </div>
            </form>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-cardBorder bg-white shadow-sm">
        <div class="border-b border-cardBorder p-5">
            <h2 class="font-bold text-navy-900">Riwayat Tahun Ajaran</h2>
            <p class="mt-1 text-xs text-textSecondary">Tahun ajaran dan semester lama tidak dihapus saat periode baru diaktifkan.</p>
        </div>
        <div class="divide-y divide-cardBorder">
            @forelse($years as $year)
                <article class="p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-bold text-navy-900">{{ $year->name }}</h3>
                                @if($year->is_active)
                                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800">Aktif</span>
                                @else
                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600">Riwayat / Nonaktif</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-textSecondary">{{ $year->semesters->count() }} semester terdaftar</p>
                        </div>
                        @unless($year->is_active)
                            <button type="button" wire:click="activateAcademicYear('{{ $year->id }}')" wire:confirm="Aktifkan tahun ajaran {{ $year->name }}? Semester dari tahun ajaran lain akan dinonaktifkan." class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-800 hover:bg-emerald-100">Aktifkan Tahun Ajaran</button>
                        @endunless
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @forelse($year->semesters as $semester)
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-cardBorder p-3">
                                <div>
                                    <p class="text-sm font-semibold text-navy-900">Semester {{ $semester->term }}</p>
                                    <p class="mt-0.5 text-[11px] text-textSecondary">{{ $semester->is_active ? 'Sedang aktif' : 'Nonaktif' }}</p>
                                </div>
                                @if($year->is_active && !$semester->is_active)
                                    <button type="button" wire:click="activateSemester('{{ $semester->id }}')" wire:confirm="Aktifkan semester {{ $semester->term }} untuk {{ $year->name }}?" class="rounded-lg bg-navy-900 px-3 py-2 text-[11px] font-bold text-white hover:bg-navy-800">Aktifkan</button>
                                @elseif($semester->is_active)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800">Aktif</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-textSecondary">Belum ada semester pada tahun ajaran ini.</p>
                        @endforelse
                    </div>
                </article>
            @empty
                <div class="p-8 text-center text-sm text-textSecondary">Belum ada tahun ajaran. Tambahkan periode akademik pertama.</div>
            @endforelse
        </div>
    </section>
</div>
