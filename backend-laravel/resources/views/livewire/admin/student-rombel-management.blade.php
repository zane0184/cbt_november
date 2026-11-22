<div class="space-y-6">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Siswa, Rombel & Riwayat Akademik</h1>
                <p class="mt-1 text-sm text-slate-600">Kelola penempatan, mutasi, kenaikan kelas, tinggal kelas, kelulusan, arsip, dan histori tanpa menghapus riwayat akademik.</p>
            </div>
            @if($activeYear)
                <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-800">Tahun aktif: {{ $activeYear->name }}</span>
            @else
                <span class="rounded-full bg-amber-50 px-3 py-1.5 text-sm font-semibold text-amber-800">Belum ada tahun pelajaran aktif</span>
            @endif
        </div>
        @if (session()->has('rombelMessage'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('rombelMessage') }}</div>
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900">Penempatan Siswa Baru / Belum Memiliki Rombel</h2>
        <p class="mt-1 text-sm text-slate-600">Satu siswa hanya boleh memiliki satu penempatan pada setiap tahun pelajaran. Untuk perpindahan rombel gunakan tindakan Mutasi agar histori tersimpan.</p>
        @if(!$activeYear)
            <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Aktifkan satu tahun pelajaran terlebih dahulu pada Data Akademik.</p>
        @else
            <form wire:submit="addStudentToRombel" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="text-sm font-semibold text-slate-700 xl:col-span-2">Siswa
                    <select wire:model="selectedStudentId" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal">
                        <option value="">Pilih siswa aktif tanpa penempatan tahun ini</option>
                        @foreach($availableStudents as $student)
                            <option value="{{ $student->id }}">{{ $student->full_name }} — {{ $student->nisn }}</option>
                        @endforeach
                    </select>
                    @error('selectedStudentId')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="text-sm font-semibold text-slate-700 xl:col-span-2">Rombel tujuan
                    <select wire:model="selectedRombelId" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal">
                        <option value="">Pilih rombel tahun aktif</option>
                        @foreach($rombels as $rombel)
                            <option value="{{ $rombel->id }}">{{ $rombel->gradeLevel?->name }} — {{ $rombel->name }}</option>
                        @endforeach
                    </select>
                    @error('selectedRombelId')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="text-sm font-semibold text-slate-700">Tanggal efektif
                    <input type="date" wire:model="enrollmentEffectiveDate" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal">
                    @error('enrollmentEffectiveDate')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="text-sm font-semibold text-slate-700 md:col-span-2 xl:col-span-3">Alasan penempatan (wajib)
                    <input type="text" wire:model="enrollmentReason" minlength="10" maxlength="1000" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal" placeholder="Contoh: Penempatan awal tahun pelajaran 2026/2027">
                    @error('enrollmentReason')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <div class="flex items-end">
                    <button type="submit" wire:loading.attr="disabled" class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Tempatkan Siswa</button>
                </div>
            </form>
            @if($availableStudents->isEmpty())
                <p class="mt-3 text-sm text-slate-500">Tidak ada siswa aktif yang memenuhi syarat untuk penempatan baru pada tahun aktif.</p>
            @endif
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Data Siswa & Status Akademik</h2>
                <p class="mt-1 text-sm text-slate-600">Status akademik berbeda dari status aktif akun. Siswa nonaktif, arsip, lulus, dan alumni tidak dapat login sebagai peserta CBT.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama / NISN" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <select wire:model.live="statusFilter" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="ACTIVE">Aktif</option>
                    <option value="INACTIVE">Nonaktif</option>
                    <option value="ARCHIVED">Arsip</option>
                    <option value="GRADUATED">Lulus</option>
                    <option value="ALUMNI">Alumni</option>
                    <option value="ALL">Semua status</option>
                </select>
            </div>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-3 py-3">Nama</th><th class="px-3 py-3">NISN</th><th class="px-3 py-3">Tahun Masuk</th><th class="px-3 py-3">Status Akademik</th><th class="px-3 py-3">Status Akun</th><th class="px-3 py-3">Tindakan</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($students as $student)
                        <tr>
                            <td class="px-3 py-3 font-semibold text-slate-800">{{ $student->full_name }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $student->nisn }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $student->entryAcademicYear?->name ?? 'Belum tercatat' }}</td>
                            <td class="px-3 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $student->academic_status === 'ACTIVE' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">{{ $student->academic_status }}</span></td>
                            <td class="px-3 py-3">{{ $student->user?->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    @if($student->academic_status === 'ACTIVE')
                                        <button type="button" wire:click="openActionModal('STATUS', '{{ $student->id }}', null, 'INACTIVE')" class="rounded-md border border-amber-200 px-2 py-1 text-xs font-semibold text-amber-800">Nonaktifkan</button>
                                        <button type="button" wire:click="openActionModal('STATUS', '{{ $student->id }}', null, 'ARCHIVED')" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700">Arsipkan</button>
                                    @elseif($student->academic_status === 'INACTIVE')
                                        <button type="button" wire:click="openActionModal('STATUS', '{{ $student->id }}', null, 'ACTIVE')" class="rounded-md border border-emerald-200 px-2 py-1 text-xs font-semibold text-emerald-800">Aktifkan</button>
                                        <button type="button" wire:click="openActionModal('STATUS', '{{ $student->id }}', null, 'ARCHIVED')" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700">Arsipkan</button>
                                    @elseif($student->academic_status === 'GRADUATED')
                                        <button type="button" wire:click="openActionModal('STATUS', '{{ $student->id }}', null, 'ALUMNI')" class="rounded-md border border-indigo-200 px-2 py-1 text-xs font-semibold text-indigo-800">Tandai Alumni</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-8 text-center text-slate-500">Belum ada data siswa sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $students->links() }}</div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900">Penempatan Rombel & Riwayat per Tahun</h2>
        <p class="mt-1 text-sm text-slate-600">Penempatan tahun sebelumnya tetap disimpan. Mutasi mengganti penempatan pada tahun yang sama; naik kelas/tinggal kelas menambahkan penempatan pada tahun aktif berikutnya.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-3 py-3">Siswa</th><th class="px-3 py-3">Penempatan</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Tindakan</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($studentsWithMemberships as $student)
                        <tr>
                            <td class="px-3 py-3 align-top">
                                <div class="font-semibold text-slate-800">{{ $student->full_name }}</div>
                                <div class="text-xs text-slate-500">{{ $student->nisn }}</div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="space-y-2">
                                    @foreach($student->rombels as $rombel)
                                        <div class="rounded-lg border border-slate-200 p-2">
                                            <div class="font-semibold text-slate-800">{{ $rombel->academicYear?->name }} · {{ $rombel->gradeLevel?->name }} {{ $rombel->name }}</div>
                                            @if($rombel->trashed() || $rombel->status !== 'ACTIVE')
                                                <span class="mt-1 inline-block rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">Rombel diarsipkan / nonaktif</span>
                                            @endif
                                            <div class="mt-1 flex flex-wrap gap-1.5">
                                                @if($student->academic_status === 'ACTIVE' && !$rombel->trashed() && $rombel->status === 'ACTIVE' && $rombel->academicYear?->is_active)
                                                    <button type="button" wire:click="openActionModal('TRANSFER', '{{ $student->id }}', '{{ $rombel->id }}')" class="rounded-md border border-blue-200 px-2 py-1 text-xs font-semibold text-blue-800">Mutasi</button>
                                                    <button type="button" wire:click="openActionModal('REMOVE', '{{ $student->id }}', '{{ $rombel->id }}')" class="rounded-md border border-amber-200 px-2 py-1 text-xs font-semibold text-amber-800">Keluarkan</button>
                                                    @if($rombel->gradeLevel && (int) $rombel->gradeLevel->level === 6)
                                                        <button type="button" wire:click="openActionModal('GRADUATE', '{{ $student->id }}', '{{ $rombel->id }}')" class="rounded-md border border-indigo-200 px-2 py-1 text-xs font-semibold text-indigo-800">Luluskan</button>
                                                    @endif
                                                @elseif($student->academic_status === 'ACTIVE' && !$rombel->trashed() && $activeYear && $rombel->academicYear && !$rombel->academicYear->is_active && (int) substr($activeYear->name, 0, 4) === ((int) substr($rombel->academicYear->name, 0, 4) + 1))
                                                    <button type="button" wire:click="openActionModal('PROMOTE', '{{ $student->id }}', '{{ $rombel->id }}')" class="rounded-md border border-emerald-200 px-2 py-1 text-xs font-semibold text-emerald-800">Naik Kelas</button>
                                                    <button type="button" wire:click="openActionModal('RETAIN', '{{ $student->id }}', '{{ $rombel->id }}')" class="rounded-md border border-orange-200 px-2 py-1 text-xs font-semibold text-orange-800">Tinggal Kelas</button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-3 py-3 align-top">{{ $student->academic_status }}</td>
                            <td class="px-3 py-3 align-top">{{ $student->user?->is_active ? 'Akun aktif' : 'Akun nonaktif' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-8 text-center text-slate-500">Belum ada penempatan rombel.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $studentsWithMemberships->links() }}</div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900">Histori Akademik Siswa</h2>
        <p class="mt-1 text-sm text-slate-600">Riwayat ini bersifat append-only dan menyimpan aktor, tanggal efektif, rombel asal/tujuan, serta alasan.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-3 py-3">Tanggal</th><th class="px-3 py-3">Siswa</th><th class="px-3 py-3">Peristiwa</th><th class="px-3 py-3">Tahun</th><th class="px-3 py-3">Asal → Tujuan</th><th class="px-3 py-3">Alasan</th><th class="px-3 py-3">Petugas</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($history as $event)
                        <tr>
                            <td class="px-3 py-3 whitespace-nowrap">{{ $event->effective_date?->format('d/m/Y') }}</td>
                            <td class="px-3 py-3">{{ $event->student?->full_name ?? 'Siswa' }}</td>
                            <td class="px-3 py-3 font-semibold">{{ $event->event_type }}</td>
                            <td class="px-3 py-3">{{ $event->academicYear?->name ?? '—' }}</td>
                            <td class="px-3 py-3">{{ $event->fromRombel?->name ?? '—' }} → {{ $event->toRombel?->name ?? '—' }}</td>
                            <td class="px-3 py-3 min-w-64">{{ $event->reason }}</td>
                            <td class="px-3 py-3">{{ $event->actor?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">Belum ada histori akademik.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $history->links() }}</div>
    </section>

    @if($showLifecycleModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <section class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-lg font-bold text-slate-900">
                    @switch($actionType)
                        @case('TRANSFER') Mutasi Rombel @break
                        @case('REMOVE') Keluarkan dari Rombel @break
                        @case('PROMOTE') Kenaikan Kelas @break
                        @case('RETAIN') Tinggal Kelas @break
                        @case('GRADUATE') Kelulusan Siswa @break
                        @case('STATUS') Perubahan Status Akademik @break
                    @endswitch
                </h2>
                @if(in_array($actionType, ['TRANSFER', 'PROMOTE', 'RETAIN'], true))
                    <label class="mt-4 block text-sm font-semibold text-slate-700">Rombel tujuan
                        <select wire:model="actionToRombelId" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal">
                            <option value="">Pilih rombel tujuan pada tahun aktif</option>
                            @foreach($rombels as $rombel)
                                <option value="{{ $rombel->id }}">{{ $rombel->gradeLevel?->name }} — {{ $rombel->name }}</option>
                            @endforeach
                        </select>
                        @error('actionToRombelId')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                @elseif($actionType === 'STATUS')
                    <p class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">Status tujuan: <strong>{{ $targetStatus }}</strong></p>
                @endif
                <form wire:submit="processLifecycleAction" class="mt-4 space-y-4">
                    <label class="block text-sm font-semibold text-slate-700">Tanggal efektif
                        <input type="date" wire:model="actionEffectiveDate" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal">
                        @error('actionEffectiveDate')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm font-semibold text-slate-700">Alasan (wajib)
                        <textarea wire:model="actionReason" rows="3" minlength="10" maxlength="1000" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal" placeholder="Tuliskan dasar dan alasan tindakan..."></textarea>
                        @error('actionReason')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="closeLifecycleModal" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Konfirmasi</button>
                    </div>
                </form>
            </section>
        </div>
    @endif
</div>
