<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Kurikulum Merdeka (CP & TP)</h1>
            <p class="text-xs text-textSecondary mt-1">Kelola Capaian Pembelajaran (CP) dan Tujuan Pembelajaran (TP) berdasarkan Kurikulum Merdeka</p>
        </div>
        @if($canManageCurriculum)
            <button wire:click="openCreateTpModal" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-xl transition text-xs flex items-center gap-2 shadow-sm">
                <span>+</span> Tambah Tujuan Pembelajaran (TP)
            </button>
        @endif
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 border-l-4 border-emerald-600 text-emerald-800 p-4 rounded-xl shadow-sm text-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Filters Card -->
    <div class="bg-white border border-cardBorder rounded-2xl p-4 shadow-sm flex flex-col md:flex-row gap-3">
        <input wire:model.live="search" type="text" placeholder="Cari kode CP/TP atau kata kunci..." class="border border-cardBorder rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none flex-1">
        <select wire:model.live="subjectFilter" class="border border-cardBorder rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none md:w-56">
            <option value="">Semua Mata Pelajaran</option>
            @foreach($subjects as $sub)
                <option value="{{ $sub->id }}">{{ $sub->name }} ({{ $sub->code }})</option>
            @endforeach
        </select>
    </div>

    <!-- Outcomes List -->
    <div class="space-y-4">
        @forelse($outcomes as $outcome)
            <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden p-6 space-y-4">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 border-b border-cardBorder pb-3">
                    <div class="flex items-center gap-2">
                        <span class="bg-navy-900 text-white font-mono font-bold text-xs px-3 py-1 rounded-lg">{{ $outcome->code }}</span>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200">
                            {{ $outcome->subject->name ?? '-' }} — Fase {{ $outcome->phase->name ?? '-' }}
                        </span>
                    </div>
                    <span class="text-xs text-textSecondary font-medium">Total {{ $outcome->objectives->count() }} TP</span>
                </div>

                @if($outcome->currentVersion)
                    <div class="bg-contentBg p-4 rounded-xl border border-cardBorder text-xs text-navy-900 leading-relaxed">
                        <p class="font-bold text-textSecondary uppercase text-[10px] mb-1">Deskripsi Capaian Pembelajaran (CP):</p>
                        {{ $outcome->currentVersion->title ?? $outcome->currentVersion->description }}
                    </div>
                @endif

                <!-- Objectives Table / List -->
                <div class="space-y-2 pt-2">
                    <h4 class="text-xs font-bold text-navy-900 uppercase tracking-wider">Tujuan Pembelajaran (TP):</h4>
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
                        <p class="text-xs text-gray-400 italic">Belum ada TP yang terhubung ke CP ini.</p>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="bg-white border border-cardBorder rounded-2xl p-12 text-center text-textSecondary text-sm shadow-sm">
                Belum ada data Capaian Pembelajaran (CP) / Tujuan Pembelajaran (TP).
            </div>
        @endforelse
    </div>

    <div class="p-4 bg-white border border-cardBorder rounded-2xl shadow-sm">
        {{ $outcomes->links() }}
    </div>

    <!-- Create TP Modal -->
    @if($showCreateTpModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-cardBorder">
                <h3 class="text-lg font-bold text-navy-900">Tambah Tujuan Pembelajaran (TP)</h3>
                <form wire:submit.prevent="createTp" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Pilih CP Induk</label>
                        <select wire:model="selectedOutcomeId" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                            <option value="">Pilih Capaian Pembelajaran...</option>
                            @foreach($outcomes as $oc)
                                <option value="{{ $oc->id }}">{{ $oc->code }} ({{ $oc->subject->name ?? '' }})</option>
                            @endforeach
                        </select>
                        @error('selectedOutcomeId') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Kode TP</label>
                        <input wire:model="tpCode" type="text" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none" placeholder="Contoh: TP-MTK-1.1">
                        @error('tpCode') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Judul / Ringkasan TP</label>
                        <input wire:model="tpTitle" type="text" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none" placeholder="Ringkasan kompetensi yang ditargetkan...">
                        @error('tpTitle') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-navy-900 uppercase mb-1">Deskripsi Lengkap (Opsional)</label>
                        <textarea wire:model="tpDescription" rows="3" class="w-full border border-cardBorder rounded-xl px-3.5 py-2 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none" placeholder="Deskripsi rinci tujuan pembelajaran..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-cardBorder">
                        <button type="button" wire:click="$set('showCreateTpModal', false)" class="px-4 py-2 text-xs font-bold text-textSecondary rounded-xl bg-gray-100">Batal</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md">Simpan TP</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
