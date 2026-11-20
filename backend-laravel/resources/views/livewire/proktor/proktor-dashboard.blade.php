<div class="space-y-6">
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-50 text-amber-800 rounded-full text-xs font-bold border border-amber-200 mb-3">Penugasan Operasional Sementara</div>
        <h1 class="text-2xl font-bold text-navy-900">Penugasan Proktor</h1>
        <p class="text-sm text-textSecondary mt-2">Proktor hanya dapat memantau ujian yang ditugaskan oleh Admin Madrasah selama masa penugasan aktif. Proktor tidak dapat mengelola token, soal, peserta, atau penilaian.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white border border-cardBorder rounded-xl p-5"><p class="text-xs text-textSecondary">Ujian dalam masa penugasan</p><p class="text-2xl font-bold text-navy-900 mt-1">{{ $allExams->count() }}</p></div>
        <div class="bg-white border border-cardBorder rounded-xl p-5"><p class="text-xs text-textSecondary">Attempt sedang berlangsung</p><p class="text-2xl font-bold text-navy-900 mt-1">{{ $inProgressAttempts }}</p></div>
        <div class="bg-white border border-cardBorder rounded-xl p-5"><p class="text-xs text-textSecondary">Attempt terkirim</p><p class="text-2xl font-bold text-navy-900 mt-1">{{ $submittedAttempts }}</p></div>
    </div>
    <div class="bg-white border border-cardBorder rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-bold text-navy-900 mb-4">Ujian yang ditugaskan</h2>
        @forelse ($assignments as $assignment)
            <div class="border border-cardBorder rounded-xl p-4 mb-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h3 class="font-bold text-navy-900">{{ $assignment->exam->title }}</h3>
                    <p class="text-xs text-textSecondary mt-1">Jadwal ujian: {{ $assignment->exam->start_at?->format('d/m/Y H:i') ?? 'Belum ditentukan' }} – {{ $assignment->exam->end_at?->format('d/m/Y H:i') ?? 'Belum ditentukan' }}</p>
                    <p class="text-xs text-textSecondary mt-1">Akses Proktor sampai {{ $assignment->expires_at?->format('d/m/Y H:i') }}</p>
                </div>
                <a href="/exams/{{ $assignment->exam_id }}/monitoring" class="inline-flex items-center justify-center rounded-lg bg-navy-900 text-white font-semibold px-4 py-2 hover:bg-navy-800">Buka monitoring</a>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-cardBorder p-8 text-center">
                <h3 class="font-bold text-navy-900">Belum ada penugasan aktif</h3>
                <p class="text-sm text-textSecondary mt-2">Daftar ujian akan muncul setelah Admin Madrasah memberikan penugasan yang masih berlaku.</p>
            </div>
        @endforelse
    </div>
</div>
