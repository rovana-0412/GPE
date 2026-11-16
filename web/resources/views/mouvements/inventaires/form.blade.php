@extends('layouts.app')

@section('title', 'Nouvelle campagne d’inventaire')

@section('content')
    <section class="welcome">
        <p class="eyebrow">Mouvements · Contrôle physique</p>
        <h1>Nouvelle campagne d’inventaire</h1>
        <p>Définissez un périmètre de service ou lancez une campagne nationale.</p>
    </section>

    @if ($errors->any())<p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>@endif

    <section class="panel form-panel">
        @if ($peutGererPatrimoine)
            <form method="POST" action="{{ route('inventaires.store') }}" class="form-grid">
                @csrf
                <div class="field span-12">
                    <label for="titre">Nom de la campagne</label>
                    <input class="input" id="titre" name="titre" value="{{ old('titre') }}" required maxlength="180">
                </div>
                <div class="field span-12">
                    <label for="service_id">Service concerné (facultatif)</label>
                    <select class="input" id="service_id" name="service_id">
                        <option value="">Tous les services</option>
                        @foreach ($services as $service)
                            <option value="{{ $service['id'] }}" @selected((string) old('service_id') === (string) $service['id'])>
                                {{ $service['nom'] }} · {{ $service['ministere']['nom'] ?? 'Ministère' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field span-12">
                    <label for="observation">Observations</label>
                    <textarea class="input" id="observation" name="observation" rows="4" maxlength="5000">{{ old('observation') }}</textarea>
                </div>
                <div class="form-actions form-panel__actions">
                    <button class="btn btn--primary" type="submit">Créer la campagne</button>
                    <a class="btn btn--ghost" href="{{ route('inventaires.index') }}">Annuler</a>
                </div>
            </form>
        @else
            <x-read-only-notice/>
        @endif
    </section>
@endsection
