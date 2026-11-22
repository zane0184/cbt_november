<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Bank Soal & Modul Asesmen</h1>
            <p class="text-xs text-textSecondary mt-1">Kelola butir soal pilihan ganda, pilihan ganda kompleks, benar/salah, menjodohkan, dan uraian/esai</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('create', \App\Models\Question::class)
                <a href="{{ route('teacher.questions.import') }}" class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-xs font-bold text-indigo-800 hover:bg-indigo-100">Impor XLSX</a>
                <button wire:click="openCreateModal" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-xl transition text-xs flex items-center gap-2 shadow-sm">
                    <span>+</span> Buat Soal Baru
                </button>
            @endcan
            <a href="{{ route('teacher.questions.template') }}" class="rounded-xl border border-cardBorder bg-white px-4 py-2.5 text-xs font-bold text-navy-900 hover:bg-slate-50">Unduh Template</a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 border-l-4 border-emerald-600 text-emerald-800 p-4 rounded-xl shadow-sm text-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Filter Header -->
    <div class="bg-white border border-cardBorder rounded-2xl p-4 shadow-sm flex flex-col md:flex-row gap-3">
        <input wire:model.live="search" type="text" placeholder="Cari kode atau isi soal..." class="border border-cardBorder rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none flex-1">
        <select wire:model.live="subjectFilter" class="border border-cardBorder rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none md:w-52">
            <option value="">Semua Mapel</option>
            @foreach($subjects as $sub)
                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="statusFilter" class="border border-cardBorder rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none md:w-44">
            <option value="">Semua Status</option>
            <option value="DRAFT">Draf</option>
            <option value="PUBLISHED">Publikasi</option>
        </select>
    </div>

    <!-- Question Cards List -->
    <div class="space-y-4">
        @forelse($questions as $q)
            <div class="bg-white border border-cardBorder rounded-2xl shadow-sm p-6 space-y-4">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 border-b border-cardBorder pb-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="bg-navy-900 text-white font-mono font-bold text-xs px-3 py-1 rounded-lg">{{ $q->question_code }}</span>
                        <span class="text-xs font-bold text-textSecondary uppercase bg-gray-100 px-2.5 py-1 rounded-md border border-cardBorder">
                            {{ $q->currentVersion->question_type ?? 'MC' }}
                        </span>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200">
                            {{ $q->subject->name ?? '-' }}
                        </span>
                        @if($q->currentVersion && $q->currentVersion->learningOutcome)
                            <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-200" title="Capaian Pembelajaran">
                                CP: {{ $q->currentVersion->learningOutcome->code }}
                            </span>
                        @endif
                        @if($q->currentVersion && $q->currentVersion->learningObjective)
                            <span class="text-xs font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200" title="Tujuan Pembelajaran">
                                TP: {{ $q->currentVersion->learningObjective->code }}
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 text-xs font-bold rounded-full
                            {{ $q->status === 'PUBLISHED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                            {{ $q->status }}
                        </span>
                        @can('update', $q)
                            @if($q->status === 'DRAFT')
                                @can('publish', $q)
                                    <button wire:click="publishQuestion('{{ $q->id }}')" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-1 px-2.5 rounded-lg shadow-sm">
                                        Publikasikan
                                    </button>
                                @endcan
                            @endif
                            <button wire:click="openEditModal('{{ $q->id }}')" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-1 px-2.5 rounded-lg shadow-sm flex items-center gap-1">
                                ✎ Edit
                            </button>
                            <button wire:click="confirmDeleteQuestion('{{ $q->id }}')" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-1 px-2.5 rounded-lg shadow-sm flex items-center gap-1">
                                ✕ Arsipkan
                            </button>
                        @endcan
                    </div>
                </div>

                @if($q->currentVersion)
                    <div class="prose prose-sm max-w-none text-navy-900 bg-contentBg p-4 rounded-xl border border-cardBorder">
                        {!! nl2br(e($q->currentVersion->stem)) !!}
                    </div>

                    @if($q->currentVersion->explanation)
                        <div class="text-xs bg-amber-50/80 border border-amber-200 p-3 rounded-xl text-amber-900">
                            <span class="font-bold block mb-0.5">Pedoman Penskoran / Rubrik:</span>
                            {!! nl2br(e($q->currentVersion->explanation)) !!}
                        </div>
                    @endif

                    @if($q->currentVersion->options && $q->currentVersion->options->count() > 0)
                        @if(($q->currentVersion->question_type ?? '') === 'MATCHING')
                            <div class="space-y-1.5 pt-1">
                                <span class="text-xs font-bold text-emerald-900 block">Pasangan Menjodohkan:</span>
                                @php
                                    $leftOpts = $q->currentVersion->options->filter(fn($o) => !empty($o->option_key) && empty($o->matching_key));
                                    $rightOpts = $q->currentVersion->options->filter(fn($o) => !empty($o->matching_key))->keyBy('matching_key');
                                @endphp
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach($leftOpts as $left)
                                        @php $right = $rightOpts->get($left->option_key); @endphp
                                        <div class="p-2.5 rounded-xl border border-emerald-200 bg-emerald-50/50 text-xs flex items-center justify-between gap-2">
                                            <span class="font-bold text-navy-900">{{ $left->content }}</span>
                                            <span class="text-emerald-600 font-bold">↔</span>
                                            <span class="font-bold text-emerald-900 bg-white px-2 py-0.5 rounded border border-emerald-300">{{ $right ? $right->content : '-' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 pt-1">
                                @foreach($q->currentVersion->options as $opt)
                                    <div class="p-2.5 rounded-xl border text-xs flex items-center gap-2
                                        {{ $opt->is_correct ? 'bg-emerald-50/80 border-emerald-300 font-bold text-emerald-900' : 'bg-gray-50 border-cardBorder text-navy-900' }}">
                                        <span class="w-6 h-6 rounded-lg font-mono font-bold flex items-center justify-center shrink-0
                                            {{ $opt->is_correct ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-textSecondary' }}">
                                            {{ $opt->key ?: '-' }}
                                        </span>
                                        <span>{{ $opt->option_text }}</span>
                                        @if($opt->is_correct)
                                            <span class="ml-auto text-emerald-600 text-xs font-bold">✓ Kunci</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                @endif
            </div>
        @empty
            <div class="bg-white border border-cardBorder rounded-2xl p-12 text-center text-textSecondary text-sm shadow-sm">
                Belum ada butir soal di dalam bank soal.
            </div>
        @endforelse
    </div>

    <div class="p-4 bg-white border border-cardBorder rounded-2xl shadow-sm">
        {{ $questions->links() }}
    </div>

    <!-- Create / Edit Question Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-6 space-y-4 border border-cardBorder my-8">
                <div class="flex justify-between items-center border-b border-cardBorder pb-3">
                    <h3 class="text-lg font-bold text-navy-900">
                        {{ $editingQuestionId ? 'Edit Soal' : 'Buat Soal Baru' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 font-bold text-lg">✕</button>
                </div>

                <form wire:submit.prevent="saveQuestion" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Kode Soal</label>
                            <input wire:model="questionCode" type="text" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none" placeholder="Contoh: SOAL-AKD-01">
                            @error('questionCode') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Mata Pelajaran</label>
                            <select wire:model.live="selectedSubjectId" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                                <option value="">Pilih Mapel...</option>
                                @foreach($subjects as $sub)
                                    <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedSubjectId') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- CP & TP Mapping -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 bg-gray-50 p-3 rounded-xl border border-cardBorder">
                        <div>
                            <label class="block text-xs font-bold text-indigo-900 uppercase mb-1">Pemetaan CP (Capaian Pembelajaran)</label>
                            <select wire:model.live="selectedOutcomeId" class="w-full border border-cardBorder rounded-xl px-3 py-1.5 text-xs focus:ring-2 focus:ring-indigo-600 focus:outline-none">
                                <option value="">Tanpa CP (Opsional)</option>
                                @foreach($outcomes as $outcome)
                                    <option value="{{ $outcome->id }}">{{ $outcome->code }} - {{ $outcome->currentVersion->title ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-amber-900 uppercase mb-1">Pemetaan TP (Tujuan Pembelajaran)</label>
                            <select wire:model="selectedObjectiveId" class="w-full border border-cardBorder rounded-xl px-3 py-1.5 text-xs focus:ring-2 focus:ring-amber-600 focus:outline-none">
                                <option value="">Tanpa TP (Opsional)</option>
                                @foreach($objectives as $objective)
                                    <option value="{{ $objective->id }}">{{ $objective->code }} - {{ $objective->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Tipe Soal</label>
                            <select wire:model.live="questionType" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                                <option value="MULTIPLE_CHOICE">Pilihan Ganda (MC)</option>
                                <option value="MULTIPLE_RESPONSE">Pilihan Ganda Kompleks (PG Kompleks)</option>
                                <option value="TRUE_FALSE">Benar / Salah</option>
                                <option value="MATCHING">Menjodohkan (Matching)</option>
                                <option value="ESSAY">Uraian / Esai</option>
                                @if($questionType === 'SHORT_ANSWER')
                                    <option value="SHORT_ANSWER" disabled>Jawaban Singkat — dinonaktifkan sampai scoring siap</option>
                                @endif
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Poin Default</label>
                            <input wire:model="defaultPoints" type="number" step="0.5" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Isi / Pertanyaan (Stem) — Mendukung Teks Arab & Harakat</label>
                        <textarea wire:model="stem" rows="3" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none font-sans" placeholder="Tuliskan butir soal di sini..."></textarea>
                        @error('stem') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Dynamic Options Form -->
                    @if($questionType === 'MULTIPLE_CHOICE')
                        <div class="space-y-2 bg-contentBg p-4 rounded-xl border border-cardBorder">
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Pilihan Jawaban & Kunci Single</label>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <div>
                                    <span class="text-[10px] font-bold text-textSecondary uppercase">Opsi A</span>
                                    <input wire:model="optionA" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                    @error('optionA') <span class="text-red-500 text-xs block mt-0.5">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-textSecondary uppercase">Opsi B</span>
                                    <input wire:model="optionB" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                    @error('optionB') <span class="text-red-500 text-xs block mt-0.5">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-textSecondary uppercase">Opsi C (Opsional)</span>
                                    <input wire:model="optionC" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-textSecondary uppercase">Opsi D (Opsional)</span>
                                    <input wire:model="optionD" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                </div>
                            </div>

                            <div class="pt-2">
                                <label class="block text-xs font-bold text-emerald-800 uppercase mb-1">Kunci Jawaban Benar</label>
                                <select wire:model="correctOption" class="w-36 border border-cardBorder rounded-lg px-3 py-1.5 text-sm font-bold text-emerald-900 bg-white">
                                    <option value="A">Opsi A</option>
                                    <option value="B">Opsi B</option>
                                    <option value="C">Opsi C</option>
                                    <option value="D">Opsi D</option>
                                </select>
                            </div>
                        </div>
                    @elseif($questionType === 'MULTIPLE_RESPONSE')
                        <div class="space-y-3 bg-indigo-50/50 p-4 rounded-xl border border-indigo-200">
                            <div class="flex justify-between items-center">
                                <label class="block text-xs font-bold text-indigo-900 uppercase">Pilihan Jawaban & Kunci Berganda (Multiple Correct)</label>
                                <span class="text-[11px] text-indigo-700 font-medium">Centang semua opsi yang merupakan jawaban benar</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="bg-white p-3 rounded-xl border border-cardBorder space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-navy-900">Opsi A</span>
                                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-indigo-700">
                                            <input type="checkbox" wire:model.live="correctOptions" value="A" class="rounded text-indigo-600 focus:ring-indigo-500">
                                            Benar
                                        </label>
                                    </div>
                                    <input wire:model="optionA" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                    @error('optionA') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                                </div>

                                <div class="bg-white p-3 rounded-xl border border-cardBorder space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-navy-900">Opsi B</span>
                                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-indigo-700">
                                            <input type="checkbox" wire:model.live="correctOptions" value="B" class="rounded text-indigo-600 focus:ring-indigo-500">
                                            Benar
                                        </label>
                                    </div>
                                    <input wire:model="optionB" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                    @error('optionB') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                                </div>

                                <div class="bg-white p-3 rounded-xl border border-cardBorder space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-navy-900">Opsi C (Opsional)</span>
                                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-indigo-700">
                                            <input type="checkbox" wire:model.live="correctOptions" value="C" class="rounded text-indigo-600 focus:ring-indigo-500">
                                            Benar
                                        </label>
                                    </div>
                                    <input wire:model="optionC" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                </div>

                                <div class="bg-white p-3 rounded-xl border border-cardBorder space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-navy-900">Opsi D (Opsional)</span>
                                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-indigo-700">
                                            <input type="checkbox" wire:model.live="correctOptions" value="D" class="rounded text-indigo-600 focus:ring-indigo-500">
                                            Benar
                                        </label>
                                    </div>
                                    <input wire:model="optionD" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                </div>
                            </div>
                            @error('correctOptions') <span class="text-red-500 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                        </div>
                    @elseif($questionType === 'MATCHING')
                        <div class="space-y-3 bg-emerald-50/50 p-4 rounded-xl border border-emerald-200">
                            <div class="flex justify-between items-center">
                                <label class="block text-xs font-bold text-emerald-900 uppercase">Pasangan Menjodohkan (Premis ↔ Jawaban)</label>
                                <button type="button" wire:click="addMatchingPair" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3 py-1 rounded-lg transition shadow-sm">
                                    + Tambah Pasangan
                                </button>
                            </div>

                            <div class="space-y-2">
                                @foreach($matchingPairs as $idx => $pair)
                                    <div class="flex flex-col md:flex-row items-center gap-2 bg-white p-3 rounded-xl border border-cardBorder shadow-sm">
                                        <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 font-bold text-xs flex items-center justify-center shrink-0">
                                            #{{ $idx + 1 }}
                                        </span>
                                        <div class="flex-1 w-full">
                                            <span class="text-[10px] font-bold text-textSecondary uppercase block mb-0.5">Premis / Pertanyaan (Kiri)</span>
                                            <input wire:model="matchingPairs.{{ $idx }}.left" type="text" placeholder="Contoh: Ibu Kota Indonesia" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                        </div>
                                        <span class="text-gray-400 font-bold hidden md:inline">↔</span>
                                        <div class="flex-1 w-full">
                                            <span class="text-[10px] font-bold text-textSecondary uppercase block mb-0.5">Pasangan Jawaban (Kanan)</span>
                                            <input wire:model="matchingPairs.{{ $idx }}.right" type="text" placeholder="Contoh: Jakarta" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                        </div>
                                        @if(count($matchingPairs) > 2)
                                            <button type="button" wire:click="removeMatchingPair({{ $idx }})" class="text-red-500 hover:text-red-700 text-sm font-bold p-1 shrink-0" title="Hapus Pasangan">
                                                ✕
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            @error('matchingPairs') <span class="text-red-500 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                        </div>
                    @elseif($questionType === 'TRUE_FALSE')
                        <div class="space-y-2 bg-emerald-50/50 p-4 rounded-xl border border-emerald-200">
                            <label class="block text-xs font-bold text-emerald-900 uppercase mb-1">Kunci Jawaban Benar / Salah</label>
                            <p class="text-xs text-emerald-700 mb-2">Pilihan jawaban otomatis disediakan: <strong>BENAR (Opsi A)</strong> dan <strong>SALAH (Opsi B)</strong>.</p>
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer bg-white px-4 py-2 rounded-xl border border-cardBorder">
                                    <input type="radio" wire:model="correctOption" value="A" class="text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-xs font-bold text-navy-900">BENAR (Opsi A)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer bg-white px-4 py-2 rounded-xl border border-cardBorder">
                                    <input type="radio" wire:model="correctOption" value="B" class="text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-xs font-bold text-navy-900">SALAH (Opsi B)</span>
                                </label>
                            </div>
                        </div>
                    @elseif($questionType === 'ESSAY')
                        <div class="space-y-2 bg-amber-50/50 p-4 rounded-xl border border-amber-200">
                            <label class="block text-xs font-bold text-amber-900 uppercase mb-1">Pedoman Penskoran / Rubrik Jawaban Uraian</label>
                            <textarea wire:model="explanation" rows="3" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-amber-600 focus:outline-none" placeholder="Tuliskan pedoman penskoran atau kriteria jawaban benar untuk korektor..."></textarea>
                        </div>
                    @endif

                    <!-- Explanation/Pembahasan General -->
                    @if($questionType !== 'ESSAY')
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Pembahasan Soal (Opsional)</label>
                            <textarea wire:model="explanation" rows="2" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none" placeholder="Penjelasan / alasan kunci jawaban..."></textarea>
                        </div>
                    @endif

                    <div class="flex justify-end gap-3 pt-2 border-t border-cardBorder">
                        <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-xs font-bold text-textSecondary rounded-xl bg-gray-100">Batal</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md">
                            {{ $editingQuestionId ? 'Perbarui Soal' : 'Simpan Soal' }}
                        </button>
                    </div>
                </form>

                @if($editingQuestionId && $editingQuestionVersionId)
                    <section class="mt-5 rounded-xl border border-cardBorder bg-slate-50 p-4 space-y-3">
                        <div>
                            <h4 class="text-sm font-bold text-navy-900">Media Soal</h4>
                            <p class="mt-1 text-xs text-textSecondary">File disimpan pada storage privat tenant dan dipasang ke versi draft ini. Versi terbit tidak dapat diubah.</p>
                        </div>

                        @if($editingQuestionVersionStatus === 'DRAFT')
                        <form action="{{ route('teacher.questions.media.store', ['question' => $editingQuestionId, 'version' => $editingQuestionVersionId]) }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
                            @csrf
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-navy-900 mb-1">Pilih File</label>
                                <input type="file" name="file" required accept=".jpg,.jpeg,.png,.mp3,.mp4,.pdf" class="w-full rounded-lg border border-cardBorder bg-white px-2 py-2 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-navy-900 mb-1">Peran Media</label>
                                <select name="role" required class="w-full rounded-lg border border-cardBorder bg-white px-2 py-2 text-xs">
                                    <option value="STEM">Ilustrasi soal</option>
                                    <option value="OPTION">Opsi jawaban</option>
                                    <option value="EXPLANATION">Pembahasan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-navy-900 mb-1">Urutan</label>
                                <input type="number" name="sequence" min="0" max="10000" value="0" class="w-full rounded-lg border border-cardBorder bg-white px-2 py-2 text-xs">
                            </div>
                            <div class="md:col-span-4 flex justify-end">
                                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700">Unggah & Pasang Media</button>
                            </div>
                        </form>
                        @else
                            <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">Versi ini sudah terbit dan tidak dapat diubah. Simpan revisi soal terlebih dahulu untuk membuat versi draft sebelum memasang atau melepas media.</p>
                        @endif

                        @if($editingVersionMedia->isNotEmpty())
                            <div class="space-y-2">
                                <p class="text-xs font-bold text-navy-900">Media pada versi ini</p>
                                @foreach($editingVersionMedia as $media)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 rounded-lg border border-cardBorder bg-white px-3 py-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-xs font-semibold text-navy-900">{{ $media->original_name }}</p>
                                            <p class="text-[11px] text-textSecondary">{{ strtoupper($media->pivot->role) }} · {{ number_format($media->file_size / 1024, 1) }} KB · {{ $media->mime_type }}</p>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <a href="{{ route('teacher.questions.media.show', ['media' => $media->id]) }}" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-indigo-700 hover:underline">Lihat</a>
                                            @if($editingQuestionVersionStatus === 'DRAFT')
                                                <form action="{{ route('teacher.questions.media.detach', ['question' => $editingQuestionId, 'version' => $editingQuestionVersionId, 'media' => $media->id]) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs font-bold text-red-600 hover:underline">Lepas</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-textSecondary">Belum ada media yang dipasang pada versi ini.</p>
                        @endif
                    </section>
                @endif
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-cardBorder">
                <h3 class="text-lg font-bold text-navy-900">Konfirmasi Hapus Soal</h3>
                <p class="text-sm text-textSecondary">
                    Apakah Anda yakin ingin menghapus soal dengan kode <strong class="text-navy-900 font-mono">{{ $deletingQuestionCode }}</strong>? Tindakan ini tidak dapat dibatalkan.
                </p>
                <div class="flex justify-end gap-3 pt-2 border-t border-cardBorder">
                    <button type="button" wire:click="$set('showDeleteModal', false)" class="px-4 py-2 text-xs font-bold text-textSecondary rounded-xl bg-gray-100">Batal</button>
                    <button type="button" wire:click="deleteQuestion" class="px-4 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-md">Ya, Hapus Soal</button>
                </div>
            </div>
        </div>
    @endif
</div>
