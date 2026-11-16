@extends('layouts.app')

@section('title', 'Maintenances')

@section('content')
    <section class="welcome">
        <p class="eyebrow">Patrimoine · Suivi technique</p>
        <h1>Maintenances</h1>
        <p>Signalez les interventions et suivez leur avancement ainsi que leur coût en FCFA.</p>
    </section>

    @if (session('success'))<p class="alert alert--success" role="status">{{ session('success') }}</p>@endif
    @if ($errors->any())<p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>@endif

    <section class="panel form-panel">
        @if ($peutGererPatrimoine)
            <div class="panel__head">
                <div><p class="eyebrow">Intervention technique</p><h2 class="panel__title">Signaler une maintenance</h2></div>
                <span class="section-index">01</span>
            </div>
            <form method="POST" action="{{ route('maintenances.store') }}" class="form-grid">
                @csrf
            <div class="field">
                <label for="bien_id">Bien concerné</label>
                <select class="input" id="bien_id" name="bien_id" required>
                    <option value="">Choisir un bien</option>
                    @foreach ($biens as $bien)
                        <option value="{{ $bien['id'] }}" @selected(old('bien_id') == $bien['id'])>{{ $bien['reference'] }} — {{ $bien['designation'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="type">Type d’intervention</label>
                <select class="input" id="type" name="type" required>
                    @foreach (['preventive' => 'Préventive', 'corrective' => 'Corrective', 'reparation' => 'Réparation'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="description">Description</label>
                <textarea class="input" id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
            </div>
            <div class="field">
                <label for="date_debut">Date prévue ou constatée</label>
                <input class="input" id="date_debut" name="date_debut" type="date" value="{{ old('date_debut', now()->toDateString()) }}" required>
            </div>
            <div class="field">
                <label for="cout">Coût estimé (FCFA)</label>
                <input class="input" id="cout" name="cout" type="number" min="0" step="1" value="{{ old('cout') }}">
            </div>
            <div class="field">
                <label for="prestataire">Prestataire</label>
                <input class="input" id="prestataire" name="prestataire" value="{{ old('prestataire') }}" maxlength="255">
            </div>
                <div class="form-actions"><button class="btn btn--primary" type="submit">Enregistrer la maintenance</button></div>
            </form>
        @else
            <x-read-only-notice/>
        @endif
    </section>

    <section class="panel">
        <div class="panel__head">
            <div><p class="eyebrow">Carnet d’entretien</p><h2 class="panel__title">Interventions enregistrées</h2></div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Bien</th><th>Type</th><th>Description</th><th>Date</th><th>Coût (FCFA)</th><th>Statut</th><th>Documents</th>@if ($peutGererPatrimoine)<th>Mise à jour</th>@endif</tr></thead>
                <tbody>
                    @forelse ($maintenances as $maintenance)
                        <tr>
                            <td>{{ data_get($biensParId, $maintenance['bien_id'].'.reference', '#'.$maintenance['bien_id']) }}</td>
                            <td>{{ ucfirst($maintenance['type']) }}</td>
                            <td>{{ $maintenance['description'] }}</td>
                            <td>{{ substr($maintenance['date_debut'], 0, 10) }}</td>
                            <td class="num">{{ isset($maintenance['cout']) ? number_format((float) $maintenance['cout'], 0, ',', ' ') : '—' }}</td>
                            <td><span class="badge badge--{{ in_array($maintenance['statut'], ['terminee', 'annulee']) ? 'neutral' : ($maintenance['statut'] === 'en_cours' ? 'warning' : 'info') }}">{{ ucfirst(str_replace('_', ' ', $maintenance['statut'])) }}</span></td>
                            <td><a class="table-link" href="{{ route('documents.index', ['maintenance', $maintenance['id']]) }}">Justificatifs</a></td>
                            @if ($peutGererPatrimoine)
                                <td>
                                    <form method="POST" action="{{ route('maintenances.update', $maintenance['id']) }}" class="form-grid">
                                        @csrf @method('PATCH')
                                        <label class="sr-only" for="statut_{{ $maintenance['id'] }}">Statut</label>
                                        <select class="input" id="statut_{{ $maintenance['id'] }}" name="statut">
                                            @foreach (['signalee', 'planifiee', 'en_cours', 'terminee', 'annulee'] as $statut)
                                                <option value="{{ $statut }}" @selected($maintenance['statut'] === $statut)>{{ ucfirst(str_replace('_', ' ', $statut)) }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn--ghost" type="submit">Mettre à jour</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $peutGererPatrimoine ? 8 : 7 }}">Aucune maintenance enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
