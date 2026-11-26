<div class="space-y-6">
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-navy-900">Referensi Kurikulum</h1>
        <p class="text-xs text-textSecondary mt-1">Tampilan baca-saja data CP/TP legacy untuk pemeriksaan administrasi</p>
    </div>

    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900" role="status">
        <p class="font-semibold">Data referensi lama — belum menjadi registry resmi terverifikasi</p>
        <p class="mt-1">Data di bawah berasal dari struktur kurikulum legacy. Jangan gunakan sebagai bukti CP resmi, versi kurikulum aktif, atau provenance sumber. Admin hanya melihat referensi; pengembangan TP dan ATP dilakukan Guru melalui penugasan yang sah.</p>
    </div>

    <div class="bg-white border border-cardBorder rounded-2xl p-4 shadow-sm flex flex-col md:flex-row gap-3">
        <input wire:model.live="search" type="text" placeholder="Cari kode CP/TP atau kata kunci..." class="border border-cardBorder rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none flex-1">
        <select wire:model.live="subjectFilter" class="border border-cardBorder rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none md:w-56">
            <option value="">Semua Mata Pelajaran</option>
            @foreach($subjects as $sub)
                <option value="{{ $sub->id }}">{{ $sub->name }} ({{ $sub->code }})</option>
            @endforeach
        </select>
    </div>

    <div class="space-y-4">
        @forelse($outcomes as $outcome)
            <article class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden p-6 space-y-4">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 border-b border-cardBorder pb-3">
                    <div class="flex items-center gap-2">
                        <span class="bg-navy-900 text-white font-mono font-bold text-xs px-3 py-1 rounded-lg">{{ $outcome->code }}</span>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200">
                            {{ $outcome->subject->name ?? '-' }} — Fase {{ $outcome->phase->name ?? '-' }}
                        </span>
                    </div>
                    <span class="text-xs text-textSecondary font-medium">TP legacy: {{ $outcome->objectives->count() }}</span>
                </div>

                @if($outcome->currentVersion)
                    <div class="bg-contentBg p-4 rounded-xl border border-cardBorder text-xs text-navy-900 leading-relaxed">
                        <p class="font-bold text-textSecondary uppercase text-[10px] mb-1">Referensi CP legacy:</p>
                        {{ $outcome->currentVersion->title ?? $outcome->currentVersion->description }}
                    </div>
                @endif

                <div class="space-y-2 pt-2">
                    <h2 class="text-xs font-bold text-navy-900 uppercase tracking-wider">TP legacy (baca-saja)</h2>
                    @forelse($outcome->objectives as $tp)
                        <div class="bg-gray-50 border border-cardBorder p-3 rounded-xl flex items-start gap-3 text-xs">
                            <span class="font-mono font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded shrink-0">{{ $tp->code }}</span>
                            <div>
                                <p class="font-bold text-navy-900">{{ $tp->title }}</p>
                                @if($tp->description)
                                    <p class="text-textSecondary mt-0.5">{{ $tp->description }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Belum ada TP legacy yang terhubung ke referensi ini.</p>
                    @endforelse
                </div>
            </article>
        @empty
            <div class="bg-white border border-cardBorder rounded-2xl p-12 text-center text-textSecondary text-sm shadow-sm">
                Belum ada data referensi CP/TP legacy.
            </div>
        @endforelse
    </div>

    <div class="p-4 bg-white border border-cardBorder rounded-2xl shadow-sm">
        {{ $outcomes->links() }}
    </div>
</div>
