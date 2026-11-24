<div class="space-y-6">
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-navy-900">Keanggotaan Siswa & Rombel</h1>
        <p class="text-xs text-textSecondary mt-1">Daftarkan Siswa ke rombongan belajar pada tahun ajaran yang sesuai. Riwayat rombel sebelumnya tetap dipertahankan.</p>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 border-l-4 border-emerald-600 text-emerald-800 p-4 rounded-xl text-sm">{{ session('message') }}</div>
    @endif

    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm space-y-4">
        <h2 class="text-base font-bold text-navy-900">Tambahkan Siswa ke Rombel</h2>
        <form wire:submit.prevent="addStudentToRombel" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
                <label class="block text-xs font-bold mb-1">Siswa</label>
                <select wire:model="selectedStudentProfileId" required class="w-full border border-cardBorder rounded-xl px-3 py-2 text-sm">
                    <option value="">Pilih Siswa</option>
                    @foreach($availableStudents as $student)
                        <option value="{{ $student->id }}">{{ $student->full_name }} — NISN {{ $student->nisn ?: 'belum diisi' }}</option>
                    @endforeach
                </select>
                @error('selectedStudentProfileId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold mb-1">Rombel Aktif</label>
                <select wire:model="selectedRombelId" required class="w-full border border-cardBorder rounded-xl px-3 py-2 text-sm">
                    <option value="">Pilih Rombel</option>
                    @foreach($rombels as $rombel)
                        <option value="{{ $rombel->id }}">{{ $rombel->name }} — {{ $rombel->academicYear?->name ?? 'Tahun ajaran tidak diketahui' }}</option>
                    @endforeach
                </select>
                @error('selectedRombelId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-xl text-xs">Tambahkan Siswa</button>
        </form>
    </div>

    <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-cardBorder">
            <input wire:model.live="search" type="search" placeholder="Cari nama Siswa atau NISN..." class="w-full border border-cardBorder rounded-xl px-4 py-2 text-sm">
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-cardBorder">
                <thead class="bg-gray-50"><tr>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase">Siswa</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase">NISN</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase">Keanggotaan Rombel</th>
                </tr></thead>
                <tbody class="divide-y divide-cardBorder">
                    @forelse($students as $student)
                        <tr>
                            <td class="px-5 py-3 text-sm font-semibold">{{ $student->full_name }}</td>
                            <td class="px-5 py-3 text-xs">{{ $student->nisn ?: '—' }}</td>
                            <td class="px-5 py-3 text-xs">
                                @forelse($student->rombels as $rombel)
                                    <span class="inline-flex mr-1 mb-1 px-2 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-100">{{ $rombel->name }} · {{ $rombel->academicYear?->name ?? '—' }}</span>
                                @empty
                                    <span class="text-amber-700">Belum memiliki rombel</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-10 text-center text-sm text-textSecondary">Belum ada data Siswa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-cardBorder">{{ $students->links() }}</div>
    </div>
</div>
