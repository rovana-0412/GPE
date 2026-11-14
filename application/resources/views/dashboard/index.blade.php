@extends('layouts.app')

@section('title', 'Vue générale')

@section('content')
@php
    $prenom = explode(' ', trim(Auth::user()->name))[0];
    $totalBiens = max($statistiques['biens'], 1);
    $immobilier = $parType['immobilier'] ?? 0;
    $mobilier = $parType['mobilier'] ?? 0;
    $maxCategorie = max($categories->max('biens_count') ?? 0, 1);

    $types = [
        'bien.cree'            => ['Bien enregistré', 'accent'],
        'bien.modifie'         => ['Informations modifiées', 'neutral'],
        'affectation.creee'    => ['Affectation', 'success'],
        'transfert.demande'    => ['Transfert demandé', 'action'],
        'transfert.valide'     => ['Transfert validé', 'success'],
        'maintenance.signalee' => ['Maintenance signalée', 'danger'],
    ];
@endphp

    <section class="welcome">
        <p class="eyebrow">{{ now()->locale('fr')->translatedFormat('l j F Y') }} · République d'Aurélia</p>
        <h1>Bonjour, {{ $prenom }}.</h1>
        <p>Voici l'état du patrimoine de l'État, tel qu'il est enregistré aujourd'hui.</p>
    </section>

    <div class="dash">

        <section class="hero-stat dash__hero" aria-labelledby="hero-titre">
            <svg class="hero-stat__art" viewBox="0 0 40 40" fill="none" stroke="currentColor"
                 stroke-width="0.8" aria-hidden="true">
                <path d="M5 16 20 6l15 10Z"/><path d="M8 20h24"/>
                <path d="M12 23v9M20 23v9M28 23v9"/><path d="M6 35h28"/>
            </svg>

            <div>
                <h2 id="hero-titre" class="eyebrow" style="font-family:var(--font-body)">Biens actifs du patrimoine</h2>
                <p class="hero-stat__value num">{{ number_format($statistiques['biens'], 0, ',', ' ') }}</p>
            </div>

            <dl class="hero-stat__foot">
                <div><dt>Immobilier</dt><dd class="num">{{ $immobilier }}</dd></div>
                <div><dt>Mobilier</dt><dd class="num">{{ $mobilier }}</dd></div>
                <div><dt>Valeur d'acquisition cumulée</dt><dd class="num">{{ number_format($statistiques['valeur_totale'], 0, ',', ' ') }}</dd></div>
            </dl>
        </section>

        <section class="panel dash__ledger" aria-labelledby="registre-titre">
            <div class="panel__head">
                <p class="eyebrow">Structure</p>
                <h2 id="registre-titre" class="panel__title">Repères</h2>
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

        <section class="panel dash__timeline" aria-labelledby="activite-titre">
            <div class="panel__head">
                <p class="eyebrow">Traçabilité</p>
                <h2 id="activite-titre" class="panel__title">Dernières opérations</h2>
            </div>

            @if ($operations->isEmpty())
                <x-empty-state
                    icon="clock"
                    title="Aucune opération enregistrée"
                    text="Les créations, affectations, transferts et maintenances apparaîtront ici dès qu'ils auront lieu."/>
            @else
                <ol class="timeline">
                    @foreach ($operations as $operation)
                        @php [$libelle, $ton] = $types[$operation->action] ?? [\Illuminate\Support\Str::headline($operation->action), 'neutral']; @endphp
                        <li class="timeline__item timeline__item--{{ $ton }}">
                            <span class="timeline__dot"></span>
                            <p class="timeline__label">{{ $libelle }}</p>
                            @if ($operation->description)
                                <p class="timeline__desc">{{ $operation->description }}</p>
                            @endif
                            <p class="timeline__meta">
                                {{ $operation->utilisateur?->name ?? 'Système' }}
                                · {{ $operation->created_at->locale('fr')->diffForHumans() }}
                            </p>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        <section class="panel dash__synthese" aria-labelledby="synthese-titre">
            <div class="panel__head">
                <p class="eyebrow">Synthèse</p>
                <h2 id="synthese-titre" class="panel__title">Répartition du patrimoine</h2>
            </div>

            @if ($statistiques['biens'] === 0)
                <x-empty-state icon="building" title="Aucun bien actif"
                    text="La répartition par catégorie s'affichera dès que des biens seront enregistrés."/>
            @else
                <div class="split" role="img"
                     aria-label="{{ $immobilier }} biens immobiliers, {{ $mobilier }} biens mobiliers">
                    <span class="split__part--immobilier" style="width: {{ $immobilier / $totalBiens * 100 }}%"></span>
                    <span class="split__part--mobilier" style="width: {{ $mobilier / $totalBiens * 100 }}%"></span>
                </div>
                <div class="split-legend">
                    <span><i style="background:var(--color-primary)"></i>Immobilier · <b class="num">{{ $immobilier }}</b></span>
                    <span><i style="background:color-mix(in srgb, var(--prune) 55%, var(--perle))"></i>Mobilier · <b class="num">{{ $mobilier }}</b></span>
                </div>

                @foreach (['immobilier' => 'Immobilier', 'mobilier' => 'Mobilier'] as $type => $titre)
                    @php $groupe = $categories->where('type', $type); @endphp
                    @if ($groupe->isNotEmpty())
                        <div class="bars-group">
                            <p class="eyebrow">{{ $titre }}</p>
                            <ul class="bars">
                                @foreach ($groupe as $categorie)
                                    <li>
                                        <span>{{ $categorie->nom }}</span>
                                        <span class="bar bar--{{ $type }}" aria-hidden="true">
                                            <span style="--w: {{ $categorie->biens_count / $maxCategorie * 100 }}%"></span>
                                        </span>
                                        <b class="num">{{ $categorie->biens_count }}</b>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            @endif
        </section>

    </div>
@endsection
