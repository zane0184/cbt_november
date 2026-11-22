<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Administrasi Akademik</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Penugasan Guru Mata Pelajaran</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600">Atur hubungan Guru, mata pelajaran, rombel, tahun ajaran, dan semester. Penugasan aktif menjadi dasar lingkup kewenangan akademik Guru. Perubahan tidak menghapus riwayat.</p>
        </div>
        <button type="button" wire:click="openCreate" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">
            + Tambah Penugasan
        </button>
    </div>

    @if (session()->has('teacherAssignmentMessage'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
            {{ session('teacherAssignmentMessage') }}
        </div>
    @endif

    @if ($showForm)
        <section class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">{{ $editingAssignmentId ? 'Ubah Penugasan Guru' : 'Penugasan Guru Baru' }}</h2>
                    <p class="text-sm text-slate-500">Semua referensi dibatasi pada tenant madrasah aktif.</p>
                </div>
                <button type="button" wire:click="cancelForm" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-600">Tutup</button>
            </div>

            <form wire:submit="save" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <label class="block text-sm font-semibold text-slate-700">Guru
                    <select wire:model="teacherProfileId" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 font-normal">
                        <option value="">Pilih Guru</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->full_name }}</option>
                        @endforeach
                    </select>
                    @error('teacherProfileId')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="block text-sm font-semibold text-slate-700">Mata Pelajaran
                    <select wire:model="subjectId" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 font-normal">
                        <option value="">Pilih Mata Pelajaran</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->code }})</option>
                        @endforeach
                    </select>
                    @error('subjectId')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="block text-sm font-semibold text-slate-700">Tahun Ajaran
                    <select wire:model.live="academicYearId" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 font-normal">
                        <option value="">Pilih Tahun Ajaran</option>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                    @error('academicYearId')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="block text-sm font-semibold text-slate-700">Semester
                    <select wire:model="semesterId" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 font-normal">
                        <option value="">Pilih Semester</option>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->id }}">{{ $semester->term }}</option>
                        @endforeach
                    </select>
                    @error('semesterId')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="block text-sm font-semibold text-slate-700">Rombel
                    <select wire:model="rombelId" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 font-normal">
                        <option value="">Pilih Rombel</option>
                        @foreach ($rombels as $rombel)
                            <option value="{{ $rombel->id }}">{{ $rombel->name }}</option>
                        @endforeach
                    </select>
                    @error('rombelId')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="block text-sm font-semibold text-slate-700">Tanggal Mulai
                    <input type="date" wire:model="startsAt" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal">
                    @error('startsAt')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="block text-sm font-semibold text-slate-700">Tanggal Berakhir
                    <input type="date" wire:model="endsAt" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal">
                    @error('endsAt')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="block text-sm font-semibold text-slate-700">Status
                    <select wire:model="assignmentStatus" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 font-normal">
                        <option value="ACTIVE">Aktif</option>
                        <option value="INACTIVE">Nonaktif</option>
                    </select>
                    @error('assignmentStatus')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                </label>

                <div class="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-3">
                    <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-50">
                        <span wire:loading.remove>{{ $editingAssignmentId ? 'Simpan Perubahan' : 'Simpan Penugasan' }}</span>
                        <span wire:loading>Memproses...</span>
                    </button>
                    <button type="button" wire:click="cancelForm" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button>
                </div>
            </form>
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Daftar Penugasan</h2>
                <p class="text-sm text-slate-500">Penugasan nonaktif tetap ditampilkan sebagai riwayat.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari Guru, mapel, rombel..." class="w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <select wire:model.live="statusFilter" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="ALL">Semua Status</option>
                    <option value="ACTIVE">Aktif</option>
                    <option value="INACTIVE">Nonaktif</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-3">Guru</th>
                        <th class="px-3 py-3">Mata Pelajaran</th>
                        <th class="px-3 py-3">Rombel</th>
                        <th class="px-3 py-3">Periode Akademik</th>
                        <th class="px-3 py-3">Masa Penugasan</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($assignments as $assignment)
                        <tr wire:key="teacher-assignment-{{ $assignment->id }}" class="align-top">
                            <td class="px-3 py-3 font-semibold text-slate-800">{{ $assignment->teacher?->full_name ?? 'Profil Guru tidak tersedia' }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ $assignment->subject?->name ?? '—' }}<span class="block text-xs text-slate-400">{{ $assignment->subject?->code }}</span></td>
                            <td class="px-3 py-3 text-slate-700">{{ $assignment->rombel?->name ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ $assignment->academicYear?->name ?? '—' }}<span class="block text-xs text-slate-400">{{ $assignment->semester?->term ?? '—' }}</span></td>
                            <td class="px-3 py-3 text-slate-700">{{ $assignment->starts_at?->format('d/m/Y') ?? 'Tanpa batas awal' }}<span class="block text-xs text-slate-400">{{ $assignment->ends_at?->format('d/m/Y') ?? 'Tanpa batas akhir' }}</span></td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $assignment->status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $assignment->status === 'ACTIVE' ? 'AKTIF' : 'NONAKTIF' }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="edit('{{ $assignment->id }}')" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</button>
                                    @if ($assignment->status === 'ACTIVE')
                                        <button type="button" wire:click="deactivate('{{ $assignment->id }}')" wire:confirm="Nonaktifkan penugasan ini? Riwayat tetap disimpan." class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Nonaktifkan</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-10 text-center text-slate-500">Belum ada penugasan Guru.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $assignments->links() }}</div>
    </section>
</div>
