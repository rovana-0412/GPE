@extends('layouts.app')

@section('title', ucfirst($mode).' un bien')

@section('content')
    <section class="welcome">
        <p class="eyebrow">Patrimoine de l’État</p>
        <h1>{{ $mode === 'creation' ? 'Enregistrer un bien' : 'Modifier un bien' }}</h1>
        <p>Renseignez les informations d’identification et de gestion du bien.</p>
    </section>

    @if ($errors->any())
        <p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>
    @endif

    <section class="panel">
        <form method="POST"
              action="{{ $mode === 'creation' ? route('biens.store') : route('biens.update', $bien['id']) }}"
              class="form-grid">
            @csrf
            @if ($mode === 'modification')
                @method('PUT')
            @endif

            <div class="field">
                <label for="reference">Référence</label>
                <input class="input" id="reference" name="reference" value="{{ old('reference', $bien['reference'] ?? '') }}" required maxlength="50">
                @error('reference')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="designation">Désignation</label>
                <input class="input" id="designation" name="designation" value="{{ old('designation', $bien['designation'] ?? '') }}" required maxlength="150">
                @error('designation')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="categorie_id">Catégorie</label>
                <select class="input" id="categorie_id" name="categorie_id" required>
                    <option value="">Choisir une catégorie</option>
                    @foreach ($categories as $categorie)
                        <option value="{{ $categorie['id'] }}" @selected((string) old('categorie_id', $bien['categorie_id'] ?? '') === (string) $categorie['id'])>{{ $categorie['nom'] }}</option>
                    @endforeach
                </select>
                @error('categorie_id')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="service_id">Service responsable</label>
                <select class="input" id="service_id" name="service_id" required>
                    <option value="">Choisir un service</option>
                    @foreach ($services as $service)
                        <option value="{{ $service['id'] }}" @selected((string) old('service_id', $bien['service_id'] ?? '') === (string) $service['id'])>
                            {{ $service['nom'] }} — {{ data_get($service, 'ministere.nom', 'Ministère non renseigné') }}
                        </option>
                    @endforeach
                </select>
                @error('service_id')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="numero_serie">Numéro de série</label>
                <input class="input" id="numero_serie" name="numero_serie" value="{{ old('numero_serie', $bien['numero_serie'] ?? '') }}" maxlength="100">
                @error('numero_serie')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="date_acquisition">Date d’acquisition</label>
                <input class="input" id="date_acquisition" name="date_acquisition" type="date" value="{{ old('date_acquisition', isset($bien['date_acquisition']) ? substr($bien['date_acquisition'], 0, 10) : '') }}">
                @error('date_acquisition')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="valeur_acquisition">Valeur d’acquisition (FCFA)</label>
                <input class="input" id="valeur_acquisition" name="valeur_acquisition" type="number" min="0" step="1" value="{{ old('valeur_acquisition', $bien['valeur_acquisition'] ?? '') }}">
                @error('valeur_acquisition')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="etat">État physique</label>
                <select class="input" id="etat" name="etat" required>
                    @foreach (['bon_etat' => 'Bon état', 'a_reparer' => 'À réparer', 'hors_service' => 'Hors service'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('etat', $bien['etat'] ?? 'bon_etat') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('etat')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="statut">Statut</label>
                <select class="input" id="statut" name="statut" required>
                    @foreach (['disponible' => 'Disponible', 'affecte' => 'Affecté', 'en_transfert' => 'En transfert', 'en_maintenance' => 'En maintenance'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('statut', $bien['statut'] ?? 'disponible') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('statut')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="localisation">Localisation</label>
                <input class="input" id="localisation" name="localisation" value="{{ old('localisation', $bien['localisation'] ?? '') }}" maxlength="255">
                @error('localisation')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="description">Description</label>
                <textarea class="input" id="description" name="description" rows="4">{{ old('description', $bien['description'] ?? '') }}</textarea>
                @error('description')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label>
                    <input type="hidden" name="actif" value="0">
                    <input type="checkbox" name="actif" value="1" @checked((bool) old('actif', $bien['actif'] ?? true))>
                    Bien actif
                </label>
            </div>

            <div class="form-actions">
                <button class="btn btn--primary" type="submit">{{ $mode === 'creation' ? 'Enregistrer' : 'Mettre à jour' }}</button>
                <a class="btn btn--ghost" href="{{ $mode === 'creation' ? route('biens.index') : route('biens.show', $bien['id']) }}">Annuler</a>
            </div>
        </form>
    </section>
@endsection
