@extends('layouts.app')

@section('title', 'Utilisateurs')

@section('content')
    <section class="welcome page-heading">
        <div>
            <p class="eyebrow">Administration · Accès</p>
            <h1>Utilisateurs</h1>
            <p>Gérez les comptes et leurs rôles sans supprimer leur historique.</p>
        </div>
        <a class="btn btn--primary" href="{{ route('utilisateurs.create') }}">Créer un compte</a>
    </section>

    @if (session('success'))
        <p class="alert alert--success" role="status">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>
    @endif

    <section class="panel">
        <form method="GET" action="{{ route('utilisateurs.index') }}" class="form-grid">
            <label class="field">
                <span>Recherche</span>
                <input class="input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom ou courriel">
            </label>
            <label class="field">
                <span>Rôle</span>
                <select class="input" name="role_id">
                    <option value="">Tous les rôles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role['id'] }}" @selected((string) ($filters['role_id'] ?? '') === (string) $role['id'])>{{ $role['nom'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Statut</span>
                <select class="input" name="actif">
                    <option value="">Tous</option>
                    <option value="1" @selected(($filters['actif'] ?? '') === '1')>Actifs</option>
                    <option value="0" @selected(($filters['actif'] ?? '') === '0')>Désactivés</option>
                </select>
            </label>
            <button class="btn btn--ghost" type="submit">Filtrer</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Nom</th><th>Courriel</th><th>Rôle</th><th>Service</th><th>Statut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user['name'] }}</td>
                            <td>{{ $user['email'] }}</td>
                            <td>{{ $user['role']['nom'] ?? '—' }}</td>
                            <td>{{ $user['service_id'] ?? '—' }}</td>
                            <td><span class="badge {{ $user['actif'] ? 'badge--success' : 'badge--danger' }}">{{ $user['actif'] ? 'Actif' : 'Désactivé' }}</span></td>
                            <td class="row-actions">
                                <a class="table-link" href="{{ route('utilisateurs.edit', $user['id']) }}">Modifier</a>
                                <form method="POST" action="{{ route('utilisateurs.status', $user['id']) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="actif" value="{{ $user['actif'] ? '0' : '1' }}">
                                    <button class="btn btn--ghost btn--compact" type="submit">
                                        {{ $user['actif'] ? 'Désactiver' : 'Réactiver' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Aucun utilisateur ne correspond à ces critères.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (($pagination['last_page'] ?? 1) > 1)
            <nav class="pagination" aria-label="Pagination des utilisateurs">
                @if (($pagination['current_page'] ?? 1) > 1)
                    <a href="{{ route('utilisateurs.index', array_merge($filters, ['page' => $pagination['current_page'] - 1])) }}">Précédent</a>
                @endif
                <span>Page {{ $pagination['current_page'] }} sur {{ $pagination['last_page'] }}</span>
                @if (($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1))
                    <a href="{{ route('utilisateurs.index', array_merge($filters, ['page' => $pagination['current_page'] + 1])) }}">Suivant</a>
                @endif
            </nav>
        @endif
    </section>
@endsection
