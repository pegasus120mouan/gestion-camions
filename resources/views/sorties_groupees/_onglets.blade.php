@php
  $ongletActif = $onglet ?? (request()->routeIs('sorties_groupees.creer', 'sorties_groupees.depenses') ? 'creer' : 'fiches');
@endphp
<ul class="nav nav-pills mb-4">
  <li class="nav-item">
    <a class="nav-link {{ $ongletActif === 'fiches' ? 'active' : '' }}" href="{{ route('sorties_groupees.index') }}">
      <i class="bx bx-list-ul me-1"></i>Fiches groupées
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link {{ $ongletActif === 'creer' ? 'active' : '' }}" href="{{ route('sorties_groupees.creer') }}">
      <i class="bx bx-plus me-1"></i>Nouvelle fiche
    </a>
  </li>
</ul>
