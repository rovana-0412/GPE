@extends('layouts.app')

@section('title', 'Sorties et réformes')

@section('content')
    <section class="welcome page-heading">
        <div>
            <p class="eyebrow">Patrimoine · Sorties</p>
            <h1>Sorties et réformes</h1>
            <p>Les biens restent dans le registre et l’historique après leur sortie.</p>
        </div>
        @if ($peutGererPatrimoine)
            <a class="btn btn--primary" href="{{ route('sorties.create') }}">Nouvelle demande</a>
        @endif
    </section>

    @if (session('success'))<p class="alert alert--success" role="status">{{ session('success') }}</p>@endif
    @if ($errors->any())<p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>@endif

    <section class="panel">
        <form method="GET" action="{{ route('sorties.index') }}" class="form-grid">
            <label class="field">
                <span>Statut</span>
                <select class="input" name="statut">
                    <option value="">Tous les statuts</option>
                    @foreach (['demandee' => 'Demandée', 'approuvee' => 'Approuvée', 'rejetee' => 'Rejetée', 'executee' => 'Exécutée'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['statut'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Type</span>
                <select class="input" name="type_sortie">
                    <option value="">Tous les types</option>
                    @foreach (['cession' => 'Cession', 'restitution' => 'Restitution', 'mise_au_rebut' => 'Mise au rebut', 'declassement' => 'Déclassement', 'autre' => 'Autre'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type_sortie'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn btn--ghost" type="submit">Filtrer</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Bien</th><th>Type</th><th>Motif</th><th>Date de demande</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($sorties as $sortie)
                        <tr>
                            <td>{{ $sortie['bien']['reference'] ?? '#'.$sortie['bien_id'] }}</td>
                            <td>{{ str_replace('_', ' ', ucfirst($sortie['type_sortie'])) }}</td>
                            <td>{{ $sortie['motif'] }}</td>
                            <td>{{ $sortie['date_demande'] }}</td>
                            <td><span class="badge badge--neutral">{{ str_replace('_', ' ', ucfirst($sortie['statut'])) }}</span></td>
                            <td class="row-actions row-actions--stack">
                                @if ($peutGererPatrimoine && $sortie['statut'] === 'demandee')
                                    <form method="POST" action="{{ route('sorties.status', $sortie['id']) }}">@csrf @method('PATCH')<input type="hidden" name="statut" value="approuvee"><button class="btn btn--primary btn--compact" type="submit">Approuver</button></form>
                                    <form method="POST" action="{{ route('sorties.status', $sortie['id']) }}">@csrf @method('PATCH')<input type="hidden" name="statut" value="rejetee"><label class="sr-only" for="motif_rejet_{{ $sortie['id'] }}">Motif du rejet</label><input class="input" id="motif_rejet_{{ $sortie['id'] }}" name="motif_rejet" required placeholder="Motif du rejet"><button class="btn btn--ghost btn--compact" type="submit">Rejeter</button></form>
                                @elseif ($peutGererPatrimoine && $sortie['statut'] === 'approuvee')
                                    <form method="POST" action="{{ route('sorties.status', $sortie['id']) }}">@csrf @method('PATCH')<input type="hidden" name="statut" value="executee"><button class="btn btn--primary btn--compact" type="submit">Confirmer la sortie</button></form>
                                @else
                                    {{ $sortie['motif_rejet'] ?? '—' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Aucune demande de sortie enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
