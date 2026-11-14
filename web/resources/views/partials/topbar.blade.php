@php
    $utilisateur = session('user', []);
    $nom = $utilisateur['name'] ?? 'Utilisateur';
    $role = $utilisateur['role'] ?? 'Utilisateur';
    $initiales = mb_strtoupper(mb_substr($nom, 0, 1));
@endphp

<header class="topbar">
    <button type="button" class="menu-toggle" data-nav-toggle
            aria-controls="navigation" aria-expanded="false" aria-label="Ouvrir le menu">
        <x-icon name="menu"/>
    </button>

    <div class="topbar__dropdown">
        Tableau de bord
    </div>

    <div class="topbar__search">
        <x-icon name="search"/>
        <input type="text" placeholder="Rechercher dans GPE..." aria-label="Rechercher">
    </div>

    <div class="topbar__actions">
        <button class="topbar__icon-btn" aria-label="Notifications">
            <x-icon name="bell"/>
        </button>
        <button class="topbar__icon-btn" aria-label="Messages">
            <x-icon name="chat"/>
        </button>
        <button class="btn btn--primary">
            <x-icon name="plus"/>
            Ajouter un bien
        </button>
    </div>

    <div class="topbar__user">
        <div class="topbar__who">
            <p class="topbar__name">{{ $nom }}</p>
            <p class="topbar__role">{{ $role }}</p>
        </div>
        <div class="avatar" aria-hidden="true">{{ $initiales }}</div>
    </div>
</header>
