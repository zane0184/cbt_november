<!DOCTYPE html>
<html>
<head>
    <title>Exam Results - {{ $exam->title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .info { margin-bottom: 20px; }
        .info table { width: 100%; }
        .info td { padding: 2px 0; }
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
        <h2 style="text-align: center;">LAPORAN HASIL UJIAN</h2>
        <table>
            <tr>
                <td width="15%">Ujian</td>
                <td width="2%">:</td>
                <td>{{ $exam->title }}</td>
                <td width="15%">Mata Pelajaran</td>
                <td width="2%">:</td>
                <td>{{ $exam->assessment->subject->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Tahun Pelajaran</td>
                <td>:</td>
                <td>{{ $exam->assessment->academicYear->name ?? '-' }}</td>
                <td>Semester</td>
                <td>:</td>
                <td>{{ $exam->assessment->semester->term ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <table class="results-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama Siswa</th>
                <th>Rombel</th>
                <th width="10%">Skor</th>
                <th width="10%">Persen</th>
                <th width="15%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $index => $attempt)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $attempt->participant?->user?->name ?? '-' }}</td>
                    <td>{{ $attempt->participant?->user?->studentProfile?->rombels?->first()?->name ?? '-' }}</td>
                    <td>{{ $attempt->score ? $attempt->score->raw_score : 0 }} / {{ $attempt->score ? $attempt->score->max_score : 0 }}</td>
                    <td>{{ $attempt->score ? $attempt->score->percentage : 0 }}%</td>
                    <td>{{ $attempt->score ? $attempt->score->grading_status : $attempt->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($teacherName) || !empty($principalName))
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
