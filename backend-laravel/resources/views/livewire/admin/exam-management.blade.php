<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Manajemen Pembuatan Ujian & Sesi</h1>
            <p class="text-xs text-textSecondary mt-1">Buat, jadwalkan, kunci, dan kelola token asesmen digital siswa per rombel</p>
        </div>
        @can('create', \\App\\Models\\Exam::class)
            <button wire:click="openCreateModal" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-xl transition text-xs flex items-center gap-2 shadow-sm">
                <span>+</span> Buat Ujian Baru
            </button>
        @endcan
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 border-l-4 border-emerald-600 text-emerald-800 p-4 rounded-xl shadow-sm text-sm">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border-l-4 border-red-600 text-red-800 p-4 rounded-xl shadow-sm text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filter Bar -->
    <div class="bg-white border border-cardBorder rounded-2xl p-4 shadow-sm flex flex-col md:flex-row gap-3">
        <input wire:model.live="search" type="text" placeholder="Cari judul ujian..." class="border border-cardBorder rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none flex-1">
        <select wire:model.live="statusFilter" class="border border-cardBorder rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none md:w-48">
            <option value="">Semua Status</option>
            <option value="DRAFT">Draf (Dapat Diubah)</option>
            <option value="PUBLISHED">Dikunci / Dijadwalkan</option>
            <option value="CLOSED">Selesai / Ditutup</option>
        </select>
    </div>

    <!-- Exam Cards List -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse($exams as $exam)
            <div class="bg-white border border-cardBorder rounded-2xl shadow-sm p-6 space-y-4 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start gap-2 border-b border-cardBorder pb-3 mb-3">
                        <div>
                            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200">
                                {{ $exam->assessment->subject->name ?? 'Asesmen' }}
                            </span>
                            <h3 class="text-lg font-bold text-navy-900 mt-2">{{ $exam->title }}</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-full
                                {{ $exam->status === 'PUBLISHED' ? 'bg-blue-50 text-blue-700 border border-blue-200' :
                                   ($exam->status === 'CLOSED' ? 'bg-gray-100 text-gray-700 border border-gray-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                {{ $exam->current_status }}
                            </span>
                            @can('update', $exam)
                                @if(!$exam->isLocked())
                                    <button wire:click="openEditModal('{{ $exam->id }}')" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-1 px-2.5 rounded-lg shadow-sm">
                                        ✎ Edit
                                    </button>
                                @endif
                            @endcan
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs text-textSecondary bg-contentBg p-3 rounded-xl border border-cardBorder">
                        <div>
                            <span class="font-bold text-navy-900">Durasi:</span> {{ floor($exam->duration_seconds / 60) }} Menit
                        </div>
                        <div>
                            <span class="font-bold text-navy-900">Batas Attempt:</span> {{ $exam->attempt_limit }}x
                        </div>
                        <div>
                            <span class="font-bold text-navy-900">Jumlah Soal Snapshot:</span> <strong class="text-emerald-700">{{ $exam->snapshots->count() }} Soal</strong>
                        </div>
                        <div>
                            <span class="font-bold text-navy-900">Peserta Ditugaskan:</span> <strong class="text-indigo-700">{{ $exam->participants->count() }} Siswa</strong>
                        </div>
                        <div>
                            <span class="font-bold text-navy-900">Mulai:</span> {{ $exam->start_at ? $exam->start_at->format('d M H:i') : '-' }}
                        </div>
                        <div>
                            <span class="font-bold text-navy-900">Selesai:</span> {{ $exam->end_at ? $exam->end_at->format('d M H:i') : '-' }}
                        </div>
                    </div>

                    @if($exam->require_token)
                        @if($exam->status === 'PUBLISHED' && $exam->locked_at)
                            <div class="mt-3 flex items-center justify-between bg-navy-900 text-white p-3 rounded-xl text-xs">
                                <span class="text-gray-300 font-medium">Token Masuk Ujian:</span>
                                <span class="font-mono font-bold text-emerald-400 text-sm tracking-widest">{{ $exam->token ?? '-' }}</span>
                                @can('generateToken', $exam)
                                    <button wire:click="generateToken('{{ $exam->id }}')" class="text-[10px] text-emerald-300 hover:text-white underline font-bold">
                                        Regenerate
                                    </button>
                                @endcan
                            </div>
                        @else
                            <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">Token dibuat otomatis setelah ujian diterbitkan.</p>
                        @endif
                    @endif
                </div>

                <!-- Action Links -->
                <div class="pt-3 border-t border-cardBorder flex flex-wrap gap-2 text-xs">
                    @can('monitor', $exam)
                        <a href="/exams/{{ $exam->id }}/monitoring" class="bg-navy-900 hover:bg-navy-800 text-white font-bold py-2 px-3 rounded-xl transition shadow-sm">
                            🖥️ Live Monitoring
                        </a>
                    @endcan
                    <a href="/exams/{{ $exam->id }}/results" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-3 rounded-xl transition shadow-sm">
                        📊 Hasil & Finalisasi
                    </a>
                    <a href="/exams/{{ $exam->id }}/analytics" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-3 rounded-xl transition shadow-sm">
                        📈 Analisis
                    </a>
                    @can('lock', $exam)
                        @if(!$exam->isLocked())
                            <button wire:click="lockExam('{{ $exam->id }}')" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-3 rounded-xl transition shadow-sm ml-auto">
                                🔒 Terbitkan & Kunci Ujian
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
        @empty
            <div class="col-span-2 bg-white border border-cardBorder rounded-2xl p-12 text-center text-textSecondary text-sm shadow-sm">
                Belum ada ujian yang dibuat.
            </div>
        @endforelse
    </div>

    <div class="p-4 bg-white border border-cardBorder rounded-2xl shadow-sm">
        {{ $exams->links() }}
    </div>

    <!-- Create / Edit Exam Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-6 space-y-4 border border-cardBorder my-8">
                <div class="flex justify-between items-center border-b border-cardBorder pb-3">
                    <h3 class="text-lg font-bold text-navy-900">
                        {{ $editingExamId ? 'Edit Ujian & Pemilih Soal' : 'Buat Ujian Baru & Pilih Soal' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 font-bold text-lg">✕</button>
                </div>

                <form wire:submit.prevent="saveExam" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Judul Ujian</label>
                        <input wire:model="title" type="text" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none" placeholder="Contoh: Asesmen Sumatif Akhir Semester Akidah Akhlak Kelas 5">
                        @error('title') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Mata Pelajaran</label>
                            <select wire:model.live="selectedSubjectId" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                                <option value="">Pilih Mata Pelajaran...</option>
                                @foreach($subjects as $sub)
                                    <option value="{{ $sub->id }}">{{ $sub->name }} ({{ $sub->code }})</option>
                                @endforeach
                            </select>
                            @error('selectedSubjectId') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Semester Aktif</label>
                            <select wire:model="selectedSemesterId" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                                <option value="">Pilih semester aktif...</option>
                                @if($activeSemester)
                                    <option value="{{ $activeSemester->id }}">{{ $activeSemester->term }}</option>
                                @endif
                            </select>
                            @error('selectedSemesterId') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Rombel Target Peserta (Wajib)</label>
                            <select wire:model.live="selectedRombelId" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                                <option value="">Pilih Rombel Target Peserta...</option>
                                @foreach($rombels as $rombel)
                                    <option value="{{ $rombel->id }}">{{ $rombel->name }} ({{ $rombel->gradeLevel->name ?? '' }})</option>
                                @endforeach
                                @if(!auth()->user()->hasRole('GURU'))
                                    <option value="ALL">⚠️ SELURUH SISWA MADRASAH (TETAPKAN GLOBAL)</option>
                                @endif
                            </select>
                            @error('selectedRombelId') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    @if($selectedRombelId === 'ALL')
                        <div class="bg-amber-50 border border-amber-300 rounded-xl p-3 text-xs text-amber-900 space-y-2">
                            <p class="font-bold">⚠️ Perhatian Penugasan Global:</p>
                            <p>Anda akan menugaskan ujian ini kepada SELURUH SISWA madrasah pada tenant ini.</p>
                            <label class="flex items-center gap-2 font-bold cursor-pointer">
                                <input type="checkbox" wire:model="confirmAllStudents" class="rounded text-amber-600 focus:ring-amber-500">
                                SAYA MENGONFIRMASI PENUGASAN SELURUH SISWA
                            </label>
                            @error('confirmAllStudents') <span class="text-red-600 font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- Question Picker Section (Phase B) -->
                    <div class="bg-gray-50 border border-cardBorder rounded-xl p-4 space-y-3">
                        <div class="flex justify-between items-center border-b border-cardBorder pb-2">
                            <label class="text-xs font-bold text-navy-900 uppercase">
                                Pilih Soal dari Bank Soal (Wajib Minimal 1)
                            </label>
                            <span class="bg-emerald-600 text-white font-bold text-xs px-2.5 py-1 rounded-lg">
                                Terpilih: {{ count($selectedQuestionVersionIds) }} Soal
                            </span>
                        </div>
                        @error('selectedQuestionVersionIds') <span class="text-red-600 text-xs font-bold block">{{ $message }}</span> @enderror

                        <div class="max-h-48 overflow-y-auto space-y-2 pr-1">
                            @forelse($availableQuestions as $q)
                                @if($q->currentVersion)
                                    <label class="flex items-start gap-3 p-2.5 rounded-xl border bg-white hover:bg-emerald-50/50 cursor-pointer transition text-xs
                                        {{ in_array($q->currentVersion->id, $selectedQuestionVersionIds) ? 'border-emerald-500 bg-emerald-50/80 font-bold' : 'border-cardBorder' }}">
                                        <input type="checkbox"
                                            wire:click="toggleQuestionSelection('{{ $q->currentVersion->id }}')"
                                            {{ in_array($q->currentVersion->id, $selectedQuestionVersionIds) ? 'checked' : '' }}
                                            class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="font-mono bg-navy-900 text-white px-2 py-0.5 rounded text-[10px]">{{ $q->question_code }}</span>
                                                <span class="text-[10px] uppercase font-bold text-textSecondary bg-gray-100 px-1.5 py-0.5 rounded">{{ $q->currentVersion->question_type }}</span>
                                                <span class="text-[10px] font-bold text-emerald-700 ml-auto">{{ $q->currentVersion->default_points }} Poin</span>
                                            </div>
                                            <p class="text-navy-900 line-clamp-2">{!! e($q->currentVersion->stem) !!}</p>
                                        </div>
                                    </label>
                                @endif
                            @empty
                                <div class="text-xs text-textSecondary text-center py-4">
                                    Pilih Mata Pelajaran terlebih dahulu atau buat soal di Bank Soal.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Durasi (Menit)</label>
                            <input wire:model="durationMinutes" type="number" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            @error('durationMinutes') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Batas Attempt (Percobaan)</label>
                            <input wire:model="attemptLimit" type="number" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Waktu Mulai</label>
                            <input wire:model="startAt" type="datetime-local" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            @error('startAt') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Waktu Selesai</label>
                            <input wire:model="endAt" type="datetime-local" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            @error('endAt') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Petunjuk Ujian</label>
                        <textarea wire:model="instructions" rows="2" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none"></textarea>
                    </div>

                    <div class="space-y-2 bg-contentBg p-3 rounded-xl border border-cardBorder text-xs">
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-900">
                            <p class="font-bold">Token masuk wajib digunakan.</p>
                            <p class="mt-1 text-[11px]">Sistem membuat token otomatis saat Guru menerbitkan ujian. Token dapat dilihat sebelum jadwal dimulai.</p>
                        </div>
                        <label class="flex items-center gap-2 font-bold text-navy-900">
                            <input type="checkbox" wire:model="shuffleQuestions" class="rounded text-emerald-600 focus:ring-emerald-600">
                            Acak Urutan Soal
                        </label>
                        <label class="flex items-center gap-2 font-bold text-navy-900">
                            <input type="checkbox" wire:model="shuffleOptions" class="rounded text-emerald-600 focus:ring-emerald-600">
                            Acak Pilihan Jawaban
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t border-cardBorder">
                        <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-xs font-bold text-textSecondary rounded-xl bg-gray-100">Batal</button>
                        @can('create', \\App\\Models\\Exam::class)
                            <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md">
                                {{ $editingExamId ? 'Perbarui Ujian & Snapshot' : 'Simpan Ujian' }}
                            </button>
                        @endcan
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
