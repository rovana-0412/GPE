@extends('layouts.app')

@section('title', 'Demander une sortie')

@section('content')
    <section class="welcome">
        <p class="eyebrow">Patrimoine · Sorties</p>
        <h1>Demander une sortie du registre</h1>
        <p>Le bien reste consultable après validation; l’exécution désactive son usage opérationnel sans effacer ses données.</p>
    </section>

    @if ($errors->any())<p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>@endif

    <section class="panel form-panel">
        @if ($peutGererPatrimoine)
            <form method="POST" action="{{ route('sorties.store') }}" class="form-grid">
                @csrf
                <div class="field">
                    <label for="bien_id">Bien concerné</label>
                    <select class="input" id="bien_id" name="bien_id" required>
                        <option value="">Choisir un bien actif</option>
                        @foreach ($biens as $bien)
                            @if ($bien['actif'] ?? true)
                                <option value="{{ $bien['id'] }}" @selected(old('bien_id') == $bien['id'])>{{ $bien['reference'] }} — {{ $bien['designation'] }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="type_sortie">Type de sortie</label>
                    <select class="input" id="type_sortie" name="type_sortie" required>
                        <option value="">Choisir</option>
                        @foreach (['cession' => 'Cession', 'restitution' => 'Restitution', 'mise_au_rebut' => 'Mise au rebut', 'declassement' => 'Déclassement', 'autre' => 'Autre'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('type_sortie') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field span-12">
                    <label for="motif">Motif de la demande</label>
                    <textarea class="input" id="motif" name="motif" rows="4" required maxlength="5000">{{ old('motif') }}</textarea>
                </div>
                <div class="field span-12">
                    <label for="observation">Observations</label>
                    <textarea class="input" id="observation" name="observation" rows="3" maxlength="5000">{{ old('observation') }}</textarea>
                </div>
                <div class="form-actions form-panel__actions">
                    <button class="btn btn--primary" type="submit">Enregistrer la demande</button>
                    <a class="btn btn--ghost" href="{{ route('sorties.index') }}">Annuler</a>
                </div>
            </form>
        @else
            <x-read-only-notice/>
        @endif
    </section>
@endsection
