<div class="space-y-6">
    <section class="rounded-2xl border border-cardBorder bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-navy-900">Penugasan Mengajar Guru</h1>
        <p class="mt-1 text-sm text-textSecondary">Admin menetapkan Guru, mata pelajaran, rombel, periode akademik, dan versi kurikulum yang menjadi konteks kerja Guru.</p>
    </section>

    <section class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        <p class="font-semibold">Aturan penugasan</p>
        <p class="mt-1">Penugasan mengikat versi kurikulum dan pemetaan mata pelajaran sesuai fase rombel. Guru hanya dapat mengelola TP/ATP, bank soal, asesmen, dan ujian dalam lingkup penugasan yang aktif. Admin tidak mendapatkan hak mengelola soal, ujian, token, Attempt, jawaban, monitoring peserta, atau nilai.</p>
    </section>

    @if (session()->has('message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('message') }}</div>
    @endif

    <section class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-navy-900">Buat penugasan baru</h2>
        <form wire:submit.prevent="createAssignment" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-bold text-navy-900">Guru</label>
                <select wire:model="selectedTeacherProfileId" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                    <option value="">Pilih Guru...</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->full_name }} — {{ $teacher->user?->email ?? $teacher->user?->username }}</option>
                    @endforeach
                </select>
                @error('selectedTeacherProfileId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-navy-900">Mata Pelajaran</label>
                <select wire:model="selectedSubjectId" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                    <option value="">Pilih mata pelajaran...</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->code }})</option>
                    @endforeach
                </select>
                @error('selectedSubjectId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-navy-900">Rombel</label>
                <select wire:model="selectedRombelId" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
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
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                    @endforeach
                </select>
                @error('selectedSemesterId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-navy-900">Versi kurikulum terdaftar</label>
                <select wire:model="selectedRegistrationId" class="w-full rounded-xl border border-cardBorder px-3 py-2 text-sm">
                    <option value="">Pilih versi...</option>
                    @foreach($registrations as $registration)
                        <option value="{{ $registration->id }}">{{ $registration->version?->title }} ({{ $registration->version?->version_code }})</option>
                    @endforeach
                </select>
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
                <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800 disabled:opacity-50">
                    Simpan penugasan Guru
                </button>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-cardBorder bg-white shadow-sm">
        <div class="border-b border-cardBorder p-5">
            <h2 class="text-lg font-bold text-navy-900">Riwayat penugasan Guru</h2>
            <p class="mt-1 text-xs text-textSecondary">Versi kurikulum pada penugasan menjadi snapshot; perubahan registry berikutnya tidak mengubah konteks penugasan lama.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-cardBorder text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Guru</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Mapel / Rombel</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Periode</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Versi kurikulum</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-textSecondary">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cardBorder">
                    @forelse($assignments as $assignment)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-navy-900">{{ $assignment->teacherProfile?->full_name }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-navy-900">{{ $assignment->subject?->name }}</p>
                                <p class="text-xs text-textSecondary">{{ $assignment->rombel?->name }} · {{ $assignment->rombel?->gradeLevel?->name }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-textSecondary">
                                {{ $assignment->academicYear?->name }} · {{ $assignment->semester?->name }}
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
                        <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-textSecondary">Belum ada penugasan Guru.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-cardBorder p-4">{{ $assignments->links() }}</div>
    </section>
</div>
