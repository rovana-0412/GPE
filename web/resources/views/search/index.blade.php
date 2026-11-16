@extends('layouts.app')

@section('title', 'Recherche globale')

@section('content')
    <section class="welcome">
        <p class="eyebrow">Recherche transversale</p>
        <h1>Recherche globale</h1>
        <p>Les résultats sont limités aux données que les services autorisent votre compte à consulter.</p>
    </section>

    <form class="panel form-grid" method="GET" action="{{ route('recherche.index') }}">
        <label class="field">
            <span>Terme de recherche</span>
            <input class="input" type="search" name="q" value="{{ $term }}" minlength="2" maxlength="100" required>
        </label>
        <div class="form-actions"><button class="btn btn--primary" type="submit"><x-icon name="search"/> Rechercher</button></div>
    </form>

    @if ($term === '')
        <x-empty-state icon="search" title="Saisissez un terme de recherche"
            text="La recherche interrogera les biens, les références, les mouvements, les documents et les opérations."/>
    @else
        <p class="list-meta">Résultats pour « {{ $term }} »</p>
        @php
            $groups = [
                'biens' => ['Biens', route('biens.index', ['q' => $term])],
                'categories' => ['Catégories', route('categories.index', ['q' => $term])],
                'ministeres' => ['Ministères', route('ministeres.index', ['q' => $term])],
                'services' => ['Services', route('services.index', ['q' => $term])],
                'affectations' => ['Affectations', route('affectations.index')],
                'transferts' => ['Transferts', route('transferts.index')],
                'maintenances' => ['Maintenances', route('maintenances.index')],
                'documents' => ['Documents', null],
                'operations' => ['Opérations', null],
            ];
        @endphp

        @foreach ($groups as $key => [$title, $moreUrl])
            @if ($results[$key] !== [])
                <section class="panel">
                    <div class="panel__head">
                        <div><p class="eyebrow">Résultats</p><h2 class="panel__title">{{ $title }}</h2></div>
                    </div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Élément</th><th>Détail</th><th>Accès</th></tr></thead>
                            <tbody>
                                @foreach ($results[$key] as $item)
                                    @php
                                        $label = $item['designation'] ?? $item['nom'] ?? $item['titre'] ?? $item['action'] ?? $item['name'] ?? 'Enregistrement';
                                        $detail = $item['reference'] ?? $item['email'] ?? $item['statut'] ?? $item['type_document'] ?? ('Identifiant #'.($item['id'] ?? ''));
                                        $url = match ($key) {
                                            'biens' => route('biens.show', $item['id']),
                                            'categories', 'ministeres', 'services' => $moreUrl,
                                            'affectations' => route('affectations.index', ['bien_id' => $item['bien_id']]),
                                            'transferts' => route('transferts.index', ['bien_id' => $item['bien_id']]),
                                            'maintenances' => route('maintenances.index', ['bien_id' => $item['bien_id']]),
                                            'documents' => route('documents.index', [$item['cible_type'], $item['cible_id']]),
                                            'operations' => route('historique.index', ['source' => 'mouvements', 'q' => $term]),
                                            'utilisateurs' => route('utilisateurs.edit', $item['id']),
                                            default => '#',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td>{{ $detail }}</td>
                                        <td><a class="table-link" href="{{ $url }}">Consulter</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        @endforeach

        @if ($peutVoirUtilisateurs && $results['utilisateurs'] !== [])
            <section class="panel">
                <div class="panel__head"><div><p class="eyebrow">Administration</p><h2 class="panel__title">Utilisateurs</h2></div></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Nom</th><th>Adresse e-mail</th><th>Accès</th></tr></thead>
                        <tbody>
                            @foreach ($results['utilisateurs'] as $user)
                                <tr><td>{{ $user['name'] }}</td><td>{{ $user['email'] }}</td><td><a class="table-link" href="{{ route('utilisateurs.edit', $user['id']) }}">Consulter</a></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if (collect($results)->every(fn ($items) => $items === []))
            <x-empty-state icon="search" title="Aucun résultat"
                text="Aucun enregistrement accessible ne correspond à ce terme."/>
        @endif
    @endif
@endsection
