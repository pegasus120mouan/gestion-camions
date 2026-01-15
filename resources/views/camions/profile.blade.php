@extends('layout.main')
@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Profil camion</h4>
      <div class="d-flex gap-2">
        <a href="{{ route('camions.index') }}" class="btn btn-outline-secondary">Retour</a>
        <a href="{{ route('camions.edit', $camion) }}" class="btn btn-primary">Modifier</a>
      </div>
    </div>

    @php($disk = \Illuminate\Support\Facades\Storage::disk('s3'))
    @php($defaultFace = 'camions/camions.png')
    @php($images = [
      ['label' => 'Face', 'path' => $camion->image_face ?: $defaultFace],
      ['label' => 'Profil gauche', 'path' => $camion->image_profil_gauche],
      ['label' => 'Profil droit', 'path' => $camion->image_profil_droit],
      ['label' => 'Arrière', 'path' => $camion->image_arriere],
    ])
    @php($thumbs = array_values(array_filter($images, fn($img) => !empty($img['path']))))
    @php($main = $thumbs[0] ?? ['label' => 'Face', 'path' => $defaultFace])

    <div class="card">
      <div class="card-body">
        <div class="row g-4">
          <div class="col-lg-6">
            <div class="border rounded p-2 bg-white">
              <img
                id="camionMainImage"
                class="img-fluid rounded w-100"
                style="max-height: 420px; object-fit: contain;"
                alt="{{ $main['label'] }}"
                src="{{ method_exists($disk, 'temporaryUrl') ? $disk->temporaryUrl($main['path'], now()->addMinutes(60)) : $disk->url($main['path']) }}"
              />
            </div>

            <div class="row g-2 mt-2">
              @foreach($thumbs as $img)
                <div class="col-3">
                  <button type="button" class="btn p-0 w-100" style="border: 1px solid rgba(0,0,0,.08);" data-camion-img="{{ method_exists($disk, 'temporaryUrl') ? $disk->temporaryUrl($img['path'], now()->addMinutes(60)) : $disk->url($img['path']) }}" data-camion-alt="{{ $img['label'] }}">
                    <img class="img-fluid" style="height: 72px; width: 100%; object-fit: cover;" alt="{{ $img['label'] }}" src="{{ method_exists($disk, 'temporaryUrl') ? $disk->temporaryUrl($img['path'], now()->addMinutes(60)) : $disk->url($img['path']) }}" />
                  </button>
                </div>
              @endforeach
            </div>
          </div>

          <div class="col-lg-6">
            <h4 class="mb-2">{{ $camion->immatriculation }}</h4>
            <div class="text-muted mb-3">{{ $camion->reference }}</div>

            <div class="card border">
              <div class="card-body">
                <div class="row">
                  <div class="col-6 mb-3">
                    <div class="text-muted">Marque</div>
                    <div class="fw-semibold">{{ $camion->marque ?? '-' }}</div>
                  </div>
                  <div class="col-6 mb-3">
                    <div class="text-muted">Modèle</div>
                    <div class="fw-semibold">{{ $camion->modele ?? '-' }}</div>
                  </div>
                  <div class="col-6 mb-3">
                    <div class="text-muted">Année</div>
                    <div class="fw-semibold">{{ $camion->annee ?? '-' }}</div>
                  </div>
                  <div class="col-6 mb-3">
                    <div class="text-muted">Actif</div>
                    <div class="fw-semibold">{{ $camion->actif ? 'Oui' : 'Non' }}</div>
                  </div>
                  <div class="col-12">
                    <div class="text-muted">Chauffeur</div>
                    <div class="fw-semibold">{{ $camion->chauffeur?->name }} {{ $camion->chauffeur?->prenom }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex gap-2 mt-3">
              <a href="{{ route('camions.edit', $camion) }}" class="btn btn-primary">Modifier</a>
              <a href="{{ route('camions.index') }}" class="btn btn-outline-secondary">Retour</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var mainImg = document.getElementById('camionMainImage');
        if (!mainImg) return;

        document.querySelectorAll('[data-camion-img]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var src = btn.getAttribute('data-camion-img');
            var alt = btn.getAttribute('data-camion-alt') || 'Image';
            if (src) {
              mainImg.src = src;
              mainImg.alt = alt;
            }
          });
        });
      });
    </script>
  </div>
</div>
@endsection
