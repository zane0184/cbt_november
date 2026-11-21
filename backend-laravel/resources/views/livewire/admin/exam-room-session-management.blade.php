<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Administrasi Ujian</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Ruang & Sesi Ujian</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600">Kelola metadata ruang, jadwal sesi, dan penempatan rombel. Halaman ini tidak menampilkan soal, token, jawaban, attempt, nilai, hasil, maupun monitoring CBT.</p>
        </div>
        <a href="/admin/proctor-assignments" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">Kelola Penugasan Guru Proktor</a>
    </div>

    @if (session()->has('examRoomSessionMessage'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
            {{ session('examRoomSessionMessage') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-slate-900">Master Ruang</h2>
                <p class="text-sm text-slate-500">Data fisik ruang milik tenant ini.</p>
            </div>
            <form wire:submit="saveRoom" class="grid gap-3 sm:grid-cols-2">
                <label class="block text-sm font-medium text-slate-700">Kode ruang
                    <input wire:model="roomCode" maxlength="50" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="A-01">
                    @error('roomCode')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-medium text-slate-700">Nama ruang
                    <input wire:model="roomName" maxlength="150" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Ruang Kelas 1">
                    @error('roomName')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-medium text-slate-700">Lokasi
                    <input wire:model="roomLocation" maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Gedung A, lantai 1">
                </label>
                <label class="block text-sm font-medium text-slate-700">Kapasitas maksimum
                    <input wire:model="roomCapacity" type="number" min="1" max="5000" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('roomCapacity')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                @if($editingRoomId)
                    <label class="block text-sm font-medium text-slate-700">Status ruang
                        <select wire:model="roomStatus" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="ACTIVE">Aktif</option>
                            <option value="INACTIVE">Nonaktif</option>
                        </select>
                        @error('roomStatus')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                @endif
                <div class="sm:col-span-2 flex flex-wrap gap-2">
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">
                        {{ $editingRoomId ? 'Simpan Perubahan Ruang' : 'Simpan Ruang' }}
                    </button>
                    @if($editingRoomId)
                        <button type="button" wire:click="cancelRoomEdit" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal Edit</button>
                    @endif
                </div>
            </form>
            <div class="mt-5 divide-y divide-slate-100 border-t border-slate-100">
                @forelse($rooms as $room)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div>
                            <p class="font-semibold text-slate-800">{{ $room->code }} · {{ $room->name }}</p>
                            <p class="text-xs text-slate-500">{{ $room->location ?: 'Lokasi belum diisi' }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $room->capacity }} peserta</span>
                            <span class="rounded-full {{ $room->status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }} px-3 py-1 text-xs font-semibold">{{ $room->status }}</span>
                            <button type="button" wire:click="editRoom('{{ $room->id }}')" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700">Edit</button>
                            @if($room->status === 'ACTIVE')
                                <button type="button" wire:click="setRoomStatus('{{ $room->id }}', 'INACTIVE')" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700">Nonaktifkan</button>
                            @else
                                <button type="button" wire:click="setRoomStatus('{{ $room->id }}', 'ACTIVE')" class="rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700">Aktifkan</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-sm text-slate-500">Belum ada ruang yang terdaftar.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-slate-900">Buat Sesi Ujian</h2>
                <p class="text-sm text-slate-500">Sesi hanya dapat dibuat untuk ujian yang sudah diterbitkan dan dikunci oleh Guru.</p>
            </div>
            <form wire:submit="createSession" class="space-y-3">
                <label class="block text-sm font-medium text-slate-700">Ujian
                    <select wire:model.live="selectedExamId" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">Pilih ujian</option>
                        @foreach($exams as $exam)
                            <option value="{{ $exam->id }}">{{ $exam->title }} · {{ $exam->start_at?->format('d/m/Y H:i') }}</option>
                        @endforeach
                    </select>
                    @error('selectedExamId')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700">Nama sesi
                        <input wire:model="sessionName" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Sesi Pagi">
                        @error('sessionName')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Nomor sesi
                        <input wire:model="sessionNumber" type="number" min="1" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        @error('sessionNumber')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700">Mulai
                        <input wire:model="sessionStartsAt" type="datetime-local" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        @error('sessionStartsAt')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Selesai
                        <input wire:model="sessionEndsAt" type="datetime-local" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        @error('sessionEndsAt')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                </div>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Buat Sesi</button>
            </form>
        </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Pasangkan Ruang ke Sesi</h2>
            <p class="mb-4 text-sm text-slate-500">Satu ruang tidak boleh digunakan pada sesi lain yang waktunya bertumpang tindih.</p>
            <form wire:submit="attachRoom" class="space-y-3">
                <label class="block text-sm font-medium text-slate-700">Ujian
                    <select wire:model.live="selectedExamId" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">Pilih ujian</option>
                        @foreach($exams as $exam)<option value="{{ $exam->id }}">{{ $exam->title }}</option>@endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium text-slate-700">Sesi
                    <select wire:model="selectedSessionId" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">Pilih sesi</option>
                        @foreach($sessions->where('exam_id', $selectedExamId) as $session)
                            <option value="{{ $session->id }}">Sesi {{ $session->session_number }} · {{ $session->name }}</option>
                        @endforeach
                    </select>
                    @error('selectedSessionId')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700">Ruang
                        <select wire:model="selectedRoomId" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">Pilih ruang</option>
                            @foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->code }} · {{ $room->name }} ({{ $room->capacity }})</option>@endforeach
                        </select>
                        @error('selectedRoomId')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Kapasitas sesi (opsional)
                        <input wire:model="capacityOverride" type="number" min="1" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Ikuti kapasitas ruang">
                        @error('capacityOverride')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                </div>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Pasangkan Ruang</button>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Petakan Rombel</h2>
            <p class="mb-4 text-sm text-slate-500">Peserta ditempatkan berdasarkan snapshot rombel pada saat Guru menambahkan peserta ke ujian.</p>
            <form wire:submit="mapRombel" class="space-y-3">
                <label class="block text-sm font-medium text-slate-700">Pasangan sesi-ruang
                    <select wire:model="selectedSessionRoomId" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">Pilih sesi dan ruang</option>
                        @foreach($sessionRooms as $scope)
                            <option value="{{ $scope->id }}">{{ $scope->exam?->title }} · Sesi {{ $scope->session?->session_number }} · {{ $scope->room?->code }}</option>
                        @endforeach
                    </select>
                    @error('selectedSessionRoomId')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-medium text-slate-700">Rombel
                    <select wire:model="selectedRombelId" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">Pilih rombel</option>
                        @foreach($rombels as $rombel)<option value="{{ $rombel->id }}">{{ $rombel->name }}</option>@endforeach
                    </select>
                    @error('selectedRombelId')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Petakan Rombel</button>
            </form>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-lg font-bold text-slate-900">Jadwal dan Penempatan Aktif</h2><p class="text-sm text-slate-500">Histori penempatan yang dilepaskan tetap disimpan.</p></div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $sessions->count() }} sesi</span>
        </div>
        <div class="space-y-4">
            @forelse($sessions as $session)
                <article class="rounded-xl border border-slate-200 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-slate-900">{{ $session->exam?->title }} · Sesi {{ $session->session_number }} — {{ $session->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $session->starts_at?->format('d/m/Y H:i') }}–{{ $session->ends_at?->format('H:i') }} · Revisi jadwal {{ $session->schedule_revision }} · {{ $session->status }}</p>
                        </div>
                        <button type="button" wire:click="selectSessionForReschedule('{{ $session->id }}')" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700">Pilih untuk ubah jadwal</button>
                    </div>
                    @if($session->sessionRooms->isNotEmpty())
                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            @foreach($session->sessionRooms as $scope)
                                <div class="rounded-lg bg-slate-50 p-3">
                                    <p class="font-semibold text-slate-800">{{ $scope->room?->code }} · {{ $scope->room?->name }}</p>
                                    <p class="text-xs text-slate-500">Kapasitas {{ $scope->capacity_override ?: $scope->room?->capacity }} · {{ $scope->participantAssignments->where('status', 'ACTIVE')->count() }} peserta ditempatkan</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($scope->rombelMappings as $mapping)
                                            <span class="rounded-full {{ $mapping->status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }} px-2.5 py-1 text-xs">{{ $mapping->rombel?->name }} · {{ $mapping->status }}</span>
                                            @if($mapping->status === 'ACTIVE')
                                                <button type="button" wire:click="releaseMapping('{{ $mapping->id }}')" class="text-xs font-semibold text-red-700 underline">Lepaskan</button>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 text-sm text-slate-500">Belum ada ruang yang dipasangkan.</p>
                    @endif
                </article>
            @empty
                <p class="text-sm text-slate-500">Belum ada sesi ujian.</p>
            @endforelse
        </div>

        @if($sessions->isNotEmpty())
            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <label class="block max-w-xl text-sm font-medium text-slate-700">Alasan pelepasan pemetaan rombel
                    <input wire:model="releaseReason" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Minimal 10 karakter; digunakan saat tombol Lepaskan dipilih">
                </label>
                @error('releaseReason')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
            </div>
        @endif

        @if($selectedSessionId)
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <h3 class="font-semibold text-amber-900">Ubah jadwal sesi terpilih</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                    <label class="text-sm font-medium text-slate-700">Mulai<input wire:model="sessionStartsAt" type="datetime-local" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                    <label class="text-sm font-medium text-slate-700">Selesai<input wire:model="sessionEndsAt" type="datetime-local" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                    <label class="text-sm font-medium text-slate-700">Alasan<input wire:model="rescheduleReason" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Alasan perubahan"></label>
                </div>
                @error('rescheduleReason')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                <button type="button" wire:click="rescheduleSession('{{ $selectedSessionId }}')" class="mt-3 rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white">Simpan Jadwal Baru</button>
            </div>
        @endif
    </section>
</div>
