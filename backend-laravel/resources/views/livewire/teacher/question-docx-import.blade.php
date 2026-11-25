<div class="min-h-screen space-y-6 bg-slate-50/70 p-1">
    <div class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">
                <a href="/guru/bank-soal" class="hover:text-indigo-800">Bank Soal</a>
                <span class="text-slate-300">/</span>
                <span>Import Microsoft Word</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 md:text-3xl">Import Soal dari Word</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Unggah dokumen .docx, periksa hasil pembacaan, pilih pemetaan kurikulum, lalu simpan soal sebagai draf. Tidak ada soal yang langsung diterbitkan.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="downloadTemplate" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-bold text-indigo-800 transition hover:bg-indigo-100">
                <span aria-hidden="true">↓</span> Unduh Template Word
            </button>
            <a href="/guru/bank-soal" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke Bank Soal</a>
        </div>
    </div>

    @if (session()->has('message'))
        <div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">{{ session('message') }}</div>
    @endif

    @error('docx')
        <div role="alert" class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">{{ $message }}</div>
    @enderror

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.85fr)]">
        <section class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <div class="mb-1 text-xs font-bold uppercase tracking-wider text-indigo-600">Langkah 1</div>
                <h2 class="text-lg font-bold text-slate-900">Pilih dokumen dan penugasan</h2>
                <p class="mt-1 text-sm text-slate-500">File harus berupa Microsoft Word .docx, maksimal 10 MB.</p>
            </div>

            <div>
                <label for="docx-assignment" class="mb-2 block text-sm font-semibold text-slate-700">Penugasan mengajar</label>
                <select id="docx-assignment" wire:model.live="selectedAssignmentId" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Pilih penugasan aktif</option>
                    @foreach($assignmentRows as $row)
                        <option value="{{ $row['assignment']->id }}">{{ $row['assignment']->subject?->name }} · {{ $row['assignment']->rombel?->name }} · {{ $row['assignment']->semester?->name }}</option>
                    @endforeach
                </select>
                @error('selectedAssignmentId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="docx-file" class="mb-2 block text-sm font-semibold text-slate-700">Dokumen soal (.docx)</label>
                <div class="rounded-2xl border-2 border-dashed border-indigo-200 bg-indigo-50/40 p-5 transition hover:border-indigo-400">
                    <input id="docx-file" type="file" wire:model="file" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="block w-full text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-white hover:file:bg-indigo-700">
                    <p class="mt-3 text-xs leading-5 text-slate-500">Dokumen boleh berisi soal dan kunci jawaban saja. Kisi-kisi, identitas penyusun, dan halaman administrasi tidak wajib.</p>
                </div>
                @error('file') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                <div wire:loading wire:target="file" class="mt-2 text-xs font-semibold text-indigo-700">Mengunggah dokumen…</div>
            </div>

            <button type="button" wire:click="preview" wire:loading.attr="disabled" class="w-full rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-60">
                Buat Preview & Validasi Dokumen
            </button>
        </section>

        <aside class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <div class="mb-1 text-xs font-bold uppercase tracking-wider text-indigo-600">Langkah 2</div>
                <h2 class="text-lg font-bold text-slate-900">Pemetaan kurikulum</h2>
                <p class="mt-1 text-sm leading-5 text-slate-500">Setiap versi soal wajib memiliki CP resmi dan TP/ATP terpublikasi pada penugasan yang dipilih.</p>
            </div>

            @if($selectedAssignment)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-sm font-bold text-slate-800">{{ $curriculumContext['assignment']['version_title'] ?? 'Versi kurikulum' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $curriculumContext['assignment']['phase'] ?? '-' }} · {{ $curriculumContext['assignment']['grade'] ?? '-' }} · {{ $curriculumContext['assignment']['official_subject'] ?? $selectedAssignment->subject?->name }}</p>
                </div>
                <div>
                    <h3 class="mb-2 text-sm font-bold text-slate-800">CP resmi</h3>
                    <div class="max-h-48 space-y-2 overflow-y-auto pr-1">
                        @forelse($cpOptions as $cp)
                            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3 text-xs transition hover:border-indigo-300 hover:bg-indigo-50/40">
                                <input type="checkbox" wire:model="selectedCpIds" value="{{ $cp['id'] }}" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span><strong class="text-slate-800">{{ $cp['code'] }} — {{ $cp['title'] }}</strong><span class="mt-1 block text-slate-500">{{ $cp['source_document'] ?? 'Sumber resmi' }}</span></span>
                            </label>
                        @empty
                            <p class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">Belum tersedia CP resmi dengan checksum terverifikasi untuk penugasan ini.</p>
                        @endforelse
                    </div>
                    @error('selectedCpIds') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <h3 class="mb-2 text-sm font-bold text-slate-800">TP/ATP terpublikasi</h3>
                    <div class="max-h-48 space-y-2 overflow-y-auto pr-1">
                        @forelse($outcomes as $outcome)
                            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3 text-xs transition hover:border-emerald-300 hover:bg-emerald-50/40">
                                <input type="checkbox" wire:model="selectedOutcomeIds" value="{{ $outcome['id'] }}" class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span><strong class="text-slate-800">{{ $outcome['outcome_type'] }} · {{ $outcome['code'] }} — {{ $outcome['title'] }}</strong><span class="mt-1 block text-slate-500">CP induk: {{ $outcome['parent_cp'] ?? '-' }}</span></span>
                            </label>
                        @empty
                            <p class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">Belum tersedia TP/ATP terpublikasi untuk penugasan ini.</p>
                        @endforelse
                    </div>
                    @error('selectedOutcomeIds') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            @else
                <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Pilih penugasan aktif terlebih dahulu. Import tidak dapat dilakukan tanpa penugasan Guru.</p>
            @endif
        </aside>
    </div>

    @if($hasPreview)
        <section class="space-y-5">
            <div class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="mb-1 text-xs font-bold uppercase tracking-wider text-indigo-600">Langkah 3</div>
                    <h2 class="text-xl font-extrabold text-slate-900">Preview hasil pembacaan</h2>
                    <p class="mt-1 text-sm text-slate-500">Periksa semua soal dan kunci. Kamu dapat mengoreksi teks, opsi, dan kunci sebelum disimpan.</p>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <div class="rounded-xl bg-slate-50 px-4 py-3"><div class="text-[11px] font-semibold uppercase text-slate-500">Total</div><div class="text-2xl font-extrabold text-slate-900">{{ $summary['total'] ?? 0 }}</div></div>
                    <div class="rounded-xl bg-emerald-50 px-4 py-3"><div class="text-[11px] font-semibold uppercase text-emerald-700">Valid awal</div><div class="text-2xl font-extrabold text-emerald-800">{{ $summary['valid'] ?? 0 }}</div></div>
                    <div class="rounded-xl bg-rose-50 px-4 py-3"><div class="text-[11px] font-semibold uppercase text-rose-700">Perlu cek</div><div class="text-2xl font-extrabold text-rose-800">{{ $summary['invalid'] ?? 0 }}</div></div>
                    <div class="rounded-xl bg-slate-100 px-4 py-3"><div class="text-[11px] font-semibold uppercase text-slate-600">Dikecualikan</div><div class="text-2xl font-extrabold text-slate-800">{{ $summary['excluded'] ?? 0 }}</div></div>
                </div>
            </div>

            @foreach($questions as $index => $question)
                <article wire:key="docx-question-{{ $index }}" class="space-y-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-sm font-extrabold text-indigo-700">{{ $index + 1 }}</span>
                            <div>
                                <h3 class="font-bold text-slate-900">Nomor sumber {{ $question['source_number'] }}</h3>
                                <select wire:model="questions.{{ $index }}.question_type" aria-label="Jenis soal nomor sumber {{ $question['source_number'] }}" class="mt-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-700">
                                    <option value="MULTIPLE_CHOICE">Pilihan Ganda (PG)</option>
                                    <option value="MULTIPLE_RESPONSE">Pilihan Ganda Kompleks (PGK)</option>
                                    <option value="TRUE_FALSE">Benar/Salah tunggal</option>
                                    <option value="TRUE_FALSE_GROUP">Benar/Salah 3 pernyataan</option>
                                    <option value="MATCHING" disabled>Menjodohkan — belum diaktifkan</option>
                                    <option value="SHORT_ANSWER" disabled>Isian singkat — belum diaktifkan</option>
                                    <option value="ESSAY">Uraian / Esai (penilaian manual)</option>
                                </select>
                            </div>
                        </div>
                        <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">
                            <input type="checkbox" wire:model="questions.{{ $index }}.included" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Sertakan dalam import
                        </label>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Teks soal</label>
                        <textarea wire:model="questions.{{ $index }}.stem" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm leading-6 text-slate-800 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></textarea>
                    </div>

                    @if(!empty($question['stimulus']['content']))
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <div class="mb-1 text-xs font-bold uppercase text-blue-700">Stimulus bersama · {{ $question['stimulus']['id'] }}</div>
                            <textarea wire:model="questions.{{ $index }}.stimulus.content" rows="3" class="w-full rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm leading-6 text-slate-700 focus:border-blue-500 focus:outline-none">{{ $question['stimulus']['content'] }}</textarea>
                        </div>
                    @endif

                    @if(in_array($question['question_type'], ['MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE', 'TRUE_FALSE'], true))
                        <div class="space-y-2">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Opsi dan kunci jawaban</div>
                            @foreach($question['options'] as $optionIndex => $option)
                                <div class="grid gap-2 rounded-xl border border-slate-200 p-3 sm:grid-cols-[auto_1fr_auto] sm:items-center">
                                    <span class="text-sm font-extrabold text-slate-600">{{ $option['option_key'] }}.</span>
                                    <input type="text" wire:model="questions.{{ $index }}.options.{{ $optionIndex }}.content" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none">
                                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-700"><input type="checkbox" wire:model="questions.{{ $index }}.options.{{ $optionIndex }}.is_correct" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Kunci benar</label>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($question['question_type'] === 'ESSAY')
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-800">Soal uraian akan disimpan sebagai DRAFT dengan penilaian manual oleh Guru. Sistem tidak akan membuat kunci otomatis.</div>
                    @endif

                    @if($question['question_type'] === 'TRUE_FALSE_GROUP')
                        <div class="space-y-3">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Tiga pernyataan dan kunci Benar/Salah</div>
                            @foreach($question['statements'] as $statementIndex => $statement)
                                <div class="grid gap-3 rounded-xl border border-slate-200 p-3 md:grid-cols-[1fr_auto] md:items-center">
                                    <div>
                                        <div class="mb-1 text-xs font-bold text-slate-500">Pernyataan {{ $statementIndex + 1 }}</div>
                                        <input type="text" wire:model="questions.{{ $index }}.statements.{{ $statementIndex }}.text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none">
                                    </div>
                                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700"><input type="checkbox" wire:model="questions.{{ $index }}.statements.{{ $statementIndex }}.is_true" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Kunci: Benar</label>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($question['errors']))
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3">
                            <p class="mb-1 text-xs font-bold text-amber-900">Perlu ditinjau dari hasil pembacaan awal</p>
                            <ul class="list-disc space-y-1 pl-5 text-xs text-amber-800">@foreach($question['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif
                    @if(!empty($question['warnings']))
                        <div class="rounded-xl border border-sky-200 bg-sky-50 p-3 text-xs text-sky-800">{{ implode(' ', $question['warnings']) }}</div>
                    @endif
                    <div class="text-[11px] text-slate-400">Sumber: {{ $question['source_locator']['kind'] ?? 'dokumen' }} · blok {{ $question['source_locator']['sequence'] ?? '-' }}</div>
                </article>
            @endforeach

            @if(count($excludedContent) > 0)
                <details class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <summary class="cursor-pointer text-sm font-bold text-slate-700">Konten yang tidak diimpor ({{ count($excludedContent) }})</summary>
                    <div class="mt-3 space-y-2">
                        @foreach($excludedContent as $excluded)
                            <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600">{{ $excluded['text'] }} <span class="text-slate-400">— {{ $excluded['reason'] }}</span></div>
                        @endforeach
                    </div>
                </details>
            @endif

            <div class="flex flex-col gap-3 rounded-3xl border border-indigo-100 bg-indigo-50/60 p-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="font-bold text-slate-900">Langkah 4 · Simpan sebagai draf Bank Soal</p>
                    <p class="mt-1 text-xs leading-5 text-slate-600">Validasi akhir akan dijalankan kembali. Soal yang disimpan tetap DRAFT; penerbitan dilakukan terpisah oleh Guru.</p>
                </div>
                <button type="button" wire:click="importDrafts" wire:loading.attr="disabled" class="rounded-xl bg-emerald-600 px-6 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-60">
                    Simpan Soal Valid sebagai Draf
                </button>
            </div>
        </section>
    @endif
</div>
