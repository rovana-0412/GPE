@extends('layouts.app')

@section('title', $inventaire['titre'])

@section('content')
    <section class="welcome page-heading">
        <div>
            <p class="eyebrow">Inventaire physique · Campagne #{{ $inventaire['id'] }}</p>
            <h1>{{ $inventaire['titre'] }}</h1>
            <p>État : <span class="badge badge--neutral">{{ str_replace('_', ' ', ucfirst($inventaire['statut'])) }}</span></p>
        </div>
        @if ($peutGererPatrimoine)
            @php
                $nextState = ['brouillon' => ['en_cours', 'Démarrer les contrôles'], 'en_cours' => ['a_valider', 'Soumettre à validation'], 'a_valider' => ['valide', 'Valider la campagne']][$inventaire['statut']] ?? null;
            @endphp
            @if ($nextState)
                <form method="POST" action="{{ route('inventaires.status', $inventaire['id']) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="statut" value="{{ $nextState[0] }}">
                    <button class="btn btn--primary" type="submit">{{ $nextState[1] }}</button>
                </form>
            @endif
        @endif
    </section>

    @if (session('success'))<p class="alert alert--success" role="status">{{ session('success') }}</p>@endif
    @if ($errors->any())<p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>@endif

    <section class="stat-grid" aria-label="Résumé des constats">
        <article class="stat-card"><span>Biens contrôlés</span><strong>{{ $rapport['biens_controles'] ?? 0 }}</strong></article>
        <article class="stat-card"><span>Biens au périmètre</span><strong>{{ $rapport['biens_theoriques'] ?? 0 }}</strong></article>
        <article class="stat-card"><span>Non contrôlés</span><strong>{{ $rapport['non_controles'] ?? 0 }}</strong></article>
        <article class="stat-card"><span>Écarts</span><strong>{{ $rapport['ecarts'] ?? 0 }}</strong></article>
        <article class="stat-card"><span>Présents</span><strong>{{ $rapport['presents'] ?? 0 }}</strong></article>
        <article class="stat-card"><span>Absents</span><strong>{{ $rapport['absents'] ?? 0 }}</strong></article>
        <article class="stat-card"><span>Détériorés</span><strong>{{ $rapport['deteriores'] ?? 0 }}</strong></article>
    </section>

    @if (($rapport['non_controles'] ?? 0) > 0)
        <section class="panel">
            <div class="panel__head">
                <div>
                    <p class="eyebrow">Rapprochement théorique et physique</p>
                    <h2 class="panel__title">Biens non contrôlés ({{ $rapport['non_controles'] }})</h2>
                </div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Référence</th><th>Désignation</th><th>Identifiant</th></tr></thead>
                    <tbody>
                        @foreach (($rapport['biens_non_controles'] ?? []) as $bienNonControle)
                            <tr>
                                <td>{{ $bienNonControle['reference'] ?? '—' }}</td>
                                <td>{{ $bienNonControle['designation'] ?? '—' }}</td>
                                <td>{{ $bienNonControle['id'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @php
                $missingPagination = $rapport['pagination_non_controles'] ?? [];
            @endphp
            @if (($missingPagination['last_page'] ?? 1) > 1)
                <nav class="pagination" aria-label="Pages des biens non contrôlés">
                    @if (($missingPagination['current_page'] ?? 1) > 1)
                        <a class="btn btn--ghost" href="{{ route('inventaires.show', array_merge(request()->query(), ['id' => $inventaire['id'], 'biens_non_controles_page' => $missingPagination['current_page'] - 1])) }}">Précédent</a>
                    @endif
                    <span>Page {{ $missingPagination['current_page'] }} sur {{ $missingPagination['last_page'] }}</span>
                    @if (($missingPagination['current_page'] ?? 1) < ($missingPagination['last_page'] ?? 1))
                        <a class="btn btn--ghost" href="{{ route('inventaires.show', array_merge(request()->query(), ['id' => $inventaire['id'], 'biens_non_controles_page' => $missingPagination['current_page'] + 1])) }}">Suivant</a>
                    @endif
                </nav>
            @endif
        </section>
    @endif

    @if (($inventaire['statut'] ?? '') === 'en_cours' && $peutGererPatrimoine)
        <section class="panel">
            <div class="panel__head"><div><p class="eyebrow">Vérification terrain</p><h2 class="panel__title">Enregistrer un constat</h2></div></div>
            <form method="GET" action="{{ route('inventaires.show', $inventaire['id']) }}" class="form-grid">
                <label class="field">
                    <span>Rechercher un bien par référence ou désignation</span>
                    <input class="input" type="search" name="bien_q" value="{{ $bienSearch }}" maxlength="100" required>
                </label>
                <div class="form-actions"><button class="btn btn--ghost" type="submit">Rechercher</button></div>
            </form>
            @if ($bienSearch !== '')
                <p class="list-meta">{{ $candidatesPagination['total'] ?? count($biensDisponibles) }} bien(s) correspondant(s).</p>
            @endif
            <form method="POST" action="{{ route('inventaires.lines.store', $inventaire['id']) }}" class="form-grid">
                @csrf
                <label class="field">
                    <span>Bien</span>
                    <select class="input" name="bien_id" required>
                        <option value="">Choisir un bien</option>
                        @foreach ($biensDisponibles as $bien)
                            <option value="{{ $bien['id'] }}">{{ $bien['reference'] }} — {{ $bien['designation'] }}</option>
                        @endforeach
                    </select>
                    @if ($bienSearch === '')
                        <small class="field-hint">Recherchez un bien avant de pouvoir l’ajouter à cette campagne.</small>
                    @endif
                </label>
                <label class="field">
                    <span>Constat</span>
                    <select class="input" name="constat" required>
                        <option value="present">Présent et conforme</option>
                        <option value="absent">Absent</option>
                        <option value="deteriore">Présent, détérioré</option>
                    </select>
                </label>
                <label class="field">
                    <span>Localisation constatée</span>
                    <input class="input" name="localisation_physique" maxlength="255">
                </label>
                <label class="field">
                    <span>Observation</span>
                    <input class="input" name="observation" maxlength="5000">
                </label>
                <div class="form-actions"><button class="btn btn--primary" type="submit">Enregistrer le constat</button></div>
            </form>
            @if (($candidatesPagination['last_page'] ?? 1) > 1)
                <nav class="pagination" aria-label="Pages de résultats">
                    @if (($candidatesPagination['current_page'] ?? 1) > 1)
                        <a class="btn btn--ghost" href="{{ route('inventaires.show', ['id' => $inventaire['id'], 'bien_q' => $bienSearch, 'page' => $candidatesPagination['current_page'] - 1]) }}">Précédent</a>
                    @endif
                    <span>Page {{ $candidatesPagination['current_page'] }} sur {{ $candidatesPagination['last_page'] }}</span>
                    @if (($candidatesPagination['current_page'] ?? 1) < ($candidatesPagination['last_page'] ?? 1))
                        <a class="btn btn--ghost" href="{{ route('inventaires.show', ['id' => $inventaire['id'], 'bien_q' => $bienSearch, 'page' => $candidatesPagination['current_page'] + 1]) }}">Suivant</a>
                    @endif
                </nav>
            @endif
        </section>
    @endif

    <section class="panel">
        <div class="panel__head"><div><p class="eyebrow">Résultats de terrain</p><h2 class="panel__title">Biens vérifiés</h2></div></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Référence</th><th>Désignation</th><th>Constat</th><th>Localisation physique</th><th>Observation</th><th>Contrôle</th>@if (($inventaire['statut'] ?? '') === 'en_cours' && $peutGererPatrimoine)<th>Modifier</th>@endif</tr></thead>
                <tbody>
                    @forelse ($lignes as $ligne)
                        @php $bien = $biensParId[$ligne['bien_id']] ?? []; @endphp
                        <tr>
                            <td>{{ $bien['reference'] ?? '#'.$ligne['bien_id'] }}</td>
                            <td>{{ $bien['designation'] ?? 'Bien non disponible' }}</td>
                            <td><span class="badge badge--{{ $ligne['constat'] === 'absent' ? 'danger' : ($ligne['constat'] === 'deteriore' ? 'warning' : 'success') }}">{{ ucfirst($ligne['constat']) }}</span></td>
                            <td>{{ $ligne['localisation_physique'] ?? '—' }}</td>
                            <td>{{ $ligne['observation'] ?? '—' }}</td>
                            <td>{{ $ligne['controle_le'] }}</td>
                            @if (($inventaire['statut'] ?? '') === 'en_cours' && $peutGererPatrimoine)
                                <td>
                                    <form method="POST" action="{{ route('inventaires.lines.update', [$inventaire['id'], $ligne['id']]) }}" class="row-actions row-actions--stack">
                                        @csrf
                                        @method('PATCH')
                                        <label class="sr-only" for="constat_{{ $ligne['id'] }}">Constat du bien {{ $ligne['bien_id'] }}</label>
                                        <select class="input" id="constat_{{ $ligne['id'] }}" name="constat">
                                            @foreach (['present' => 'Présent', 'absent' => 'Absent', 'deteriore' => 'Détérioré'] as $value => $label)
                                                <option value="{{ $value }}" @selected($ligne['constat'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <label class="sr-only" for="localisation_{{ $ligne['id'] }}">Localisation physique</label>
                                        <input class="input" id="localisation_{{ $ligne['id'] }}" name="localisation_physique" value="{{ $ligne['localisation_physique'] ?? '' }}" placeholder="Localisation">
                                        <label class="sr-only" for="observation_{{ $ligne['id'] }}">Observation</label>
                                        <input class="input" id="observation_{{ $ligne['id'] }}" name="observation" value="{{ $ligne['observation'] ?? '' }}" placeholder="Observation">
                                        <button class="btn btn--ghost btn--compact" type="submit">Enregistrer</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ (($inventaire['statut'] ?? '') === 'en_cours' && $peutGererPatrimoine) ? 7 : 6 }}">Aucun constat enregistré pour cette campagne.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
