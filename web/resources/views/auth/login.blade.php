<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion | GPE</title>
    @vite(['resources/css/gpe.css'])
</head>
<body>

<div class="auth">

    <aside class="auth__aside">
        <div class="auth__brand">
            <x-logo :signature="false"/>
        </div>

        <div class="auth__pitch">
            <p class="eyebrow">République togolaise</p>
            <h1>Gestion du Patrimoine de l'État</h1>
            <p>Plateforme nationale de recensement, de suivi et de contrôle
               des biens publics — immobilier et mobilier — au service
               de la transparence et de la bonne gestion.</p>
        </div>

        <p class="auth__foot">
            <x-icon name="landmark"/>
            Administration publique togolaise
        </p>
    </aside>

    <main class="auth__main">
        <div class="auth__card">
            <div class="auth__card-head">
                <p class="eyebrow">Espace sécurisé</p>
                <h2>Connexion</h2>
                <p>Accédez au tableau de bord du patrimoine national.</p>
            </div>

            @if ($errors->any())
                <div class="auth__error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form class="auth__form" action="{{ route('login.store') }}" method="POST">
                @csrf

                <div class="auth__field">
                    <label for="email">Adresse e-mail</label>
                    <input class="auth__input" type="email" id="email" name="email"
                           value="{{ old('email') }}" placeholder="nom@exemple.tg"
                           autocomplete="username" required autofocus>
                    @error('email')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth__field">
                    <label for="password">Mot de passe</label>
                    <input class="auth__input" type="password" id="password" name="password"
                           placeholder="Votre mot de passe" autocomplete="current-password" required>
                    @error('password')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <button class="auth__submit" type="submit">Se connecter</button>
            </form>

            <p class="auth__legal">
                République togolaise — Plateforme GPE<br>
                © {{ date('Y') }} Tous droits réservés
            </p>
        </div>
    </main>

</div>

</body>
</html>
