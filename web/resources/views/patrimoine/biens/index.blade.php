@extends('layouts.app')

@section('title', 'Biens')

@section('content')
    <section class="welcome page-heading">
        <div>
            <p class="eyebrow">Registre patrimonial · État togolais</p>
            <h1>Biens</h1>
            <p>Consultez et gérez les biens recensés dans les services publics.</p>
        </div>
        @if ($peutGererPatrimoine)
            <a class="btn btn--primary" href="{{ route('biens.create') }}">
                <x-icon name="plus"/>
                Enregistrer un bien
            </a>
        @endif
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
                <p class="eyebrow">Inventaire</p>
                <h2 class="panel__title">Liste des biens</h2>
            </div>
        </div>
        @unless ($peutGererPatrimoine)
            <x-read-only-notice/>
        @endunless

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
                <button class="btn btn--primary" type="submit"><x-icon name="search"/> Appliquer les filtres</button>
                <a class="btn btn--ghost" href="{{ route('biens.index') }}">Réinitialiser</a>
            </div>
        </form>

        <div class="list-meta">
            <span>Résultats de l’inventaire</span>
            <span>Valeurs en FCFA</span>
        </div>
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
                            <td><span class="badge badge--{{ $bien['etat'] === 'bon_etat' ? 'success' : ($bien['etat'] === 'a_reparer' ? 'warning' : 'danger') }}">{{ ucfirst(str_replace('_', ' ', $bien['etat'])) }}</span></td>
                            <td class="num">{{ isset($bien['valeur_acquisition']) ? number_format((float) $bien['valeur_acquisition'], 0, ',', ' ') : '—' }}</td>
                            <td><a class="table-link" href="{{ route('biens.show', $bien['id']) }}">Ouvrir la fiche <x-icon name="arrow-right"/></a></td>
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
