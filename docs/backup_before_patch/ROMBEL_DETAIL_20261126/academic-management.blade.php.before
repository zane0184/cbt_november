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
    </div>

    <!-- Search Input -->
    @if($activeTab !== 'grade_level')
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
</div>
