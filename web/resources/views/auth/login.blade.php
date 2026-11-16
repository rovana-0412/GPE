<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#153f33">
    <title>Connexion · GPE</title>
    @vite(['resources/css/gpe.css'])
</head>
<body class="auth-page">
    <main class="auth">
        <section class="auth__story" aria-labelledby="auth-title">
            <div class="auth__brand">
                <x-logo/>
                <span class="auth__republic">République togolaise</span>
            </div>

            <div class="auth__story-copy">
                <p class="eyebrow">Gestion publique · Patrimoine national</p>
                <h1 id="auth-title">Le patrimoine public, <em>en confiance.</em></h1>
                <p class="auth__lead">
                    Une vision claire des biens de l’État, de leur affectation et de leur entretien.
                    La plateforme de référence pour une gestion rigoureuse et traçable.
                </p>
            </div>

            <div class="auth__signature">
                <span class="auth__signature-line"></span>
                <p>Transparence, continuité et responsabilité au service de l’action publique.</p>
            </div>

            <div class="auth__seal" aria-hidden="true">
                <span>GPE</span>
                <span>TOGO</span>
            </div>
        </section>

        <section class="auth__access" aria-label="Formulaire de connexion">
            <div class="auth__card">
                <div class="auth__card-head">
                    <span class="auth__secure"><x-icon name="shield"/> Accès sécurisé</span>
                    <h2>Bienvenue</h2>
                    <p>Connectez-vous à votre espace de gestion.</p>
                </div>

                @if (session('success'))
                    <p class="alert alert--success auth__success" role="status" aria-live="polite">
                        {{ session('success') }}
                    </p>
                @endif

                @if ($errors->any())
                    <div class="alert alert--danger auth__error" role="alert">
                        <strong>Connexion impossible</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form class="auth__form" action="{{ route('login.store') }}" method="POST">
                    @csrf

                    <div class="field auth__field">
                        <label for="email">Adresse e-mail</label>
                        <input class="input auth__input" type="email" id="email" name="email"
                               value="{{ old('email') }}" placeholder="nom@exemple.tg"
                               autocomplete="username" required autofocus
                               @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
                        @error('email')
                            <span class="field__error" id="email-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field auth__field">
                        <label for="password">Mot de passe</label>
                        <div class="auth__password-control">
                            <input class="input auth__input" type="password" id="password" name="password"
                                   placeholder="Saisissez votre mot de passe"
                                   autocomplete="current-password" required
                                   @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                            <button class="auth__password-toggle" type="button" aria-controls="password"
                                    aria-label="Afficher le mot de passe" aria-pressed="false" data-password-toggle>
                                Afficher
                            </button>
                        </div>
                        @error('password')
                            <span class="field__error" id="password-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <button class="btn btn--primary auth__submit" type="submit">
                        <span class="auth__submit-label">Ouvrir ma session</span>
                        <span class="auth__loading" role="status" aria-live="polite">
                            <span class="auth__spinner" aria-hidden="true"></span>
                            Connexion en cours…
                        </span>
                        <x-icon name="arrow-right" class="auth__submit-arrow"/>
                    </button>
                </form>

                <p class="auth__help">
                    L’accès à cette plateforme est réservé aux agents habilités de l’administration.
                    Pour toute assistance, rapprochez-vous de votre administrateur.
                </p>

                <footer class="auth__legal">
                    <span>GPE · Gestion du Patrimoine de l’État</span>
                    <span>© {{ date('Y') }} · Lomé, Togo</span>
                </footer>
            </div>
        </section>
    </main>
    <script>
        (function () {
            var form = document.querySelector('.auth__form');
            var submit = document.querySelector('.auth__submit');
            var password = document.getElementById('password');
            var toggle = document.querySelector('[data-password-toggle]');

            if (toggle && password) {
                toggle.addEventListener('click', function () {
                    var visible = password.type === 'password';
                    password.type = visible ? 'text' : 'password';
                    toggle.textContent = visible ? 'Masquer' : 'Afficher';
                    toggle.setAttribute('aria-label', visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                    toggle.setAttribute('aria-pressed', String(visible));
                    password.focus();
                });
            }

            if (form && submit) {
                form.addEventListener('submit', function () {
                    submit.disabled = true;
                    submit.classList.add('is-loading');
                    submit.setAttribute('aria-busy', 'true');
                });
            }
        })();
    </script>
</body>
</html>
