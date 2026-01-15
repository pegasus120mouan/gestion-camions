<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <title>Ticket - {{ $pesee->reference }}</title>
    <style>
      body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
      .header { width: 100%; margin-bottom: 16px; }
      .header-table { width: 100%; border-collapse: collapse; }
      .header-table td { vertical-align: middle; }
      .title { font-size: 18px; font-weight: 700; }
      .muted { color: #555; }
      .section { margin-top: 12px; }
      .grid { width: 100%; border-collapse: collapse; }
      .grid td { padding: 6px 8px; border: 1px solid #ddd; }
      .grid td.label { width: 30%; background: #f6f6f6; font-weight: 600; }
      .qrs { width: 100%; margin-top: 14px; }
      .qrs-table { width: 100%; border-collapse: collapse; }
      .qrs-table td { width: 50%; vertical-align: top; }
      .qr-box { border: 1px solid #ddd; padding: 10px; }
      .qr-title { font-weight: 700; margin-bottom: 6px; }
      .footer { margin-top: 16px; font-size: 10px; color: #666; }
    </style>
  </head>
  <body>
    <div class="header">
      <table class="header-table">
        <tr>
          <td>
            @if($logoBase64)
              <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo" style="height: 80px;" />
            @endif
          </td>
          <td style="text-align: right;">
            <div class="title">Ticket de pesée</div>
            <div class="muted">Réf: {{ $pesee->reference }}</div>
            <div class="muted">{{ optional($pesee->pese_le)->format('d/m/Y H:i') }}</div>
          </td>
        </tr>
      </table>
    </div>

    <div class="section">
      <table class="grid">
        <tr>
          <td class="label">Pont</td>
          <td>{{ $pesee->pontPesage?->code }} - {{ $pesee->pontPesage?->nom }}</td>
        </tr>
        <tr>
          <td class="label">Camion</td>
          <td>{{ $pesee->camion?->immatriculation }}</td>
        </tr>
        <tr>
          <td class="label">Produit</td>
          <td>{{ $pesee->produit?->nom }}</td>
        </tr>
        <tr>
          <td class="label">Agent</td>
          <td>{{ $pesee->agent?->name }} {{ $pesee->agent?->prenom }}</td>
        </tr>
        <tr>
          <td class="label">Chauffeur</td>
          <td>{{ $pesee->chauffeur ? ($pesee->chauffeur->name . ' ' . $pesee->chauffeur->prenom) : '-' }}</td>
        </tr>
      </table>
    </div>

    <div class="section">
      <table class="grid">
        <tr>
          <td class="label">Poids Brut</td>
          <td>{{ $pesee->poids_apres_refraction }}</td>
        </tr>
        <tr>
          <td class="label">Poids vide</td>
          <td>{{ $pesee->poids_vide }}</td>
        </tr>
        <tr>
          <td class="label">Poids net</td>
          <td><strong>{{ $pesee->poids_net }}</strong></td>
        </tr>
      </table>
    </div>

    <div class="qrs">
      <table class="qrs-table">
        <tr>
          <td style="padding-right: 8px;">
            <div class="qr-box">
              <div class="qr-title">QR Référence</div>
              <img src="data:image/svg+xml;base64,{{ $qrRefBase64 }}" alt="QR Référence" style="width: 140px; height: 140px;" />
              <div class="muted" style="margin-top: 6px;">{{ $pesee->reference }}</div>
            </div>
          </td>
          <td style="padding-left: 8px;">
            <div class="qr-box">
              <div class="qr-title">QR Lien</div>
              <img src="data:image/svg+xml;base64,{{ $qrUrlBase64 }}" alt="QR Lien" style="width: 140px; height: 140px;" />
              <div class="muted" style="margin-top: 6px;">{{ $ticketUrl }}</div>
            </div>
          </td>
        </tr>
      </table>
    </div>

    <div class="footer">
      Généré le {{ now()->format('d/m/Y H:i') }}
    </div>
  </body>
</html>
