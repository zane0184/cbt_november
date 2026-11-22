<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white border border-cardBorder rounded-2xl p-6 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Analisis Hasil Ujian: {{ $exam->title }}</h1>
            <p class="text-xs text-textSecondary">Ringkasan statistik perolehan nilai dan distribusi hasil peserta</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('teacher.exams.results', ['exam' => $exam->id]) }}" class="bg-navy-900 hover:bg-navy-800 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm">Hasil Attempt</a>
            <a href="{{ route('teacher.exams.analytics.items', ['exam' => $exam->id]) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm">Analisis Butir Soal</a>
        </div>
    </div>

    @if($summary['participant_count'] > 0)
        <!-- Summary Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-6 rounded-2xl border border-cardBorder border-l-4 border-l-blue-500 shadow-sm">
                <div class="text-xs font-bold text-textSecondary uppercase tracking-wider">Peserta Difinalisasi</div>
                <div class="text-3xl font-bold text-navy-900 mt-2 font-mono">{{ $summary['participant_count'] }}</div>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-cardBorder border-l-4 border-l-emerald-600 shadow-sm">
                <div class="text-xs font-bold text-textSecondary uppercase tracking-wider">Rata-rata Nilai</div>
                <div class="text-3xl font-bold text-emerald-600 mt-2 font-mono">{{ $summary['average_percentage'] }}%</div>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-cardBorder border-l-4 border-l-purple-500 shadow-sm">
                <div class="text-xs font-bold text-textSecondary uppercase tracking-wider">Median (%)</div>
                <div class="text-3xl font-bold text-navy-900 mt-2 font-mono">{{ $summary['median_percentage'] }}%</div>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-cardBorder border-l-4 border-l-amber-500 shadow-sm">
                <div class="text-xs font-bold text-textSecondary uppercase tracking-wider">Std Deviasi</div>
                <div class="text-3xl font-bold text-navy-900 mt-2 font-mono">{{ $summary['standard_deviation_percentage'] }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white p-6 rounded-2xl border border-cardBorder border-l-4 border-l-emerald-600 shadow-sm">
                <div class="text-xs font-bold text-textSecondary uppercase tracking-wider">Nilai Tertinggi (%)</div>
                <div class="text-3xl font-bold text-emerald-600 mt-2 font-mono">{{ $summary['highest_percentage'] }}%</div>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-cardBorder border-l-4 border-l-red-500 shadow-sm">
                <div class="text-xs font-bold text-textSecondary uppercase tracking-wider">Nilai Terendah (%)</div>
                <div class="text-3xl font-bold text-red-600 mt-2 font-mono">{{ $summary['lowest_percentage'] }}%</div>
            </div>
        </div>

        <!-- Score Distribution Bars -->
        <div class="bg-white p-6 rounded-2xl border border-cardBorder shadow-sm">
            <h3 class="text-lg font-bold text-navy-900 mb-6">Distribusi Persebaran Nilai</h3>
            <div class="space-y-4">
                @foreach($distribution as $bucket)
                    <div>
                        <div class="flex justify-between text-xs font-bold mb-1.5">
                            <span class="text-navy-900">{{ $bucket['label'] }}</span>
                            <span class="text-textSecondary">{{ $bucket['count'] }} Peserta ({{ $bucket['percentage_of_participants'] }}%)</span>
                        </div>
                        <div class="w-full bg-contentBg rounded-full h-3 border border-cardBorder overflow-hidden">
                            <div class="bg-emerald-600 h-3 rounded-full transition-all duration-500" style="width: {{ $bucket['percentage_of_participants'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-amber-50 border-l-4 border-amber-500 p-6 rounded-2xl border border-amber-100 shadow-sm">
            <div class="flex items-start gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <h4 class="text-sm font-bold text-amber-900">Data Hasil Belum Tersedia</h4>
                    <p class="text-xs text-amber-800 mt-1">
                        Belum ada peserta dengan hasil yang telah difinalisasi. Pastikan ujian telah disubmit dan penilaian difinalisasi oleh guru/admin.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
