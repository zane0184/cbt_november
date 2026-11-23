<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hasil Ujian - CBT Madrasah</title>
    <link rel="stylesheet" href="{{ asset('css/student-portal.css') }}">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <header class="bg-slate-900 text-white shadow">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
            <div><div class="text-xs uppercase tracking-widest text-slate-300">CBT Madrasah</div><h1 class="text-xl font-bold">Hasil Ujian</h1></div>
            <a href="{{ route('student.exams.index') }}" class="rounded-lg border border-slate-500 px-3 py-2 text-sm">Kembali</a>
        </div>
    </header>
    <main class="mx-auto max-w-3xl px-4 py-8">
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <p dir="auto" class="text-sm text-slate-500">{{ $student->name }}</p>
            <h2 dir="auto" class="mt-1 text-2xl font-bold">{{ $exam->title }}</h2>
            <p dir="auto" class="mt-1 text-sm text-slate-500">{{ $exam->assessment?->subject?->name ?? 'Hasil asesmen' }}</p>
            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Nilai</p><p class="mt-1 text-2xl font-bold">{{ number_format((float) $score->percentage, 2, ',', '.') }}%</p></div>
                <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Skor</p><p class="mt-1 text-2xl font-bold">{{ number_format((float) $score->raw_score, 2, ',', '.') }} / {{ number_format((float) $score->max_score, 2, ',', '.') }}</p></div>
                <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Status</p><p class="mt-1 text-lg font-bold text-emerald-700">Final</p></div>
            </div>
            <dl class="mt-6 space-y-2 border-t border-slate-100 pt-4 text-sm text-slate-600">
                <div class="flex justify-between gap-4"><dt>Percobaan</dt><dd>{{ $attempt->attempt_number }}</dd></div>
                <div class="flex justify-between gap-4"><dt>Mulai</dt><dd>{{ optional($attempt->started_at)->format('d/m/Y H:i:s') ?? '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt>Dikumpulkan</dt><dd>{{ optional($attempt->submitted_at)->format('d/m/Y H:i:s') ?? '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt>Penilaian final</dt><dd>{{ optional($score->finalized_at)->format('d/m/Y H:i:s') ?? '-' }}</dd></div>
            </dl>
            <p class="mt-5 rounded-xl bg-blue-50 p-4 text-sm text-blue-800">Hasil ini merupakan nilai final yang telah dipublikasikan oleh Guru.</p>
        </section>
    </main>
</body>
</html>
