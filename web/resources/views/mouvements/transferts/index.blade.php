@extends('layouts.app')

@section('title', 'Transferts')

@section('content')
    <section class="welcome">
        <p class="eyebrow">Mouvements du patrimoine</p>
        <h1>Transferts</h1>
        <p>Les transferts suivent un circuit contrôlé avant le changement du service responsable.</p>
    </section>

    @if (session('success'))<p class="alert alert--success" role="status">{{ session('success') }}</p>@endif
    @if ($errors->any())<p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>@endif

    <section class="panel">
        <div class="panel__head"><h2 class="panel__title">Nouvelle demande</h2></div>
        <form method="POST" action="{{ route('transferts.store') }}" class="form-grid">
            @csrf
            <div class="field">
                <label for="bien_id">Bien à transférer</label>
                <select class="input" id="bien_id" name="bien_id" required>
                    <option value="">Choisir un bien</option>
                    @foreach ($biens as $bien)
                        <option value="{{ $bien['id'] }}" @selected(old('bien_id') == $bien['id'])>{{ $bien['reference'] }} — {{ $bien['designation'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="service_destination_id">Service destinataire</label>
                <select class="input" id="service_destination_id" name="service_destination_id" required>
                    <option value="">Choisir un service</option>
                    @foreach ($services as $service)
                        <option value="{{ $service['id'] }}" @selected(old('service_destination_id') == $service['id'])>{{ $service['nom'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="motif">Motif du transfert</label>
                <textarea class="input" id="motif" name="motif" rows="3" required maxlength="2000">{{ old('motif') }}</textarea>
            </div>
            <div class="field">
                <label for="observation">Observation</label>
                <textarea class="input" id="observation" name="observation" rows="3" maxlength="5000">{{ old('observation') }}</textarea>
            </div>
            <div class="form-actions"><button class="btn btn--primary" type="submit">Créer le brouillon</button></div>
        </form>
    </section>

    <section class="panel">
        <div class="panel__head"><h2 class="panel__title">Suivi des demandes</h2></div>
        <form method="GET" action="{{ route('transferts.index') }}" class="form-grid">
            <div class="field">
                <label for="statut">Statut</label>
                <select class="input" id="statut" name="statut">
                    <option value="">Tous les statuts</option>
                    @foreach (['brouillon', 'en_attente', 'approuvé', 'effectué', 'rejeté'] as $statut)
                        <option value="{{ $statut }}" @selected(($filtres['statut'] ?? '') === $statut)>{{ ucfirst(str_replace('_', ' ', $statut)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="service_origine_id">Service d’origine</label>
                <select class="input" id="service_origine_id" name="service_origine_id">
                    <option value="">Tous les services</option>
                    @foreach ($services as $service)
                        <option value="{{ $service['id'] }}" @selected(($filtres['service_origine_id'] ?? '') == $service['id'])>{{ $service['nom'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="service_destination_id">Service destinataire</label>
                <select class="input" id="service_destination_id" name="service_destination_id">
                    <option value="">Tous les services</option>
                    @foreach ($services as $service)
                        <option value="{{ $service['id'] }}" @selected(($filtres['service_destination_id'] ?? '') == $service['id'])>{{ $service['nom'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-actions"><button class="btn btn--primary" type="submit">Filtrer</button><a class="btn btn--ghost" href="{{ route('transferts.index') }}">Réinitialiser</a></div>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Bien</th><th>Origine</th><th>Destination</th><th>Demande</th><th>Statut</th><th>Motif</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($transferts as $transfert)
                        <tr>
                            <td>{{ data_get($biensParId, $transfert['bien_id'].'.reference', '#'.$transfert['bien_id']) }}</td>
                            <td>{{ data_get($servicesParId, $transfert['service_origine_id'].'.nom', '#'.$transfert['service_origine_id']) }}</td>
                            <td>{{ data_get($servicesParId, $transfert['service_destination_id'].'.nom', '#'.$transfert['service_destination_id']) }}</td>
                            <td>{{ substr($transfert['date_demande'], 0, 10) }}</td>
                            <td><span class="badge badge--{{ $transfert['statut'] === 'rejeté' ? 'danger' : (in_array($transfert['statut'], ['approuvé', 'effectué']) ? 'success' : 'neutral') }}">{{ ucfirst(str_replace('_', ' ', $transfert['statut'])) }}</span></td>
                            <td>{{ $transfert['motif'] }}</td>
                            <td>
                                @if ($transfert['statut'] === 'brouillon')
                                    <form method="POST" action="{{ route('transferts.statut', $transfert['id']) }}">@csrf @method('PATCH')<input type="hidden" name="statut" value="en_attente"><button class="btn btn--ghost" type="submit">Soumettre</button></form>
                                @elseif ($transfert['statut'] === 'en_attente')
                                    <form method="POST" action="{{ route('transferts.statut', $transfert['id']) }}">@csrf @method('PATCH')<input type="hidden" name="statut" value="approuvé"><button class="btn btn--primary" type="submit">Approuver</button></form>
                                    <form method="POST" action="{{ route('transferts.statut', $transfert['id']) }}" class="form-grid">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="statut" value="rejeté">
                                        <label class="sr-only" for="motif_refus_{{ $transfert['id'] }}">Motif du refus</label>
                                        <input class="input" id="motif_refus_{{ $transfert['id'] }}" name="motif_refus" placeholder="Motif du refus" required>
                                        <button class="btn btn--ghost" type="submit">Rejeter</button>
                                    </form>
                                @elseif ($transfert['statut'] === 'approuvé')
                                    <form method="POST" action="{{ route('transferts.statut', $transfert['id']) }}">@csrf @method('PATCH')<input type="hidden" name="statut" value="effectué"><button class="btn btn--primary" type="submit">Effectuer</button></form>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Aucun transfert ne correspond à ces critères.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
