
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord | GPE</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f1f5f9;
            color: #1e293b;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Barre latérale */
        .sidebar {
            width: 250px;
            background: #102a43;
            color: white;
            padding: 25px 18px;
            flex-shrink: 0;
        }

        .brand {
            padding: 5px 10px 28px;
            border-bottom: 1px solid #334e68;
        }

        .brand h2 {
            font-size: 22px;
            margin-bottom: 7px;
        }

        .brand p {
            font-size: 12px;
            color: #bcccdc;
            line-height: 1.5;
        }

        .menu-title {
            margin: 28px 10px 12px;
            color: #9fb3c8;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .menu a {
            display: block;
            padding: 12px 13px;
            margin-bottom: 6px;
            border-radius: 7px;
            color: #d9e2ec;
            text-decoration: none;
            font-size: 14px;
            transition: 0.2s;
        }

        .menu a:hover,
        .menu a.active {
            background: #243f5a;
            color: white;
        }

        .menu a.active {
            border-left: 3px solid #60a5fa;
        }

        /* Zone principale */
        .main {
            flex: 1;
            min-width: 0;
        }

        .topbar {
            height: 75px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 35px;
            border-bottom: 1px solid #e2e8f0;
        }

        .topbar h3 {
            color: #102a43;
            font-size: 18px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 38px;
            height: 38px;
            background: #dbeafe;
            color: #1d4ed8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .user-name {
            font-size: 13px;
            font-weight: bold;
        }

        .user-role {
            color: #64748b;
            font-size: 11px;
            margin-top: 4px;
        }

        .content {
            padding: 35px;
        }

        .welcome {
            margin-bottom: 28px;
        }

        .welcome h1 {
            font-size: 25px;
            color: #102a43;
            margin-bottom: 8px;
        }

        .welcome p {
            color: #64748b;
            font-size: 14px;
        }

        /* Cartes statistiques */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 23px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.03);
        }

        .stat-label {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 14px;
        }

        .stat-value {
            font-size: 29px;
            font-weight: bold;
            color: #102a43;
        }

        .stat-note {
            margin-top: 10px;
            color: #94a3b8;
            font-size: 12px;
        }

        /* Bloc inférieur */
        .section-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .section-card h2 {
            font-size: 17px;
            color: #102a43;
            margin-bottom: 20px;
        }

        .activity-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        .activity-item {
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .activity-item span {
            display: block;
            color: #64748b;
            font-size: 13px;
            margin-bottom: 9px;
        }

        .activity-item strong {
            font-size: 23px;
            color: #102a43;
        }

        .logout-form {
            margin-top: 30px;
        }

        .logout-button {
            width: 100%;
            padding: 12px;
            border: 1px solid #52667a;
            border-radius: 7px;
            background: transparent;
            color: #e2e8f0;
            cursor: pointer;
            font-size: 13px;
        }

        .logout-button:hover {
            background: #243f5a;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .sidebar {
                width: 210px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .layout {
                display: block;
            }

            .sidebar {
                width: 100%;
            }

            .topbar {
                padding: 0 18px;
            }

            .content {
                padding: 22px 16px;
            }

            .stats-grid,
            .activity-list {
                grid-template-columns: 1fr;
            }

            .user-name {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <aside class="sidebar">

        <div class="brand">
            <h2>GPE</h2>
            <p>Gestion du Patrimoine de l'État</p>
        </div>

        <p class="menu-title">Menu principal</p>

        <nav class="menu">
            <a href="{{ route('dashboard') }}" class="active">Tableau de bord</a>
            <a href="#">Inventaire des biens</a>
            <a href="#">Catégories</a>
            <a href="#">Affectations</a>
            <a href="#">Transferts</a>
            <a href="#">Maintenances</a>
        </nav>

        <p class="menu-title">Administration</p>

        <nav class="menu">
            <a href="#">Ministères</a>
            <a href="#">Services</a>
            <a href="#">Utilisateurs</a>
            <a href="#">Historique</a>
        </nav>

        <form action="{{ route('logout') }}" method="POST" class="logout-form">
            @csrf
            <button type="submit" class="logout-button">
                Se déconnecter
            </button>
        </form>

    </aside>

    <main class="main">

        <header class="topbar">

            <h3>Administration GPE</h3>

            <div class="user-info">
                <div class="avatar">A</div>

                <div>
                    <p class="user-name">{{ Auth::user()->name }}</p>
                    <p class="user-role">
                        {{ Auth::user()->role?->nom ?? 'Utilisateur' }}
                    </p>
                </div>
            </div>

        </header>

        <section class="content">

            <div class="welcome">
                <h1>Bienvenue sur votre tableau de bord</h1>
                <p>Voici un aperçu général du patrimoine de l'État.</p>
            </div>

            <div class="stats-grid">

                <div class="stat-card">
                    <p class="stat-label">Biens enregistrés</p>
                    <p class="stat-value">{{ $statistiques['biens'] }}</p>
                    <p class="stat-note">Biens actifs dans le patrimoine</p>
                </div>

                <div class="stat-card">
                    <p class="stat-label">Catégories</p>
                    <p class="stat-value">{{ $statistiques['categories'] }}</p>
                    <p class="stat-note">Catégories de biens actives</p>
                </div>

                <div class="stat-card">
                    <p class="stat-label">Ministères</p>
                    <p class="stat-value">{{ $statistiques['ministeres'] }}</p>
                    <p class="stat-note">Ministères enregistrés</p>
                </div>

                <div class="stat-card">
                    <p class="stat-label">Services</p>
                    <p class="stat-value">{{ $statistiques['services'] }}</p>
                    <p class="stat-note">Services administratifs actifs</p>
                </div>

                <div class="stat-card">
                    <p class="stat-label">Transferts en attente</p>
                    <p class="stat-value">{{ $statistiques['transferts_en_attente'] }}</p>
                    <p class="stat-note">Demandes à examiner</p>
                </div>

                <div class="stat-card">
                    <p class="stat-label">Maintenances signalées</p>
                    <p class="stat-value">{{ $statistiques['maintenances'] }}</p>
                    <p class="stat-note">Interventions à suivre</p>
                </div>

            </div>

            <div class="section-card">

                <h2>État général de la plateforme</h2>

                <div class="activity-list">

                    <div class="activity-item">
                        <span>Patrimoine immobilier et mobilier</span>
                        <strong>{{ $statistiques['biens'] }}</strong>
                    </div>

                    <div class="activity-item">
                        <span>Structures administratives</span>
                        <strong>{{ $statistiques['ministeres'] + $statistiques['services'] }}</strong>
                    </div>

                </div>

            </div>

        </section>

    </main>

</div>

</body>
</html>
