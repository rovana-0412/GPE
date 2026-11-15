@extends('layouts.app')

@section('title', 'Historique des opérations')

@section('content')
    <section class="welcome">
        <p class="eyebrow">Traçabilité</p>
        <h1>Historique des opérations</h1>
        <p>Consultez les créations, modifications et changements de statut enregistrés par le service mouvements.</p>
    </section>

    <section class="panel">
        <form method="GET" action="{{ route('historique.index') }}" class="form-grid">
            <div class="field">
                <label for="bien_id">Identifiant du bien</label>
                <input class="input" id="bien_id" name="bien_id" type="number" min="1" value="{{ request('bien_id') }}">
            </div>
            <div class="field">
                <label for="action">Action</label>
                <input class="input" id="action" name="action" value="{{ request('action') }}">
            </div>
            <div class="form-actions"><button class="btn btn--primary" type="submit">Filtrer</button><a class="btn btn--ghost" href="{{ route('historique.index') }}">Réinitialiser</a></div>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Date</th><th>Utilisateur</th><th>Bien</th><th>Action</th><th>Ancienne valeur</th><th>Nouvelle valeur</th></tr></thead>
                <tbody>
                    @forelse ($operations as $operation)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($operation['created_at'])->locale('fr')->translatedFormat('d/m/Y H:i') }}</td>
                            <td>{{ $operation['utilisateur_id'] ?? '—' }}</td>
                            <td>{{ $operation['bien_id'] ?? '—' }}</td>
                            <td>{{ str_replace('.', ' · ', $operation['action']) }}</td>
                            <td><code>{{ $operation['ancienne_valeur'] === null ? '—' : json_encode($operation['ancienne_valeur'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></td>
                            <td><code>{{ $operation['nouvelle_valeur'] === null ? '—' : json_encode($operation['nouvelle_valeur'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Aucune opération enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
