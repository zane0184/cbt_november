<div class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800">Kewenangan Administratif Admin Madrasah</div>
        <h1 class="text-2xl font-bold text-slate-900">Penugasan Guru Proktor Sementara</h1>
        <p class="mt-2 max-w-4xl text-sm text-slate-600">Admin menetapkan Guru pada pasangan ruang dan sesi tertentu. Capability monitoring hanya aktif setelah status CONFIRMED, selama jadwal, tenant, ruang-sesi, revisi jadwal, dan masa penugasan tetap cocok. Penugasan ini tidak membuat role PROKTOR permanen dan tidak memberi Admin akses ke data operasional CBT.</p>
    </div>

    @if (session()->has('proctorAssignmentMessage'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('proctorAssignmentMessage') }}</div>
    @endif

    <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div><h2 class="text-lg font-bold text-slate-900">Tetapkan Guru pada ruang-sesi</h2><p class="mt-1 text-sm text-slate-500">Rentang penugasan wajib berada di dalam jadwal sesi yang dipilih.</p></div>
        <form wire:submit="assign" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-semibold text-slate-700">Ujian · Sesi · Ruang</label>
                <select wire:model.live="selectedSessionRoomId" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Pilih pasangan ruang-sesi</option>
                    @foreach ($sessionRooms as $scope)
                        <option value="{{ $scope->id }}">{{ $scope->exam?->title }} · Sesi {{ $scope->session?->session_number }} ({{ $scope->session?->name }}) · {{ $scope->room?->code }} — {{ $scope->room?->name }} · Revisi {{ $scope->session?->schedule_revision }}</option>
                    @endforeach
                </select>
                @error('selectedSessionRoomId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @if($sessionRooms->isEmpty())<p class="mt-1 text-xs text-amber-700">Belum ada pasangan ruang-sesi aktif. Buat ruang, sesi, dan pemetaan terlebih dahulu.</p>@endif
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Guru yang ditugaskan</label>
                <select wire:model="selectedProctorId" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Pilih Guru dengan profil aktif</option>
                    @foreach ($proctors as $proctor)<option value="{{ $proctor->id }}">{{ $proctor->name }}</option>@endforeach
                </select>
                @error('selectedProctorId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Alasan penugasan</label>
                <textarea wire:model="reason" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Minimal 10 karakter"></textarea>
                @error('reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Mulai penugasan</label>
                <input type="datetime-local" wire:model="startsAt" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                @error('startsAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Berakhir penugasan</label>
                <input type="datetime-local" wire:model="expiresAt" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                @error('expiresAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-50">Simpan sebagai PENDING</button>
            </div>
        </form>
    </section>

    <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Riwayat penugasan</h2>
            <p class="mt-1 text-sm text-slate-500">Riwayat terminal dipertahankan. Penugasan yang terdampak perubahan jadwal menjadi NEEDS_REVIEW.</p>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700">Alasan pencabutan</label>
            <input wire:model="revokeReason" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 md:w-1/2" placeholder="Alasan minimal 10 karakter">
            @error('revokeReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-slate-200 text-left text-slate-500"><th class="py-3 pr-3">Ujian / Sesi / Ruang</th><th class="py-3 pr-3">Guru</th><th class="py-3 pr-3">Masa berlaku</th><th class="py-3 pr-3">Revisi</th><th class="py-3 pr-3">Status</th><th class="py-3">Aksi</th></tr></thead>
                <tbody>
                @forelse ($assignments as $assignment)
                    <tr class="border-b border-slate-100 align-top">
                        <td class="py-3 pr-3">
                            <p class="font-semibold text-slate-800">{{ $assignment->exam?->title ?? 'Ujian tidak tersedia' }}</p>
                            <p class="text-xs text-slate-500">
                                @if($assignment->sessionRoom)
                                    Sesi {{ $assignment->sessionRoom->session?->session_number }} · {{ $assignment->sessionRoom->session?->name }} · {{ $assignment->sessionRoom->room?->code }}
                                @else
                                    Scope legacy belum dipetakan; tidak memberi capability monitoring
                                @endif
                            </p>
                        </td>
                        <td class="py-3 pr-3">{{ $assignment->proctorForHistory?->name ?? 'Akun tidak tersedia' }}</td>
                        <td class="whitespace-nowrap py-3 pr-3">{{ $assignment->starts_at?->format('d/m/Y H:i') }} – {{ $assignment->expires_at?->format('d/m/Y H:i') }}</td>
                        <td class="py-3 pr-3 text-center">{{ $assignment->schedule_revision ?? '—' }}</td>
                        <td class="py-3 pr-3">
                            @if ($assignment->status === 'PENDING')
                                <span class="font-semibold text-amber-700">Menunggu konfirmasi</span>
                            @elseif ($assignment->status === 'CONFIRMED' && $assignment->expires_at?->isFuture() && !$assignment->revoked_at)
                                <span class="font-semibold text-emerald-700">Terkonfirmasi</span>
                            @elseif ($assignment->status === 'CONFIRMED')
                                <span class="font-semibold text-slate-500">Waktu berakhir</span>
                            @elseif ($assignment->status === 'NEEDS_REVIEW')
                                <span class="font-semibold text-amber-700">Perlu ditinjau ulang</span>
                            @elseif ($assignment->status === 'REVOKED')
                                <span class="font-semibold text-red-700">Dicabut</span>
                            @elseif ($assignment->status === 'EXPIRED')
                                <span class="font-semibold text-slate-500">Kedaluwarsa</span>
                            @elseif ($assignment->status === 'COMPLETED')
                                <span class="font-semibold text-slate-500">Selesai</span>
                            @else
                                <span class="font-semibold text-red-700">Status tidak valid</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap py-3">
                            @if ($assignment->exam_session_room_id && in_array($assignment->status, ['PENDING', 'NEEDS_REVIEW'], true) && !$assignment->revoked_at)
                                <button type="button" wire:click="confirm('{{ $assignment->id }}')" wire:loading.attr="disabled" class="mr-3 font-semibold text-emerald-700 hover:underline">Konfirmasi</button>
                            @endif
                            @if (in_array($assignment->status, ['PENDING', 'CONFIRMED', 'NEEDS_REVIEW'], true) && !$assignment->revoked_at)
                                <button type="button" wire:click="revoke('{{ $assignment->id }}')" wire:loading.attr="disabled" class="font-semibold text-red-700 hover:underline">Cabut</button>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-slate-500">Belum ada penugasan Proktor.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $assignments->links() }}</div>
    </section>
</div>
