@php
    $utilisateur = session('user', []);
    $nom = $utilisateur['name'] ?? 'Utilisateur';
    $role = $utilisateur['role'] ?? $utilisateur['role_slug'] ?? 'Utilisateur';
@endphp

<aside class="sidebar" id="navigation" aria-label="Navigation principale">
    <div class="sidebar__brand">
        <a class="sidebar__brand-link" href="{{ route('dashboard') }}" aria-label="GPE, accueil">
            <x-logo/>
        </a>
        <span class="sidebar__brand-caption">{{ $parametresPlateforme['nom_institution'] ?? 'République togolaise' }}</span>
    </div>

    <nav class="sidebar__nav">
        <div class="nav-group">
            <p class="nav-group__title">Pilotage</p>
            <x-nav-item icon="grid" :href="route('dashboard')" :active="request()->routeIs('dashboard')">Vue générale</x-nav-item>
            <x-nav-item icon="chart" :href="route('rapports.index')" :active="request()->routeIs('rapports.*')">Rapports</x-nav-item>
        </div>

        <div class="nav-group">
            <p class="nav-group__title">Patrimoine</p>
            <x-nav-item icon="building" :href="route('biens.index')" :active="request()->routeIs('biens.*')">Tous les biens</x-nav-item>
            <x-nav-item icon="plus" :href="route('acquisitions.index')" :active="request()->routeIs('acquisitions.*')">Acquisitions</x-nav-item>
            <x-nav-item icon="file-text" :href="route('sorties.index')" :active="request()->routeIs('sorties.*')">Sorties et réformes</x-nav-item>
            <x-nav-item icon="tag" :href="route('categories.index')" :active="request()->routeIs('categories.*')">Catégories</x-nav-item>
            <x-nav-item icon="link" :href="route('affectations.index')" :active="request()->routeIs('affectations.*')">Affectations</x-nav-item>
        </div>

        <div class="nav-group">
            <p class="nav-group__title">Mouvements</p>
            <x-nav-item icon="chart" :href="route('inventaires.index')" :active="request()->routeIs('inventaires.*')">Inventaires</x-nav-item>
            <x-nav-item icon="swap" :href="route('transferts.index')" :active="request()->routeIs('transferts.*')">
                Transferts
                @if ($transferts_en_attente ?? 0 > 0)
                    <span class="nav-item__badge">{{ $transferts_en_attente }}</span>
                @endif
            </x-nav-item>
            <x-nav-item icon="wrench" :href="route('maintenances.index')" :active="request()->routeIs('maintenances.*')">Maintenances</x-nav-item>
        </div>

        <div class="nav-group">
            <p class="nav-group__title">Organisation</p>
            <x-nav-item icon="landmark" :href="route('ministeres.index')" :active="request()->routeIs('ministeres.*')">Ministères</x-nav-item>
            <x-nav-item icon="sitemap" :href="route('services.index')" :active="request()->routeIs('services.*')">Services</x-nav-item>
            @if ($peutGererUtilisateurs)
                <x-nav-item icon="user" :href="route('utilisateurs.index')" :active="request()->routeIs('utilisateurs.*')">Utilisateurs</x-nav-item>
                <x-nav-item icon="settings" :href="route('administration.parametres')" :active="request()->routeIs('administration.parametres*')">Paramètres</x-nav-item>
            @endif
        </div>

        <div class="nav-group">
            <p class="nav-group__title">Traçabilité</p>
            <x-nav-item icon="bell" :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">Notifications</x-nav-item>
            <x-nav-item icon="clock" :href="route('historique.index')" :active="request()->routeIs('historique.*')">Historique</x-nav-item>
        </div>
    </nav>

    <div class="sidebar__foot">
        @if (filled($parametresPlateforme['contact_support'] ?? null))
            <a class="sidebar__brand-caption" href="mailto:{{ $parametresPlateforme['contact_support'] }}">
                Assistance : {{ $parametresPlateforme['contact_support'] }}
            </a>
        @endif
        <div class="sidebar__account">
            <div class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($nom, 0, 1)) }}</div>
            <div class="sidebar__account-copy">
                <p class="sidebar__account-name">{{ $nom }}</p>
                <p class="sidebar__account-role">{{ str_replace('-', ' ', $role) }}</p>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST" class="sidebar__logout">
            @csrf
            <button type="submit" class="btn btn--on-dark">
                <x-icon name="logout"/>
                Se déconnecter
            </button>
        </form>
    </div>
</aside>
