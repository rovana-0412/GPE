@extends('layouts.app')

@section('title', 'Acquisitions')

@section('content')
    <section class="welcome page-heading">
        <div>
            <p class="eyebrow">Patrimoine · Entrées</p>
            <h1>Acquisitions</h1>
            <p>Suivez la demande, la réception et la validation avant l’inscription au registre.</p>
        </div>
        @if ($peutGererPatrimoine)
            <a class="btn btn--primary" href="{{ route('acquisitions.create') }}">Nouvelle acquisition</a>
        @endif
    </section>

    @if (session('success'))
        <p class="alert alert--success" role="status">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>
    @endif

    <section class="panel">
        <form method="GET" action="{{ route('acquisitions.index') }}" class="form-grid">
            <label class="field">
                <span>Recherche</span>
                <input class="input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Référence, désignation, fournisseur">
            </label>
            <label class="field">
                <span>Statut</span>
                <select class="input" name="statut">
                    <option value="">Tous les statuts</option>
                    @foreach (['brouillon' => 'Brouillon', 'en_attente' => 'En attente', 'receptionnee' => 'Réceptionnée', 'validee' => 'Validée', 'rejetee' => 'Rejetée'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['statut'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn btn--ghost" type="submit">Filtrer</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Référence</th><th>Bien</th><th>Fournisseur</th><th>Date</th><th>Coût (FCFA)</th><th>Statut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($acquisitions as $acquisition)
                        <tr>
                            <td>{{ $acquisition['reference'] }}</td>
                            <td>{{ $acquisition['designation'] }}</td>
                            <td>{{ $acquisition['fournisseur'] }}</td>
                            <td>{{ $acquisition['date_acquisition'] }}</td>
                            <td><x-montant :value="$acquisition['cout']"/></td>
                            <td><span class="badge badge--neutral">{{ str_replace('_', ' ', ucfirst($acquisition['statut'])) }}</span></td>
                            <td class="row-actions">
                                <a class="table-link" href="{{ route('documents.index', ['acquisition', $acquisition['id']]) }}">Justificatifs</a>
                                @if ($peutGererPatrimoine)
                                    @php
                                        $actions = [
                                            'brouillon' => ['en_attente' => 'Soumettre'],
                                            'en_attente' => ['receptionnee' => 'Confirmer la réception', 'rejetee' => 'Rejeter'],
                                            'receptionnee' => ['validee' => 'Valider', 'rejetee' => 'Rejeter'],
                                        ][$acquisition['statut']] ?? [];
                                    @endphp
                                    @foreach ($actions as $status => $label)
                                        <form method="POST" action="{{ route('acquisitions.status', $acquisition['id']) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="statut" value="{{ $status }}">
                                            @if ($status === 'rejetee')
                                                <label class="sr-only" for="motif-{{ $acquisition['id'] }}">Motif du rejet</label>
                                                <input class="input" id="motif-{{ $acquisition['id'] }}" name="motif_rejet" required placeholder="Motif du rejet">
                                            @endif
                                            <button class="btn btn--ghost btn--compact" type="submit">{{ $label }}</button>
                                        </form>
                                    @endforeach
                                @else
                                    {{ $acquisition['bien']['reference'] ?? '—' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Aucune acquisition enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (($pagination['last_page'] ?? 1) > 1)
            <nav class="pagination" aria-label="Pagination des acquisitions">
                <span>Page {{ $pagination['current_page'] }} sur {{ $pagination['last_page'] }}</span>
            </nav>
        @endif
    </section>
@endsection
