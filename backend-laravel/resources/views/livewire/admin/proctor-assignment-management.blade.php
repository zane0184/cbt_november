<div class="space-y-6">
    <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <strong>Penugasan sementara ditahan.</strong>
        {{ $assignmentBlockReason }}
    </div>

    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-50 text-amber-800 rounded-full text-xs font-bold border border-amber-200 mb-3">Kewenangan Admin Madrasah</div>
        <h1 class="text-2xl font-bold text-navy-900">Penugasan Guru Pengawas Sementara</h1>
        <p class="text-sm text-textSecondary mt-2">Penugasan adalah catatan administratif sementara pada Guru untuk ujian terpusat yang kelayakannya telah ditetapkan secara kanonis. Penugasan tidak membuka ruang, roster, monitoring, Attempt, token, soal, jawaban, atau nilai.</p>
    </div>

    @if (session()->has('proctorAssignmentMessage'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('proctorAssignmentMessage') }}</div>
    @endif

    {{-- Assignment controls remain intentionally absent until owner-approved central-exam eligibility exists. --}}


    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm space-y-4">
        <div><h2 class="text-lg font-bold text-navy-900">Riwayat penugasan</h2><p class="text-xs text-textSecondary mt-1">Maksimal 100 perubahan terakhir pada tenant aktif. Riwayat perubahan juga dicatat dalam audit log.</p></div>
        <div>
            <label class="block text-sm font-semibold mb-1">Alasan pencabutan</label>
            <input wire:model="revokeReason" type="text" class="w-full md:w-1/2 rounded-lg border border-cardBorder px-3 py-2" placeholder="Alasan pencabutan minimal 10 karakter">
            @error('revokeReason') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left border-b border-cardBorder text-textSecondary"><th class="py-3 pr-3">Ujian</th><th class="py-3 pr-3">Guru Pengawas</th><th class="py-3 pr-3">Masa berlaku</th><th class="py-3 pr-3">Status</th><th class="py-3">Aksi</th></tr></thead>
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
                    <tr><td colspan="5" class="py-6 text-center text-textSecondary">Belum ada penugasan Guru Pengawas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
