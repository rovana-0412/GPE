@php
    $utilisateur = session('user', []);
    $nom = $utilisateur['name'] ?? 'Utilisateur';
    $role = $utilisateur['role'] ?? $utilisateur['role_slug'] ?? 'Utilisateur';
    $roleSlug = $utilisateur['role_slug'] ?? $utilisateur['role'] ?? '';
    $initiales = mb_strtoupper(mb_substr($nom, 0, 1));
    $peutCreerBien = in_array($roleSlug, ['administrateur-general', 'gestionnaire-patrimoine'], true);
@endphp

<header class="topbar">
    <button type="button" class="menu-toggle" data-nav-toggle
            aria-controls="navigation" aria-expanded="false" aria-label="Ouvrir le menu">
        <x-icon name="menu"/>
    </button>

    <div class="topbar__context">
        <span class="topbar__context-kicker">GPE · Patrimoine de l’État</span>
        <h1 class="topbar__title">@yield('title', 'Espace de pilotage')</h1>
    </div>

    <form class="topbar__search" action="{{ route('recherche.index') }}" method="GET" role="search">
        <x-icon name="search"/>
        <label class="sr-only" for="recherche-globale">Recherche globale dans GPE</label>
        <input id="recherche-globale" name="q" type="search" value="{{ request('q') }}"
               placeholder="Rechercher dans GPE" autocomplete="off" minlength="2" maxlength="100">
        <button class="topbar__search-submit" type="submit" aria-label="Lancer la recherche">
            <span>Entrée</span>
        </button>
    </form>

    <div class="topbar__actions">
        @if ($peutCreerBien)
            <a class="btn btn--primary topbar__create" href="{{ route('biens.create') }}">
                <x-icon name="plus"/>
                <span>Nouveau bien</span>
            </a>
        @endif
    </div>

    <div class="topbar__profile">
        <div class="avatar" aria-hidden="true">{{ $initiales }}</div>
        <div class="topbar__who">
            <p class="topbar__name">{{ $nom }}</p>
            <p class="topbar__role">{{ str_replace('-', ' ', $role) }}</p>
        </div>
    </div>
</header>
