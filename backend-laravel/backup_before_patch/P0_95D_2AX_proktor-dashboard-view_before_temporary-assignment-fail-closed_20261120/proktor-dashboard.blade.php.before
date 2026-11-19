<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-50 text-amber-800 rounded-full text-xs font-bold border border-amber-200 mb-2">
                <span class="w-2 h-2 rounded-full bg-amber-600 animate-pulse"></span> Ruang Pengawasan & Proktor Ujian
            </div>
            <h1 class="text-2xl font-bold text-navy-900">Dashboard Proktor Ujian</h1>
            <p class="text-sm text-textSecondary">Pengawasan sesi ujian, rilis token, pemantauan status peserta, dan penanganan kendala teknis.</p>
        </div>
    </div>

    <!-- Live Proctoring Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-6 rounded-2xl border-l-4 border-emerald-600 border-y border-r border-cardBorder shadow-sm">
            <p class="text-xs font-bold text-textSecondary uppercase tracking-wider">Sedang Mengerjakan</p>
            <p class="text-3xl font-bold text-emerald-600 mt-2">{{ $inProgressAttempts }} Peserta</p>
            <p class="text-xs text-gray-500 mt-1">Peserta Aktif di Ruang Ujian</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border-l-4 border-indigo-600 border-y border-r border-cardBorder shadow-sm">
            <p class="text-xs font-bold text-textSecondary uppercase tracking-wider">Selesai Submit</p>
            <p class="text-3xl font-bold text-indigo-600 mt-2">{{ $submittedAttempts }} Peserta</p>
            <p class="text-xs text-gray-500 mt-1">Jawaban Telah Terkirim Ke Server</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border-l-4 border-navy-900 border-y border-r border-cardBorder shadow-sm">
            <p class="text-xs font-bold text-textSecondary uppercase tracking-wider">Ujian Aktif Hari Ini</p>
            <p class="text-3xl font-bold text-navy-900 mt-2">{{ $activeExams->count() }} Ujian</p>
            <p class="text-xs text-gray-500 mt-1">Sesi Siap / Sedang Berlangsung</p>
        </div>
    </div>

    <!-- Active Exams List for Proctoring -->
    <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-cardBorder flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 class="font-bold text-navy-900 text-lg">Sesi Ujian untuk Diuji & Diawasi</h3>
                <p class="text-xs text-textSecondary">Pilih ujian untuk membuka dashboard monitoring realtime dan memantau ruang ujian.</p>
            </div>
        </div>
        <div class="divide-y divide-cardBorder">
            @forelse($activeExams as $exam)
                <div class="p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:bg-gray-50/80 transition">
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-navy-900 text-base">{{ $exam->title }}</h4>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ $exam->status }}
                            </span>
                        </div>
                        <p class="text-xs text-textSecondary mt-1">
                            Mapel: <span class="font-bold text-navy-900">{{ $exam->subject->name ?? '-' }}</span> |
                            Durasi: <span class="font-bold text-navy-900">{{ $exam->duration_seconds / 60 }} Menit</span> |
                            Token: <span class="font-mono font-bold text-emerald-600">{{ $exam->token ?: 'Belum Rilis' }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <a href="/exams/{{ $exam->id }}/monitoring" target="_blank" class="w-full md:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition shadow-sm text-center">
                            🖥️ Buka Monitor Realtime
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-textSecondary text-sm">Tidak ada sesi ujian aktif saat ini.</div>
            @endforelse
        </div>
    </div>
</div>
