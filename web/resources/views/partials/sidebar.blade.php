@php
    $utilisateur = session('user', []);
    $nom = $utilisateur['name'] ?? 'Utilisateur';
    $role = $utilisateur['role'] ?? 'Utilisateur';
@endphp

<aside class="sidebar" id="navigation" aria-label="Navigation principale">
    <div class="sidebar__brand">
        <x-logo :signature="false"/>
    </div>

    <nav class="sidebar__nav">
        <div class="nav-group">
            <p class="nav-group__title">Pilotage</p>
            <x-nav-item icon="grid" :href="route('dashboard')" :active="request()->routeIs('dashboard')">Vue générale</x-nav-item>
            <x-nav-item icon="chart">Statistiques</x-nav-item>
        </div>

        <div class="nav-group">
            <p class="nav-group__title">Patrimoine</p>
            <x-nav-item icon="building">Tous les biens</x-nav-item>
            <x-nav-item icon="tag">Catégories</x-nav-item>
            <x-nav-item icon="link">Affectations</x-nav-item>
        </div>

        <div class="nav-group">
            <p class="nav-group__title">Mouvements</p>
            <x-nav-item icon="swap">
                Transferts
                @if ($transferts_en_attente ?? 0 > 0)
                    <span class="nav-item__badge">{{ $transferts_en_attente }}</span>
                @endif
            </x-nav-item>
            <x-nav-item icon="wrench">Maintenances</x-nav-item>
        </div>

        <div class="nav-group">
            <p class="nav-group__title">Administration</p>
            <x-nav-item icon="landmark">Ministères</x-nav-item>
            <x-nav-item icon="sitemap">Services</x-nav-item>
            <x-nav-item icon="user">Utilisateurs et rôles</x-nav-item>
        </div>

        <div class="nav-group">
            <p class="nav-group__title">Traçabilité</p>
            <x-nav-item icon="clock">Historique</x-nav-item>
            <x-nav-item icon="file-text">Rapports</x-nav-item>
        </div>

        <div class="nav-group">
            <p class="nav-group__title">General</p>
            <x-nav-item icon="settings">Paramètres</x-nav-item>
            <x-nav-item icon="shield">Sécurité</x-nav-item>
        </div>
    </nav>

    <div class="sidebar__foot">
        <div class="topbar__user">
            <div class="topbar__who">
                <p class="topbar__name">{{ $nom }}</p>
                <p class="topbar__role">{{ $role }}</p>
            </div>
            <div class="avatar" aria-hidden="true">
                {{ mb_strtoupper(mb_substr($nom, 0, 1)) }}
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST" class="mt-3">
            @csrf
            <button type="submit" class="btn btn--on-dark">
                <x-icon name="logout"/>
                Se déconnecter
            </button>
        </form>
    </div>
</aside>
