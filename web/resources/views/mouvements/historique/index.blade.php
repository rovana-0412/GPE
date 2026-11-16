@extends('layouts.app')

@section('title', 'Historique des opérations')

@section('content')
    <section class="welcome">
        <p class="eyebrow">Traçabilité</p>
        <h1>Historique des opérations</h1>
        <p>Consultez les actions métier enregistrées par les microservices, selon vos droits de consultation.</p>
    </section>

    <section class="panel">
        <div class="panel__head">
            <div><p class="eyebrow">Journal de traçabilité</p><h2 class="panel__title">Filtrer les opérations</h2></div>
        </div>
        <form method="GET" action="{{ route('historique.index') }}" class="form-grid">
            <label class="field">
                <span>Journal source</span>
                <select class="input" name="source">
                    <option value="mouvements" @selected($source === 'mouvements')>Mouvements</option>
                    @if ($canViewAdminAudit)
                        <option value="patrimoine" @selected($source === 'patrimoine')>Patrimoine</option>
                        <option value="auth" @selected($source === 'auth')>Administration des comptes</option>
                    @endif
                </select>
            </label>
            <div class="field">
                <label for="bien_id">Identifiant du bien</label>
                <input class="input" id="bien_id" name="bien_id" type="number" min="1" value="{{ request('bien_id') }}">
            </div>
            <div class="field">
                <label for="action">Action</label>
                <input class="input" id="action" name="action" value="{{ request('action') }}">
            </div>
            <div class="field">
                <label for="q">Recherche dans les actions</label>
                <input class="input" id="q" name="q" value="{{ request('q') }}">
            </div>
            <div class="form-actions"><button class="btn btn--primary" type="submit"><x-icon name="search"/> Filtrer</button><a class="btn btn--ghost" href="{{ route('historique.index') }}">Réinitialiser</a></div>
        </form>
        <div class="list-meta"><span>Journal : {{ ['mouvements' => 'Mouvements', 'patrimoine' => 'Patrimoine', 'auth' => 'Administration des comptes'][$source] }}</span></div>
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
        @if (($pagination['last_page'] ?? 1) > 1)
            <nav class="pagination" aria-label="Pagination du journal">
                @if (($pagination['current_page'] ?? 1) > 1)
                    <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}">Précédent</a>
                @endif
                <span>Page {{ $pagination['current_page'] }} sur {{ $pagination['last_page'] }}</span>
                @if (($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1))
                    <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}">Suivant</a>
                @endif
            </nav>
        @endif
    </section>
@endsection
