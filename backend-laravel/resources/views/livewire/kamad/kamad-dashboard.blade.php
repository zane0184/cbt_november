<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-bold border border-indigo-200 mb-2">
                <span class="w-2 h-2 rounded-full bg-indigo-600"></span> Executive View: Kepala Madrasah
            </div>
            <h1 class="text-2xl font-bold text-navy-900">Dashboard Eksekutif Kepala Madrasah</h1>
            <p class="text-sm text-textSecondary">Ringkasan pelaksanaan ujian dan hasil belajar dalam batas kewenangan baca saja.</p>
        </div>
        <div class="flex items-center gap-2 text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 px-3.5 py-2 rounded-xl">
            🔒 Mode Monitoring Eksekutif (Read-Only)
        </div>
    </div>

    <!-- Summary Cards Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-2xl border border-cardBorder shadow-sm">
            <p class="text-xs font-bold text-textSecondary uppercase tracking-wider">Total Ujian</p>
            <p class="text-3xl font-bold text-navy-900 mt-2">{{ $totalExams }}</p>
            <p class="text-xs text-emerald-600 mt-1 font-semibold">Aktif: {{ $activeExams }} | Selesai: {{ $completedExams }}</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-cardBorder shadow-sm">
            <p class="text-xs font-bold text-textSecondary uppercase tracking-wider">Total Partisipasi Ujian</p>
            <p class="text-3xl font-bold text-emerald-600 mt-2">{{ $totalAttempts }}</p>
            <p class="text-xs text-gray-500 mt-1 font-semibold">Lembar Jawaban Submitted</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-cardBorder shadow-sm">
            <p class="text-xs font-bold text-textSecondary uppercase tracking-wider">Ujian Berlangsung</p>
            <p class="text-3xl font-bold text-indigo-600 mt-2">{{ $activeExams }}</p>
            <p class="text-xs text-gray-500 mt-1 font-semibold">Sedang Diikuti Siswa</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-cardBorder shadow-sm">
            <p class="text-xs font-bold text-textSecondary uppercase tracking-wider">Hak Akses</p>
            <p class="text-3xl font-bold text-purple-600 mt-2">BACA SAJA</p>
            <p class="text-xs text-gray-500 mt-1 font-semibold">Tanpa hak mengubah ujian atau nilai</p>
        </div>
    </div>

    <!-- Exam Performance & Monitoring Table -->
    <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-cardBorder flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 class="font-bold text-navy-900 text-lg">Daftar Pelaksanaan Ujian Madrasah</h3>
                <p class="text-xs text-textSecondary">Pantau status, partisipasi, analisis item, dan cetak laporan resmi.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-textSecondary text-xs font-bold uppercase tracking-wider border-b border-cardBorder">
                        <th class="p-4">Nama Ujian</th>
                        <th class="p-4">Mata Pelajaran</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-center">Aksi Laporan & Monitoring</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cardBorder text-sm">
                    @forelse($exams as $exam)
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="p-4 font-bold text-navy-900">
                                {{ $exam->title }}
                                <div class="text-xs font-normal text-textSecondary">Durasi: {{ $exam->duration_seconds / 60 }} Menit</div>
                            </td>
                            <td class="p-4 text-textSecondary">
                                {{ $exam->subject->name ?? '-' }}
                            </td>
                            <td class="p-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold
                                    @if($exam->status === 'PUBLISHED') bg-emerald-50 text-emerald-700 border border-emerald-200
                                    @elseif($exam->status === 'CLOSED') bg-gray-100 text-gray-700 border border-gray-200
                                    @else bg-amber-50 text-amber-700 border border-amber-200 @endif">
                                    {{ $exam->status }}
                                </span>
                            </td>
                            <td class="p-4 text-center space-x-2">
                                <a href="/exams/{{ $exam->id }}/monitoring" target="_blank" class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs rounded-lg border border-emerald-200 transition inline-flex items-center gap-1">
                                    <span>👁️</span> Monitoring Realtime
                                </a>
                                <a href="/exams/{{ $exam->id }}/analytics" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs rounded-lg border border-indigo-200 transition inline-flex items-center gap-1">
                                    <span>📊</span> Analisis Hasil
                                </a>
                                <a href="/exams/{{ $exam->id }}/export/pdf" class="px-3 py-1.5 bg-navy-900 hover:bg-navy-800 text-white font-bold text-xs rounded-lg transition inline-flex items-center gap-1">
                                    <span>📄</span> Export PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-textSecondary text-sm">Belum ada ujian yang terdaftar di sistem.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
