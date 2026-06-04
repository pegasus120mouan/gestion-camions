<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau de déchargement - {{ $ligne['numero_ticket'] ?? '' }}</title>
    <style>
        @page {
            margin: 18mm 22mm;
            size: A4 portrait;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #000;
            padding: 4mm 6mm;
        }
        .page-content {
            max-width: 100%;
            margin: 0 auto;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .header-table td {
            vertical-align: top;
            border: none;
        }
        .logo-cell { width: 90px; }
        .logo {
            max-width: 80px;
            max-height: 80px;
        }
        .company-block { text-align: center; }
        .company-name {
            color: #1a5fb4;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .company-tagline {
            color: #444;
            font-size: 10px;
            margin-top: 4px;
        }
        .doc-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            margin: 20px 0 16px;
            text-transform: uppercase;
        }
        .meta {
            margin: 0 8mm 18px;
            line-height: 1.7;
        }
        .meta strong { font-weight: bold; }
        .usine-title {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin: 12px 8mm 10px;
            text-transform: uppercase;
        }
        .table-wrap {
            margin: 0 8mm 20px;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th,
        table.data td {
            border: 1px solid #333;
            padding: 7px 10px;
            font-size: 10px;
        }
        table.data th {
            background: #e8e8e8;
            font-weight: bold;
            text-align: center;
        }
        table.data td.text-right { text-align: right; }
        table.data td.text-center { text-align: center; }
        .summary td {
            background: #f0f0f0;
            font-weight: bold;
        }
        .footer {
            margin: 36px 8mm 0 0;
            text-align: right;
            line-height: 1.7;
            font-size: 11px;
        }
        .footer .signature {
            font-weight: bold;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <div class="page-content">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($logoPath && file_exists($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo" class="logo">
                    @endif
                </td>
                <td class="company-block">
                    <div class="company-name">PGF</div>
                    <div class="company-tagline">Gestion des tickets et déchargements</div>
                </td>
                <td style="width: 90px;"></td>
            </tr>
        </table>

        <div class="doc-title">Bordereau de déchargement</div>

        <div class="meta">
            <div><strong>CHARGE DE MISSION :</strong> {{ $chargeMission }}</div>
            <div><strong>Période du :</strong> {{ $periodeDebut }} au {{ $periodeFin }}</div>
        </div>

        <div class="usine-title">{{ $nomUsine }}</div>

        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Date Réception</th>
                        <th>Date Ticket</th>
                        <th>Véhicule</th>
                        <th>N° Ticket</th>
                        <th>Poids (kg)</th>
                        <th>Montant (FCFA)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center">{{ $ligne['date_reception'] }}</td>
                        <td class="text-center">{{ $ligne['date_ticket'] }}</td>
                        <td class="text-center">{{ $ligne['vehicule'] }}</td>
                        <td>{{ $ligne['numero_ticket'] }}</td>
                        <td class="text-right">{{ $ligne['poids'] }}</td>
                        <td class="text-right">{{ $ligne['montant'] }}</td>
                    </tr>
                    <tr class="summary">
                        <td colspan="4">Sous-total {{ $nomUsine }} (1 ticket)</td>
                        <td class="text-right">{{ $ligne['poids'] }}</td>
                        <td class="text-right">{{ $ligne['montant'] }}</td>
                    </tr>
                    <tr class="summary">
                        <td colspan="4">TOTAL GÉNÉRAL (1 ticket)</td>
                        <td class="text-right">{{ $ligne['poids'] }}</td>
                        <td class="text-right">{{ $ligne['montant'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <div>Fait à {{ $lieu }}, le {{ $dateDocument }}</div>
            <div class="signature">PGF</div>
        </div>
    </div>
</body>
</html>
