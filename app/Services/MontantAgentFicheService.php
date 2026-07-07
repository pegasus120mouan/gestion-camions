<?php

namespace App\Services;

use App\Models\CodeTransporteurVehicule;
use App\Models\FicheSortie;
use App\Models\PrixAgent;
use App\Models\Ticket;
use App\Services\MontantAgentReportingService;
use App\Services\UsinesParProduitService;

class MontantAgentFicheService
{
    /**
     * Type de grille tarifaire PrixAgent : transporteur (camion Pisteur), pgf, autre_camion.
     */
    public function typePrixPourMatricule(?string $matricule): string
    {
        if ($matricule === null || $matricule === '') {
            return 'transporteur';
        }

        $link = CodeTransporteurVehicule::with('codeTransporteur')
            ->where('matricule_vehicule', $matricule)
            ->first();

        if (!$link || !$link->codeTransporteur) {
            return 'transporteur';
        }

        $nom = trim((string) $link->codeTransporteur->nom);
        if ($nom === 'Camion PGF') {
            return 'pgf';
        }
        if (strcasecmp($nom, 'Autre Camion') === 0 || strcasecmp($nom, 'Autre') === 0) {
            return 'autre_camion';
        }

        return 'transporteur';
    }

    public function prixUnitairePourFiche(FicheSortie $fiche): ?float
    {
        if (!$fiche->id_agent || !$fiche->usine) {
            return null;
        }

        $usine = trim((string) $fiche->usine);
        if ($usine === '') {
            return null;
        }

        $type = $this->typePrixPourMatricule($fiche->matricule_vehicule);

        $query = PrixAgent::query()
            ->where('id_agent', $fiche->id_agent)
            ->where('nom_usine', $usine)
            ->where('type', $type);

        $dateRef = $fiche->date_chargement?->format('Y-m-d');
        if ($dateRef) {
            $query->where(function ($q) use ($dateRef) {
                $q->where(function ($q2) use ($dateRef) {
                    $q2->whereNull('date_debut')
                        ->orWhere('date_debut', '<=', $dateRef);
                })->where(function ($q3) use ($dateRef) {
                    $q3->whereNull('date_fin')
                        ->orWhere('date_fin', '>=', $dateRef);
                });
            });
        }

        $row = null;
        if ($fiche->produit_id) {
            $row = (clone $query)->where('produit_id', $fiche->produit_id)->first();
        }
        if (!$row) {
            $row = (clone $query)->whereNull('produit_id')->first();
        }
        if (!$row) {
            $row = $query->first();
        }

        return $row ? (float) $row->prix : null;
    }

    /**
     * Montant total dû à l’agent pour cette fiche (FCFA) : PU (FCFA/kg) × poids (kg).
     *
     * @param  float|null  $poidsKg  Si fourni (ex. poids saisi au déchargement), remplace celui de la fiche.
     */
    public function calculerMontantPourFiche(FicheSortie $fiche, ?float $poidsKg = null): ?float
    {
        $pu = $this->prixUnitairePourFiche($fiche);
        if ($pu === null) {
            return null;
        }

        $poids = $poidsKg ?? (float) $fiche->poids_pont;
        if ($poids <= 0) {
            return null;
        }

        return $pu * $poids;
    }

    public function fichePourTicket(Ticket $ticket, bool $reconcilier = true): ?FicheSortie
    {
        $fiche = FicheSortie::query()
            ->where('id_ticket', $ticket->id_ticket)
            ->orderByDesc('id')
            ->first();

        $numero = trim((string) ($ticket->numero_ticket ?? ''));
        if (! $fiche && $numero !== '') {
            $fiche = FicheSortie::query()
                ->where('numero_ticket', $numero)
                ->orderByDesc('id')
                ->first();
        }

        if (! $fiche) {
            return null;
        }

        if ($reconcilier) {
            $this->reconcilierFicheAvecTicket($fiche, $ticket);
        }

        return $this->ficheCorrespondAuTicket($fiche, $ticket) ? $fiche : null;
    }

    public function reconcilierFicheAvecTicket(FicheSortie $fiche, Ticket $ticket): void
    {
        if (! $this->ficheCorrespondAuTicket($fiche, $ticket, false)) {
            return;
        }

        $updates = [];

        if ((int) $fiche->id_ticket !== (int) $ticket->id_ticket) {
            $updates['id_ticket'] = $ticket->id_ticket;
        }

        $numero = trim((string) ($ticket->numero_ticket ?? ''));
        if ($numero !== '' && trim((string) ($fiche->numero_ticket ?? '')) === '') {
            $updates['numero_ticket'] = $numero;
        }

        if ((int) ($ticket->id_agent ?? 0) > 0 && (int) ($fiche->id_agent ?? 0) !== (int) $ticket->id_agent) {
            $updates['id_agent'] = (int) $ticket->id_agent;
        }

        if (! empty($updates)) {
            $fiche->update($updates);
            $fiche->refresh();
        }
    }

    public function ficheCorrespondAuTicket(FicheSortie $fiche, Ticket $ticket, bool $exigerIdTicket = false): bool
    {
        if ((int) $fiche->id_ticket === (int) $ticket->id_ticket) {
            return true;
        }

        if ($exigerIdTicket) {
            return false;
        }

        $numero = trim((string) ($ticket->numero_ticket ?? ''));
        $numeroFiche = trim((string) ($fiche->numero_ticket ?? ''));

        return $numero !== '' && $numeroFiche !== '' && strcasecmp($numero, $numeroFiche) === 0;
    }

    /**
     * Vraie fiche de sortie parc (FICH-…) — exclut les fiches techniques TKT- créées à la validation ticket.
     */
    public function estFicheSortieReelle(FicheSortie $fiche): bool
    {
        $numero = trim((string) ($fiche->numero_fiche ?? ''));
        if ($numero === '' || $numero === '—') {
            return false;
        }

        return ! str_starts_with(strtoupper($numero), 'TKT-');
    }

    public function assurerFichePourTicketAgent(Ticket $ticket, ?array $produitInfo = null): FicheSortie
    {
        $existing = $this->fichePourTicket($ticket, false);
        $usine = app(MontantAgentReportingService::class)->nomUsinePourTicket($existing, $ticket);

        if ($produitInfo === null) {
            $produitInfo = app(UsinesParProduitService::class)->produitPourUsine(
                (int) ($ticket->id_usine ?? 0) ?: null,
                $usine
            );
        }

        if ($existing) {
            $updates = [];
            if ($usine && trim((string) ($existing->usine ?? '')) === '') {
                $updates['usine'] = $usine;
            }
            if ($produitInfo && ! (int) ($existing->produit_id ?? 0)) {
                $updates['produit_id'] = (int) $produitInfo['produit_id'];
                $updates['nom_produit'] = (string) ($produitInfo['nom'] ?? '');
            }
            if ($updates !== []) {
                $existing->update($updates);
                $existing->refresh();
            }

            return $existing;
        }

        $date = $ticket->date_ticket?->format('Y-m-d') ?? now()->format('Y-m-d');
        $numero = trim((string) ($ticket->numero_ticket ?? ''));

        return FicheSortie::create([
            'numero_fiche' => 'TKT-' . preg_replace('/[^A-Za-z0-9\-_]/', '', $numero !== '' ? $numero : (string) $ticket->id_ticket),
            'vehicule_id' => (int) ($ticket->vehicule_id ?? 0) ?: null,
            'matricule_vehicule' => (string) ($ticket->matricule_vehicule ?? ''),
            'id_ticket' => $ticket->id_ticket,
            'numero_ticket' => $numero,
            'id_agent' => (int) ($ticket->id_agent ?? 0) ?: null,
            'usine' => $usine,
            'produit_id' => $produitInfo ? (int) $produitInfo['produit_id'] : null,
            'nom_produit' => $produitInfo ? (string) ($produitInfo['nom'] ?? '') : null,
            'date_chargement' => $date,
            'date_dechargement' => $ticket->estValide() ? $date : null,
            'poids_pont' => $ticket->poids,
            'id_pont' => 0,
            'nom_pont' => 'Usine',
            'code_pont' => '',
        ]);
    }
}
