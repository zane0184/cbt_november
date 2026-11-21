<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Data Akademik & Rombel</h1>
            <p class="text-xs text-textSecondary mt-1">Kelola rombongan belajar, tingkat kelas, dan mata pelajaran madrasah</p>
        </div>
        <div class="flex gap-2">
            @if(auth()->user()?->hasPermission('academic.manage'))
            @if($activeTab === 'rombel')
                <button wire:click="openCreateRombelModal" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-xl transition text-xs flex items-center gap-2 shadow-sm">
                    <span>+</span> Tambah Rombel
                </button>
            @elseif($activeTab === 'subject')
                <button wire:click="openCreateSubjectModal" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-xl transition text-xs flex items-center gap-2 shadow-sm">
                    <span>+</span> Tambah Mata Pelajaran
                </button>
            @elseif($activeTab === 'period')
                <button wire:click="openCreateAcademicYearModal" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-xl transition text-xs">+ Tahun Ajaran</button>
                <button wire:click="openCreateSemesterModal" class="border border-cardBorder bg-white px-4 py-2.5 rounded-xl text-xs font-bold text-navy-900">+ Semester</button>
            @endif
            @endif
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 border-l-4 border-emerald-600 text-emerald-800 p-4 rounded-xl shadow-sm text-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="flex border-b border-cardBorder gap-2">
        <button wire:click="setTab('rombel')" class="px-5 py-3 font-bold text-sm rounded-t-xl transition border-b-2 {{ $activeTab === 'rombel' ? 'border-emerald-600 text-emerald-700 bg-white' : 'border-transparent text-textSecondary hover:text-navy-900' }}">
            Rombongan Belajar (Rombel)
        </button>
        <button wire:click="setTab('subject')" class="px-5 py-3 font-bold text-sm rounded-t-xl transition border-b-2 {{ $activeTab === 'subject' ? 'border-emerald-600 text-emerald-700 bg-white' : 'border-transparent text-textSecondary hover:text-navy-900' }}">
            Mata Pelajaran
        </button>
        <button wire:click="setTab('grade_level')" class="px-5 py-3 font-bold text-sm rounded-t-xl transition border-b-2 {{ $activeTab === 'grade_level' ? 'border-emerald-600 text-emerald-700 bg-white' : 'border-transparent text-textSecondary hover:text-navy-900' }}">
            Tingkat Kelas & Fase
        </button>
        <button wire:click="setTab('period')" class="px-5 py-3 font-bold text-sm rounded-t-xl transition border-b-2 {{ $activeTab === 'period' ? 'border-emerald-600 text-emerald-700 bg-white' : 'border-transparent text-textSecondary hover:text-navy-900' }}">
            Tahun Ajaran & Semester
        </button>
    </div>

    <!-- Search Input -->
    @if(in_array($activeTab, ['rombel', 'subject'], true))
        <div class="bg-white border border-cardBorder rounded-2xl p-4 shadow-sm">
            <input wire:model.live="search" type="text" placeholder="Cari nama..." class="w-full border border-cardBorder rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
        </div>
    @endif

    <!-- Tab Contents -->
    @if($activeTab === 'rombel')
        <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-cardBorder">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Nama Rombel</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Tingkat Kelas</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Tahun Akademik</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-cardBorder">
                        @forelse($rombels as $rombel)
                            <tr class="hover:bg-gray-50/80 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-navy-900">{{ $rombel->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-textSecondary font-medium">{{ $rombel->gradeLevel->name ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-textSecondary font-mono">{{ $rombel->academicYear->name ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $rombel->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-12 text-center text-textSecondary text-sm">Belum ada data rombel.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-cardBorder">{{ $rombels->links() }}</div>
        </div>

    @elseif($activeTab === 'subject')
        <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-cardBorder">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Kode</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Nama Mata Pelajaran</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Singkatan</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold text-textSecondary uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-cardBorder">
                        @forelse($subjects as $subject)
                            <tr class="hover:bg-gray-50/80 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-navy-900 font-mono">{{ $subject->code }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-navy-900">{{ $subject->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-textSecondary">{{ $subject->short_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $subject->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-12 text-center text-textSecondary text-sm">Belum ada mata pelajaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-cardBorder">{{ $subjects->links() }}</div>
        </div>

    @elseif($activeTab === 'grade_level')
        <div class="bg-white border border-cardBorder rounded-2xl shadow-sm p-6 space-y-4">
            <h3 class="text-base font-bold text-navy-900">Daftar Tingkat Kelas & Fase Kurikulum</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($gradeLevels as $gl)
                    <div class="border border-cardBorder p-4 rounded-xl bg-contentBg shadow-sm">
                        <div class="text-xs font-bold text-emerald-700 uppercase">Fase {{ $gl->phase->name ?? '-' }}</div>
                        <div class="text-lg font-bold text-navy-900 mt-1">{{ $gl->name }}</div>
                        <div class="text-xs text-textSecondary mt-1">Level: {{ $gl->level }} | Kode: {{ $gl->code }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @elseif($activeTab === 'period')
        <div class="space-y-4">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                Sistem mendukung tahun ajaran 2026/2027 sampai 2031/2032. Membuat tahun ajaran atau semester tidak otomatis mengaktifkannya. Saat tahun ajaran diganti, semester aktif dinonaktifkan dan Admin harus memilih semester baru secara terpisah.
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-textSecondary">Tahun Ajaran Aktif</p>
                    <p class="mt-2 text-xl font-bold text-navy-900">{{ $activeYear ? $activeYear->name : ($activeYearCount === 0 ? 'Belum Ditetapkan' : 'Perlu Ditinjau — lebih dari satu aktif') }}</p>
                </div>
                <div class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-textSecondary">Semester Aktif</p>
                    <p class="mt-2 text-xl font-bold text-navy-900">{{ $activeSemesterCount === 0 ? 'Belum Ditetapkan' : ($activeSemesterCount > 1 ? 'Perlu Ditinjau — lebih dari satu aktif' : ($activeYear && (string) $activeSemester->academic_year_id === (string) $activeYear->id ? ucfirst(strtolower($activeSemester->term)) : 'Perlu Ditinjau — tidak sesuai tahun aktif')) }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-cardBorder bg-white shadow-sm">
                <div class="border-b border-cardBorder p-5">
                    <h3 class="font-bold text-navy-900">Daftar Tahun Ajaran</h3>
                    <p class="mt-1 text-xs text-textSecondary">Pilih satu tahun ajaran aktif. Riwayat tahun sebelumnya tetap disimpan.</p>
                </div>
                <div class="divide-y divide-cardBorder">
                    @forelse($academicYears as $year)
                        @php($yearSemesters = $semesters->where('academic_year_id', $year->id)->values())
                        <div class="p-5">
                            <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-base font-bold text-navy-900">{{ $year->name }}</h4>
                                        @if($year->is_active)
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-800">AKTIF</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">RIWAYAT / BELUM AKTIF</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-xs text-textSecondary">{{ $yearSemesters->count() }} semester terdaftar</p>
                                </div>
                                @if(!$year->is_active)
                                    <button wire:click="activateAcademicYear('{{ $year->id }}')" wire:confirm="Aktifkan tahun ajaran {{ $year->name }}? Semester aktif saat ini akan dinonaktifkan dan harus dipilih kembali." class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-100">
                                        Jadikan Tahun Aktif
                                    </button>
                                @else
                                    <span class="text-xs font-semibold text-emerald-700">Tahun ajaran terpilih</span>
                                @endif
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach(['GANJIL' => 'Semester Ganjil', 'GENAP' => 'Semester Genap'] as $term => $label)
                                    @php($semester = $yearSemesters->firstWhere('term', $term))
                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-cardBorder bg-slate-50 p-3">
                                        <div>
                                            <p class="text-sm font-semibold text-navy-900">{{ $label }}</p>
                                            @if($semester)
                                                <p class="mt-0.5 text-[11px] text-textSecondary">{{ $semester->is_active ? 'Sedang aktif' : 'Belum aktif' }}</p>
                                            @else
                                                <p class="mt-0.5 text-[11px] text-textSecondary">Belum dibuat</p>
                                            @endif
                                        </div>
                                        @if($semester && $semester->is_active)
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-800">AKTIF</span>
                                        @elseif($semester && $year->is_active && $activeYearCount === 1)
                                            <button wire:click="activateSemester('{{ $semester->id }}')" wire:confirm="Aktifkan {{ $label }} untuk {{ $year->name }}?" class="rounded-lg bg-emerald-600 px-3 py-2 text-[11px] font-bold text-white hover:bg-emerald-700">Aktifkan</button>
                                        @elseif($semester)
                                            <span class="text-[10px] font-semibold text-textSecondary">Aktifkan tahun dahulu</span>
                                        @else
                                            <button wire:click="openCreateSemesterModal('{{ $year->id }}')" class="rounded-lg border border-cardBorder bg-white px-3 py-2 text-[11px] font-bold text-navy-900 hover:bg-gray-100">Buat semester</button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-textSecondary">Belum ada tahun ajaran. Tambahkan tahun ajaran terlebih dahulu.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- Create Rombel Modal -->
    @if($showCreateRombelModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-cardBorder">
                <h3 class="text-lg font-bold text-navy-900">Tambah Rombel Baru</h3>
                <form wire:submit.prevent="createRombel" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Nama Rombel</label>
                        <input wire:model="rombelName" type="text" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none" placeholder="Contoh: Kelas 1A">
                        @error('rombelName') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Tingkat Kelas</label>
                        <select wire:model="selectedGradeLevelId" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            <option value="">Pilih Tingkat Kelas...</option>
                            @foreach($gradeLevels as $gl)
                                <option value="{{ $gl->id }}">{{ $gl->name }} (Fase {{ $gl->phase->name ?? '' }})</option>
                            @endforeach
                        </select>
                        @error('selectedGradeLevelId') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Tahun Akademik</label>
                        <select wire:model="selectedAcademicYearId" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            <option value="">Pilih Tahun Akademik...</option>
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}">{{ $ay->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedAcademicYearId') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-cardBorder">
                        <button type="button" wire:click="$set('showCreateRombelModal', false)" class="px-4 py-2 text-xs font-bold text-textSecondary rounded-xl bg-gray-100">Batal</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md">Simpan Rombel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Create Subject Modal -->
    @if($showCreateSubjectModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-cardBorder">
                <h3 class="text-lg font-bold text-navy-900">Tambah Mata Pelajaran</h3>
                <form wire:submit.prevent="createSubject" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Kode Mapel</label>
                        <input wire:model="subjectCode" type="text" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none" placeholder="Contoh: MTK">
                        @error('subjectCode') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Nama Mata Pelajaran</label>
                        <input wire:model="subjectName" type="text" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none" placeholder="Contoh: Matematika">
                        @error('subjectName') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Singkatan / Short Name</label>
                        <input wire:model="subjectShortName" type="text" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none" placeholder="Contoh: MTK">
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-cardBorder">
                        <button type="button" wire:click="$set('showCreateSubjectModal', false)" class="px-4 py-2 text-xs font-bold text-textSecondary rounded-xl bg-gray-100">Batal</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md">Simpan Mapel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showCreateAcademicYearModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md space-y-4 rounded-2xl border border-cardBorder bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-navy-900">Tambah Tahun Ajaran</h3>
                <p class="text-xs text-textSecondary">Tahun baru disimpan sebagai belum aktif. Admin harus memilihnya secara eksplisit.</p>
                <form wire:submit.prevent="createAcademicYear" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-navy-900">Tahun Ajaran</label>
                        <select wire:model="academicYearName" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
                            <option value="">Pilih tahun ajaran...</option>
                            @foreach($supportedAcademicYears as $supportedYear)
                                <option value="{{ $supportedYear }}">{{ $supportedYear }}</option>
                            @endforeach
                        </select>
                        @error('academicYearName') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end gap-2 border-t border-cardBorder pt-3">
                        <button type="button" wire:click="$set('showCreateAcademicYearModal', false)" class="rounded-xl bg-gray-100 px-4 py-2 text-xs font-bold text-textSecondary">Batal</button>
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">Simpan Tahun</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showCreateSemesterModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md space-y-4 rounded-2xl border border-cardBorder bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-navy-900">Tambah Semester</h3>
                <p class="text-xs text-textSecondary">Semester baru tidak otomatis menjadi semester aktif.</p>
                <form wire:submit.prevent="createSemester" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-navy-900">Tahun Ajaran</label>
                        <select wire:model="selectedPeriodYearId" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
                            <option value="">Pilih tahun ajaran...</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedPeriodYearId') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-navy-900">Semester</label>
                        <select wire:model="selectedPeriodTerm" class="w-full rounded-xl border border-cardBorder px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
                            <option value="GANJIL">Ganjil</option>
                            <option value="GENAP">Genap</option>
                        </select>
                        @error('selectedPeriodTerm') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end gap-2 border-t border-cardBorder pt-3">
                        <button type="button" wire:click="$set('showCreateSemesterModal', false)" class="rounded-xl bg-gray-100 px-4 py-2 text-xs font-bold text-textSecondary">Batal</button>
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">Simpan Semester</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
