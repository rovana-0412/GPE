@extends('layouts.app')

@section('title', 'Paramètres de la plateforme')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">Administration</p>
            <h1>Paramètres de la plateforme</h1>
            <p>Identité affichée dans votre espace GPE.</p>
        </div>
    </section>

    @if (session('success'))
        <p class="alert alert--success" role="status">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>
    @endif

    <section class="panel">
        <div class="panel__head">
            <div>
                <p class="eyebrow">Identité institutionnelle</p>
                <h2 class="panel__title">Informations visibles</h2>
            </div>
        </div>
        <form method="POST" action="{{ route('administration.parametres.update') }}" class="form-grid">
            @csrf
            @method('PUT')
            <label class="field">
                <span>Nom de la plateforme</span>
                <input class="input" name="nom_plateforme" maxlength="80" required
                       value="{{ old('nom_plateforme', $settings['nom_plateforme'] ?? '') }}">
                @error('nom_plateforme')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Institution</span>
                <input class="input" name="nom_institution" maxlength="120" required
                       value="{{ old('nom_institution', $settings['nom_institution'] ?? '') }}">
                @error('nom_institution')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Contact de support (facultatif)</span>
                <input class="input" name="contact_support" type="email" maxlength="255"
                       value="{{ old('contact_support', $settings['contact_support'] ?? '') }}">
                @error('contact_support')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <div class="form-actions">
                <button class="btn btn--primary" type="submit">Enregistrer les paramètres</button>
            </div>
        </form>
    </section>
@endsection
