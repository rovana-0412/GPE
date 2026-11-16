@extends('layouts.app')

@section('title', 'Affectations')

@section('content')
    <section class="welcome">
        <p class="eyebrow">Patrimoine · Mise à disposition</p>
        <h1>Affectations</h1>
        <p>Suivez le service responsable et la période d’affectation de chaque bien.</p>
    </section>

    @if (session('success'))<p class="alert alert--success" role="status">{{ session('success') }}</p>@endif
    @if ($errors->any())<p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>@endif

    <section class="panel form-panel">
        @if ($peutGererPatrimoine)
            <div class="panel__head">
                <div><p class="eyebrow">Mise à disposition</p><h2 class="panel__title">Nouvelle affectation</h2></div>
                <span class="section-index">01</span>
            </div>
            <form method="POST" action="{{ route('affectations.store') }}" class="form-grid">
                @csrf
            <div class="field">
                <label for="bien_id">Bien</label>
                <select class="input" id="bien_id" name="bien_id" required>
                    <option value="">Choisir un bien</option>
                    @foreach ($biens as $bien)
                        <option value="{{ $bien['id'] }}" @selected(old('bien_id') == $bien['id'])>{{ $bien['reference'] }} — {{ $bien['designation'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="service_id">Service responsable</label>
                <select class="input" id="service_id" name="service_id" required>
                    <option value="">Choisir un service</option>
                    @foreach ($services as $service)
                        <option value="{{ $service['id'] }}" @selected(old('service_id') == $service['id'])>{{ $service['nom'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="date_debut">Date de début</label>
                <input class="input" id="date_debut" name="date_debut" type="date" value="{{ old('date_debut', now()->toDateString()) }}" required>
            </div>
            <div class="field">
                <label for="date_fin">Date de fin prévue</label>
                <input class="input" id="date_fin" name="date_fin" type="date" value="{{ old('date_fin') }}">
            </div>
            <div class="field">
                <label for="motif">Motif</label>
                <input class="input" id="motif" name="motif" value="{{ old('motif') }}" maxlength="500">
            </div>
            <div class="field">
                <label for="observation">Observation</label>
                <input class="input" id="observation" name="observation" value="{{ old('observation') }}" maxlength="5000">
            </div>
                <div class="form-actions"><button class="btn btn--primary" type="submit">Enregistrer l’affectation</button></div>
            </form>
        @else
            <x-read-only-notice/>
        @endif
    </section>

    <section class="panel">
        <div class="panel__head">
            <div><p class="eyebrow">Suivi des biens</p><h2 class="panel__title">Affectations enregistrées</h2></div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Bien</th><th>Service</th><th>Début</th><th>Fin prévue</th><th>Statut</th><th>Documents</th>@if ($peutGererPatrimoine)<th>Action</th>@endif</tr></thead>
                <tbody>
                    @forelse ($affectations as $affectation)
                        <tr>
                            <td>{{ data_get($biensParId, $affectation['bien_id'].'.reference', '#'.$affectation['bien_id']) }}</td>
                            <td>{{ data_get($servicesParId, $affectation['service_id'].'.nom', '#'.$affectation['service_id']) }}</td>
                            <td>{{ substr($affectation['date_debut'], 0, 10) }}</td>
                            <td>{{ isset($affectation['date_fin']) ? substr($affectation['date_fin'], 0, 10) : '—' }}</td>
                            <td><span class="badge badge--{{ $affectation['statut'] === 'active' ? 'success' : 'neutral' }}">{{ ucfirst($affectation['statut']) }}</span></td>
                            <td><a class="table-link" href="{{ route('documents.index', ['affectation', $affectation['id']]) }}">Justificatifs</a></td>
                            @if ($peutGererPatrimoine)
                                <td class="row-actions">
                                    @if ($affectation['statut'] === 'active')
                                        <form method="POST" action="{{ route('affectations.update', $affectation['id']) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="statut" value="terminee">
                                            <button class="btn btn--ghost btn--compact" type="submit">Terminer</button>
                                        </form>
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $peutGererPatrimoine ? 7 : 6 }}">Aucune affectation enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
