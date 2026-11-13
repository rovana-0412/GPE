<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | GPE</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 430px;
            background: white;
            padding: 40px;
            border-radius: 14px;
            box-shadow: 0 10px 35px rgba(15, 23, 42, 0.08);
        }

        .brand {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo {
            width: 58px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            background: #102a43;
            color: white;
            border-radius: 12px;
            font-size: 22px;
            font-weight: bold;
        }

        .brand h1 {
            color: #102a43;
            font-size: 23px;
            margin-bottom: 8px;
        }

        .brand p {
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 14px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            outline: none;
            font-size: 14px;
        }

        input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .error {
            margin-top: 7px;
            color: #dc2626;
            font-size: 13px;
        }

        .error-box {
            margin-bottom: 20px;
            padding: 12px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 7px;
            color: #b91c1c;
            font-size: 13px;
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 7px;
            background: #102a43;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        button:hover {
            background: #1e40af;
        }

        .footer {
            margin-top: 25px;
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.6;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 28px 22px;
            }
        }
    </style>
</head>

<body>

    <main class="login-container">

        <div class="brand">
            <div class="brand-logo">GPE</div>

            <h1>Gestion du Patrimoine de l'État</h1>

            <p>
                Plateforme numérique de gestion et de suivi
                des biens publics.
            </p>
        </div>

        @if ($errors->any())
            <div class="error-box">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email">Adresse e-mail</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="Votre adresse e-mail"
                    required
                    autofocus
                >

                @error('email')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Votre mot de passe"
                    required
                >
            </div>

            <button type="submit">
                Se connecter
            </button>
        </form>

        <div class="footer">
            République d'Aurélia — Plateforme GPE<br>
            © 2026 Tous droits réservés
        </div>

    </main>

</body>
</html>
