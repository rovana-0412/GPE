@extends('layouts.app')

@section('title', 'Inventaires physiques')

@section('content')
    <section class="welcome page-heading">
        <div>
            <p class="eyebrow">Mouvements · Contrôle physique</p>
            <h1>Inventaires physiques</h1>
            <p>Suivez les campagnes, leurs constats et les écarts relevés.</p>
        </div>
        @if ($peutGererPatrimoine)
            <a class="btn btn--primary" href="{{ route('inventaires.create') }}">Nouvelle campagne</a>
        @endif
    </section>

    @if (session('success'))<p class="alert alert--success" role="status">{{ session('success') }}</p>@endif
    @if ($errors->any())<p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>@endif

    <section class="panel">
        <form method="GET" action="{{ route('inventaires.index') }}" class="form-grid">
            <label class="field">
                <span>Service</span>
                <select class="input" name="service_id">
                    <option value="">Tous les services</option>
                    @foreach ($services as $service)
                        <option value="{{ $service['id'] }}" @selected((string) ($filters['service_id'] ?? '') === (string) $service['id'])>{{ $service['nom'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Statut</span>
                <select class="input" name="statut">
                    <option value="">Tous les statuts</option>
                    @foreach (['brouillon' => 'Brouillon', 'en_cours' => 'En cours', 'a_valider' => 'À valider', 'valide' => 'Validé'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['statut'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn btn--ghost" type="submit">Filtrer</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Campagne</th><th>Périmètre</th><th>Début</th><th>Biens contrôlés</th><th>Statut</th><th>Accès</th></tr></thead>
                <tbody>
                    @forelse ($inventaires as $inventaire)
                        <tr>
                            <td>{{ $inventaire['titre'] }}</td>
                            <td>{{ data_get($servicesParId, ($inventaire['service_id'] ?? '').'.nom', 'Tous les services') }}</td>
                            <td>{{ $inventaire['date_debut'] ?? '—' }}</td>
                            <td>{{ $inventaire['lignes_count'] }}</td>
                            <td><span class="badge badge--neutral">{{ str_replace('_', ' ', ucfirst($inventaire['statut'])) }}</span></td>
                            <td><a class="table-link" href="{{ route('inventaires.show', $inventaire['id']) }}">Ouvrir la campagne</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Aucune campagne d’inventaire enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
