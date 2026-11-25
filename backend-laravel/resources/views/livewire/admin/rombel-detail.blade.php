<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="/admin/academic" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                <span aria-hidden="true">&larr;</span> Kembali ke Data Akademik
            </a>
            <h1 class="mt-3 text-2xl font-bold text-navy-900">Rombel {{ $rombel->name }}</h1>
            <p class="mt-1 text-sm text-textSecondary">
                {{ $rombel->gradeLevel?->name ?? 'Tingkat kelas belum ditentukan' }}
                · Fase {{ $rombel->gradeLevel?->phase?->name ?? '—' }}
                · Tahun ajaran {{ $rombel->academicYear?->name ?? '—' }}
            </p>
        </div>
        <a href="/admin/rombel-students" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">
            Kelola Penempatan Siswa
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-textSecondary">Jumlah Siswa di Rombel</p>
            <p class="mt-2 text-3xl font-bold text-navy-900">{{ number_format($rombel->students_count) }}</p>
        </div>
        <div class="rounded-2xl border border-cardBorder bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-textSecondary">Status Rombel</p>
            <p class="mt-2">
                <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-bold text-emerald-700">
                    {{ $rombel->status }}
                </span>
            </p>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-cardBorder bg-white shadow-sm">
        <div class="border-b border-cardBorder p-5">
            <h2 class="text-lg font-bold text-navy-900">Daftar Siswa</h2>
            <p class="mt-1 text-sm text-textSecondary">Siswa yang terdaftar pada rombel ini untuk tahun ajaran terkait.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-cardBorder">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-textSecondary">No.</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-textSecondary">Nama Siswa</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-textSecondary">NIS</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-textSecondary">NISN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cardBorder">
                    @forelse($students as $index => $student)
                        <tr wire:key="rombel-student-{{ $student->id }}" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-5 py-3 text-sm text-textSecondary">{{ $students->firstItem() + $index }}</td>
                            <td class="px-5 py-3 text-sm font-semibold text-navy-900">{{ $student->full_name }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-sm text-textSecondary">{{ $student->nis ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-sm text-textSecondary">
                                {{ $student->nisn ? str_repeat('•', max(0, strlen($student->nisn) - 4)) . substr($student->nisn, -4) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-sm text-textSecondary">
                                Belum ada siswa yang ditempatkan pada rombel ini.
                                <div class="mt-3">
                                    <a href="/admin/rombel-students" class="font-semibold text-emerald-700 hover:underline">Buka penempatan siswa</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="border-t border-cardBorder p-4">{{ $students->links() }}</div>
        @endif
    </section>
</div>
