@extends('layouts.app')

@section('title', 'Biens')

@section('content')
    <section class="welcome">
        <p class="eyebrow">Patrimoine de l’État</p>
        <h1>Biens</h1>
        <p>Consultez et gérez les biens recensés dans les services publics.</p>
    </section>

    @if (session('success'))
        <p class="alert alert--success" role="status">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>
    @endif

    <section class="panel">
        <div class="panel__head">
            <h2 class="panel__title">Liste des biens</h2>
            <a class="btn btn--primary" href="{{ route('biens.create') }}">Ajouter un bien</a>
        </div>

        <form method="GET" action="{{ route('biens.index') }}" class="form-grid">
            <div class="field">
                <label for="q">Recherche</label>
                <input class="input" id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Référence, désignation, lieu">
            </div>
            <div class="field">
                <label for="categorie_id">Catégorie</label>
                <select class="input" id="categorie_id" name="categorie_id">
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $categorie)
                        <option value="{{ $categorie['id'] }}" @selected((string) request('categorie_id') === (string) $categorie['id'])>{{ $categorie['nom'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="service_id">Service</label>
                <select class="input" id="service_id" name="service_id">
                    <option value="">Tous les services</option>
                    @foreach ($services as $service)
                        <option value="{{ $service['id'] }}" @selected((string) request('service_id') === (string) $service['id'])>{{ $service['nom'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="etat">État</label>
                <select class="input" id="etat" name="etat">
                    <option value="">Tous les états</option>
                    @foreach (['bon_etat' => 'Bon état', 'a_reparer' => 'À réparer', 'hors_service' => 'Hors service'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('etat') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-actions">
                <button class="btn btn--primary" type="submit">Filtrer</button>
                <a class="btn btn--ghost" href="{{ route('biens.index') }}">Réinitialiser</a>
            </div>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Désignation</th>
                        <th>Catégorie</th>
                        <th>Service</th>
                        <th>État</th>
                        <th>Valeur (FCFA)</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($biens as $bien)
                        <tr>
                            <td>{{ $bien['reference'] }}</td>
                            <td>{{ $bien['designation'] }}</td>
                            <td>{{ data_get($bien, 'categorie.nom', '—') }}</td>
                            <td>{{ data_get($bien, 'service.nom', '—') }}</td>
                            <td>{{ str_replace('_', ' ', $bien['etat']) }}</td>
                            <td class="num">{{ isset($bien['valeur_acquisition']) ? number_format((float) $bien['valeur_acquisition'], 0, ',', ' ') : '—' }}</td>
                            <td><a href="{{ route('biens.show', $bien['id']) }}">Consulter</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Aucun bien ne correspond à ces critères.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (($pagination['last_page'] ?? 1) > 1)
            <nav class="pagination" aria-label="Pagination des biens">
                @if (($pagination['current_page'] ?? 1) > 1)
                    <a href="{{ route('biens.index', array_merge(request()->query(), ['page' => $pagination['current_page'] - 1])) }}">Précédent</a>
                @endif
                <span>Page {{ $pagination['current_page'] }} sur {{ $pagination['last_page'] }}</span>
                @if (($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1))
                    <a href="{{ route('biens.index', array_merge(request()->query(), ['page' => $pagination['current_page'] + 1])) }}">Suivant</a>
                @endif
            </nav>
        @endif
    </section>
@endsection
