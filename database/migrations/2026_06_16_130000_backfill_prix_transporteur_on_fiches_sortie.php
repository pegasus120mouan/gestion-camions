<?php

use App\Models\FicheSortie;
use App\Models\Ticket;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        FicheSortie::query()
            ->whereNotNull('transporteur_id')
            ->where(function ($query) {
                $query->whereNull('prix_unitaire_transport')
                    ->orWhere('prix_unitaire_transport', '<=', 0);
            })
            ->whereNotNull('id_ticket')
            ->orderBy('id')
            ->chunkById(200, function ($fiches) {
                foreach ($fiches as $fiche) {
                    $ticket = Ticket::query()
                        ->where('id_ticket', $fiche->id_ticket)
                        ->first();

                    if ($ticket && (float) $ticket->prix_unitaire > 0) {
                        $fiche->update(['prix_unitaire_transport' => $ticket->prix_unitaire]);
                    }
                }
            });
    }

    public function down(): void
    {
        //
    }
};
