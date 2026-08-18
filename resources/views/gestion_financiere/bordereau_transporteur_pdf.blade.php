<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau transporteur - {{ $bordereau->numero }}</title>
    <style>
        @page {
            margin: 12mm 12mm 14mm 12mm;
            size: A4 portrait;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #222;
            line-height: 1.45;
        }
        .page-frame {
            border: 1.5px solid #222;
            padding: 20px;
            min-height: 255mm;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }
        .header td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }
        .logo {
            max-width: 120px;
            max-height: 90px;
        }
        .company-name {
            color: #006400;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-subtitle {
            color: #228B22;
            font-size: 11px;
            font-style: italic;
            margin-top: 4px;
        }

        .doc-title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin: 8px 0 18px 0;
            padding-bottom: 6px;
            border-bottom: 2px solid #222;
            display: inline-block;
            width: 100%;
        }

        .meta {
            margin-bottom: 16px;
            font-size: 12px;
        }
        .meta strong {
            font-weight: bold;
        }
        .meta-line {
            margin-bottom: 4px;
        }

        .usine-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0 12px 0;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.data th,
        table.data td {
            border: 1px solid #222;
            padding: 7px 8px;
            font-size: 10.5px;
        }
        table.data thead th {
            background: #e8e8e8;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            text-transform: none;
        }
        table.data tbody td {
            vertical-align: middle;
        }
        .subtotal-row td {
            font-style: italic;
            background: #f7f7f7;
        }
        .total-row td {
            background: #e8e8e8;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .label-right { text-align: right; padding-right: 10px !important; }

        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 48px;
        }
        .signatures td {
            border: none;
            vertical-align: top;
            width: 50%;
            font-size: 11px;
        }
        .sig-line {
            margin-top: 42px;
            border-top: 1px solid #555;
            width: 200px;
        }
        .sig-right { text-align: right; }
        .sig-right .sig-line { margin-left: auto; }
    </style>
</head>
<body>
    <div class="page-frame">
    <table class="header">
        <tr>
            <td style="width:130px;">
                @if(!empty($logoSrc))
                    <img src="{{ $logoSrc }}" alt="PGF" class="logo">
                @endif
            </td>
            <td>
                <div class="company-name">PGF-AFRICA SCOOPS</div>
                <div class="company-subtitle">Plateforme de Gestion des Fournisseurs</div>
            </td>
        </tr>
    </table>

    <div class="doc-title">Bordereau transporteur N° {{ $bordereau->numero }}</div>

    <div class="meta">
        <div class="meta-line">
            <strong>TRANSPORTEUR :</strong>
            {{ strtoupper($transporteurNom) }}
        </div>
        <div class="meta-line">
            <strong>Période du :</strong>
            {{ $bordereau->date_debut?->format('d/m/Y') }} au {{ $bordereau->date_fin?->format('d/m/Y') }}
        </div>
        <div class="meta-line">
            <strong>Date d'émission :</strong> {{ $dateCreation }}
        </div>
    </div>

    @php
        $usinesTitre = collect($groupesUsine)->pluck('usine')->filter()->unique()->values();
    @endphp
    @if($usinesTitre->count() === 1)
        <div class="usine-title">{{ $usinesTitre->first() }}</div>
    @endif

    <table class="data">
        <thead>
            <tr>
                <th style="width:11%;">Date</th>
                <th style="width:13%;">N° fiche</th>
                <th style="width:14%;">N° ticket</th>
                <th style="width:16%;">Usine</th>
                <th style="width:13%;">Véhicule</th>
                <th style="width:11%;">Poids (kg)</th>
                <th style="width:11%;">Prix unit.</th>
                <th style="width:11%;">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupesUsine as $groupe)
                @foreach($groupe['lignes'] as $ligne)
                <tr>
                    <td class="text-center">
                        {{ !empty($ligne['date_chargement']) ? \Carbon\Carbon::parse($ligne['date_chargement'])->format('d/m/Y') : '—' }}
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
                @if(count($groupesUsine) > 1)
                <tr class="subtotal-row">
                    <td colspan="5" class="label-right">Sous-total {{ $groupe['usine'] }}</td>
                    <td class="text-right">{{ number_format($groupe['poids_total'], 0, ',', ' ') }}</td>
                    <td></td>
                    <td class="text-right">{{ number_format($groupe['montant_total'], 0, ',', ' ') }}</td>
                </tr>
                @endif
            @endforeach
            <tr class="total-row">
                <td colspan="5" class="label-right">TOTAL GÉNÉRAL</td>
                <td class="text-right">{{ number_format((float) $bordereau->poids_total, 0, ',', ' ') }}</td>
                <td></td>
                <td class="text-right">{{ number_format((float) $bordereau->montant_total, 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                Signature du transporteur :
                <div class="sig-line"></div>
            </td>
            <td class="sig-right">
                Signature du responsable :
                <div class="sig-line"></div>
            </td>
        </tr>
    </table>
    </div>
</body>
</html>
