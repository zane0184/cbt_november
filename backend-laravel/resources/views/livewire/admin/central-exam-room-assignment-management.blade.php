<div class="space-y-6">
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-50 text-amber-800 rounded-full text-xs font-bold border border-amber-200 mb-3">Kewenangan Admin Madrasah</div>
        <h1 class="text-2xl font-bold text-navy-900">Penjadwalan Ruang & Guru Pengawas</h1>
        <p class="text-sm text-textSecondary mt-2">Admin hanya mengelola ujian terpusat ASTS, ASAS, ASAT, UM, dan SIMULASI_AN. Satu ruang/sesi dapat memiliki beberapa Guru pengawas. Guru yang sama tidak boleh ditugaskan pada jadwal yang beririsan.</p>
    </div>

    @if (session()->has('centralRoomAssignmentMessage'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('centralRoomAssignmentMessage') }}</div>
    @endif

    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm space-y-4">
        <h2 class="text-lg font-bold text-navy-900">Buat penugasan ruang dan sesi</h2>
        <form wire:submit="assign" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Ujian terpusat</label>
                <select wire:model.live="selectedExamId" class="w-full rounded-lg border border-cardBorder px-3 py-2">
                    <option value="">Pilih ASTS / ASAS / ASAT / UM / SIMULASI AN</option>
                    @foreach ($exams as $exam)
                        <option value="{{ $exam->id }}">{{ $exam->category }} — {{ $exam->title }}</option>
                    @endforeach
                </select>
                @error('selectedExamId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Ruang aktif</label>
                <select wire:model="selectedRoomId" class="w-full rounded-lg border border-cardBorder px-3 py-2">
                    <option value="">Pilih ruang ujian</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}">{{ $room->code }} — {{ $room->name }} (kapasitas {{ $room->capacity }})</option>
                    @endforeach
                </select>
                @error('selectedRoomId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Guru pengawas (boleh lebih dari satu)</label>
                <select multiple wire:model="selectedProctorIds" size="6" class="w-full rounded-lg border border-cardBorder px-3 py-2">
                    @foreach ($proctors as $proctor)
                        <option value="{{ $proctor->id }}">{{ $proctor->name }} — {{ $proctor->email }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-textSecondary mt-1">Gunakan Ctrl (Windows) atau Cmd (Mac) untuk memilih beberapa Guru.</p>
                @error('selectedProctorIds') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                @error('selectedProctorIds.*') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Pengawas pendamping</label>
                <select multiple wire:model="supervisorIds" size="6" class="w-full rounded-lg border border-cardBorder px-3 py-2">
                    @foreach ($proctors as $proctor)
                        <option value="{{ $proctor->id }}">{{ $proctor->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-textSecondary mt-1">Pilihan yang ditandai di sini disimpan dengan label supervisor; lainnya proctor.</p>
                @error('supervisorIds.*') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Nomor sesi</label>
                <input type="number" min="1" wire:model="sessionNumber" class="w-full rounded-lg border border-cardBorder px-3 py-2">
                @error('sessionNumber') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Mulai jadwal</label>
                <input type="datetime-local" wire:model="scheduledStart" class="w-full rounded-lg border border-cardBorder px-3 py-2">
                @error('scheduledStart') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Selesai jadwal</label>
                <input type="datetime-local" wire:model="scheduledEnd" class="w-full rounded-lg border border-cardBorder px-3 py-2">
                @error('scheduledEnd') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-2 pt-7">
                <input id="mergeRoom" type="checkbox" wire:model="mergeRoom" class="rounded border-cardBorder">
                <label for="mergeRoom" class="text-sm font-semibold">Gabung Ruang (hanya jadwal identik dan disetujui kedua penugasan)</label>
            </div>
            <div class="md:col-span-2">
                @error('proctors') <p class="text-xs text-red-600 mb-2">{{ $message }}</p> @enderror
                @error('room') <p class="text-xs text-red-600 mb-2">{{ $message }}</p> @enderror
                <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-navy-900 text-white font-semibold px-5 py-2.5 hover:bg-navy-800 disabled:opacity-50">Simpan penugasan ruang</button>
            </div>
        </form>
    </div>

    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm space-y-4">
        <div>
            <h2 class="text-lg font-bold text-navy-900">Daftar sesi dan pengawas</h2>
            <p class="text-xs text-textSecondary mt-1">Hanya metadata ujian terpusat, ruang, jadwal, dan Guru yang ditugaskan. Tidak menampilkan roster, progres, Attempt, jawaban, atau nilai.</p>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Alasan pencabutan pengawas</label>
            <input wire:model="revokeReason" type="text" class="w-full md:w-1/2 rounded-lg border border-cardBorder px-3 py-2" placeholder="Minimal 10 karakter">
            @error('revokeReason') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left border-b border-cardBorder text-textSecondary">
                    <th class="py-3 pr-3">Ujian</th><th class="py-3 pr-3">Ruang / Sesi</th><th class="py-3 pr-3">Jadwal</th><th class="py-3 pr-3">Guru pengawas</th><th class="py-3">Status</th>
                </tr></thead>
                <tbody>
                @forelse ($assignments as $assignment)
                    <tr class="border-b border-cardBorder/70 align-top">
                        <td class="py-3 pr-3 font-semibold">{{ $assignment->exam?->category }} — {{ $assignment->exam?->title }}</td>
                        <td class="py-3 pr-3">{{ $assignment->room?->code }} — {{ $assignment->room?->name }}<br><span class="text-xs text-textSecondary">Sesi {{ $assignment->session_number }}</span></td>
                        <td class="py-3 pr-3 whitespace-nowrap">{{ $assignment->scheduled_start?->format('d/m/Y H:i') }} – {{ $assignment->scheduled_end?->format('H:i') }}</td>
                        <td class="py-3 pr-3">
                            @forelse ($assignment->activeProctors as $membership)
                                <div class="flex items-center justify-between gap-2 py-1">
                                    <span>{{ $membership->user?->name }} <span class="text-xs text-textSecondary">({{ $membership->role_label }})</span></span>
                                    <button type="button" wire:click="revokeProctor('{{ $assignment->id }}', '{{ $membership->user_id }}')" wire:loading.attr="disabled" class="text-red-700 text-xs font-semibold hover:underline">Cabut</button>
                                </div>
                            @empty
                                <span class="text-xs text-textSecondary">Belum ada pengawas aktif</span>
                            @endforelse
                        </td>
                        <td class="py-3">{{ $assignment->scheduled_end?->isFuture() ? 'Terjadwal / aktif' : 'Selesai' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-textSecondary">Belum ada penugasan ruang ujian terpusat.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
