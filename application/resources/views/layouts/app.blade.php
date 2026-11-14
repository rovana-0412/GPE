<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'GPE') | GPE</title>
    @vite(['resources/css/gpe.css'])
</head>
<body>
    <a class="skip-link" href="#contenu">Aller au contenu</a>

    <div class="app">
        @include('partials.sidebar')

        <div class="main">
            @include('partials.topbar')

            <main id="contenu" class="content" tabindex="-1">
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
                if (toggle) toggle.setAttribute('aria-expanded', open);
            }
            if (toggle) toggle.addEventListener('click', function () {
                set(!body.classList.contains('nav-open'));
            });
            document.querySelector('[data-nav-close]').addEventListener('click', function () { set(false); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
        })();
    </script>
</body>
</html>
