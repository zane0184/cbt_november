<div class="space-y-6">
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-navy-900">Penugasan Proktor</h1>
                <p class="text-sm text-textSecondary mt-1">Admin Madrasah mengatur masa tugas Proktor untuk setiap ujian. Akses monitoring otomatis berakhir ketika penugasan kedaluwarsa atau dicabut.</p>
            </div>
            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800">Penugasan sementara</span>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 border-l-4 border-emerald-600 text-emerald-800 p-4 rounded-xl text-sm">{{ session('message') }}</div>
    @endif

    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm space-y-4">
        <div>
            <h2 class="text-lg font-bold text-navy-900">Buat / Perbarui Penugasan</h2>
            <p class="text-xs text-textSecondary mt-1">Memilih pasangan ujian dan Proktor yang sama akan memperbarui jendela waktu assignment yang sudah ada.</p>
        </div>

        @if($proctors->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                Belum ada akun dengan role PROKTOR pada madrasah ini. Buat atau tetapkan role PROKTOR terlebih dahulu melalui pengelolaan pengguna.
            </div>
        @endif

        <form wire:submit.prevent="saveAssignment" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Ujian</label>
                <select wire:model="examId" class="w-full border border-cardBorder rounded-xl px-3 py-2.5 text-sm">
                    <option value="">Pilih ujian</option>
                    @foreach($exams as $exam)
                        <option value="{{ $exam->id }}">{{ $exam->title }} — {{ $exam->status }}</option>
                    @endforeach
                </select>
                @error('examId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Akun Proktor</label>
                <select wire:model="proctorUserId" class="w-full border border-cardBorder rounded-xl px-3 py-2.5 text-sm">
                    <option value="">Pilih Proktor</option>
                    @foreach($proctors as $proctor)
                        <option value="{{ $proctor->id }}">{{ $proctor->name }} — {{ $proctor->email }}</option>
                    @endforeach
                </select>
                @error('proctorUserId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Mulai Penugasan</label>
                <input type="datetime-local" wire:model="startsAt" class="w-full border border-cardBorder rounded-xl px-3 py-2.5 text-sm">
                @error('startsAt') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Akhir Penugasan</label>
                <input type="datetime-local" wire:model="endsAt" class="w-full border border-cardBorder rounded-xl px-3 py-2.5 text-sm">
                @error('endsAt') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-gray-600 mb-1">Catatan Operasional (opsional)</label>
                <textarea wire:model="notes" rows="2" maxlength="2000" class="w-full border border-cardBorder rounded-xl px-3 py-2.5 text-sm" placeholder="Contoh: Proktor ruang 2 / sesi pagi"></textarea>
                @error('notes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2 flex justify-end">
                <button type="submit" @disabled($proctors->isEmpty()) class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-bold">
                    Simpan Penugasan
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-cardBorder flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-navy-900">Riwayat Penugasan</h2>
                <p class="text-xs text-textSecondary mt-1">Riwayat tidak dihapus ketika tugas dicabut. Seluruh perubahan dicatat pada audit log.</p>
            </div>
            <select wire:model.live="examFilter" class="border border-cardBorder rounded-xl px-3 py-2 text-sm md:w-72">
                <option value="">Semua ujian</option>
                @foreach($exams as $exam)
                    <option value="{{ $exam->id }}">{{ $exam->title }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="text-left px-5 py-3">Ujian</th>
                        <th class="text-left px-5 py-3">Proktor</th>
                        <th class="text-left px-5 py-3">Masa Penugasan</th>
                        <th class="text-left px-5 py-3">Status</th>
                        <th class="text-left px-5 py-3">Ditugaskan Oleh</th>
                        <th class="text-right px-5 py-3">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($assignments as $assignment)
                        @php
                            $statusLabel = $assignment->status === 'REVOKED'
                                ? 'Dicabut'
                                : ($assignment->ends_at?->isPast()
                                    ? 'Kedaluwarsa'
                                    : ($assignment->starts_at?->isFuture() ? 'Akan Datang' : 'Aktif'));
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-navy-900">{{ $assignment->exam?->title ?? 'Ujian tidak ditemukan' }}</p>
                                <p class="text-xs text-gray-500">{{ $assignment->exam?->status ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-gray-800">{{ $assignment->proctor?->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ $assignment->proctor?->email ?? '' }}</p>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-xs text-gray-600">
                                <p>Mulai: {{ $assignment->starts_at?->format('d/m/Y H:i') ?? '-' }}</p>
                                <p>Akhir: {{ $assignment->ends_at?->format('d/m/Y H:i') ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $statusLabel === 'Aktif' ? 'bg-emerald-50 text-emerald-700' : ($statusLabel === 'Dicabut' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">{{ $statusLabel }}</span>
                                @if($assignment->revoked_at)
                                    <p class="text-xs text-gray-500 mt-1">Dicabut: {{ $assignment->revoked_at->format('d/m/Y H:i') }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-600">{{ $assignment->assigner?->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-right">
                                @if($assignment->status !== 'REVOKED')
                                    <button type="button" wire:click="revokeAssignment('{{ $assignment->id }}')" wire:confirm="Cabut penugasan Proktor ini?" class="text-xs font-bold text-red-600 hover:text-red-800">
                                        Cabut
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400">Tidak ada tindakan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500">Belum ada penugasan Proktor untuk madrasah ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-cardBorder">{{ $assignments->links() }}</div>
    </div>
</div>
