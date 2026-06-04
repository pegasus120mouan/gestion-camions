@php
  $usinesParProduitJson = json_encode(
    \App\Models\Usine::query()
      ->when(\Illuminate\Support\Facades\Schema::hasColumn('usines', 'produit_id'), fn ($q) => $q->whereNotNull('produit_id'))
      ->orderBy('nom_usine')
      ->get()
      ->groupBy('produit_id')
      ->map(fn ($g) => $g->map(fn ($u) => ['nom' => $u->nom_usine])->values()->all())
      ->all()
  );
@endphp
<script>
var usinesParProduitMontant = {!! $usinesParProduitJson !!};

document.addEventListener('DOMContentLoaded', function() {
  var filtreProduit = document.getElementById('filtre_produit_montant');
  var filtreUsine = document.getElementById('filtre_usine_montant');
  if (!filtreProduit || !filtreUsine) return;

  var usineInitiale = filtreUsine.dataset.usineSelectionnee || '';
  var toutesUsines = [];
  @foreach($usines ?? [] as $nomUsine)
    toutesUsines.push(@json($nomUsine));
  @endforeach

  function remplirUsinesFiltre(produitId, garderSelection) {
    var selection = garderSelection ? usineInitiale : '';
    usineInitiale = '';
    filtreUsine.innerHTML = '<option value="">Toutes</option>';
    var liste = produitId ? (usinesParProduitMontant[produitId] || usinesParProduitMontant[String(produitId)] || []) : [];
    if (produitId && liste.length) {
      liste.forEach(function(u) {
        var opt = document.createElement('option');
        opt.value = u.nom;
        opt.textContent = u.nom;
        if (selection && u.nom === selection) opt.selected = true;
        filtreUsine.appendChild(opt);
      });
    } else if (!produitId) {
      toutesUsines.forEach(function(nom) {
        var opt = document.createElement('option');
        opt.value = nom;
        opt.textContent = nom;
        if (selection && nom === selection) opt.selected = true;
        filtreUsine.appendChild(opt);
      });
    }
  }

  filtreProduit.addEventListener('change', function() {
    remplirUsinesFiltre(this.value, false);
  });
  remplirUsinesFiltre(filtreProduit.value, true);
});
</script>
