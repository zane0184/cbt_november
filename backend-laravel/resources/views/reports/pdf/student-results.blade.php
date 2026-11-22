<!DOCTYPE html>
<html>
<head>
    <title>Student Results - {{ $student->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .info { margin-bottom: 20px; }
        .results-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .results-table th, .results-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .results-table th { bg-color: #f2f2f2; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 10px; }
    .signature { width: 100%; margin-top: 48px; page-break-inside: avoid; }
    .signature td { width: 50%; text-align: center; vertical-align: top; padding: 4px 18px; }
    .signature .sign-space { height: 64px; }
    .signature .sign-name { font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>
    @include('reports.pdf.header')

    <div class="info">
        <h2 style="text-align: center;">LAPORAN HASIL UJIAN SISWA</h2>
        <p><strong>Nama Siswa:</strong> {{ $student->name }}</p>
        <p><strong>NIS/NISN:</strong> {{ $student->studentProfile?->nis ?? '-' }} / {{ $student->studentProfile?->nisn ?? '-' }}</p>
        <p><strong>Rombel:</strong> {{ $student->studentProfile?->rombels->first()->name ?? '-' }}</p>
    </div>

    <table class="results-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Ujian</th>
                <th>Mata Pelajaran</th>
                <th width="10%">Skor</th>
                <th width="10%">Persen</th>
                <th width="15%">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $index => $attempt)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $attempt->exam->title }}</td>
                    <td>{{ $attempt->exam->assessment->subject->name ?? '-' }}</td>
                    <td>{{ $attempt->score ? $attempt->score->raw_score : 0 }} / {{ $attempt->score ? $attempt->score->max_score : 0 }}</td>
                    <td>{{ $attempt->score ? $attempt->score->percentage : 0 }}%</td>
                    <td>{{ $attempt->submitted_at?->format('d/m/Y') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($teacherName))
        <table class="signature">
            <tr>
                <td>Mengetahui,<br>Kepala Madrasah</td>
                <td>{{ $exam ?? null ? 'Guru Mata Pelajaran' : 'Guru' }}</td>
            </tr>
            <tr><td class="sign-space"></td><td class="sign-space"></td></tr>
            <tr>
                <td class="sign-name">{{ $principalName ?: '........................................' }}</td>
                <td class="sign-name">{{ $teacherName ?: '........................................' }}</td>
            </tr>
        </table>
    @endif

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
