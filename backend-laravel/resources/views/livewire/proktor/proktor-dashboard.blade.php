<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-50 text-amber-800 rounded-full text-xs font-bold border border-amber-200 mb-2">
                <span class="w-2 h-2 rounded-full bg-amber-600 animate-pulse"></span> Penugasan Operasional Proktor
            </div>
            <h1 class="text-2xl font-bold text-navy-900">Penugasan Proktor</h1>
            <p class="text-sm text-textSecondary">Daftar ujian yang ditugaskan kepada akun Proktor ini, sesuai masa berlaku penugasan.</p>
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
            <p class="text-xs font-bold text-textSecondary uppercase tracking-wider">Penugasan Aktif Saat Ini</p>
            <p class="text-3xl font-bold text-navy-900 mt-2">{{ $activeExams->count() }} Ujian</p>
            <p class="text-xs text-gray-500 mt-1">Hanya penugasan yang sedang berlaku</p>
        </div>
    </div>

    <!-- Active Exams List for Proctoring -->
    <div class="bg-white border border-cardBorder rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-cardBorder flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 class="font-bold text-navy-900 text-lg">Ujian yang Ditugaskan</h3>
                <p class="text-xs text-textSecondary">Monitoring hanya tersedia selama penugasan aktif.</p>
            </div>
        </div>
        <div class="divide-y divide-cardBorder">
            @forelse($assignments as $assignment)
                @php($exam = $assignment->exam)
                @if($exam)
                <div class="p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:bg-gray-50/80 transition">
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-navy-900 text-base">{{ $exam->title }}</h4>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ $assignment->isActiveAt(now()) ? 'PENUGASAN AKTIF' : 'AKAN DATANG' }}
                            </span>
                        </div>
                        <p class="text-xs text-textSecondary mt-1">
                            Mapel: <span class="font-bold text-navy-900">{{ $exam->subject->name ?? '-' }}</span> |
                            Durasi: <span class="font-bold text-navy-900">{{ $exam->duration_seconds / 60 }} Menit</span> |
                            Token: <span class="font-mono font-bold text-emerald-600">{{ $exam->token ?: 'Belum Rilis' }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        @if($assignment->isActiveAt(now()))
                            <a href="/exams/{{ $exam->id }}/monitoring" target="_blank" class="w-full md:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition shadow-sm text-center">
                                🖥️ Buka Monitor Realtime
                            </a>
                        @else
                            <span class="w-full md:w-auto px-5 py-2.5 bg-gray-100 text-gray-500 font-bold text-sm rounded-xl text-center">
                                Monitoring belum aktif
                            </span>
                        @endif
                    </div>
                </div>
                @endif
            @empty
                <div class="p-8 text-center text-textSecondary text-sm">Tidak ada penugasan Proktor yang aktif atau akan datang untuk akun ini.</div>
            @endforelse
        </div>
    </div>
</div>
