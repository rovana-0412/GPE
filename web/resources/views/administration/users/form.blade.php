@extends('layouts.app')

@section('title', $mode === 'creation' ? 'Créer un compte' : 'Modifier un compte')

@section('content')
    <section class="welcome">
        <p class="eyebrow">Administration · Accès</p>
        <h1>{{ $mode === 'creation' ? 'Créer un compte' : 'Modifier un compte' }}</h1>
        <p>Attribuez uniquement le rôle et le périmètre nécessaires aux missions du compte.</p>
    </section>

    @if ($errors->any())
        <p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>
    @endif

    <section class="panel form-panel">
        <form method="POST"
              action="{{ $mode === 'creation' ? route('utilisateurs.store') : route('utilisateurs.update', $user['id']) }}"
              class="form-grid">
            @csrf
            @if ($mode === 'modification')
                @method('PUT')
            @endif

            <div class="field">
                <label for="name">Nom complet</label>
                <input class="input" id="name" name="name" type="text" value="{{ old('name', $user['name'] ?? '') }}" required>
                @error('name')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="email">Courriel</label>
                <input class="input" id="email" name="email" type="email" value="{{ old('email', $user['email'] ?? '') }}" required>
                @error('email')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="password">{{ $mode === 'creation' ? 'Mot de passe temporaire' : 'Nouveau mot de passe (facultatif)' }}</label>
                <input class="input" id="password" name="password" type="password" @required($mode === 'creation') minlength="12" autocomplete="new-password">
                <small>Au moins 12 caractères. Lors d’une modification, laisser vide pour conserver le mot de passe.</small>
                @error('password')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="role_id">Rôle</label>
                <select class="input" id="role_id" name="role_id" required>
                    <option value="">Choisir un rôle</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role['id'] }}" @selected((string) old('role_id', $user['role_id'] ?? '') === (string) $role['id'])>{{ $role['nom'] }}</option>
                    @endforeach
                </select>
                @error('role_id')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="service_id">Service de rattachement</label>
                <select class="input" id="service_id" name="service_id">
                    <option value="">Aucun service</option>
                    @foreach ($services as $service)
                        <option value="{{ $service['id'] }}" @selected((string) old('service_id', $user['service_id'] ?? '') === (string) $service['id'])>
                            {{ $service['nom'] }} · {{ $service['ministere']['nom'] ?? 'Ministère' }}
                        </option>
                    @endforeach
                </select>
                @error('service_id')<span class="field__error">{{ $message }}</span>@enderror
            </div>
            <div class="form-actions form-panel__actions">
                <button class="btn btn--primary" type="submit">{{ $mode === 'creation' ? 'Créer le compte' : 'Enregistrer' }}</button>
                <a class="btn btn--ghost" href="{{ route('utilisateurs.index') }}">Annuler</a>
            </div>
        </form>
    </section>
@endsection
