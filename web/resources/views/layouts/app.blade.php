<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#153f33">
    <title>@yield('title', $parametresPlateforme['nom_plateforme'] ?? 'GPE') · {{ $parametresPlateforme['nom_plateforme'] ?? 'GPE' }}</title>
    @vite(['resources/css/gpe.css'])
</head>
<body>
    <a class="skip-link" href="#contenu">Aller au contenu</a>

    <div class="app-shell">
        @include('partials.sidebar')

        <div class="app-main">
            @include('partials.topbar')

            <main id="contenu" class="page-content" tabindex="-1">
                @yield('content')
            </main>
        </div>
    </div>

    <div class="scrim" data-nav-close></div>

    <script>
        (function () {
            var body = document.body;
            var toggle = document.querySelector('[data-nav-toggle]');
            function set(open) {
                body.classList.toggle('nav-open', open);
                if (toggle) {
                    toggle.setAttribute('aria-expanded', String(open));
                    toggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
                }
            }
            if (toggle) toggle.addEventListener('click', function () {
                set(!body.classList.contains('nav-open'));
            });
            var scrim = document.querySelector('[data-nav-close]');
            if (scrim) scrim.addEventListener('click', function () { set(false); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
        })();
    </script>
</body>
</html>
