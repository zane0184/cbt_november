<!DOCTYPE html>
<html>
<head>
    <title>Exam Summary - {{ $summary['exam_title'] }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .summary-box { margin-bottom: 30px; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table td { padding: 10px; border: 1px solid #ccc; }
        .label { font-weight: bold; width: 40%; bg-color: #f9f9f9; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 10px; }
    </style>
</head>
<body>
    @include('reports.pdf.header')

    <h2 style="text-align: center; margin-bottom: 15px;">RINGKASAN HASIL UJIAN</h2>

    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td class="label">Judul Ujian</td>
                <td>{{ $summary['exam_title'] }}</td>
            </tr>
            <tr>
                <td class="label">Total Peserta</td>
                <td>{{ $summary['participant_count'] }}</td>
            </tr>
            <tr>
                <td class="label">Total Percobaan (Attempt)</td>
                <td>{{ $summary['attempt_count'] }}</td>
            </tr>
            <tr>
                <td class="label">Telah Dinilai</td>
                <td>{{ $summary['total_scored'] }}</td>
            </tr>
            <tr>
                <td class="label">Status Final</td>
                <td>{{ $summary['finalized_count'] }}</td>
            </tr>
            <tr>
                <td class="label">Menunggu Koreksi Manual</td>
                <td>{{ $summary['pending_manual_count'] }}</td>
            </tr>
            <tr>
                <td class="label">Rata-rata Persentase</td>
                <td>{{ $summary['average_percentage'] }}%</td>
            </tr>
            <tr>
                <td class="label">Persentase Tertinggi</td>
                <td>{{ $summary['highest_percentage'] }}%</td>
            </tr>
            <tr>
                <td class="label">Persentase Terendah</td>
                <td>{{ $summary['lowest_percentage'] }}%</td>
            </tr>
            <tr>
                <td class="label">Rata-rata Skor</td>
                <td>{{ $summary['average_score'] }}</td>
            </tr>
            <tr>
                <td class="label">Skor Tertinggi</td>
                <td>{{ $summary['highest_score'] }}</td>
            </tr>
            <tr>
                <td class="label">Skor Terendah</td>
                <td>{{ $summary['lowest_score'] }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
