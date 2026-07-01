<?php

namespace App\Services;

use App\Models\FicheSortie;
use App\Models\Ticket;
use App\Models\Transporteur;

class TicketTransporteurFicheService
{
    public function poidsEffectif(FicheSortie $fiche): float
    {
        $poids = (float) ($fiche->poids_pont ?? 0);
        if ($poids > 0) {
            return $poids;
        }

        $ticket = $this->ticketPourFiche($fiche);
        if ($ticket) {
            return (float) ($ticket->poids ?? 0);
        }

        return 0.0;
    }

    public function numeroTicketEffectif(FicheSortie $fiche): ?string
    {
        $numero = trim((string) ($fiche->numero_ticket ?? ''));
        if ($numero !== '') {
            return $numero;
        }

        $ticket = $this->ticketPourFiche($fiche);

        return $ticket && trim((string) $ticket->numero_ticket) !== ''
            ? trim((string) $ticket->numero_ticket)
            : null;
    }

    /**
     * Complète la fiche avec le numéro et le poids du ticket lié si manquants.
     */
    public function synchroniserDonneesTicketSurFiche(FicheSortie $fiche): FicheSortie
    {
        $ticket = $this->ticketPourFiche($fiche);
        if (!$ticket) {
            return $fiche;
        }

        $updates = [];

        if (trim((string) ($fiche->numero_ticket ?? '')) === '' && trim((string) $ticket->numero_ticket) !== '') {
            $updates['numero_ticket'] = trim((string) $ticket->numero_ticket);
        }

        if (!(int) ($fiche->id_ticket ?? 0) && (int) ($ticket->id_ticket ?? 0)) {
            $updates['id_ticket'] = $ticket->id_ticket;
        }

        if ((float) ($fiche->poids_pont ?? 0) <= 0 && (float) ($ticket->poids ?? 0) > 0) {
            $updates['poids_pont'] = $ticket->poids;
        }

        if (!empty($updates)) {
            $fiche->update($updates);
            $fiche->refresh();
        }

        return $fiche;
    }

    public function lierFicheAuTransporteur(FicheSortie $fiche, Transporteur $transporteur, ?Ticket $ticket = null): void
    {
        $data = [
            'transporteur_id' => $transporteur->id,
        ];

        if ($ticket) {
            $data = array_merge($data, $this->donneesTicketPourFiche($ticket));
        }

        $fiche->update($data);
    }

    /**
     * @param  array{nom_usine?: string, produit_id?: int|null, nom_produit?: string, nom_agent?: string, numero_agent?: string, id_agent?: int|null}  $context
     */
    public function creerFicheDepuisTicket(Ticket $ticket, Transporteur $transporteur, array $context): FicheSortie
    {
        $poids = (float) ($ticket->poids ?? 0);
        $date = $ticket->date_ticket?->format('Y-m-d') ?? now()->format('Y-m-d');

        return FicheSortie::create(array_merge([
            'numero_fiche' => 'TKT-' . preg_replace('/[^A-Za-z0-9\-_]/', '', (string) $ticket->numero_ticket),
            'vehicule_id' => (int) ($ticket->vehicule_id ?? 0),
            'matricule_vehicule' => (string) $ticket->matricule_vehicule,
            'transporteur_id' => $transporteur->id,
            'id_pont' => 0,
            'nom_pont' => 'Usine',
            'code_pont' => '',
            'usine' => $context['nom_usine'] ?? null,
            'produit_id' => $context['produit_id'] ?? null,
            'nom_produit' => $context['nom_produit'] ?? '',
            'id_agent' => (int) ($context['id_agent'] ?? 0),
            'nom_agent' => $context['nom_agent'] ?? '',
            'numero_agent' => $context['numero_agent'] ?? '',
            'date_chargement' => $date,
            'poids_pont' => $poids > 0 ? $poids : null,
            'prix_unitaire_transport' => null,
        ], $this->donneesTicketPourFiche($ticket)));
    }

    /**
     * Lie le ticket à un transporteur sans renseigner le prix unitaire (saisie manuelle).
     *
     * @param  array{nom_usine?: string, produit_id?: int|null, nom_produit?: string, nom_agent?: string, numero_agent?: string, id_agent?: int|null}  $context
     */
    public function synchroniserTicketTransporteur(Ticket $ticket, ?FicheSortie $fiche, array $context): ?Transporteur
    {
        $transporteur = app(TransporteurVehiculeService::class)->transporteurPourVehicule(
            $ticket->vehicule_id ? (int) $ticket->vehicule_id : null,
            $ticket->matricule_vehicule
        );

        if (!$transporteur) {
            return null;
        }

        if ($fiche) {
            $this->lierFicheAuTransporteur($fiche, $transporteur, $ticket);

            return $transporteur;
        }

        if ((float) ($ticket->poids ?? 0) > 0 || trim((string) $ticket->numero_ticket) !== '') {
            $this->creerFicheDepuisTicket($ticket, $transporteur, $context);
        }

        return $transporteur;
    }

    private function ticketPourFiche(FicheSortie $fiche): ?Ticket
    {
        if ($fiche->id_ticket) {
            $ticket = Ticket::query()->where('id_ticket', $fiche->id_ticket)->first();
            if ($ticket) {
                return $ticket;
            }
        }

        $numero = trim((string) ($fiche->numero_ticket ?? ''));
        if ($numero !== '') {
            return Ticket::query()->where('numero_ticket', $numero)->first();
        }

        if ($fiche->matricule_vehicule && $fiche->date_chargement) {
            return Ticket::query()
                ->where('matricule_vehicule', $fiche->matricule_vehicule)
                ->whereDate('date_ticket', $fiche->date_chargement)
                ->whereIn('conformite', ['valide', 'conforme'])
                ->orderByDesc('id_ticket')
                ->first();
        }

        return null;
    }

    private function donneesTicketPourFiche(Ticket $ticket): array
    {
        $data = [];

        if ($ticket->id_ticket) {
            $data['id_ticket'] = $ticket->id_ticket;
        }

        $numero = trim((string) ($ticket->numero_ticket ?? ''));
        if ($numero !== '') {
            $data['numero_ticket'] = $numero;
        }

        $poids = (float) ($ticket->poids ?? 0);
        if ($poids > 0) {
            $data['poids_pont'] = $poids;
        }

        if ($ticket->date_ticket) {
            $data['date_dechargement'] = $ticket->date_ticket->format('Y-m-d');
        }

        return $data;
    }
}
