<div class="space-y-6">
    <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Guru · Materi & Topik</p>
        <h1 class="mt-1 text-2xl font-bold text-navy-900">Materi Pembelajaran</h1>
        <p class="mt-1 text-sm text-textSecondary">Materi dan topik dipatok ke penugasan, mapel, fase/kelas, periode akademik, versi kurikulum, serta TP/ATP yang dipublikasikan.</p>
    </section>

    @if(!$assignment)
        <section class="rounded-xl border border-amber-300 bg-amber-50 p-5 text-sm text-amber-900">
            <p class="font-semibold">Belum ada penugasan mengajar aktif</p>
            <p class="mt-1">Materi hanya dapat dibuat dari penugasan Guru yang aktif dan memiliki konteks kurikulum yang sah. Hubungi Admin Madrasah jika penugasan belum tersedia.</p>
        </section>
    @else
        <section class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-navy-900">Pilih penugasan</label>
                    <select wire:model.live="selectedAssignmentId" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                        @foreach($assignmentRows as $row)
                            <option value="{{ $row['assignment']->id }}">{{ $row['assignment']->subject?->name }} · {{ $row['assignment']->rombel?->name }} · {{ $row['assignment']->semester?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rounded-xl bg-contentBg p-3">
                    <p class="text-xs font-bold text-navy-900">Konteks kurikulum</p>
                    <p class="mt-1 text-sm font-semibold text-navy-900">{{ $context['assignment']['version_title'] ?? 'Versi belum dipatok' }}</p>
                    <p class="text-xs text-textSecondary">{{ $context['assignment']['phase'] ?? 'Fase belum tersedia' }} · {{ $context['assignment']['grade'] ?? 'Kelas belum tersedia' }} · {{ $context['assignment']['official_subject'] ?? 'Mapel resmi belum dipetakan' }}</p>
                    <span class="mt-2 inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold {{ ($context['status'] ?? '') === 'READY' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">{{ $context['status'] ?? 'CONTEXT_UNKNOWN' }}</span>
                </div>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-navy-900">Buat materi</h2>
                <p class="mt-1 text-xs text-textSecondary">Materi baru disimpan sebagai draft. CP induk tidak disalin atau diubah; materi dapat dikaitkan ke TP/ATP yang sudah dipublikasikan.</p>
                <form wire:submit.prevent="createMaterial" class="mt-4 space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-navy-900">Kode materi</label>
                        <input wire:model="materialCode" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm" placeholder="MAT-01">
                        @error('materialCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-navy-900">Nama materi</label>
                        <input wire:model="materialName" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm" placeholder="Nama materi pembelajaran">
                        @error('materialName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-navy-900">Deskripsi</label>
                        <textarea wire:model="materialDescription" rows="3" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm" placeholder="Ringkasan ruang lingkup materi..."></textarea>
                        @error('materialDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-navy-900">Kaitkan dengan TP/ATP (opsional)</label>
                        <select wire:model="selectedMaterialOutcomeId" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                            <option value="">Tanpa pemetaan TP/ATP</option>
                            @foreach($outcomes as $outcome)
                                <option value="{{ $outcome->id }}">{{ $outcome->outcome_type }} · {{ $outcome->code }} — {{ $outcome->title }}</option>
                            @endforeach
                        </select>
                        @error('selectedMaterialOutcomeId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-800 disabled:opacity-50">Simpan materi sebagai Draft</button>
                </form>
            </section>

            <section class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-navy-900">Buat topik</h2>
                <p class="mt-1 text-xs text-textSecondary">Topik merupakan bagian dari materi dan mewarisi versi kurikulum serta penugasan materi induknya.</p>
                <form wire:submit.prevent="createTopic" class="mt-4 space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-navy-900">Materi induk</label>
                        <select wire:model.live="selectedMaterialId" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                            <option value="">Pilih materi...</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}">{{ $material->material_code }} — {{ $material->name }} ({{ $material->status }})</option>
                            @endforeach
                        </select>
                        @error('selectedMaterialId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-navy-900">Topik induk (opsional)</label>
                        <select wire:model="selectedParentTopicId" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                            <option value="">Topik tingkat utama</option>
                            @foreach(($materials->firstWhere('id', $selectedMaterialId)?->topics ?? collect()) as $topic)
                                <option value="{{ $topic->id }}">{{ $topic->code }} — {{ $topic->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-navy-900">Kode topik</label>
                            <input wire:model="topicCode" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm" placeholder="TOP-01">
                            @error('topicCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-navy-900">Nama topik</label>
                            <input wire:model="topicTitle" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm" placeholder="Nama topik">
                            @error('topicTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-navy-900">Deskripsi topik</label>
                        <textarea wire:model="topicDescription" rows="2" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm" placeholder="Uraian topik..."></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-navy-900">Kaitkan dengan TP/ATP (opsional)</label>
                        <select wire:model="selectedTopicOutcomeId" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                            <option value="">Tanpa pemetaan TP/ATP</option>
                            @foreach($outcomes as $outcome)
                                <option value="{{ $outcome->id }}">{{ $outcome->outcome_type }} · {{ $outcome->code }} — {{ $outcome->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-navy-900 px-4 py-2.5 text-xs font-bold text-white hover:bg-navy-800 disabled:opacity-50">Simpan topik sebagai Draft</button>
                </form>
            </section>
        </div>

        <section class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-navy-900">Materi dan topik pada penugasan ini</h2>
            <div class="mt-4 space-y-3">
                @forelse($materials as $material)
                    <article class="rounded-xl border border-cardBorder p-4">
                        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="font-bold text-navy-900">{{ $material->material_code }} · {{ $material->name }}</p>
                                <p class="mt-1 text-xs text-textSecondary">{{ $material->description }}</p>
                                <p class="mt-2 text-xs text-textSecondary">Versi {{ $material->version_code }} · TP/ATP: {{ $material->learningOutcome?->code ?? 'Belum dipetakan' }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full border px-2.5 py-1 text-[10px] font-bold {{ $material->status === 'PUBLISHED' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">{{ $material->status }}</span>
                                @if($material->status === 'DRAFT')
                                    <button type="button" wire:click="publishMaterial('{{ $material->id }}')" wire:loading.attr="disabled" class="rounded-lg bg-emerald-700 px-3 py-1.5 text-[10px] font-bold text-white">Publikasikan</button>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3 space-y-2 border-t border-cardBorder pt-3">
                            @forelse($material->topics as $topic)
                                <div class="flex flex-col gap-2 rounded-lg bg-contentBg p-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold text-navy-900">{{ $topic->code }} · {{ $topic->title }}</p>
                                        <p class="mt-1 text-xs text-textSecondary">{{ $topic->description }}</p>
                                        <p class="mt-1 text-[10px] text-textSecondary">TP/ATP: {{ $topic->learningOutcome?->code ?? 'Belum dipetakan' }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full border px-2 py-1 text-[10px] font-bold {{ $topic->status === 'PUBLISHED' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">{{ $topic->status }}</span>
                                        @if($topic->status === 'DRAFT')
                                            <button type="button" wire:click="publishTopic('{{ $topic->id }}')" wire:loading.attr="disabled" class="rounded-lg bg-navy-900 px-3 py-1.5 text-[10px] font-bold text-white">Publikasikan</button>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-textSecondary">Belum ada topik pada materi ini.</p>
                            @endforelse
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-cardBorder p-8 text-center text-sm text-textSecondary">Belum ada materi untuk penugasan ini.</div>
                @endforelse
            </div>
        </section>
    @endif
</div>
