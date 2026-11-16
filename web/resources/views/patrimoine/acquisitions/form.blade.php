@extends('layouts.app')

@section('title', 'Nouvelle acquisition')

@section('content')
    <section class="welcome">
        <p class="eyebrow">Patrimoine · Entrées</p>
        <h1>Nouvelle acquisition</h1>
        <p>Le bien sera créé dans le registre uniquement après réception et validation.</p>
    </section>

    @if ($errors->any())
        <p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>
    @endif

    <section class="panel form-panel">
        @if ($peutGererPatrimoine)
            <form method="POST" action="{{ route('acquisitions.store') }}" class="form-grid">
                @csrf
                <div class="field">
                    <label for="reference">Référence d’inventaire</label>
                    <input class="input" id="reference" name="reference" value="{{ old('reference') }}" required maxlength="50">
                    @error('reference')<span class="field__error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="designation">Désignation du bien</label>
                    <input class="input" id="designation" name="designation" value="{{ old('designation') }}" required maxlength="150">
                    @error('designation')<span class="field__error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="categorie_id">Catégorie</label>
                    <select class="input" id="categorie_id" name="categorie_id" required>
                        <option value="">Choisir une catégorie</option>
                        @foreach ($categories as $categorie)
                            <option value="{{ $categorie['id'] }}" @selected((string) old('categorie_id') === (string) $categorie['id'])>{{ $categorie['nom'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="service_id">Service destinataire</label>
                    <select class="input" id="service_id" name="service_id" required>
                        <option value="">Choisir un service</option>
                        @foreach ($services as $service)
                            <option value="{{ $service['id'] }}" @selected((string) old('service_id') === (string) $service['id'])>
                                {{ $service['nom'] }} · {{ $service['ministere']['nom'] ?? 'Ministère' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="fournisseur">Fournisseur / donateur</label>
                    <input class="input" id="fournisseur" name="fournisseur" value="{{ old('fournisseur') }}" required maxlength="180">
                </div>
                <div class="field">
                    <label for="cout">Coût d’acquisition (FCFA)</label>
                    <input class="input" id="cout" name="cout" type="number" min="0" step="0.01" value="{{ old('cout') }}" required>
                </div>
                <div class="field">
                    <label for="date_acquisition">Date d’acquisition</label>
                    <input class="input" id="date_acquisition" name="date_acquisition" type="date" value="{{ old('date_acquisition') }}" required>
                </div>
                <div class="field">
                    <label for="mode_acquisition">Mode d’acquisition</label>
                    <select class="input" id="mode_acquisition" name="mode_acquisition" required>
                        @foreach (['achat' => 'Achat', 'don' => 'Don', 'dotation' => 'Dotation', 'transfert' => 'Transfert', 'autre' => 'Autre'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('mode_acquisition') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="reference_facture">Référence de facture</label>
                    <input class="input" id="reference_facture" name="reference_facture" value="{{ old('reference_facture') }}" maxlength="100">
                </div>
                <div class="field">
                    <label for="reference_bon_commande">Référence du bon de commande</label>
                    <input class="input" id="reference_bon_commande" name="reference_bon_commande" value="{{ old('reference_bon_commande') }}" maxlength="100">
                </div>
                <div class="field">
                    <label for="numero_serie">Numéro de série</label>
                    <input class="input" id="numero_serie" name="numero_serie" value="{{ old('numero_serie') }}" maxlength="100">
                </div>
                <div class="field">
                    <label for="localisation">Localisation</label>
                    <input class="input" id="localisation" name="localisation" value="{{ old('localisation') }}" maxlength="255">
                </div>
                <div class="field span-12">
                    <label for="description">Description</label>
                    <textarea class="input" id="description" name="description" rows="4">{{ old('description') }}</textarea>
                </div>
                <div class="form-actions form-panel__actions">
                    <button class="btn btn--primary" type="submit">Enregistrer le brouillon</button>
                    <a class="btn btn--ghost" href="{{ route('acquisitions.index') }}">Annuler</a>
                </div>
            </form>
        @else
            <x-read-only-notice/>
        @endif
    </section>
@endsection
