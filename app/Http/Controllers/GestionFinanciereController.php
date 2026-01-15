<?php

namespace App\Http\Controllers;

use App\Models\DemandeSortie;
use App\Models\MouvementSolde;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GestionFinanciereController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $mouvements = MouvementSolde::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $solde = (float) MouvementSolde::query()
            ->where('user_id', $user->id)
            ->selectRaw("SUM(CASE WHEN type = 'deposit' THEN montant ELSE -montant END) as solde")
            ->value('solde');

        return view('gestionfinanciere.index', [
            'mouvements' => $mouvements,
            'solde' => $solde,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $rawMontant = (string) $request->input('montant', '');
        $normalizedMontant = str_replace([' ', "\u{00A0}"], '', $rawMontant);
        $normalizedMontant = str_replace(',', '.', $normalizedMontant);
        $request->merge(['montant' => $normalizedMontant]);

        $validated = $request->validate([
            'type' => ['required', 'in:deposit,withdraw'],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        MouvementSolde::create([
            'user_id' => $user->id,
            'type' => $validated['type'],
            'montant' => $validated['montant'],
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('gestionfinanciere.index');
    }

    public function destroy(Request $request, MouvementSolde $mouvement)
    {
        $user = $request->user();

        abort_if($mouvement->user_id !== $user->id, 403);

        $mouvement->delete();

        return redirect()->route('gestionfinanciere.index');
    }

    public function sorties(Request $request)
    {
        $demandes = DemandeSortie::query()
            ->orderByDesc('date_demande')
            ->paginate(20)
            ->withQueryString();

        return view('gestionfinanciere.sorties', [
            'demandes' => $demandes,
        ]);
    }

    public function storeSortie(Request $request)
    {
        $rawMontant = (string) $request->input('montant', '');
        $normalizedMontant = str_replace([' ', "\u{00A0}"], '', $rawMontant);
        $normalizedMontant = str_replace(',', '.', $normalizedMontant);
        $request->merge(['montant' => $normalizedMontant]);

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
            'motif' => ['required', 'string'],
        ]);

        $now = Carbon::now();

        do {
            $candidate = 'DS-' . $now->format('YmdHis') . '-' . random_int(100, 999);
        } while (DemandeSortie::query()->where('numero_demande', $candidate)->exists());

        DemandeSortie::create([
            'numero_demande' => $candidate,
            'date_demande' => $now,
            'montant' => $validated['montant'],
            'motif' => $validated['motif'],
            'statut' => 'en_attente',
            'montant_payer' => null,
            'montant_reste' => $validated['montant'],
        ]);

        return redirect()->route('gestionfinanciere.sorties');
    }
}
