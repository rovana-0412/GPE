@extends('layouts.app')

@section('title', 'Vue générale')

@section('content')
@php
    $prenom = explode(' ', trim(session('user.name', 'Utilisateur')))[0];
    $totalBiens = max($statistiques['biens'], 1);
    $immobilier = $parType['immobilier'] ?? 0;
    $mobilier = $parType['mobilier'] ?? 0;
    $maxCategorie = max($categories->max('biens_count') ?? 0, 1);
    $maxMinistere = max($ministeres->max('biens_count') ?? 0, 1);

    $types = [
        'bien.cree'            => ['Bien enregistré', 'accent'],
        'bien.modifie'         => ['Informations modifiées', 'neutral'],
        'affectation.creee'    => ['Affectation', 'success'],
        'affectation.modifiee' => ['Affectation mise à jour', 'neutral'],
        'transfert.cree'       => ['Transfert créé', 'action'],
        'transfert.demande'    => ['Transfert demandé', 'action'],
        'transfert.valide'     => ['Transfert validé', 'success'],
        'transfert.statut_modifie' => ['Transfert mis à jour', 'success'],
        'maintenance.creee'    => ['Maintenance signalée', 'danger'],
        'maintenance.modifiee' => ['Maintenance mise à jour', 'neutral'],
        'maintenance.signalee' => ['Maintenance signalée', 'danger'],
    ];
@endphp

    <section class="welcome">
        <p class="eyebrow">{{ now()->locale('fr')->translatedFormat('l j F Y') }} · République togolaise</p>
        <h1>Bonjour, {{ $prenom }}.</h1>
        <p>Voici l'état du patrimoine de l'État, tel qu'il est enregistré aujourd'hui.</p>
    </section>

    <div class="date-range">
        <x-icon name="calendar"/>
        <span>{{ now()->startOfMonth()->locale('fr')->translatedFormat('F Y') }} - {{ now()->locale('fr')->translatedFormat('F Y') }}</span>
    </div>

    <div class="dash">

        <section class="hero-card">
            <div>
                <p class="eyebrow">Valeur du patrimoine</p>
                <p class="hero-card__message">Somme des valeurs d’acquisition des biens recensés.</p>
            </div>
            <p class="hero-card__value num">{{ number_format($statistiques['valeur_totale'], 0, ',', ' ') }} FCFA</p>
            <div class="hero-card__meta">
                <p>{{ now()->locale('fr')->translatedFormat('j F Y') }}</p>
                <a href="{{ route('biens.index') }}" class="hero-card__link">Consulter les biens <x-icon name="arrow-right"/></a>
            </div>
        </section>

        <section class="metric-card">
            <p class="eyebrow">Biens recensés</p>
            <p class="metric-card__value num">{{ $statistiques['biens'] }}</p>
            <span class="metric-card__delta">{{ $statistiques['categories'] }} catégories</span>
        </section>

        <section class="donut-card">
            <div class="donut-card__center">
                <div class="donut-card__chart" style="--immobilier-part: {{ round($immobilier / $totalBiens * 100, 2) }}%"></div>
                <div class="donut-card__total">
                    <p class="num">{{ $statistiques['biens'] }}</p>
                    <small>Total biens</small>
                </div>
            </div>
            <div class="donut-card__legend">
                <span><i style="background:var(--color-primary)"></i>Immobilier ({{ round($immobilier/$totalBiens*100) }}%)</span>
                <span><i style="background:var(--or)"></i>Mobilier ({{ round($mobilier/$totalBiens*100) }}%)</span>
            </div>
            <p class="donut-card__tips">Répartition du patrimoine par type.</p>
        </section>

        <section class="list-card">
            <div class="list-card__head">
                <p class="eyebrow">Traçabilité</p>
                <h3>Dernières opérations</h3>
            </div>

            @if ($operations->isEmpty())
                <x-empty-state
                    icon="clock"
                    title="Aucune opération enregistrée"
                    text="Les créations, affectations, transferts et maintenances apparaîtront ici dès qu'ils auront lieu."/>
            @else
                <div class="list-card__items">
                    @foreach ($operations as $operation)
                        @php [$libelle, $ton] = $types[$operation->action] ?? [\Illuminate\Support\Str::headline($operation->action), 'neutral']; @endphp
                        <div class="list-card__item">
                            <div class="list-card__item--main">
                                <div class="list-card__item-icon">
                                    <x-icon name="clock"/>
                                </div>
                                <div class="list-card__item-text">
                                    <h4>{{ $libelle }}</h4>
                                    <small>{{ $operation->created_at->locale('fr')->diffForHumans() }}</small>
                                </div>
                            </div>
                            <div class="list-card__item-meta">
                                <span class="badge badge--{{ $ton }}">{{ $operation->action }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="bar-card">
            <p class="eyebrow">Répartition par ministère</p>
            <h3>{{ $statistiques['ministeres'] }} ministères</h3>
            <div class="bar-card__chart">
                @forelse ($ministeres as $ministere)
                    <div class="bar-card__bar">
                        <div class="bar" style="--h: {{ $ministere->biens_count / $maxMinistere * 100 }}%"></div>
                        <span title="{{ $ministere->nom }}">{{ \Illuminate\Support\Str::limit($ministere->nom, 12) }}</span>
                    </div>
                @empty
                    <p>Aucun ministère enregistré.</p>
                @endforelse
            </div>
        </section>

        <section class="panel">
            <div class="panel__head">
                <p class="eyebrow">Structure</p>
                <h2 class="panel__title">Repères</h2>
            </div>

            <dl class="ledger">
                <div class="ledger__row"><dt>Catégories</dt><dd class="num">{{ $statistiques['categories'] }}</dd></div>
                <div class="ledger__row"><dt>Ministères</dt><dd class="num">{{ $statistiques['ministeres'] }}</dd></div>
                <div class="ledger__row"><dt>Services</dt><dd class="num">{{ $statistiques['services'] }}</dd></div>
            </dl>

            <p class="eyebrow ledger__sep">À traiter</p>
            <dl class="ledger">
                <div @class(['ledger__row', 'is-alert' => $statistiques['transferts_en_attente'] > 0])>
                    <dt>Transferts en attente<small>Demandes à examiner</small></dt>
                    <dd class="num">{{ $statistiques['transferts_en_attente'] }}</dd>
                </div>
                <div @class(['ledger__row', 'is-alert' => $statistiques['maintenances'] > 0])>
                    <dt>Maintenances signalées<small>Interventions à suivre</small></dt>
                    <dd class="num">{{ $statistiques['maintenances'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="hbar-card">
            <p class="eyebrow">Synthèse</p>
            <h3>Biens par catégorie</h3>
            <div class="hbar-card__bars">
                @foreach (['immobilier' => 'Immobilier', 'mobilier' => 'Mobilier'] as $type => $titre)
                    @php $groupe = $categories->where('type', $type); @endphp
                    @if ($groupe->isNotEmpty())
                        @foreach ($groupe as $categorie)
                            <div class="hbar-card__item">
                                <span>{{ $categorie->nom }}</span>
                                <div class="hbar-card__bar">
                                    <span style="--w: {{ $categorie->biens_count / $maxCategorie * 100 }}%; --bar: {{ $type === 'immobilier' ? 'var(--color-primary)' : 'var(--or)' }}"></span>
                                </div>
                                <b class="num">{{ $categorie->biens_count }}</b>
                            </div>
                        @endforeach
                    @endif
                @endforeach
            </div>
        </section>

        <section class="promo-card">
            <p class="eyebrow">Amélioration</p>
            <h3>Optimisez la gestion du patrimoine</h3>
            <p>Suivez les affectations, transferts et maintenances pour une meilleure traçabilité.</p>
            <a href="#" class="promo-card__btn">Voir le guide <x-icon name="arrow-right"/></a>
        </section>

    </div>
@endsection
