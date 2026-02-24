@extends('layout.main')
@section('title', 'Planteurs')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
  .leaflet-container { z-index: 1; }
</style>
@endpush

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Liste des Planteurs</h4>
      <span class="badge bg-primary fs-6">{{ $total }} planteur(s)</span>
    </div>

    @if($error)
      <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <div class="card">
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-4">
            <input type="text" id="searchPlanteur" class="form-control" placeholder="Rechercher un planteur..." />
          </div>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover" id="planteursTable">
          <thead class="table-light">
            <tr>
              <th>Photo</th>
              <th>N° Fiche</th>
              <th>Nom & Prénoms</th>
              <th>Téléphone</th>
              <th>Lieu de naissance</th>
              <th>Région</th>
              <th>Collecteur</th>
              <th>Date enregistrement</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($planteurs as $planteur)
              <tr>
                <td>
                  @if(!empty($planteur['photo']))
                    <img src="{{ route('minio.planteur.image', ['filename' => $planteur['photo']]) }}" alt="Photo" class="rounded-circle" width="40" height="40" style="object-fit: cover;" onerror="this.src='{{ asset('img/avatars/default.png') }}'" />
                  @else
                    <div class="rounded-circle bg-label-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                      <i class="bx bx-user"></i>
                    </div>
                  @endif
                </td>
                <td><strong>{{ $planteur['numero_fiche'] ?? '-' }}</strong></td>
                <td>{{ $planteur['nom_prenoms'] ?? '-' }}</td>
                <td>{{ $planteur['telephone'] ?? '-' }}</td>
                <td>{{ $planteur['lieu_naissance'] ?? '-' }}</td>
                <td>{{ $planteur['exploitation']['region'] ?? '-' }}</td>
                <td>
                  @if(!empty($planteur['collecteur']))
                    <span class="badge bg-label-info">
                      {{ $planteur['collecteur']['nom'] ?? '' }} {{ $planteur['collecteur']['prenoms'] ?? '' }}
                    </span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  @if(!empty($planteur['date_enregistrement']))
                    {{ \Carbon\Carbon::parse($planteur['date_enregistrement'])->format('d-m-Y') }}
                  @else
                    -
                  @endif
                </td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#modalPlanteur{{ $planteur['id'] }}" title="Voir détails">
                    <i class="bx bx-show"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEditPlanteur{{ $planteur['id'] }}" title="Modifier">
                    <i class="bx bx-edit"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDeletePlanteur{{ $planteur['id'] }}" title="Supprimer">
                    <i class="bx bx-trash"></i>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center">Aucun planteur trouvé</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modals pour les détails des planteurs -->
@foreach($planteurs as $planteur)
<div class="modal fade" id="modalPlanteur{{ $planteur['id'] }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title text-white"><i class="bx bx-user me-2"></i>{{ $planteur['nom_prenoms'] ?? 'Planteur' }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-4 text-center mb-3">
            @if(!empty($planteur['photo']))
              <img src="{{ route('minio.planteur.image', ['filename' => $planteur['photo']]) }}" alt="Photo" class="rounded img-fluid mb-2" style="max-height: 200px; object-fit: cover;" onerror="this.src='{{ asset('img/avatars/default.png') }}'" />
            @else
              <div class="rounded bg-label-secondary d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px;">
                <i class="bx bx-user" style="font-size: 4rem;"></i>
              </div>
            @endif
            <h5 class="mt-2 mb-0">{{ $planteur['nom_prenoms'] ?? '-' }}</h5>
            <small class="text-muted">{{ $planteur['numero_fiche'] ?? '' }}</small>
          </div>
          <div class="col-md-8">
            <h6 class="text-primary mb-3"><i class="bx bx-id-card me-1"></i>Informations personnelles</h6>
            <div class="row">
              <div class="col-6">
                <p><strong>Date de naissance:</strong> {{ $planteur['date_naissance'] ?? '-' }}</p>
                <p><strong>Lieu de naissance:</strong> {{ $planteur['lieu_naissance'] ?? '-' }}</p>
                <p><strong>Pièce d'identité:</strong> {{ $planteur['piece_identite'] ?? '-' }}</p>
              </div>
              <div class="col-6">
                <p><strong>Téléphone:</strong> {{ $planteur['telephone'] ?? '-' }}</p>
                <p><strong>Situation matrimoniale:</strong> {{ $planteur['situation_matrimoniale'] ?? '-' }}</p>
                <p><strong>Nombre d'enfants:</strong> {{ $planteur['nombre_enfants'] ?? 0 }}</p>
              </div>
            </div>
          </div>
        </div>

        <hr>

        <div class="row">
          <div class="col-md-6">
            <h6 class="text-success mb-3"><i class="bx bx-map me-1"></i>Exploitation</h6>
            @if(!empty($planteur['exploitation']))
              <p><strong>Région:</strong> {{ $planteur['exploitation']['region'] ?? '-' }}</p>
              <p><strong>Sous-préfecture/Village:</strong> {{ $planteur['exploitation']['sous_prefecture_village'] ?? '-' }}</p>
              @if(!empty($planteur['exploitation']['latitude']) && !empty($planteur['exploitation']['longitude']))
                <p><strong>Coordonnées GPS:</strong> 
                  <span class="badge bg-label-success">
                    {{ number_format($planteur['exploitation']['latitude'], 6) }}, {{ number_format($planteur['exploitation']['longitude'], 6) }}
                  </span>
                </p>
              @else
                <p class="text-muted"><i class="bx bx-map-pin me-1"></i>Pas de coordonnées GPS</p>
              @endif
            @else
              <p class="text-muted">Aucune exploitation enregistrée</p>
            @endif
          </div>
          <div class="col-md-6">
            <h6 class="text-warning mb-3"><i class="bx bx-leaf me-1"></i>Cultures</h6>
            @if(!empty($planteur['cultures']))
              @foreach($planteur['cultures'] as $culture)
                <div class="mb-2 p-2 bg-light rounded">
                  <strong>{{ $culture['type_culture'] ?? 'Culture' }}</strong>
                  @if(!empty($culture['autre_culture']))
                    ({{ $culture['autre_culture'] }})
                  @endif
                  <br>
                  <small>
                    Superficie: {{ $culture['superficie_ha'] ?? '-' }} ha |
                    Âge: {{ $culture['age_culture'] ?? '-' }} ans |
                    Production: {{ $culture['production_estimee_kg'] ?? '-' }} kg
                  </small>
                </div>
              @endforeach
            @else
              <p class="text-muted">Aucune culture enregistrée</p>
            @endif
          </div>
        </div>

        @if(!empty($planteur['collecteur']))
          <hr>
          <h6 class="text-info mb-3"><i class="bx bx-user-check me-1"></i>Collecteur</h6>
          <p><strong>Nom:</strong> {{ $planteur['collecteur']['nom'] ?? '' }} {{ $planteur['collecteur']['prenoms'] ?? '' }}</p>
        @endif

        {{-- Cartographie des parcelles --}}
        @if(!empty($planteur['parcelles']) || (!empty($planteur['exploitation']['latitude']) && !empty($planteur['exploitation']['longitude'])))
          <hr>
          <h6 class="text-primary mb-3"><i class="bx bx-map-alt me-1"></i>Cartographie des champs</h6>
          @if(!empty($planteur['parcelles']))
            <div class="mb-2">
              <small class="text-muted">
                <strong>{{ count($planteur['parcelles']) }}</strong> parcelle(s) enregistrée(s)
                @php
                  $totalSuperficie = collect($planteur['parcelles'])->sum('superficie_calculee');
                @endphp
                @if($totalSuperficie > 0)
                  - Superficie totale: <strong>{{ number_format($totalSuperficie, 4) }} ha</strong>
                @endif
              </small>
            </div>
          @endif
          <div id="map-{{ $planteur['id'] }}" class="rounded border" style="height: 300px; width: 100%;"></div>
          @if(!empty($planteur['parcelles']))
            <div class="mt-2">
              @foreach($planteur['parcelles'] as $parcelle)
                <span class="badge bg-label-success me-1 mb-1">
                  <i class="bx bx-shape-polygon me-1"></i>{{ $parcelle['nom'] ?? 'Parcelle' }}
                  @if(!empty($parcelle['superficie_calculee']))
                    ({{ number_format($parcelle['superficie_calculee'], 4) }} ha)
                  @endif
                </span>
              @endforeach
            </div>
          @endif
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>
@endforeach

<!-- Modals pour modifier les planteurs -->
@foreach($planteurs as $planteur)
<div class="modal fade" id="modalEditPlanteur{{ $planteur['id'] }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bx bx-edit me-2"></i>Modifier le planteur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('planteurs.update', ['id' => $planteur['id']]) }}">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">N° Fiche</label>
              <input type="text" name="numero_fiche" class="form-control" value="{{ $planteur['numero_fiche'] ?? '' }}" readonly />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Nom & Prénoms <span class="text-danger">*</span></label>
              <input type="text" name="nom_prenoms" class="form-control" value="{{ $planteur['nom_prenoms'] ?? '' }}" required />
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Téléphone</label>
              <input type="text" name="telephone" class="form-control" value="{{ $planteur['telephone'] ?? '' }}" />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date de naissance</label>
              <input type="date" name="date_naissance" class="form-control" value="{{ $planteur['date_naissance'] ?? '' }}" />
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Lieu de naissance</label>
              <input type="text" name="lieu_naissance" class="form-control" value="{{ $planteur['lieu_naissance'] ?? '' }}" />
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Pièce d'identité</label>
              <input type="text" name="piece_identite" class="form-control" value="{{ $planteur['piece_identite'] ?? '' }}" />
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Situation matrimoniale</label>
              <select name="situation_matrimoniale" class="form-select">
                <option value="">-- Sélectionner --</option>
                <option value="Célibataire" {{ ($planteur['situation_matrimoniale'] ?? '') == 'Célibataire' ? 'selected' : '' }}>Célibataire</option>
                <option value="Marié(e)" {{ ($planteur['situation_matrimoniale'] ?? '') == 'Marié(e)' ? 'selected' : '' }}>Marié(e)</option>
                <option value="Divorcé(e)" {{ ($planteur['situation_matrimoniale'] ?? '') == 'Divorcé(e)' ? 'selected' : '' }}>Divorcé(e)</option>
                <option value="Veuf(ve)" {{ ($planteur['situation_matrimoniale'] ?? '') == 'Veuf(ve)' ? 'selected' : '' }}>Veuf(ve)</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Nombre d'enfants</label>
              <input type="number" name="nombre_enfants" class="form-control" value="{{ $planteur['nombre_enfants'] ?? 0 }}" min="0" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning"><i class="bx bx-save me-1"></i>Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

<!-- Modals pour supprimer les planteurs -->
@foreach($planteurs as $planteur)
<div class="modal fade" id="modalDeletePlanteur{{ $planteur['id'] }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white"><i class="bx bx-trash me-2"></i>Supprimer le planteur</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Êtes-vous sûr de vouloir supprimer ce planteur ?</p>
        <div class="alert alert-warning">
          <strong>{{ $planteur['nom_prenoms'] ?? 'Planteur' }}</strong><br>
          <small>N° Fiche: {{ $planteur['numero_fiche'] ?? '-' }}</small>
        </div>
        <p class="text-danger"><small><i class="bx bx-error me-1"></i>Cette action est irréversible.</small></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <form method="POST" action="{{ route('planteurs.destroy', ['id' => $planteur['id']]) }}" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger"><i class="bx bx-trash me-1"></i>Supprimer</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endforeach

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('searchPlanteur');
  const table = document.getElementById('planteursTable');
  const rows = table.querySelectorAll('tbody tr');

  searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
  });

  // Données des planteurs avec coordonnées GPS et parcelles
  @php
    $planteursFiltered = collect($planteurs)->filter(function($p) {
      $hasCoords = !empty($p['exploitation']['latitude']) && !empty($p['exploitation']['longitude']);
      $hasParcelles = !empty($p['parcelles']);
      return $hasCoords || $hasParcelles;
    })->map(function($p) {
      return [
        'id' => $p['id'],
        'nom' => $p['nom_prenoms'] ?? '',
        'lat' => $p['exploitation']['latitude'] ?? null,
        'lng' => $p['exploitation']['longitude'] ?? null,
        'region' => $p['exploitation']['region'] ?? '',
        'village' => $p['exploitation']['sous_prefecture_village'] ?? '',
        'parcelles' => $p['parcelles'] ?? []
      ];
    })->values();
  @endphp
  const planteursData = @json($planteursFiltered);

  // Couleurs pour les parcelles
  const parcelleColors = ['#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14'];

  // Initialiser les cartes dans les modals
  const mapsInitialized = {};

  planteursData.forEach(function(planteur) {
    const modalId = 'modalPlanteur' + planteur.id;
    const mapId = 'map-' + planteur.id;
    const modal = document.getElementById(modalId);

    if (modal) {
      modal.addEventListener('shown.bs.modal', function() {
        if (!mapsInitialized[planteur.id]) {
          const mapContainer = document.getElementById(mapId);
          if (mapContainer) {
            // Déterminer le centre de la carte
            let centerLat = planteur.lat || 5.35;
            let centerLng = planteur.lng || -4.0;
            let initialZoom = 15;

            // Si on a des parcelles, calculer le centre à partir des points
            if (planteur.parcelles && planteur.parcelles.length > 0) {
              let allPoints = [];
              planteur.parcelles.forEach(function(parcelle) {
                if (parcelle.points && parcelle.points.length > 0) {
                  parcelle.points.forEach(function(pt) {
                    allPoints.push([pt.latitude, pt.longitude]);
                  });
                }
              });
              if (allPoints.length > 0) {
                let sumLat = 0, sumLng = 0;
                allPoints.forEach(function(pt) {
                  sumLat += pt[0];
                  sumLng += pt[1];
                });
                centerLat = sumLat / allPoints.length;
                centerLng = sumLng / allPoints.length;
              }
            }

            const map = L.map(mapId).setView([centerLat, centerLng], initialZoom);
            
            // Couche satellite (optionnel) ou OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
              attribution: '© OpenStreetMap'
            }).addTo(map);

            // Ajouter le marqueur de position de l'exploitation
            if (planteur.lat && planteur.lng) {
              L.marker([planteur.lat, planteur.lng])
                .addTo(map)
                .bindPopup('<strong>' + planteur.nom + '</strong><br>' + planteur.region + '<br>' + planteur.village);
            }

            // Ajouter les polygones des parcelles
            const bounds = [];
            
            if (planteur.parcelles && planteur.parcelles.length > 0) {
              planteur.parcelles.forEach(function(parcelle, index) {
                if (parcelle.points && parcelle.points.length >= 3) {
                  const polygonPoints = parcelle.points.map(function(pt) {
                    if (pt.latitude && pt.longitude) {
                      bounds.push([pt.latitude, pt.longitude]);
                      return [pt.latitude, pt.longitude];
                    }
                    return null;
                  }).filter(function(pt) { return pt !== null; });

                  if (polygonPoints.length >= 3) {
                    const color = parcelleColors[index % parcelleColors.length];
                    
                    const polygon = L.polygon(polygonPoints, {
                      color: color,
                      fillColor: color,
                      fillOpacity: 0.4,
                      weight: 3
                    }).addTo(map);

                    let popupContent = '<strong>' + (parcelle.nom || 'Parcelle') + '</strong>';
                    if (parcelle.superficie_calculee) {
                      popupContent += '<br>Superficie: ' + parcelle.superficie_calculee.toFixed(4) + ' ha';
                    }
                    polygon.bindPopup(popupContent);
                  }
                }
              });

              // Ajuster la vue pour voir toutes les parcelles
              if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [30, 30] });
              }
            }

            mapsInitialized[planteur.id] = map;

            // Forcer le redimensionnement de la carte après l'ouverture du modal
            setTimeout(function() {
              map.invalidateSize();
            }, 200);
            setTimeout(function() {
              map.invalidateSize();
              // Recentrer sur les bounds si disponibles
              if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [20, 20] });
              }
            }, 500);
          }
        } else {
          // Redimensionner la carte existante
          setTimeout(function() {
            mapsInitialized[planteur.id].invalidateSize();
          }, 200);
        }
      });
    }
  });
});
</script>
@endsection
