<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Bank Soal & Modul Asesmen</h1>
            <p class="text-xs text-textSecondary mt-1">Kelola butir soal pilihan ganda, pilihan ganda kompleks, benar/salah, menjodohkan, dan uraian/esai</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/guru/bank-soal/import-docx" class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-xs font-bold text-indigo-800 transition hover:bg-indigo-100">
                <span aria-hidden="true">⇧</span> Import Soal Word (.docx)
            </a>
            @can('create', \App\Models\Question::class)
                <button wire:click="openCreateModal" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-xl transition text-xs flex items-center gap-2 shadow-sm">
                    <span>+</span> Buat Soal Baru
                </button>
            @endcan
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
                        @if($q->currentVersion)
                            @foreach($q->currentVersion->curriculumMappings->unique('multi_learning_outcome_id') as $mapping)
                                <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-200" title="CP resmi">
                                    CP: {{ $mapping->learningOutcome?->code ?? 'CP' }}
                                </span>
                            @endforeach
                            @foreach($q->currentVersion->tenantOutcomeMappings->unique('tenant_learning_outcome_id') as $mapping)
                                <span class="text-xs font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200" title="Tujuan Pembelajaran">
                                    {{ $mapping->mapping_role }}: {{ $mapping->learningOutcome?->code ?? '-' }}
                                </span>
                            @endforeach
                            @foreach($q->currentVersion->structureMappings->unique('multi_curriculum_structure_id') as $mapping)
                                <span class="text-xs font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200" title="Integrasi KBC/PM/Profil Lulusan">
                                    {{ $mapping->mapping_role }}
                                </span>
                            @endforeach
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
                    <div dir="{{ ($q->currentVersion->metadata['rich_content']['text_direction'] ?? 'auto') === 'rtl' ? 'rtl' : 'auto' }}" class="prose prose-sm max-w-none text-navy-900 bg-contentBg p-4 rounded-xl border border-cardBorder">
                        {!! nl2br(e($q->currentVersion->stem)) !!}
                    </div>

                    @if(($q->currentVersion->metadata['rich_content']['requires_manual_review'] ?? false) === true)
                        <div class="space-y-2 rounded-xl border border-amber-300 bg-amber-50 p-3">
                            <p class="text-xs leading-5 text-amber-900">Konten DOCX memuat rumus atau tabel kompleks. Periksa pratinjau sumber dan tampilan soal. Penerbitan dikunci sampai Guru mencatat peninjauan.</p>
                            <button type="button" wire:click="acknowledgeImportedRichContentReview('{{ $q->id }}')" wire:loading.attr="disabled" class="rounded-lg bg-amber-700 px-3 py-2 text-xs font-bold text-white hover:bg-amber-800 disabled:opacity-50">Saya sudah memeriksa konten DOCX</button>
                        </div>
                    @elseif(!empty($q->currentVersion->metadata['rich_content']['reviewed_at']))
                        <p class="rounded-lg border border-emerald-200 bg-emerald-50 p-2 text-xs text-emerald-800">Konten DOCX kompleks telah ditinjau Guru pada {{ $q->currentVersion->metadata['rich_content']['reviewed_at'] }}.</p>
                    @endif

                    @if(!empty($q->currentVersion->metadata['rich_content']['preview_runs']))
                        <details class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <summary class="cursor-pointer text-xs font-bold text-slate-700">Pratinjau format sumber DOCX</summary>
                            <div class="mt-3 space-y-2 rounded-lg bg-white p-3">
                                @foreach($q->currentVersion->metadata['rich_content']['preview_runs'] as $paragraph)
                                    <p dir="{{ in_array($paragraph['direction'] ?? 'auto', ['rtl', 'ltr'], true) ? $paragraph['direction'] : 'auto' }}" class="whitespace-pre-wrap text-sm leading-7 text-slate-800">
                                        @foreach($paragraph['runs'] ?? [] as $run)
                                            <span class="{{ (!empty($run['bold']) ? 'font-bold ' : '') . (!empty($run['italic']) ? 'italic ' : '') . (!empty($run['underline']) ? 'underline' : '') }}">{{ $run['text'] ?? '' }}</span>
                                        @endforeach
                                    </p>
                                @endforeach
                            </div>
                        </details>
                    @endif

                    @if(!empty($q->currentVersion->metadata['rich_content']['table_structure']))
                        <details class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-3">
                            <summary class="cursor-pointer text-xs font-bold text-indigo-800">Struktur tabel Word bersarang</summary>
                            <div class="mt-3 space-y-3 overflow-x-auto rounded-lg bg-white p-3">
                                @foreach(collect($q->currentVersion->metadata['rich_content']['table_structure'])->groupBy('table_index') as $tableIndex => $tableEntries)
                                    <div>
                                        <p class="mb-1 text-[11px] font-bold text-slate-500">Tabel {{ $tableIndex }}</p>
                                        @include('components.question-table-structure', ['rows' => $tableEntries->sortBy('row_index')->pluck('row')->all()])
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif

                    @if($q->currentVersion->media->isNotEmpty())
                        <div class="flex flex-wrap gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                            @foreach($q->currentVersion->media as $media)
                                @if(in_array($media->mime_type, ['image/jpeg', 'image/png'], true))
                                    <img src="{{ url('/api/v1/media/' . $media->id) }}" alt="Gambar soal" loading="lazy" class="max-h-56 max-w-full rounded-lg border border-slate-200 bg-white object-contain">
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if($q->currentVersion->explanation)
                        <div class="text-xs bg-amber-50/80 border border-amber-200 p-3 rounded-xl text-amber-900">
                            <span class="font-bold block mb-0.5">Pedoman Penskoran / Rubrik:</span>
                            {!! nl2br(e($q->currentVersion->explanation)) !!}
                        </div>
                    @endif

                    @if(!empty($q->currentVersion->metadata['shared_stimulus']['content']))
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs text-slate-700">
                            <span class="mb-1 block font-bold text-blue-800">Stimulus bersama · {{ $q->currentVersion->metadata['shared_stimulus']['source_group_key'] ?? 'Kelompok' }}</span>
                            {!! nl2br(e($q->currentVersion->metadata['shared_stimulus']['content'])) !!}
                        </div>
                    @endif

                    @if(($q->currentVersion->question_type ?? '') === 'TRUE_FALSE_GROUP')
                        <div class="space-y-2 rounded-xl border border-indigo-200 bg-indigo-50/50 p-3">
                            <span class="block text-xs font-bold text-indigo-900">Benar / Salah · 3 Pernyataan</span>
                            @foreach(($q->currentVersion->metadata['statement_items'] ?? []) as $statement)
                                <div class="flex items-start justify-between gap-3 text-xs">
                                    <span class="text-slate-700">{{ $statement['text'] ?? '' }}</span>
                                    <strong class="shrink-0 text-indigo-800">{{ is_bool($statement['is_true'] ?? null) ? ($statement['is_true'] ? 'BENAR' : 'SALAH') : 'BELUM DIISI' }}</strong>
                                </div>
                            @endforeach
                            <p class="text-[10px] text-indigo-700">Skor proporsional: sepertiga poin untuk setiap pernyataan benar.</p>
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
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 p-3 backdrop-blur-sm sm:items-center sm:p-4">
            <div class="my-0 flex max-h-[calc(100vh-1.5rem)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-cardBorder bg-white shadow-2xl sm:my-4 sm:max-h-[calc(100vh-2rem)]">
                <div class="flex shrink-0 items-center justify-between border-b border-cardBorder bg-white px-5 py-4 sm:px-7">
                    <h3 class="text-lg font-bold text-navy-900">
                        {{ $editingQuestionId ? 'Edit Soal' : 'Buat Soal Baru' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 font-bold text-lg">✕</button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-5 sm:px-7">
                <form wire:submit.prevent="saveQuestion" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Kode Soal</label>
                            <input wire:model="questionCode" type="text" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none" placeholder="Contoh: SOAL-AKD-01">
                            @error('questionCode') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Penugasan Mengajar</label>
                            <select wire:model.live="selectedAssignmentId" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                                <option value="">Pilih penugasan...</option>
                                @foreach($assignmentRows as $row)
                                    <option value="{{ $row['assignment']->id }}">{{ $row['assignment']->subject?->name }} · {{ $row['assignment']->rombel?->name }} · {{ $row['assignment']->semester?->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedAssignmentId') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                            <p class="mt-1 text-[11px] text-textSecondary">Mata pelajaran: {{ $selectedAssignment?->subject?->name ?? 'Pilih penugasan terlebih dahulu' }}</p>
                            @error('selectedSubjectId') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Canonical curriculum mapping -->
                    <div class="space-y-4 rounded-xl border border-cardBorder bg-gray-50 p-4">
                        <div>
                            <h4 class="text-xs font-bold uppercase text-navy-900">Pemetaan kurikulum per versi soal</h4>
                            <p class="mt-1 text-[11px] text-textSecondary">Pemetaan CP dan TP/ATP bersifat opsional untuk soal umum. Jika pemetaan dipilih, sistem tetap mewajibkan CP resmi terverifikasi, TP/ATP terpublikasi, dan struktur kurikulum resmi yang lengkap.</p>
                        </div>
                        @if($selectedAssignment)
                            <div class="rounded-lg border border-cardBorder bg-white p-3">
                                <p class="text-xs font-semibold text-navy-900">{{ $curriculumContext['assignment']['version_title'] ?? 'Versi kurikulum' }}</p>
                                <p class="mt-1 text-[11px] text-textSecondary">{{ $curriculumContext['assignment']['phase'] ?? '-' }} · {{ $curriculumContext['assignment']['grade'] ?? '-' }} · {{ $curriculumContext['assignment']['official_subject'] ?? 'Mapel belum dipetakan' }}</p>
                            </div>
                            @if(auth()->user()->hasPermission('curriculum.manage'))
                            <div>
                                <p class="mb-2 text-xs font-bold text-indigo-900">CP resmi (pilih minimal satu jika ingin memetakan)</p>
                                <div class="grid gap-2 md:grid-cols-2">
                                    @forelse($cpOptions as $cp)
                                        <label class="flex items-start gap-2 rounded-lg border border-indigo-100 bg-white p-3 text-xs">
                                            <input type="checkbox" wire:model="selectedCpIds" value="{{ $cp['id'] }}" class="mt-0.5 rounded border-cardBorder text-indigo-700 focus:ring-indigo-600">
                                            <span><strong>{{ $cp['code'] }} — {{ $cp['title'] }}</strong><span class="mt-1 block text-[11px] text-textSecondary">{{ $cp['source_document'] ?? 'Sumber resmi' }}</span></span>
                                        </label>
                                    @empty
                                        <p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">Belum ada CP resmi yang artefak dan checksum-nya terverifikasi untuk penugasan ini. Pemetaan soal belum dapat disimpan.</p>
                                    @endforelse
                                </div>
                                @error('selectedCpIds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <p class="mb-2 text-xs font-bold text-amber-900">TP/ATP terpublikasi (pilih minimal satu)</p>
                                <div class="grid gap-2 md:grid-cols-2">
                                    @forelse($outcomes as $outcome)
                                        <label class="flex items-start gap-2 rounded-lg border border-amber-100 bg-white p-3 text-xs">
                                            <input type="checkbox" wire:model="selectedOutcomeIds" value="{{ $outcome['id'] }}" class="mt-0.5 rounded border-cardBorder text-amber-700 focus:ring-amber-600">
                                            <span><strong>{{ $outcome['outcome_type'] }} · {{ $outcome['code'] }} — {{ $outcome['title'] }}</strong><span class="mt-1 block text-[11px] text-textSecondary">CP induk: {{ $outcome['parent_cp'] ?? '-' }}</span></span>
                                        </label>
                                    @empty
                                        <p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">Belum ada TP/ATP terpublikasi pada penugasan ini. Buat dan publikasikan TP/ATP terlebih dahulu di menu Pembelajaran & Kurikulum.</p>
                                    @endforelse
                                </div>
                                @error('selectedOutcomeIds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            @else
                                <p class="rounded-lg border border-sky-200 bg-sky-50 p-3 text-xs text-sky-900">Soal dapat disimpan dan diterbitkan sebagai soal umum tanpa CP/TP/ATP. Pemetaan kurikulum hanya tersedia bagi akun yang memiliki kewenangan pengelolaan kurikulum.</p>
                            @endif
                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-bold text-navy-900">Materi (opsional)</label>
                                    <select wire:model.live="selectedMaterialId" class="w-full rounded-lg border border-cardBorder bg-white px-3 py-2 text-xs">
                                        <option value="">Tanpa materi</option>
                                        @foreach($materials as $material)
                                            <option value="{{ $material->id }}">{{ $material->material_code }} — {{ $material->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedMaterialId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-bold text-navy-900">Topik (opsional)</label>
                                    <select wire:model="selectedMaterialTopicId" class="w-full rounded-lg border border-cardBorder bg-white px-3 py-2 text-xs" {{ $selectedMaterialId === '' ? 'disabled' : '' }}>
                                        <option value="">Tanpa topik</option>
                                        @foreach($topics as $topic)
                                            <option value="{{ $topic->id }}">{{ $topic->code }} — {{ $topic->title }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedMaterialTopicId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @else
                            <p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">Pilih penugasan Guru untuk menampilkan CP, TP/ATP, materi, dan topik yang sah.</p>
                        @endif
                        @error('curriculumMapping') <p class="text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Tipe Soal</label>
                            <select wire:model.live="questionType" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                                <option value="MULTIPLE_CHOICE">Pilihan Ganda (MC)</option>
                                <option value="MULTIPLE_RESPONSE">Pilihan Ganda Kompleks (PG Kompleks)</option>
                                <option value="TRUE_FALSE">Benar / Salah</option>
                                <option value="TRUE_FALSE_GROUP">Benar / Salah — 3 Pernyataan</option>
                                <option value="MATCHING">Menjodohkan (Matching)</option>
                                <option value="ESSAY">Uraian / Esai</option>
                                @if($questionType === 'SHORT_ANSWER')
                                    <option value="SHORT_ANSWER" disabled>Jawaban Singkat — dinonaktifkan sampai scoring siap</option>
                                @endif
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Poin Default</label>
                            <input wire:model="defaultPoints" type="number" min="0.5" step="0.5" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            @if((float) $defaultPoints <= 0)
                                <span class="mt-1 block text-xs font-semibold text-amber-700">Bobot belum ditetapkan. Isi sebelum menyimpan atau menerbitkan.</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Isi / Pertanyaan (Stem) — Mendukung Teks Arab & Harakat</label>
                        <textarea dir="auto" wire:model="stem" rows="3" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none font-sans" placeholder="Tuliskan butir soal di sini..."></textarea>
                        @error('stem') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Dynamic Options Form -->
                    @if($questionType === 'MULTIPLE_CHOICE')
                        <div class="space-y-2 bg-contentBg p-4 rounded-xl border border-cardBorder">
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Pilihan Jawaban & Kunci Single</label>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <div>
                                    <span class="text-[10px] font-bold text-textSecondary uppercase">Opsi A</span>
                                    <input dir="auto" wire:model="optionA" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                    @error('optionA') <span class="text-red-500 text-xs block mt-0.5">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-textSecondary uppercase">Opsi B</span>
                                    <input dir="auto" wire:model="optionB" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                    @error('optionB') <span class="text-red-500 text-xs block mt-0.5">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-textSecondary uppercase">Opsi C (Opsional)</span>
                                    <input dir="auto" wire:model="optionC" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-textSecondary uppercase">Opsi D (Opsional)</span>
                                    <input dir="auto" wire:model="optionD" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                </div>
                            </div>

                            <div class="pt-2">
                                <label class="block text-xs font-bold text-emerald-800 uppercase mb-1">Kunci Jawaban Benar</label>
                                <select wire:model="correctOption" class="w-36 border border-cardBorder rounded-lg px-3 py-1.5 text-sm font-bold text-emerald-900 bg-white">
                                    <option value="">Pilih kunci jawaban...</option>
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
                                    <input dir="auto" wire:model="optionA" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
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
                                    <input dir="auto" wire:model="optionB" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
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
                                    <input dir="auto" wire:model="optionC" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                </div>

                                <div class="bg-white p-3 rounded-xl border border-cardBorder space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-navy-900">Opsi D (Opsional)</span>
                                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-indigo-700">
                                            <input type="checkbox" wire:model.live="correctOptions" value="D" class="rounded text-indigo-600 focus:ring-indigo-500">
                                            Benar
                                        </label>
                                    </div>
                                    <input dir="auto" wire:model="optionD" type="text" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
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
                                            <input dir="auto" wire:model="matchingPairs.{{ $idx }}.left" type="text" placeholder="Contoh: Ibu Kota Indonesia" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
                                        </div>
                                        <span class="text-gray-400 font-bold hidden md:inline">↔</span>
                                        <div class="flex-1 w-full">
                                            <span class="text-[10px] font-bold text-textSecondary uppercase block mb-0.5">Pasangan Jawaban (Kanan)</span>
                                            <input dir="auto" wire:model="matchingPairs.{{ $idx }}.right" type="text" placeholder="Contoh: Jakarta" class="w-full border border-cardBorder rounded-lg px-3 py-1.5 text-sm">
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
                    @elseif($questionType === 'TRUE_FALSE_GROUP')
                        <div class="space-y-3 rounded-xl border border-indigo-200 bg-indigo-50/50 p-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-indigo-900">Tiga Pernyataan Benar / Salah</label>
                                <p class="mt-1 text-xs leading-5 text-indigo-700">Setiap pernyataan memiliki kunci Benar atau Salah. Nilai diberikan proporsional per pernyataan (1/3 dari poin maksimal).</p>
                            </div>
                            @foreach($groupStatements as $idx => $statement)
                                <div class="grid gap-3 rounded-xl border border-indigo-100 bg-white p-3 md:grid-cols-[1fr_auto] md:items-center">
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Pernyataan {{ $idx + 1 }}</label>
                                        <input type="hidden" wire:model="groupStatements.{{ $idx }}.id">
                                        <textarea dir="auto" wire:model="groupStatements.{{ $idx }}.text" rows="2" class="w-full rounded-lg border border-cardBorder px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none"></textarea>
                                        @error('groupStatements.'.$idx.'.text') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Kunci jawaban</label>
                                        <select wire:model="groupStatements.{{ $idx }}.is_true" class="rounded-lg border border-cardBorder bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                                            <option value="">Pilih Benar / Salah</option>
                                            <option value="1">Benar</option>
                                            <option value="0">Salah</option>
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                            @error('groupStatements') <span class="block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                        </div>
                    @elseif($questionType === 'ESSAY')
                        <div class="space-y-2 bg-amber-50/50 p-4 rounded-xl border border-amber-200">
                            <label class="block text-xs font-bold text-amber-900 uppercase mb-1">Pedoman Penskoran / Rubrik Jawaban Uraian</label>
                            <textarea dir="auto" wire:model="explanation" rows="3" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-amber-600 focus:outline-none" placeholder="Tuliskan pedoman penskoran atau kriteria jawaban benar untuk korektor..."></textarea>
                        </div>
                    @endif

                    <!-- Explanation/Pembahasan General -->
                    @if($questionType !== 'ESSAY')
                        <div>
                            <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Pembahasan Soal (Opsional)</label>
                            <textarea dir="auto" wire:model="explanation" rows="2" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none" placeholder="Penjelasan / alasan kunci jawaban..."></textarea>
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
