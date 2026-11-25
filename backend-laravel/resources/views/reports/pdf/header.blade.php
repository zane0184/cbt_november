<style>
    .kop-container {
        width: 100%;
        margin-bottom: 20px;
        border-bottom: 3px double #000;
        padding-bottom: 8px;
    }
    .kop-table {
        width: 100%;
        border-collapse: collapse;
        border: none;
    }
    .kop-table td {
        border: none;
        padding: 0;
        vertical-align: middle;
    }
    .kop-logo {
        text-align: center;
    }
    .kop-logo img {
        max-height: 70px;
        max-width: 80px;
        object-fit: contain;
    }
    .kop-text {
        text-align: center;
    }
    .kop-institution {
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
    }
    .kop-name {
        font-size: 15px;
        font-weight: bold;
        text-transform: uppercase;
        margin: 2px 0;
    }
    .kop-address {
        font-size: 10px;
        margin: 2px 0;
    }
    .kop-meta {
        font-size: 9px;
        margin-top: 2px;
        color: #333;
    }
</style>

<div class="kop-container">
    <table class="kop-table">
        <tr>
            @if(!empty($logoDataUri))
                <td class="kop-logo" width="15%">
                    <img src="{{ $logoDataUri }}" alt="Logo">
                </td>
            @endif
            <td class="kop-text" width="{{ !empty($logoDataUri) ? '85%' : '100%' }}">
                <div class="kop-institution">KEMENTERIAN AGAMA REPUBLIK INDONESIA</div>
                <div class="kop-name">{{ $tenant->name ?? 'MADRASAH' }}</div>
                @if(!empty($tenant->address))
                    <div class="kop-address">{{ $tenant->address }}</div>
                @endif
                <div class="kop-meta">
                    @php
                        $metaParts = [];
                        if (!empty($tenant->nsm)) { $metaParts[] = 'NSM: ' . $tenant->nsm; }
                        if (!empty($tenant->npsn)) { $metaParts[] = 'NPSN: ' . $tenant->npsn; }
                        if (!empty($tenant->phone)) { $metaParts[] = 'Telp: ' . $tenant->phone; }
                        if (!empty($tenant->email)) { $metaParts[] = 'Email: ' . $tenant->email; }
                    @endphp
                    {{ implode(' | ', $metaParts) }}
                </div>
            </td>
        </tr>
    </table>
</div>
