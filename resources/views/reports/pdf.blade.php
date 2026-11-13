<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan #{{ $report->id }}</title>
    <style>
        @page { size: A4; margin: 20mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #000; font-size: 11pt; line-height: 1.5; }
        h1 { font-size: 15pt; margin: 0 0 2mm; }
        .meta { font-size: 9pt; margin: 0 0 8mm; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 3mm; text-align: left; vertical-align: top; }
        th { width: 38mm; font-weight: normal; }
        .block { margin-top: 6mm; }
        .block h2 { font-size: 11pt; margin: 0 0 2mm; }
        .note { border: 1px solid #000; padding: 3mm; }
    </style>
</head>
<body>
    <h1>Lapak Satoe, Laporan #{{ $report->id }}</h1>
    <p class="meta">Dicetak {{ $printedAt->translatedFormat('d F Y H:i') }}</p>

    <table>
        <tr>
            <th>Objek terlapor</th>
            <td>{{ $report->targetLabel() }}</td>
        </tr>
        <tr>
            <th>Pemilik terlapor</th>
            <td>{{ '@'.$report->reportedUser?->username }}</td>
        </tr>
        <tr>
            <th>Pelapor</th>
            <td>{{ '@'.$report->reporter?->username }}</td>
        </tr>
        <tr>
            <th>Jenis pelanggaran</th>
            <td>{{ $violation }}</td>
        </tr>
        <tr>
            <th>Pesanan</th>
            <td>{{ $report->order?->code ?? 'Tidak terkait pesanan' }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>
                {{ $report->status->value }}@if ($report->restored_at !== null), dipulihkan {{ $report->restored_at->translatedFormat('d F Y H:i') }}@endif
            </td>
        </tr>
        <tr>
            <th>Ditangani oleh</th>
            <td>
                {{ $report->handler?->username ?? 'Belum ditangani' }}@if ($report->handled_at !== null), {{ $report->handled_at->translatedFormat('d F Y H:i') }}@endif
            </td>
        </tr>
        <tr>
            <th>Dilaporkan pada</th>
            <td>{{ $report->created_at?->translatedFormat('d F Y H:i') }}</td>
        </tr>
    </table>

    <div class="block">
        <h2>Deskripsi pelapor</h2>
        <p class="note">{{ $report->description }}</p>
    </div>

    @if ($report->reported_note !== null)
        <div class="block">
            <h2>Catatan untuk pemilik</h2>
            <p class="note">{{ $report->reported_note }}</p>
        </div>
    @endif

    @if ($report->reporter_note !== null)
        <div class="block">
            <h2>Catatan untuk pelapor</h2>
            <p class="note">{{ $report->reporter_note }}</p>
        </div>
    @endif
</body>
</html>
