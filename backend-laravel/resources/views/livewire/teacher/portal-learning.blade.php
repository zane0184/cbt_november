<div class="space-y-6">
    <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Guru · Kurikulum Merdeka + KBC</p>
                <h1 class="mt-1 text-2xl font-bold text-navy-900">Pembelajaran & Kurikulum</h1>
                <p class="mt-1 text-sm text-textSecondary">Konteks kurikulum dipatok per penugasan, bukan dipilih bebas tanpa lingkup akademik.</p>
            </div>
            <span class="rounded-full border border-cardBorder bg-contentBg px-3 py-1 text-xs font-semibold text-navy-800">{{ count($assignments) }} penugasan ditampilkan</span>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-cardBorder bg-white p-4">
            <p class="text-xs text-textSecondary">Versi terdaftar</p>
            <p class="mt-1 text-2xl font-bold text-navy-900">{{ $curriculum['counts']['registered_versions'] ?? 0 }}</p>
        </article>
        <article class="rounded-xl border border-cardBorder bg-white p-4">
            <p class="text-xs text-textSecondary">CP resmi</p>
            <p class="mt-1 text-2xl font-bold text-navy-900">{{ $curriculum['counts']['cp_artifact_verified'] ?? 0 }} <span class="text-sm font-medium text-textSecondary">/ {{ $curriculum['counts']['cp'] ?? 0 }}</span></p>
            <p class="mt-1 text-xs text-textSecondary">Checksum terverifikasi</p>
        </article>
        <article class="rounded-xl border border-cardBorder bg-white p-4">
            <p class="text-xs text-textSecondary">Panca Cinta</p>
            <p class="mt-1 text-2xl font-bold text-navy-900">{{ $curriculum['counts']['panca_cinta'] ?? 0 }} <span class="text-sm font-medium text-textSecondary">/ 5</span></p>
        </article>
        <article class="rounded-xl border border-cardBorder bg-white p-4">
            <p class="text-xs text-textSecondary">Dimensi Profil Lulusan</p>
            <p class="mt-1 text-2xl font-bold text-navy-900">{{ $curriculum['counts']['graduate_dimension'] ?? 0 }} <span class="text-sm font-medium text-textSecondary">/ 8</span></p>
        </article>
    </section>

    @if(($curriculum['status'] ?? '') === 'NOT_REGISTERED')
        <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">Versi kurikulum belum didaftarkan</p>
            <p class="mt-1">Hubungi Admin Madrasah untuk mendaftarkan versi resmi dan memetakan mata pelajaran lokal. Guru tidak dapat mengganti registry atau mengubah CP induk.</p>
        </section>
    @elseif(!($curriculum['official_cp_verified'] ?? false))
        <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">Konten CP resmi masih menunggu verifikasi artefak</p>
            <p class="mt-1">Struktur fase, kelas, mapel, Panca Cinta, Pembelajaran Mendalam, dan dimensi profil lulusan telah diregistrasi. CP induk belum dapat dipilih untuk authoring TP/ATP sampai isi sumber CP dan checksum diverifikasi.</p>
        </section>
    @endif

    <section class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-navy-900">Versi kurikulum yang didaftarkan Admin</h2>
        <div class="mt-3 grid gap-3 md:grid-cols-2">
            @forelse(($curriculum['versions'] ?? []) as $version)
                <div class="rounded-xl border border-cardBorder p-4">
                    <p class="font-semibold text-navy-900">{{ $version['title'] }}</p>
                    <p class="mt-1 text-xs text-textSecondary">{{ $version['version_code'] }} · Efektif {{ $version['effective_year'] }}</p>
                    <p class="mt-2 text-xs text-textSecondary">{{ $version['source_document'] }} · {{ $version['source_verification_status'] }}</p>
                </div>
            @empty
                <p class="text-sm text-textSecondary">Belum ada versi kurikulum aktif pada instalasi ini.</p>
            @endforelse
        </div>
    </section>

    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-bold text-navy-900">Konteks kurikulum per penugasan</h2>
            <p class="mt-1 text-xs text-textSecondary">CP dan TP/ATP tidak boleh tercampur lintas mapel, fase, rombel, tahun ajaran, semester, atau versi kurikulum.</p>
        </div>

        @forelse($assignments as $row)
            @php
                $assignment = $row['assignment'];
                $context = $row['curriculum_context'] ?? [];
            @endphp
            <article class="overflow-hidden rounded-2xl border border-cardBorder bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-cardBorder bg-gray-50 p-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="font-bold text-navy-900">{{ $assignment->subject?->name }} · {{ $assignment->rombel?->name }}</h3>
                        <p class="mt-1 text-xs text-textSecondary">{{ $assignment->academicYear?->name }} · {{ $assignment->semester?->name }} · {{ $row['status_label'] }}</p>
                    </div>
                    <div class="text-left md:text-right">
                        <p class="text-xs font-semibold text-navy-900">{{ $context['assignment']['version_code'] ?? 'Versi belum dipatok' }}</p>
                        <p class="text-xs text-textSecondary">{{ $context['assignment']['phase'] ?? 'Fase belum tersedia' }} · {{ $context['assignment']['grade'] ?? 'Kelas belum tersedia' }}</p>
                    </div>
                </div>

                <div class="grid gap-4 p-4 xl:grid-cols-2">
                    <div class="space-y-3">
                        <div class="rounded-xl border border-cardBorder p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-textSecondary">Pemetaan mata pelajaran</p>
                            <p class="mt-1 font-semibold text-navy-900">{{ $context['assignment']['official_subject'] ?? 'Belum dipetakan' }}</p>
                            <p class="mt-1 text-xs text-textSecondary">{{ $context['notice'] ?? 'Konteks kurikulum tersedia.' }}</p>
                            <span class="mt-2 inline-flex rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ ($context['status'] ?? '') === 'READY' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
                                {{ $context['status'] ?? 'CONTEXT_UNKNOWN' }}
                            </span>
                        </div>

                        <div class="rounded-xl border border-cardBorder p-4">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold text-navy-900">Capaian Pembelajaran (CP)</h4>
                                <span class="text-xs text-textSecondary">{{ count($context['cp_options'] ?? []) }} CP</span>
                            </div>
                            @forelse(($context['cp_options'] ?? []) as $cp)
                                <div class="mt-3 rounded-lg bg-contentBg p-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-navy-900">{{ $cp['code'] }} — {{ $cp['title'] }}</p>
                                        <span class="rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $cp['selectable'] ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
                                            {{ $cp['selectable'] ? 'Terverifikasi' : 'Menunggu verifikasi' }}
                                        </span>
                                    </div>
                                    <p class="mt-2 whitespace-pre-line text-xs leading-relaxed text-textSecondary">{{ $cp['description'] }}</p>
                                    <p class="mt-2 text-[11px] text-textSecondary">Sumber: {{ $cp['source_document'] ?? 'Belum tercatat' }} · {{ $cp['source_verification_status'] ?? 'PENDING' }}</p>
                                </div>
                            @empty
                                <p class="mt-3 rounded-lg bg-amber-50 p-3 text-xs text-amber-900">CP resmi untuk mapel dan fase ini belum tersedia/terverifikasi. Penyusunan TP dan ATP dinonaktifkan sampai CP induk yang sah tersedia.</p>
                            @endforelse

                            @if($context['official_cp_verified'] ?? false)
                                <form wire:submit.prevent="createTp('{{ $assignment->id }}')" class="mt-4 space-y-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4">
                                    <div>
                                        <h5 class="font-bold text-navy-900">Susun Tujuan Pembelajaran (TP)</h5>
                                        <p class="mt-1 text-xs text-textSecondary">TP dibuat Guru sebagai data milik tenant dan selalu terhubung ke CP induk resmi.</p>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-navy-900">CP induk</label>
                                        <select wire:model="tpForms.{{ $assignment->id }}.parent_cp_id" class="w-full rounded-lg border border-cardBorder px-3 py-2 text-sm">
                                            <option value="">Pilih CP resmi...</option>
                                            @foreach(($context['cp_options'] ?? []) as $cp)
                                                @if($cp['selectable'])
                                                    <option value="{{ $cp['id'] }}">{{ $cp['code'] }} — {{ $cp['title'] }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('tpForms.' . $assignment->id . '.parent_cp_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-navy-900">Kode TP</label>
                                            <input wire:model="tpForms.{{ $assignment->id }}.code" maxlength="100" class="w-full rounded-lg border border-cardBorder px-3 py-2 text-sm" placeholder="TP-A-01">
                                            @error('tpForms.' . $assignment->id . '.code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-navy-900">Judul TP</label>
                                            <input wire:model="tpForms.{{ $assignment->id }}.title" maxlength="255" class="w-full rounded-lg border border-cardBorder px-3 py-2 text-sm" placeholder="Judul tujuan pembelajaran">
                                            @error('tpForms.' . $assignment->id . '.title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-navy-900">Deskripsi/rumusan TP</label>
                                        <textarea wire:model="tpForms.{{ $assignment->id }}.description" rows="3" maxlength="12000" class="w-full rounded-lg border border-cardBorder px-3 py-2 text-sm" placeholder="Rumuskan tujuan pembelajaran yang terukur dan sesuai CP..."></textarea>
                                        @error('tpForms.' . $assignment->id . '.description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="grid gap-3 lg:grid-cols-2">
                                        <div>
                                            <p class="mb-2 text-xs font-bold text-navy-900">Panca Cinta (pilih minimal satu)</p>
                                            <div class="space-y-1.5">
                                                @foreach(($context['panca_cinta'] ?? []) as $node)
                                                    <label class="flex items-center gap-2 text-xs text-navy-800">
                                                        <input type="checkbox" value="{{ $node['id'] }}" wire:model="tpForms.{{ $assignment->id }}.panca_cinta" class="rounded border-cardBorder text-emerald-700 focus:ring-emerald-600">
                                                        {{ $node['title'] }}
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error('tpForms.' . $assignment->id . '.panca_cinta') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <p class="mb-2 text-xs font-bold text-navy-900">Prinsip Pembelajaran Mendalam (pilih minimal satu)</p>
                                            <div class="space-y-1.5">
                                                @foreach(($context['deep_learning_principles'] ?? []) as $node)
                                                    <label class="flex items-center gap-2 text-xs text-navy-800">
                                                        <input type="checkbox" value="{{ $node['id'] }}" wire:model="tpForms.{{ $assignment->id }}.pm_principles" class="rounded border-cardBorder text-emerald-700 focus:ring-emerald-600">
                                                        {{ $node['title'] }}
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error('tpForms.' . $assignment->id . '.pm_principles') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <p class="mb-2 text-xs font-bold text-navy-900">Kerangka Pembelajaran Mendalam (opsional)</p>
                                            <div class="space-y-1.5">
                                                @foreach(($context['deep_learning_framework'] ?? []) as $node)
                                                    <label class="flex items-center gap-2 text-xs text-navy-800">
                                                        <input type="checkbox" value="{{ $node['id'] }}" wire:model="tpForms.{{ $assignment->id }}.pm_framework" class="rounded border-cardBorder text-emerald-700 focus:ring-emerald-600">
                                                        {{ $node['title'] }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div>
                                            <p class="mb-2 text-xs font-bold text-navy-900">Dimensi Profil Lulusan (pilih minimal satu)</p>
                                            <div class="grid gap-1.5 sm:grid-cols-2">
                                                @foreach(($context['graduate_dimensions'] ?? []) as $node)
                                                    <label class="flex items-center gap-2 text-xs text-navy-800">
                                                        <input type="checkbox" value="{{ $node['id'] }}" wire:model="tpForms.{{ $assignment->id }}.graduate_dimensions" class="rounded border-cardBorder text-emerald-700 focus:ring-emerald-600">
                                                        {{ $node['title'] }}
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error('tpForms.' . $assignment->id . '.graduate_dimensions') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-800 disabled:opacity-50">Simpan TP sebagai Draft</button>
                                </form>
                            @else
                                <p class="mt-4 rounded-xl border border-dashed border-amber-300 bg-amber-50 p-3 text-xs text-amber-900">Form authoring TP/ATP terkunci sampai CP induk resmi untuk mapel dan fase ini diverifikasi.</p>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-3">
                        @php
                            $teacherOutcomes = collect($context['teacher_outcomes'] ?? []);
                            $tpOutcomes = $teacherOutcomes->where('outcome_type', 'TP')->values();
                            $atpOutcomes = $teacherOutcomes->where('outcome_type', 'ATP')->values();
                        @endphp
                        <div class="rounded-xl border border-cardBorder p-4">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold text-navy-900">Tujuan Pembelajaran & Alur Tujuan Pembelajaran</h4>
                                <span class="text-xs text-textSecondary">{{ $teacherOutcomes->count() }} item</span>
                            </div>
                            @forelse($tpOutcomes as $tp)
                                <div class="mt-3 rounded-lg bg-contentBg p-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-navy-900">TP · {{ $tp['code'] }} — {{ $tp['title'] }}</p>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[11px] text-textSecondary">v{{ $tp['version_code'] }} · {{ $tp['status'] }}</span>
                                            @if($tp['status'] === 'DRAFT')
                                                <button type="button" wire:click="publishOutcome('{{ $tp['id'] }}')" wire:loading.attr="disabled" class="rounded-md bg-emerald-700 px-2.5 py-1 text-[10px] font-bold text-white">Publikasikan</button>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="mt-1 whitespace-pre-line text-xs text-textSecondary">{{ $tp['description'] }}</p>
                                    <p class="mt-2 text-[11px] text-textSecondary">Induk CP: {{ $tp['parent_cp'] ?? '-' }}</p>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach($tp['mappings'] as $mapping)
                                            <span class="rounded-full border border-cardBorder bg-white px-2 py-0.5 text-[10px] text-navy-800">{{ $mapping['role'] }} · {{ $mapping['title'] }}</span>
                                        @endforeach
                                    </div>

                                    <form wire:submit.prevent="createAtp('{{ $assignment->id }}', '{{ $tp['id'] }}')" class="mt-3 space-y-2 rounded-lg border border-cardBorder bg-white p-3">
                                        <p class="text-xs font-bold text-navy-900">Tambah Alur Tujuan Pembelajaran (ATP)</p>
                                        <div class="grid gap-2 sm:grid-cols-3">
                                            <div>
                                                <label class="mb-1 block text-[11px] font-semibold text-navy-900">Urutan</label>
                                                <input type="number" min="1" max="999" wire:model="atpForms.{{ $tp['id'] }}.sequence_number" class="w-full rounded-lg border border-cardBorder px-2.5 py-2 text-xs" placeholder="1">
                                                @error('atpForms.' . $tp['id'] . '.sequence_number') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[11px] font-semibold text-navy-900">Kode ATP</label>
                                                <input wire:model="atpForms.{{ $tp['id'] }}.code" class="w-full rounded-lg border border-cardBorder px-2.5 py-2 text-xs" placeholder="ATP-01">
                                                @error('atpForms.' . $tp['id'] . '.code') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[11px] font-semibold text-navy-900">Judul ATP</label>
                                                <input wire:model="atpForms.{{ $tp['id'] }}.title" class="w-full rounded-lg border border-cardBorder px-2.5 py-2 text-xs" placeholder="Judul alur tujuan">
                                                @error('atpForms.' . $tp['id'] . '.title') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[11px] font-semibold text-navy-900">Deskripsi ATP</label>
                                            <textarea wire:model="atpForms.{{ $tp['id'] }}.description" rows="2" class="w-full rounded-lg border border-cardBorder px-2.5 py-2 text-xs" placeholder="Urutan pengalaman/tujuan pembelajaran..."></textarea>
                                            @error('atpForms.' . $tp['id'] . '.description') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-navy-900 px-3 py-2 text-[11px] font-bold text-white">Simpan ATP sebagai Draft</button>
                                    </form>

                                    @foreach($atpOutcomes->where('parent_tp_id', $tp['id']) as $atp)
                                        <div class="mt-3 rounded-lg border border-indigo-100 bg-indigo-50/50 p-3">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <p class="text-xs font-bold text-navy-900">ATP {{ $atp['sequence_number'] ?? '' }} · {{ $atp['code'] }} — {{ $atp['title'] }}</p>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] text-textSecondary">v{{ $atp['version_code'] }} · {{ $atp['status'] }}</span>
                                                    @if($atp['status'] === 'DRAFT')
                                                        <button type="button" wire:click="publishOutcome('{{ $atp['id'] }}')" wire:loading.attr="disabled" class="rounded-md bg-emerald-700 px-2 py-1 text-[10px] font-bold text-white">Publikasikan</button>
                                                    @endif
                                                </div>
                                            </div>
                                            <p class="mt-1 whitespace-pre-line text-xs text-textSecondary">{{ $atp['description'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                <p class="mt-3 rounded-lg bg-contentBg p-3 text-xs text-textSecondary">Belum ada TP yang dibuat untuk penugasan ini. Buat TP setelah CP induk tersedia dan terverifikasi.</p>
                            @endforelse
                        </div>


                        <div class="rounded-xl border border-cardBorder p-4">
                            <h4 class="font-semibold text-navy-900">Integrasi Panca Cinta</h4>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach(($context['panca_cinta'] ?? []) as $node)
                                    <span class="rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-800">{{ $node['title'] }}</span>
                                @endforeach
                            </div>
                            @if(!($context['panca_cinta_available'] ?? false))
                                <p class="mt-2 text-xs text-amber-800">Registry Panca Cinta belum lengkap/terverifikasi.</p>
                            @endif
                        </div>

                        <div class="rounded-xl border border-cardBorder p-4">
                            <h4 class="font-semibold text-navy-900">Pembelajaran Mendalam</h4>
                            <p class="mt-2 text-xs font-semibold text-textSecondary">Prinsip</p>
                            <div class="mt-1 flex flex-wrap gap-2">
                                @foreach(($context['deep_learning_principles'] ?? []) as $node)
                                    <span class="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs text-indigo-800">{{ $node['title'] }}</span>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs font-semibold text-textSecondary">Olah pikir, hati, rasa, raga</p>
                            <div class="mt-1 flex flex-wrap gap-2">
                                @foreach(($context['deep_learning_olah'] ?? []) as $node)
                                    <span class="rounded-full border border-cardBorder bg-contentBg px-2.5 py-1 text-xs text-navy-800">{{ $node['title'] }}</span>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs font-semibold text-textSecondary">Kerangka desain</p>
                            <div class="mt-1 flex flex-wrap gap-2">
                                @foreach(($context['deep_learning_framework'] ?? []) as $node)
                                    <span class="rounded-full border border-cardBorder bg-contentBg px-2.5 py-1 text-xs text-navy-800">{{ $node['title'] }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-xl border border-cardBorder p-4">
                            <h4 class="font-semibold text-navy-900">Delapan Dimensi Profil Lulusan</h4>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                @foreach(($context['graduate_dimensions'] ?? []) as $node)
                                    <div class="rounded-lg bg-contentBg px-3 py-2 text-xs text-navy-800">{{ $node['title'] }}</div>
                                @endforeach
                            </div>
                            @if(!($context['graduate_dimensions_available'] ?? false))
                                <p class="mt-2 text-xs text-amber-800">Registry delapan dimensi belum lengkap/terverifikasi.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-cardBorder bg-white p-8 text-center text-sm text-textSecondary">Belum ada penugasan mengajar aktif untuk akun ini.</div>
        @endforelse
    </section>
</div>
