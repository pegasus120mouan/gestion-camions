<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau de déchargement - {{ $bordereau->numero }}</title>
    <style>
        @page { margin: 14mm 16mm; size: A4 portrait; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #222;
            line-height: 1.4;
        }

        /* ── En-tête société ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .logo-cell { width: 110px; }
        .logo { max-width: 95px; max-height: 95px; }
        .company-name {
            color: #006400;
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .company-subtitle {
            color: #228B22;
            font-size: 10px;
            font-style: italic;
            margin-top: 4px;
        }

        /* ── Titre document ── */
        .doc-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 18px;
            letter-spacing: 0.5px;
        }

        /* ── Bloc informations ── */
        .info-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
            border: 1px solid #333;
        }
        .info-box td {
            border: 1px solid #333;
            padding: 8px 12px;
            vertical-align: middle;
        }
        .info-box-header {
            background: #1e3a6e;
            color: #fff;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: center;
            padding: 9px 12px !important;
        }
        .info-label {
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #333;
            background: #f9f9f9;
        }
        .info-value { font-size: 11px; }
        .info-value.poids { color: #006400; font-weight: bold; font-size: 12px; }
        .info-value.montant { color: #c0392b; font-weight: bold; font-size: 12px; }
        .info-center { text-align: center; }

        /* ── Tableau détail ── */
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        table.data th,
        table.data td {
            border: 1px solid #333;
            padding: 7px 8px;
            font-size: 10px;
        }
        table.data thead th {
            background: #d6eaf8;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
            text-transform: uppercase;
        }
        table.data tbody td { vertical-align: middle; }
        .subtotal-row td {
            font-style: italic;
            background: #fafafa;
        }
        .subtotal-label { text-align: right; padding-right: 12px !important; }
        .total-row td {
            background: #d6eaf8;
            font-weight: bold;
            font-size: 10px;
        }
        .total-label { text-align: right; padding-right: 12px !important; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ── Signatures ── */
        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 50px;
        }
        .signatures td {
            border: none;
            vertical-align: top;
            width: 50%;
            font-size: 10px;
            padding-top: 8px;
        }
        .sig-right { text-align: right; }
        .sig-line {
            margin-top: 40px;
            border-top: 1px solid #999;
            width: 180px;
        }
        .sig-right .sig-line { margin-left: auto; }
    </style>
</head>
<body>

    {{-- En-tête --}}
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if($logoPath && file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="Logo" class="logo">
                @endif
            </td>
            <td style="text-align:center;">
                <div class="company-name">PGF</div>
                <div class="company-subtitle">Plateforme de Gestion des Fournisseurs</div>
            </td>
            <td style="width:110px;"></td>
        </tr>
    </table>

    <div class="doc-title">Bordereau de déchargement N° {{ $bordereau->numero }}</div>

    {{-- Informations du bordereau --}}
    <table class="info-box">
        <tr>
            <td colspan="2" class="info-box-header">Informations du bordereau</td>
        </tr>
        <tr>
            <td class="info-label" style="width:50%;">Groupe</td>
            <td class="info-label" style="width:50%;">Période de collecte</td>
        </tr>
        <tr>
            <td class="info-value">
                <strong>CAMIONS PGF</strong>
            </td>
            <td class="info-value">
                {{ $bordereau->date_debut?->format('d/m/Y') }} au {{ $bordereau->date_fin?->format('d/m/Y') }}
            </td>
        </tr>
        <tr>
            <td class="info-label">Poids total collecté</td>
            <td class="info-label">Montant total</td>
        </tr>
        <tr>
            <td class="info-value poids">{{ number_format((float) $bordereau->poids_total, 0, ',', ' ') }} KG</td>
            <td class="info-value montant">{{ number_format((float) $bordereau->montant_total, 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <td colspan="2" class="info-label info-center">Date de création</td>
        </tr>
        <tr>
            <td colspan="2" class="info-value info-center">{{ $dateCreation }}</td>
        </tr>
    </table>

    {{-- Tableau unique des fiches --}}
    <table class="data">
        <thead>
            <tr>
                <th style="width:10%;">Date</th>
                <th style="width:12%;">N° fiche</th>
                <th style="width:12%;">N° ticket</th>
                <th style="width:16%;">Usine</th>
                <th style="width:12%;">Véhicule</th>
                <th style="width:12%;">Poids (Kg)</th>
                <th style="width:12%;">Prix unit.</th>
                <th style="width:14%;">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupesUsine as $groupe)
                @foreach($groupe['lignes'] as $ligne)
                <tr>
                    <td class="text-center">
                        {{ !empty($ligne['date_dechargement']) ? \Carbon\Carbon::parse($ligne['date_dechargement'])->format('d/m/Y') : '—' }}
                    </td>
                    <td class="text-center">{{ $ligne['numero_fiche'] ?? ('#' . ($ligne['fiche_id'] ?? '')) }}</td>
                    <td class="text-center">{{ $ligne['numero_ticket'] ?? '—' }}</td>
                    <td class="text-center">{{ $ligne['usine'] ?? '—' }}</td>
                    <td class="text-center">{{ $ligne['matricule_vehicule'] ?? '—' }}</td>
                    <td class="text-right">{{ number_format((float) ($ligne['poids'] ?? 0), 0, ',', ' ') }}</td>
                    <td class="text-right">
                        @if(!empty($ligne['prix_unitaire']))
                            {{ number_format((float) $ligne['prix_unitaire'], 0, ',', ' ') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right">{{ number_format((int) ($ligne['montant'] ?? 0), 0, ',', ' ') }}</td>
                </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="5" class="subtotal-label">Sous-total {{ $groupe['usine'] }}</td>
                    <td class="text-right">{{ number_format($groupe['poids_total'], 0, ',', ' ') }}</td>
                    <td></td>
                    <td class="text-right">{{ number_format($groupe['montant_total'], 0, ',', ' ') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5" class="total-label">TOTAL GÉNÉRAL</td>
                <td class="text-right">{{ number_format((float) $bordereau->poids_total, 0, ',', ' ') }}</td>
                <td></td>
                <td class="text-right">{{ number_format((float) $bordereau->montant_total, 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Signatures --}}
    <table class="signatures">
        <tr>
            <td>
                Signature :
                <div class="sig-line"></div>
            </td>
            <td class="sig-right">
                Signature du responsable :
                <div class="sig-line"></div>
            </td>
        </tr>
    </table>

</body>
</html>
