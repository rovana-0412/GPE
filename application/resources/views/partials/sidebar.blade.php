<aside class="sidebar" id="navigation" aria-label="Navigation principale">
    <div class="sidebar__brand">
        <x-logo/>
    </div>

    <nav class="sidebar__nav">
        <div class="nav-group">
            <p class="eyebrow nav-group__title">Pilotage</p>
            <x-nav-item icon="grid" :href="route('dashboard')" :active="request()->routeIs('dashboard')">Vue générale</x-nav-item>
        </div>

        <div class="nav-group">
            <p class="eyebrow nav-group__title">Patrimoine</p>
            <x-nav-item icon="building">Tous les biens</x-nav-item>
            <x-nav-item icon="tag">Catégories</x-nav-item>
            <x-nav-item icon="link">Affectations</x-nav-item>
        </div>

        <div class="nav-group">
            <p class="eyebrow nav-group__title">Mouvements</p>
            <x-nav-item icon="swap">Transferts</x-nav-item>
            <x-nav-item icon="wrench">Maintenances</x-nav-item>
        </div>

        <div class="nav-group">
            <p class="eyebrow nav-group__title">Administration</p>
            <x-nav-item icon="landmark">Ministères</x-nav-item>
            <x-nav-item icon="sitemap">Services</x-nav-item>
            <x-nav-item icon="user">Utilisateurs et rôles</x-nav-item>
        </div>

        <div class="nav-group">
            <p class="eyebrow nav-group__title">Traçabilité</p>
            <x-nav-item icon="clock">Historique</x-nav-item>
            <x-nav-item icon="chart">Rapports</x-nav-item>
        </div>
    </nav>

    <div class="sidebar__foot">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn--on-dark">
                <x-icon name="logout"/>
                Se déconnecter
            </button>
        </form>
    </div>
</aside>
