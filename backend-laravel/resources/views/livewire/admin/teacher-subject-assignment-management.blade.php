<div class="space-y-6">
    <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm">
        @if($isTeacher)
            <h1 class="text-2xl font-bold text-navy-900">Kelas Saya</h1>
            <p class="mt-1 text-sm text-textSecondary">Pilih sendiri mata pelajaran dan rombel yang Anda ampu. Sistem memvalidasi kesesuaian akademik dan mencatat setiap penugasan.</p>
        @else
            <h1 class="text-2xl font-bold text-navy-900">Monitoring Kelas & Pengampu</h1>
            <p class="mt-1 text-sm text-textSecondary">Pantau kelas dan penugasan yang dibuat Guru melalui akun masing-masing.</p>
        @endif
    </section>

    @if($isTeacher)
        <section class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">Aturan pembuatan kelas</p>
            <p class="mt-1">Pilih satu rombel dan satu atau beberapa mata pelajaran yang benar-benar Anda ampu. Versi kurikulum mengikuti registrasi aktif madrasah. Tidak diperlukan persetujuan Admin.</p>
        </section>
    @else
        <section class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
            <p class="font-semibold">Monitoring Admin</p>
            <p class="mt-1">Guru membuat sendiri kelas dan memilih mapel–rombel. Admin mengelola data induk akademik serta memantau hasil penetapan Guru dari halaman ini.</p>
        </section>
    @endif

    @if (session()->has('message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('message') }}</div>
    @endif

    @if($isTeacher)
    <section class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-navy-900">Buat kelas / tambah mata pelajaran</h2>
        <p class="mt-1 text-sm text-textSecondary">Pilih satu rombel untuk setiap pengiriman. Anda dapat memilih banyak mata pelajaran sekaligus agar tidak perlu mengulang formulir untuk setiap mapel.</p>
        <form wire:submit.prevent="createBulkAssignments" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-xl border border-cardBorder bg-gray-50 px-3 py-3">
                <p class="text-xs font-bold text-textSecondary">AKUN GURU</p>
                <p class="mt-1 font-semibold text-navy-900">{{ $teachers->first()?->full_name ?? auth()->user()->name }}</p>
                <p class="mt-1 text-xs text-textSecondary">Identitas Guru diambil otomatis dari akun yang sedang login.</p>
            </div>
            <div class="xl:row-span-2">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <label class="block text-xs font-bold text-navy-900">Mata Pelajaran (bisa pilih banyak)</label>
                    <span class="text-xs text-textSecondary">{{ count($selectedSubjectIds) }} dipilih</span>
                </div>
                <div class="max-h-64 space-y-2 overflow-y-auto rounded-xl border border-cardBorder bg-gray-50 p-3">
                    @foreach($subjects as $subject)
                        <label wire:key="bulk-subject-{{ $subject->id }}" class="flex cursor-pointer items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm hover:bg-emerald-50">
                            <input type="checkbox" wire:model="selectedSubjectIds" value="{{ $subject->id }}" class="rounded border-cardBorder text-emerald-700 focus:ring-emerald-600">
                            <span class="font-medium text-navy-900">{{ $subject->name }}</span>
                            <span class="ml-auto text-xs text-textSecondary">{{ $subject->subject_category === 'MULOK' ? 'Mulok' : 'Wajib' }}</span>
                        </label>
                    @endforeach
                </div>
                @error('selectedSubjectIds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('selectedSubjectIds.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-navy-900">Rombel</label>
                <select wire:model.live="selectedRombelId" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                    <option value="">Pilih rombel...</option>
                    @foreach($rombels as $rombel)
                        <option value="{{ $rombel->id }}">{{ $rombel->name }} — {{ $rombel->gradeLevel?->name }} / Fase {{ $rombel->gradeLevel?->phase?->name }}</option>
                    @endforeach
                </select>
                @error('selectedRombelId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-navy-900">Semester</label>
                <select wire:model="selectedSemesterId" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                    <option value="">Pilih semester...</option>
                    @forelse($semesters as $semester)
                        <option value="{{ $semester->id }}">Semester {{ $semester->term === 'GANJIL' ? 'Ganjil' : 'Genap' }} — {{ $semester->academicYear?->name ?? 'Tahun ajaran tidak tersedia' }}</option>
                    @empty
                        <option value="" disabled>Belum ada semester untuk tahun ajaran rombel ini</option>
                    @endforelse
                </select>
                @error('selectedSemesterId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @if($semesters->isEmpty()) <p class="mt-1 text-xs text-amber-700">Periksa Data Akademik & Rombel: tahun ajaran rombel harus memiliki Semester Ganjil dan Genap.</p> @endif
            </div>
            <div class="rounded-xl border border-cardBorder bg-gray-50 px-3 py-3">
                <p class="text-xs font-bold text-textSecondary">VERSI KURIKULUM OTOMATIS</p>
                @if($registrations->count() === 1)
                    <p class="mt-1 font-semibold text-navy-900">{{ $registrations->first()->version?->title }} ({{ $registrations->first()->version?->version_code }})</p>
                    <p class="mt-1 text-xs text-textSecondary">Diambil dari registrasi aktif madrasah.</p>
                @else
                    <p class="mt-1 text-sm font-semibold text-amber-800">Registry perlu diperiksa Admin</p>
                    <p class="mt-1 text-xs text-textSecondary">Pembuatan kelas dihentikan sampai tepat satu versi kurikulum aktif tersedia.</p>
                @endif
                @error('selectedRegistrationId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-navy-900">Mulai</label>
                    <input type="date" wire:model="startsAt" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                    @error('startsAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-navy-900">Selesai</label>
                    <input type="date" wire:model="endsAt" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                    @error('endsAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-end xl:col-span-3">
                <button type="submit" wire:loading.attr="disabled" @disabled($registrations->count() !== 1) class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800 disabled:opacity-50">
                    Buat {{ count($selectedSubjectIds) }} kelas/mapel
                </button>
            </div>
        </form>
    </section>
    @endif

    <section class="overflow-hidden rounded-2xl border border-cardBorder bg-white shadow-sm">
        <div class="border-b border-cardBorder p-5">
            <h2 class="text-lg font-bold text-navy-900">Riwayat penugasan Guru</h2>
            <p class="mt-1 text-xs text-textSecondary">Versi kurikulum pada penugasan menjadi snapshot; perubahan registry berikutnya tidak mengubah konteks penugasan lama.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-cardBorder text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        @unless($isTeacher)<th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Guru</th>@endunless
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Mapel / Rombel</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Periode</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Versi kurikulum</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cardBorder">
                    @forelse($assignments as $assignment)
                        <tr>
                            @unless($isTeacher)<td class="px-4 py-3 font-semibold text-navy-900">{{ $assignment->teacher?->full_name }}</td>@endunless
                            <td class="px-4 py-3">
                                <p class="font-semibold text-navy-900">{{ $assignment->subject?->name }}</p>
                                <p class="text-xs text-textSecondary">{{ $assignment->rombel?->name }} · {{ $assignment->rombel?->gradeLevel?->name }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-textSecondary">
                                {{ $assignment->academicYear?->name }} · Semester {{ $assignment->semester?->term === 'GANJIL' ? 'Ganjil' : ($assignment->semester?->term === 'GENAP' ? 'Genap' : '—') }}
                                <p>{{ $assignment->starts_at?->format('d/m/Y') }} – {{ $assignment->ends_at?->format('d/m/Y') }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-textSecondary">
                                {{ $assignment->curriculumVersion?->version_code ?? 'Belum dipatok' }}
                                @if($assignment->curriculum_context_snapshot)
                                    <p>{{ $assignment->curriculum_context_snapshot['phase']['title'] ?? '-' }} · {{ $assignment->curriculum_context_snapshot['subject']['official_name'] ?? '-' }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $assignment->status === 'ACTIVE' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-gray-200 bg-gray-50 text-gray-700' }}">{{ $assignment->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isTeacher ? 4 : 5 }}" class="px-4 py-10 text-center text-sm text-textSecondary">
                            @if($isTeacher)Belum ada kelas. Pilih rombel dan mata pelajaran yang Anda ampu di formulir di atas.
                            @elseBelum ada kelas yang dibuat Guru.
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-cardBorder p-4">{{ $assignments->links() }}</div>
    </section>
    @unless($isTeacher)
        <p class="text-xs text-textSecondary">Halaman ini bersifat monitoring. Guru tetap menjadi pihak yang membuat kelas dan menentukan mata pelajaran yang diampu.</p>
    @endunless
</div>
