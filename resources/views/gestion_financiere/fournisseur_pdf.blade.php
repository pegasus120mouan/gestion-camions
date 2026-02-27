<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Historique des transactions - {{ $fournisseurNom }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
        }
        .logo-cell {
            width: 180px;
        }
        .logo {
            max-width: 150px;
        }
        .title-cell {
            text-align: center;
        }
        .company-name {
            color: #006400;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .company-subtitle {
            color: #dc3545;
            font-size: 12px;
            font-style: italic;
            margin: 5px 0 0 0;
        }
        .document-title {
            text-align: center;
            margin: 20px 0;
            padding: 10px 0;
            border-top: 2px solid #006400;
            border-bottom: 2px solid #006400;
        }
        .document-title h1 {
            color: #333;
            margin: 0;
            font-size: 18px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .info-table td {
            padding: 8px 10px;
            border: 1px solid #ddd;
        }
        .info-table .label {
            font-weight: bold;
            background-color: #f9f9f9;
            width: 200px;
        }
        
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            padding: 8px 15px;
            margin: 0 0 10px 0;
            font-size: 14px;
            background-color: #f5f5f5;
            color: #333;
            border-left: 4px solid #006400;
            font-weight: bold;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.data-table th, table.data-table td {
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table.data-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 11px;
            color: #333;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('img/logo/logo.png') }}" alt="Logo" class="logo">
            </td>
            <td class="title-cell">
                <p class="company-name">WEIGHTRACK</p>
                <p class="company-subtitle">Gestion de Transport et Logistique</p>
            </td>
        </tr>
    </table>

    <div class="document-title">
        <h1>HISTORIQUE DES TRANSACTIONS</h1>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Informations du fournisseur</td>
            <td></td>
        </tr>
        <tr>
            <td>Fournisseur:</td>
            <td>{{ $fournisseurNom }}</td>
        </tr>
        @if($service)
        <tr>
            <td>Service:</td>
            <td>{{ $service->nom_service }}</td>
        </tr>
        @endif
        @if(isset($dateDebut) && isset($dateFin))
        <tr>
            <td>Période du:</td>
            <td>{{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} Au: {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}</td>
        </tr>
        @endif
        <tr>
            <td>Montant Dû:</td>
            <td>{{ number_format($montantDu, 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <td>Montant Payé:</td>
            <td>{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <td>Reste à Payer:</td>
            <td>{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <td>Date de création:</td>
            <td>{{ date('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <div class="section">
        <h3 class="section-title">Montant</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Véhicule</th>
                    <th class="text-right">Montant</th>
                </tr>
            </thead>
            <tbody>
                @forelse($depenses as $depense)
                    <tr>
                        <td>{{ $depense->date_depense ? $depense->date_depense->format('d/m/Y') : '-' }}</td>
                        <td>{{ $depense->matricule_vehicule }}</td>
                        <td class="text-right">{{ number_format($depense->montant, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align: center;">Aucune dépense</td>
                    </tr>
                @endforelse
            </tbody>
            @if($depenses->count() > 0)
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2">Total</td>
                        <td class="text-right">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <div class="section">
        <h3 class="section-title">Paiement</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Mode</th>
                    <th>Référence</th>
                    <th class="text-right">Montant</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paiements as $paiement)
                    <tr>
                        <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '-' }}</td>
                        <td>
                            @if($paiement->mode_paiement == 'especes')
                                Espèces
                            @elseif($paiement->mode_paiement == 'virement')
                                Virement
                            @elseif($paiement->mode_paiement == 'cheque')
                                Chèque
                            @else
                                {{ $paiement->mode_paiement }}
                            @endif
                        </td>
                        <td>{{ $paiement->reference ?? '-' }}</td>
                        <td class="text-right">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center;">Aucun paiement</td>
                    </tr>
                @endforelse
            </tbody>
            @if($paiements->count() > 0)
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3">Total</td>
                        <td class="text-right">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <div class="footer">
        Document généré le {{ date('d/m/Y à H:i') }} - WeighTrack
    </div>
</body>
</html>
