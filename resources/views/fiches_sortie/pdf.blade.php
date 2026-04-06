<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de Sortie - {{ $fiche->matricule_vehicule }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }
        .page {
            width: 100%;
            height: 100%;
        }
        .exemplaire {
            width: 100%;
            height: 48%;
            padding: 15px;
            border: 2px solid #333;
            margin-bottom: 10px;
            position: relative;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 12px;
            color: #666;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-grid td {
            padding: 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        .info-grid .label {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 30%;
            color: #555;
        }
        .info-grid .value {
            width: 70%;
        }
        .section-title {
            background-color: #333;
            color: white;
            padding: 5px 10px;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 12px;
        }
        .two-columns {
            width: 100%;
        }
        .two-columns td {
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .signature-section {
            margin-top: 15px;
            width: 100%;
        }
        .signature-section td {
            width: 33%;
            text-align: center;
            padding: 10px;
            vertical-align: top;
        }
        .signature-box {
            border: 1px dashed #999;
            height: 40px;
            margin-top: 5px;
        }
        .footer {
            position: absolute;
            bottom: 10px;
            left: 15px;
            right: 15px;
            font-size: 9px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }
        .footer-left {
            float: left;
        }
        .footer-right {
            float: right;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .exemplaire-label {
            position: absolute;
            top: 5px;
            right: 10px;
            font-size: 9px;
            color: #999;
            font-style: italic;
        }
        .highlight {
            background-color: #fffde7;
        }
    </style>
</head>
<body>
    <div class="page">
        @for($i = 1; $i <= 2; $i++)
        <div class="exemplaire">
            <span class="exemplaire-label">Exemplaire {{ $i }}/2</span>
            
            <div class="header">
                <h1>Fiche de Sortie</h1>
                <div class="subtitle">N° {{ str_pad($fiche->id, 6, '0', STR_PAD_LEFT) }}</div>
            </div>

            <table class="two-columns">
                <tr>
                    <td>
                        <div class="section-title">VÉHICULE & TRANSPORT</div>
                        <table class="info-grid">
                            <tr>
                                <td class="label">Matricule</td>
                                <td class="value"><strong>{{ $fiche->matricule_vehicule ?? '-' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="label">Date Chargement</td>
                                <td class="value">{{ $fiche->date_chargement ? $fiche->date_chargement->format('d/m/Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Date Déchargement</td>
                                <td class="value">
                                    @if($fiche->date_dechargement)
                                        {{ $fiche->date_dechargement->format('d/m/Y') }}
                                    @else
                                        <span class="badge badge-warning">Non déchargé</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="label">Poids (kg)</td>
                                <td class="value highlight"><strong>{{ $fiche->poids_pont ? number_format($fiche->poids_pont, 0, ',', ' ') : '-' }}</strong></td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <div class="section-title">DESTINATION & AGENT</div>
                        <table class="info-grid">
                            <tr>
                                <td class="label">Pont</td>
                                <td class="value">{{ $fiche->nom_pont ?? '-' }} @if($fiche->code_pont)({{ $fiche->code_pont }})@endif</td>
                            </tr>
                            <tr>
                                <td class="label">Usine</td>
                                <td class="value">{{ $fiche->usine ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Agent</td>
                                <td class="value">{{ $fiche->nom_agent ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">N° Agent</td>
                                <td class="value">{{ $fiche->numero_agent ?? '-' }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            @if($fiche->carburant || $fiche->frais_route)
            <div class="section-title">AVANCES</div>
            <table class="info-grid">
                <tr>
                    <td class="label">Carburant</td>
                    <td class="value">{{ $fiche->carburant ? number_format($fiche->carburant, 0, ',', ' ') . ' FCFA' : '-' }}</td>
                    <td class="label">Frais Route</td>
                    <td class="value">{{ $fiche->frais_route ? number_format($fiche->frais_route, 0, ',', ' ') . ' FCFA' : '-' }}</td>
                </tr>
            </table>
            @endif

            <table class="signature-section">
                <tr>
                    <td>
                        <strong>Chauffeur</strong>
                        <div class="signature-box"></div>
                    </td>
                    <td>
                        <strong>Agent</strong>
                        <div class="signature-box"></div>
                    </td>
                    <td>
                        <strong>Responsable</strong>
                        <div class="signature-box"></div>
                    </td>
                </tr>
            </table>

            <div class="footer">
                <span class="footer-left">Imprimé le {{ $printedAt }} par {{ $printedBy }}</span>
                <span class="footer-right">Fiche N° {{ str_pad($fiche->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
        </div>
        @endfor
    </div>
</body>
</html>
