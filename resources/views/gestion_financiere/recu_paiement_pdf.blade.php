<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de paiement {{ $numeroRecu }}</title>
    <style>
        @page { margin: 8mm 12mm; size: A4 portrait; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10.5px;
            color: #1a1a1a;
            line-height: 1.35;
        }

        .recu-page {
            width: 100%;
            min-height: 128mm;
            padding: 4mm 2mm 2mm;
        }

        /* ── En-tête ── */
        .hdr {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .hdr td { border: none; vertical-align: top; padding: 0; }
        .hdr-logo-text { width: 55%; vertical-align: top; }
        .logo-line1,
        .logo-line2 {
            color: #006400;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.3;
        }
        .logo-line3 {
            color: #228B22;
            font-size: 10px;
            font-style: italic;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 2px;
            line-height: 1.3;
        }
        .hdr-num {
            text-align: right;
            vertical-align: top;
            width: 120px;
        }
        .num-box {
            display: inline-block;
            border: 1px solid #333;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: bold;
            background: #f8f8f8;
        }

        /* ── Titre document ── */
        .doc-title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 6px 0 8px;
            color: #111;
        }
        .doc-meta {
            text-align: center;
            font-size: 10.5px;
            margin-bottom: 3px;
        }
        .doc-meta strong { font-weight: bold; }

        /* ── Blocs encadrés ── */
        .box {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border: 1px solid #333;
        }
        .box td { border: 1px solid #333; padding: 7px 10px; vertical-align: middle; }
        .box-hdr td {
            background: #006400;
            color: #fff;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            text-align: center;
            padding: 7px 10px !important;
        }
        .lbl {
            font-weight: bold;
            width: 38%;
            background: #f5f5f5;
            font-size: 10px;
        }
        .val { font-size: 10.5px; }
        .val.montant { font-weight: bold; color: #b00020; }
        .val.montant-zero { font-weight: bold; color: #006400; }
        .val.montant-credit { font-weight: bold; color: #856404; }

        /* ── Signatures ── */
        .sigs {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .sigs td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            border: none;
            padding: 0 8px;
        }
        .sig-label {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 32px;
        }
        .sig-name {
            font-size: 10.5px;
            border-top: 1px solid #666;
            display: inline-block;
            min-width: 160px;
            padding-top: 4px;
        }
        .footer-lieu {
            text-align: center;
            font-style: italic;
            font-size: 10px;
            margin-top: 12px;
            color: #333;
        }

        /* ── Ligne de découpe ── */
        .coupe-wrap {
            width: 100%;
            margin: 6px 0 8px;
            text-align: center;
        }
        .coupe-line {
            border-top: 1px dashed #888;
            width: 100%;
            margin-bottom: 4px;
        }
        .coupe-text {
            font-size: 8px;
            color: #666;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
@for($i = 0; $i < 2; $i++)
    <div class="recu-page">
        {{-- Logo texte UNIPALM --}}
        <table class="hdr">
            <tr>
                <td class="hdr-logo-text">
                    <div class="logo-line1">Société Coopérative Agricole</div>
                    <div class="logo-line2">Unis pour le Palmier</div>
                    <div class="logo-line3">UNIPALM COOP-CA</div>
                </td>
                <td class="hdr-num">
                    <span class="num-box">N° {{ $numeroRecu }}</span>
                </td>
            </tr>
        </table>

        <div class="doc-title">Reçu de Paiement</div>
        <div class="doc-meta"><strong>N° Bordereau :</strong> {{ $numeroBordereau }}</div>
        <div class="doc-meta"><strong>Date :</strong> {{ $dateHeure }}</div>

        {{-- Agent --}}
        <table class="box">
            <tr class="box-hdr">
                <td colspan="2">Informations Agent</td>
            </tr>
            <tr>
                <td class="lbl">Nom de l'agent</td>
                <td class="val">{{ $nomAgent }}</td>
            </tr>
            <tr>
                <td class="lbl">Contact</td>
                <td class="val">{{ $contactAgent }}</td>
            </tr>
        </table>

        {{-- Montants --}}
        <table class="box">
            <tr class="box-hdr">
                <td colspan="2">Détail du paiement</td>
            </tr>
            @if(empty($estAvance))
            <tr>
                <td class="lbl">Montant total</td>
                <td class="val montant">{{ number_format($montantTotal, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endif
            <tr>
                <td class="lbl">{{ !empty($estAvance) ? 'Montant avancé' : 'Montant payé' }}</td>
                <td class="val montant">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr>
                <td class="lbl">Source de paiement</td>
                <td class="val">{{ $sourcePaiement }}</td>
            </tr>
            @if(empty($estAvance))
            <tr>
                <td class="lbl">Reste à payer sur ce Bordereau</td>
                <td class="val {{ $resteAPayer > 0 ? 'montant' : 'montant-zero' }}">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endif
            <tr>
                <td class="lbl">Solde compte</td>
                <td class="val {{ $soldeCompte > 0 ? 'montant' : ($soldeCompte < 0 ? 'montant-credit' : 'montant-zero') }}">{{ number_format($soldeCompte, 0, ',', ' ') }} FCFA</td>
            </tr>
        </table>

        {{-- Signatures --}}
        <table class="sigs">
            <tr>
                <td>
                    <div class="sig-label">Signature Caissier</div>
                    <div class="sig-name">{{ $nomCaissier }}</div>
                </td>
                <td>
                    <div class="sig-label">Signature Récepteur</div>
                    <div class="sig-name">{{ $nomRecepteur }}</div>
                </td>
            </tr>
        </table>

        <div class="footer-lieu">Fait à Abidjan, le {{ $dateFait }}</div>
    </div>

    @if($i === 0)
        <div class="coupe-wrap">
            <div class="coupe-line"></div>
            <div class="coupe-text">— — — Découper ici — — —</div>
        </div>
    @endif
@endfor
</body>
</html>
