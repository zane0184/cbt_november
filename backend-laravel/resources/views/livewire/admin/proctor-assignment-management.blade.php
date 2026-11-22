<div class="space-y-6">
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-50 text-amber-800 rounded-full text-xs font-bold border border-amber-200 mb-3">Kewenangan Admin Madrasah</div>
        <h1 class="text-2xl font-bold text-navy-900">Penugasan Guru Pengawas Sementara</h1>
        <p class="text-sm text-textSecondary mt-2">Penugasan dibatasi pada ujian tertentu, tenant aktif, dan rentang waktu yang ditentukan. Guru Pengawas hanya memperoleh akses monitoring selama penugasan aktif; tidak memperoleh hak mengelola token, soal, peserta, atau nilai.</p>
    </div>

    @if (session()->has('proctorAssignmentMessage'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('proctorAssignmentMessage') }}</div>
    @endif

    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm space-y-4">
        <h2 class="text-lg font-bold text-navy-900">Buat atau perbarui penugasan</h2>
        <form wire:submit="assign" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Ujian terbit</label>
                <select wire:model="selectedExamId" class="w-full rounded-lg border border-cardBorder px-3 py-2">
                    <option value="">Pilih ujian</option>
                    @foreach ($exams as $exam)<option value="{{ $exam->id }}">{{ $exam->title }}</option>@endforeach
                </select>
                @error('selectedExamId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Guru aktif</label>
                <select wire:model="selectedProctorId" class="w-full rounded-lg border border-cardBorder px-3 py-2">
                    <option value="">Pilih Proktor tenant ini</option>
                    @foreach ($proctors as $proctor)<option value="{{ $proctor->id }}">{{ $proctor->name }} — {{ $proctor->email }}</option>@endforeach
                </select>
                @error('selectedProctorId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Mulai penugasan</label>
                <input type="datetime-local" wire:model="startsAt" class="w-full rounded-lg border border-cardBorder px-3 py-2">
                @error('startsAt') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Berakhir penugasan</label>
                <input type="datetime-local" wire:model="expiresAt" class="w-full rounded-lg border border-cardBorder px-3 py-2">
                @error('expiresAt') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-1">Alasan penugasan</label>
                <textarea wire:model="reason" rows="2" class="w-full rounded-lg border border-cardBorder px-3 py-2" placeholder="Contoh: penugasan pengawasan ujian kelas 6"></textarea>
                @error('reason') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-navy-900 text-white font-semibold px-5 py-2.5 hover:bg-navy-800 disabled:opacity-50">Simpan penugasan</button>
            </div>
        </form>
    </div>

    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm space-y-4">
        <div><h2 class="text-lg font-bold text-navy-900">Riwayat penugasan</h2><p class="text-xs text-textSecondary mt-1">Maksimal 100 perubahan terakhir pada tenant aktif. Riwayat perubahan juga dicatat dalam audit log.</p></div>
        <div>
            <label class="block text-sm font-semibold mb-1">Alasan pencabutan</label>
            <input wire:model="revokeReason" type="text" class="w-full md:w-1/2 rounded-lg border border-cardBorder px-3 py-2" placeholder="Alasan pencabutan minimal 10 karakter">
            @error('revokeReason') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left border-b border-cardBorder text-textSecondary"><th class="py-3 pr-3">Ujian</th><th class="py-3 pr-3">Proktor</th><th class="py-3 pr-3">Masa berlaku</th><th class="py-3 pr-3">Status</th><th class="py-3">Aksi</th></tr></thead>
                <tbody>
                @forelse ($assignments as $assignment)
                    <tr class="border-b border-cardBorder/70">
                        <td class="py-3 pr-3 font-semibold">{{ $assignment->exam?->title ?? 'Ujian tidak tersedia' }}</td>
                        <td class="py-3 pr-3">{{ $assignment->proctor?->name ?? 'Akun tidak tersedia' }}</td>
                        <td class="py-3 pr-3 whitespace-nowrap">{{ $assignment->starts_at?->format('d/m/Y H:i') }} – {{ $assignment->expires_at?->format('d/m/Y H:i') }}</td>
                        <td class="py-3 pr-3">
                            @if ($assignment->status === 'ACTIVE' && $assignment->expires_at?->isFuture())<span class="text-emerald-700 font-semibold">Aktif / terjadwal</span>
                            @elseif ($assignment->status === 'REVOKED')<span class="text-red-700 font-semibold">Dicabut</span>
                            @else<span class="text-textSecondary font-semibold">Kedaluwarsa</span>@endif
                        </td>
                        <td class="py-3">
                            @if ($assignment->status === 'ACTIVE' && !$assignment->revoked_at)
                                <button type="button" wire:click="revoke('{{ $assignment->id }}')" wire:loading.attr="disabled" class="text-red-700 font-semibold hover:underline">Cabut</button>
                            @else<span class="text-textSecondary">—</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-textSecondary">Belum ada penugasan Proktor.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
