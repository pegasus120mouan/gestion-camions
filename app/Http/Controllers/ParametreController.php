<?php

namespace App\Http\Controllers;

use App\Models\Approvisionnement;
use App\Models\DepensePont;
use App\Models\EntreeStock;
use App\Models\FicheSortie;
use App\Models\Parc;
use App\Models\Stock;
use App\Services\MesTicketsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParametreController extends Controller
{
    public function index(Request $request, MesTicketsService $mesTicketsService)
    {
        try {
            $counts = $mesTicketsService->countsTicketsEnAttente($request);
        } catch (\Throwable) {
            $counts = ['total' => 0, 'professionnel' => 0, 'particulier' => 0];
        }

        return view('parametres.index', [
            'countsEnAttente' => $counts,
            'fichesNonDechargees' => FicheSortie::query()->whereNull('date_dechargement')->count(),
            'remiseAZero' => [
                'stocks' => Stock::query()->count() + EntreeStock::query()->count(),
                'ponts' => Approvisionnement::query()->count() + DepensePont::query()->count(),
                'parcs' => Parc::query()->count(),
            ],
        ]);
    }

    /**
     * Stocks des ponts : supprime les stocks et leurs entrées (les fiches déchargées perdent leur lien au stock).
     * Ponts : supprime les approvisionnements et dépenses des ponts.
     * Parcs : supprime tous les parcs (stocks et fiches perdent leur lien au parc).
     */
    public function vider(Request $request)
    {
        $cible = (string) $request->validate([
            'cible' => ['required', 'in:stocks,ponts,parcs'],
        ])['cible'];

        $message = DB::transaction(function () use ($cible) {
            return match ($cible) {
                'stocks' => $this->viderStocks(),
                'ponts' => $this->viderPonts(),
                'parcs' => $this->viderParcs(),
            };
        });

        return redirect()->route('parametres.index')->with('success', $message);
    }

    private function viderStocks(): string
    {
        $stockIds = Stock::query()->pluck('id');

        FicheSortie::query()->whereIn('stock_id', $stockIds)->update(['stock_id' => null]);
        $entrees = EntreeStock::query()->whereIn('stock_id', $stockIds)->delete();
        $stocks = Stock::query()->whereIn('id', $stockIds)->delete();

        return "Stocks des ponts vidés : {$stocks} stock(s) et {$entrees} entrée(s) supprimés.";
    }

    private function viderPonts(): string
    {
        $approvisionnements = Approvisionnement::query()->delete();
        $depenses = DepensePont::query()->delete();

        return "Ponts vidés : {$approvisionnements} approvisionnement(s) et {$depenses} dépense(s) supprimés.";
    }

    private function viderParcs(): string
    {
        $parcIds = Parc::query()->pluck('id');

        Stock::query()->whereIn('parc_id', $parcIds)->update(['parc_id' => null, 'nom_parc' => null]);
        FicheSortie::query()->whereIn('parc_id', $parcIds)->update(['parc_id' => null]);
        $parcs = Parc::query()->whereIn('id', $parcIds)->delete();

        return "Parcs vidés : {$parcs} parc(s) supprimé(s).";
    }
}
