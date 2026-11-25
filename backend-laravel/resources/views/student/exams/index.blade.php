<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ujian Saya - CBT Madrasah</title>
    <link rel="stylesheet" href="{{ asset('css/student-portal.css') }}">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <header class="bg-slate-900 text-white shadow">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
            <div><div class="text-xs uppercase tracking-widest text-slate-300">CBT Madrasah</div><h1 class="text-xl font-bold">Ujian Saya</h1></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-slate-500 px-3 py-2 text-sm">Keluar</button></form>
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-8">
        <section class="mb-6 rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Selamat datang,</p>
            <h2 dir="auto" class="text-2xl font-bold">{{ $student->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">Pilih ujian yang ditugaskan kepada Anda. Jadwal resmi mengikuti waktu server madrasah.</p>
        </section>

        @if (session('status'))
            <div class="mb-4 rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('status') }}</div>
        @endif

        @if ($examRows->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
                <h3 class="font-semibold">Belum ada ujian yang ditugaskan</h3>
                <p class="mt-2 text-sm text-slate-500">Jika menurut Guru Anda sudah mendapat tugas ujian, silakan hubungi Guru atau Admin Madrasah.</p>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($examRows as $row)
                    <article class="rounded-2xl bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <h3 dir="auto" class="text-lg font-bold">{{ $row['exam']->title }}</h3>
                            <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold">{{ $row['statusLabel'] }}</span>
                        </div>
                        <dl class="mt-4 space-y-2 text-sm text-slate-600">
                            <div class="flex justify-between gap-3"><dt>Mulai</dt><dd class="text-right">{{ optional($row['exam']->start_at)->format('d/m/Y H:i') }}</dd></div>
                            <div class="flex justify-between gap-3"><dt>Selesai</dt><dd class="text-right">{{ optional($row['exam']->end_at)->format('d/m/Y H:i') }}</dd></div>
                            <div class="flex justify-between gap-3"><dt>Durasi</dt><dd>{{ ceil($row['exam']->duration_seconds / 60) }} menit</dd></div>
                            <div class="flex justify-between gap-3"><dt>Percobaan</dt><dd>{{ $row['attemptCount'] }} / {{ $row['attemptLimit'] }}</dd></div>
                        </dl>
                        <div class="mt-5">
                            @if ($row['canOpen'])
                                <a href="{{ route('student.exams.show', $row['exam']->id) }}" class="inline-flex w-full justify-center rounded-xl bg-blue-700 px-4 py-3 font-semibold text-white hover:bg-blue-800">{{ $row['activeAttempt'] ? 'Lanjutkan Ujian' : 'Buka Ujian' }}</a>
                            @elseif ($row['resultAvailable'] && $row['latestAttempt'])
                                <a href="{{ route('student.results.show', $row['latestAttempt']->id) }}" class="inline-flex w-full justify-center rounded-xl bg-emerald-700 px-4 py-3 font-semibold text-white hover:bg-emerald-800">Lihat Hasil Ujian</a>
                            @else
                                <button disabled class="w-full cursor-not-allowed rounded-xl bg-slate-200 px-4 py-3 font-semibold text-slate-500">Belum dapat dikerjakan</button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </main>
</body>
</html>
