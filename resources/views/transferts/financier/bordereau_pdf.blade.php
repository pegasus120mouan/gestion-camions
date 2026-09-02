<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau de transfert - {{ $bordereau->numero }}</title>
    <style>
        @page { margin: 10mm 12mm; size: A4 portrait; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.4;
        }

        .page-frame {
            border: 2px solid #1e3a6e;
            padding: 16px 18px 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border-bottom: 2px solid #1e3a6e;
            padding-bottom: 8px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0 0 10px 0;
        }
        .logo-cell { width: 110px; }
        .logo { max-width: 95px; max-height: 85px; }
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

        .doc-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 14px 0 16px;
            letter-spacing: 0.6px;
            border: 1.5px solid #1e3a6e;
            background: #f4f7fb;
            padding: 10px 12px;
        }

        .section-title {
            background: #1e3a6e;
            color: #fff;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: center;
            padding: 8px 10px;
            border: 1.5px solid #1e3a6e;
            border-bottom: none;
        }

        .info-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            border: 1.5px solid #1e3a6e;
        }
        .info-box td {
            border: 1px solid #8aa0c2;
            padding: 8px 12px;
            vertical-align: middle;
        }
        .info-label {
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #1e3a6e;
            background: #eef3f9;
            width: 22%;
        }
        .info-value { font-size: 11px; width: 28%; }
        .info-value.poids { color: #006400; font-weight: bold; font-size: 12px; }
        .info-value.montant { color: #c0392b; font-weight: bold; font-size: 12px; }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            border: 1.5px solid #1e3a6e;
        }
        table.data th,
        table.data td {
            border: 1px solid #5a7399;
            padding: 7px 8px;
            font-size: 10px;
        }
        table.data thead th {
            background: #1e3a6e;
            color: #fff;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
            text-transform: uppercase;
            border-color: #1e3a6e;
        }
        table.data tbody td { vertical-align: middle; }
        .total-row td {
            background: #d6eaf8;
            font-weight: bold;
            font-size: 10px;
            border-top: 2px solid #1e3a6e;
            border-color: #1e3a6e;
        }
        .total-label { text-align: right; padding-right: 12px !important; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .signatures {
            width: 100%;
            border-collapse: separate;
            border-spacing: 14px 0;
            margin-top: 28px;
        }
        .signatures td {
            border: 1.5px solid #1e3a6e;
            vertical-align: top;
            width: 50%;
            font-size: 10px;
            padding: 12px 14px 16px;
            background: #fafbfd;
        }
        .sig-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            color: #1e3a6e;
            letter-spacing: 0.4px;
            margin-bottom: 6px;
        }
        .sig-line {
            margin-top: 48px;
            border-top: 1px solid #666;
            width: 85%;
        }
        .sig-right .sig-line { margin-left: auto; margin-right: 0; }
    </style>
</head>
<body>
<div class="page-frame">

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

    <div class="doc-title">Bordereau de transfert N° {{ $bordereau->numero }}</div>

    <div class="section-title">Informations du bordereau</div>
    <table class="info-box">
        <tr>
            <td class="info-label">{{ $clientTypeLabel }}</td>
            <td class="info-value">
                <strong>
                    @if(!empty($bordereau->client_code))
                        {{ strtoupper($bordereau->client_code) }} —
                    @endif
                    {{ strtoupper($bordereau->client_nom ?? '') }}
                </strong>
            </td>
            <td class="info-label">Période</td>
            <td class="info-value">
                {{ $bordereau->date_debut?->format('d/m/Y') }} au {{ $bordereau->date_fin?->format('d/m/Y') }}
            </td>
        </tr>
        <tr>
            <td class="info-label">Poids total</td>
            <td class="info-value poids">{{ number_format((float) $bordereau->poids_total, 0, ',', ' ') }} KG</td>
            <td class="info-label">Montant total</td>
            <td class="info-value montant">{{ number_format((float) $bordereau->montant_total, 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <td class="info-label">Date de création</td>
            <td class="info-value" colspan="3">{{ $dateCreation }}</td>
        </tr>
    </table>

    <div class="section-title">Détail des transferts</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:12%;">Date</th>
                <th style="width:14%;">Véhicule</th>
                <th style="width:18%;">Départ</th>
                <th style="width:18%;">Destination</th>
                <th style="width:12%;">Poids (Kg)</th>
                <th style="width:12%;">Prix unit.</th>
                <th style="width:14%;">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lignes as $ligne)
                <tr>
                    <td class="text-center">
                        @if(!empty($ligne['date_chargement']))
                            {{ \Carbon\Carbon::parse($ligne['date_chargement'])->format('d/m/Y') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-center">{{ $ligne['matricule_vehicule'] ?? '—' }}</td>
                    <td class="text-center">{{ $ligne['lieu_depart'] ?? '—' }}</td>
                    <td class="text-center">{{ $ligne['lieu_destination'] ?? '—' }}</td>
                    <td class="text-right">{{ number_format((float) ($ligne['poids'] ?? 0), 0, ',', ' ') }}</td>
                    <td class="text-right">
                        @if(isset($ligne['prix_unitaire']) && $ligne['prix_unitaire'] !== null)
                            {{ number_format((float) $ligne['prix_unitaire'], 0, ',', ' ') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right">{{ number_format((float) ($ligne['montant'] ?? 0), 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Aucun transfert</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="4" class="total-label">TOTAL GÉNÉRAL</td>
                <td class="text-right">{{ number_format((float) $bordereau->poids_total, 0, ',', ' ') }}</td>
                <td></td>
                <td class="text-right">{{ number_format((float) $bordereau->montant_total, 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-title">Signature du client</div>
                <div class="sig-line"></div>
            </td>
            <td class="sig-right">
                <div class="sig-title">Signature du responsable</div>
                <div class="sig-line"></div>
            </td>
        </tr>
    </table>

</div>
</body>
</html>
