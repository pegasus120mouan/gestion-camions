<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de Sortie - {{ $fiche->matricule_vehicule }}</title>
    <style>
        @page {
            margin: 5mm;
            size: A4 portrait;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            color: #000;
        }
        .exemplaire {
            width: 100%;
            height: 140mm;
            padding: 8px 10px;
            border: 1px solid #ccc;
            margin-bottom: 4px;
            position: relative;
        }
        .header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px solid #2980b9;
            padding-bottom: 5px;
        }
        .company-name {
            font-size: 8px;
            color: #666;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 3px 0;
        }
        .doc-number {
            font-size: 11px;
            color: #2980b9;
            font-weight: bold;
        }
        .exemplaire-label {
            position: absolute;
            top: 5px;
            right: 10px;
            font-size: 8px;
            color: #999;
        }
        .two-cols {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .two-cols > tbody > tr > td {
            width: 50%;
            vertical-align: top;
            padding: 0 3px;
        }
        .section-title {
            background: #34495e;
            color: #fff;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 4px 8px;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        .info-table .label {
            background: #f5f5f5;
            width: 35%;
            color: #555;
        }
        .info-table .value {
            width: 65%;
        }
        .info-table .value.highlight {
            font-weight: bold;
        }
        .info-table .value.blue {
            color: #2980b9;
        }
        .info-table .value.orange {
            color: #e67e22;
        }
        .avances-section {
            margin-bottom: 8px;
        }
        .avances-title {
            background: #34495e;
            color: #fff;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }
        .avances-table {
            width: 100%;
            border-collapse: collapse;
        }
        .avances-table td {
            padding: 4px 8px;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        .avances-table .label {
            background: #f5f5f5;
            color: #555;
        }
        .avances-table .value {
            color: #27ae60;
            font-weight: bold;
        }
        .avances-table .total-label {
            text-align: right;
            background: #f5f5f5;
        }
        .avances-table .total-value {
            font-weight: bold;
            font-size: 11px;
        }
        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .signatures td {
            width: 33.33%;
            text-align: center;
            padding: 5px;
        }
        .signatures .title {
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .signatures .box {
            border: 1px dashed #ccc;
            height: 30px;
        }
        .footer {
            position: absolute;
            bottom: 5px;
            left: 10px;
            right: 10px;
            font-size: 7px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 3px;
        }
        .footer-left { float: left; }
        .footer-right { float: right; }
        .status-ok {
            display: inline-block;
            width: 12px;
            height: 12px;
            background: #27ae60;
            border-radius: 50%;
            color: #fff;
            text-align: center;
            line-height: 12px;
            font-size: 8px;
        }
            </style>
</head>
<body>
@for($i = 1; $i <= 2; $i++)
<div class="exemplaire">
    <span class="exemplaire-label">Exemplaire {{ $i }}</span>
    
    <div class="header">
        <div class="company-name">PGF Africa Scoops</div>
        <h1>FICHE DE SORTIE</h1>
        <div class="doc-number">N° {{ str_pad($fiche->id, 6, '0', STR_PAD_LEFT) }}</div>
    </div>

    <table class="two-cols">
        <tr>
            <td>
                <div class="section-title">VÉHICULE & TRANSPORT</div>
                <table class="info-table">
                    <tr>
                        <td class="label">Matricule</td>
                        <td class="value highlight">{{ $fiche->matricule_vehicule ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Date Chargement</td>
                        <td class="value">{{ $fiche->date_chargement ? $fiche->date_chargement->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Date Déchargement</td>
                        <td class="value">
                            {{ $fiche->date_dechargement ? $fiche->date_dechargement->format('d/m/Y') : '-' }}
                            @if($fiche->date_dechargement)<span class="status-ok">✓</span>@endif
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Poids (kg)</td>
                        <td class="value highlight">{{ $fiche->poids_pont ? number_format($fiche->poids_pont, 0, ',', ' ') : '-' }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <div class="section-title">DESTINATION & AGENT</div>
                <table class="info-table">
                    <tr>
                        <td class="label">Pont</td>
                        <td class="value blue">{{ $fiche->nom_pont ?? '-' }}@if($fiche->code_pont) ({{ $fiche->code_pont }})@endif</td>
                    </tr>
                    <tr>
                        <td class="label">Usine</td>
                        <td class="value">{{ $fiche->usine ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Agent</td>
                        <td class="value highlight orange">{{ $fiche->nom_agent ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">N° Agent</td>
                        <td class="value">{{ $fiche->numero_agent ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="avances-section">
        <div class="avances-title">AVANCES</div>
        <table class="avances-table">
            <tr>
                <td class="label">Carburant</td>
                <td class="value">{{ $fiche->carburant ? number_format($fiche->carburant, 0, ',', ' ') . ' FCFA' : '-' }}</td>
                <td class="label">Frais Route</td>
                <td class="value">{{ $fiche->frais_route ? number_format($fiche->frais_route, 0, ',', ' ') . ' FCFA' : '-' }}</td>
            </tr>
            @php $totalAvances = ($fiche->carburant ?? 0) + ($fiche->frais_route ?? 0); @endphp
            @if($totalAvances > 0)
            <tr>
                <td colspan="2" class="total-label">Total Avances</td>
                <td colspan="2" class="total-value">{{ number_format($totalAvances, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endif
        </table>
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="title">Chauffeur</div>
                <div class="box"></div>
            </td>
            <td>
                <div class="title">Agent</div>
                <div class="box"></div>
            </td>
            <td>
                <div class="title">Responsable</div>
                <div class="box"></div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <span class="footer-left">Imprimé le {{ $printedAt }} par {{ $printedBy }}</span>
        <span class="footer-right">Document N° {{ str_pad($fiche->id, 6, '0', STR_PAD_LEFT) }} | Exemplaire {{ $i }}</span>
    </div>
</div>
@endfor
</body>
</html>
