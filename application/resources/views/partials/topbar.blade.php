@php
    $utilisateur = Auth::user();
    $initiales = mb_strtoupper(
        collect(explode(' ', trim($utilisateur->name)))
            ->filter()->take(2)
            ->map(fn ($mot) => mb_substr($mot, 0, 1))
            ->implode('')
    );
@endphp

<header class="topbar">
    <button type="button" class="menu-toggle" data-nav-toggle
            aria-controls="navigation" aria-expanded="false" aria-label="Ouvrir le menu">
        <x-icon name="menu"/>
    </button>

    <h2 class="topbar__title">@yield('title')</h2>

    <div class="topbar__user">
        <div class="topbar__who">
            <p class="topbar__name">{{ $utilisateur->name }}</p>
            <p class="topbar__role">{{ $utilisateur->role?->nom ?? 'Utilisateur' }}</p>
        </div>
        <div class="avatar" aria-hidden="true">{{ $initiales }}</div>
    </div>
</header>
