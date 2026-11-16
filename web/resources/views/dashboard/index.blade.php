@extends('layouts.app')

@section('title', 'Vue générale')

@section('content')
@php
    $prenom = explode(' ', trim(session('user.name', 'Utilisateur')))[0];
    $totalBiens = max((int) $statistiques['biens'], 1);
    $immobilier = (int) ($parType['immobilier'] ?? 0);
    $mobilier = (int) ($parType['mobilier'] ?? 0);
    $maxCategorie = max((int) ($categories->max('biens_count') ?? 0), 1);
    $maxMinistere = max((int) ($ministeres->max('biens_count') ?? 0), 1);
    $partImmobilier = round($immobilier / $totalBiens * 100);
    $partMobilier = round($mobilier / $totalBiens * 100);

    $types = [
        'bien.cree' => ['Bien enregistré', 'building', 'success'],
        'bien.modifie' => ['Informations modifiées', 'building', 'neutral'],
        'affectation.creee' => ['Nouvelle affectation', 'link', 'success'],
        'affectation.modifiee' => ['Affectation mise à jour', 'link', 'neutral'],
        'transfert.cree' => ['Transfert créé', 'swap', 'action'],
        'transfert.demande' => ['Transfert demandé', 'swap', 'action'],
        'transfert.valide' => ['Transfert validé', 'swap', 'success'],
        'transfert.statut_modifie' => ['Transfert mis à jour', 'swap', 'success'],
        'maintenance.creee' => ['Maintenance signalée', 'wrench', 'danger'],
        'maintenance.modifiee' => ['Maintenance mise à jour', 'wrench', 'neutral'],
        'maintenance.signalee' => ['Maintenance signalée', 'wrench', 'danger'],
    ];
@endphp

    <section class="welcome page-heading">
        <div>
            <p class="eyebrow">Espace national · {{ now()->locale('fr')->translatedFormat('l j F Y') }}</p>
            <h1>Bonjour, {{ $prenom }}.</h1>
            <p>Un aperçu fiable des biens publics enregistrés et des opérations en cours.</p>
        </div>
        <a class="page-heading__link" href="{{ route('biens.index') }}">
            Consulter le registre <x-icon name="arrow-right"/>
        </a>
    </section>

    @if (session('success'))
        <p class="alert alert--success" role="status" aria-live="polite">{{ session('success') }}</p>
    @endif

    <section class="overview-grid" aria-label="Synthèse du patrimoine">
        <article class="overview-card">
            <div class="overview-card__top">
                <span class="eyebrow">Valeur d’acquisition cumulée</span>
                <span class="overview-card__seal"><x-icon name="landmark"/></span>
            </div>
            <p class="overview-card__amount num">{{ number_format((float) $statistiques['valeur_totale'], 0, ',', ' ') }}</p>
            <div class="overview-card__foot">
                <span>FCFA · Valeur des biens recensés</span>
                <a href="{{ route('biens.index') }}">Voir le registre <x-icon name="arrow-right"/></a>
            </div>
            <div class="overview-card__rule" aria-hidden="true"></div>
        </article>

        <aside class="attention-card" aria-labelledby="attention-title">
            <div class="attention-card__heading">
                <span class="attention-card__mark"><x-icon name="clock"/></span>
                <div>
                    <p class="eyebrow">Suivi opérationnel</p>
                    <h2 id="attention-title">À examiner</h2>
                </div>
            </div>
            <a class="attention-row" href="{{ route('transferts.index') }}">
                <span>Transferts en attente</span>
                <strong class="num">{{ $statistiques['transferts_en_attente'] }}</strong>
                <x-icon name="arrow-right"/>
            </a>
            <a class="attention-row" href="{{ route('maintenances.index') }}">
                <span>Maintenances à suivre</span>
                <strong class="num">{{ $statistiques['maintenances'] }}</strong>
                <x-icon name="arrow-right"/>
            </a>
        </aside>
    </section>

    <section class="metric-strip" aria-label="Indicateurs de structure">
        <a class="metric" href="{{ route('biens.index') }}">
            <span class="metric__icon"><x-icon name="building"/></span>
            <span class="metric__copy"><span class="metric__label">Biens recensés</span><strong class="metric__value num">{{ $statistiques['biens'] }}</strong></span>
            <x-icon name="arrow-right"/>
        </a>
        <a class="metric" href="{{ route('categories.index') }}">
            <span class="metric__icon metric__icon--gold"><x-icon name="tag"/></span>
            <span class="metric__copy"><span class="metric__label">Catégories</span><strong class="metric__value num">{{ $statistiques['categories'] }}</strong></span>
            <x-icon name="arrow-right"/>
        </a>
        <a class="metric" href="{{ route('ministeres.index') }}">
            <span class="metric__icon"><x-icon name="landmark"/></span>
            <span class="metric__copy"><span class="metric__label">Ministères</span><strong class="metric__value num">{{ $statistiques['ministeres'] }}</strong></span>
            <x-icon name="arrow-right"/>
        </a>
        <a class="metric" href="{{ route('services.index') }}">
            <span class="metric__icon"><x-icon name="sitemap"/></span>
            <span class="metric__copy"><span class="metric__label">Services</span><strong class="metric__value num">{{ $statistiques['services'] }}</strong></span>
            <x-icon name="arrow-right"/>
        </a>
    </section>

    <section class="dashboard-grid">
        <article class="panel composition-panel">
            <div class="panel__head">
                <div>
                    <p class="eyebrow">Composition du registre</p>
                    <h2 class="panel__title">Nature des biens</h2>
                </div>
                <a class="text-link" href="{{ route('categories.index') }}">Catégories <x-icon name="arrow-right"/></a>
            </div>
            <div class="composition">
                <div class="composition__visual">
                    <div @class(['composition__ring', 'is-empty' => (int) $statistiques['biens'] === 0]) style="--part: {{ $partImmobilier }}%">
                        <div class="composition__center">
                            <strong class="num">{{ $statistiques['biens'] }}</strong>
                            <span>biens</span>
                        </div>
                    </div>
                </div>
                <div class="composition__legend">
                    <div class="composition__item">
                        <span class="composition__swatch composition__swatch--forest"></span>
                        <span><strong>Immobilier</strong><small>Biens immobiliers</small></span>
                        <b class="num">{{ $immobilier }} <small>({{ $partImmobilier }} %)</small></b>
                    </div>
                    <div class="composition__item">
                        <span class="composition__swatch composition__swatch--gold"></span>
                        <span><strong>Mobilier</strong><small>Équipements et matériels</small></span>
                        <b class="num">{{ $mobilier }} <small>({{ $partMobilier }} %)</small></b>
                    </div>
                </div>
            </div>
            <p class="panel__note">Répartition calculée à partir des catégories présentes dans le registre.</p>
        </article>

        <article class="panel activity-panel">
            <div class="panel__head">
                <div>
                    <p class="eyebrow">Traçabilité</p>
                    <h2 class="panel__title">Opérations récentes</h2>
                </div>
                <a class="text-link" href="{{ route('historique.index') }}">Tout l’historique <x-icon name="arrow-right"/></a>
            </div>
            @if ($operations->isEmpty())
                <x-empty-state icon="clock" title="Aucune opération enregistrée"
                    text="Les créations, affectations, transferts et maintenances apparaîtront ici."/>
            @else
                <ol class="activity-list">
                    @foreach ($operations as $operation)
                        @php [$libelle, $icone, $ton] = $types[$operation->action] ?? [\Illuminate\Support\Str::headline($operation->action), 'clock', 'neutral']; @endphp
                        <li class="activity-item">
                            <span class="activity-item__icon activity-item__icon--{{ $ton }}"><x-icon :name="$icone"/></span>
                            <span class="activity-item__body">
                                <strong>{{ $libelle }}</strong>
                                <small>{{ $operation->created_at->locale('fr')->diffForHumans() }}</small>
                            </span>
                            <span class="activity-item__code">{{ $operation->action }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </article>

        <article class="panel ministry-panel">
            <div class="panel__head">
                <div>
                    <p class="eyebrow">Répartition administrative</p>
                    <h2 class="panel__title">Biens par ministère</h2>
                </div>
                <a class="text-link" href="{{ route('ministeres.index') }}">Voir les ministères <x-icon name="arrow-right"/></a>
            </div>
            <div class="ranking-list">
                @forelse ($ministeres as $ministere)
                    <div class="ranking-item">
                        <span class="ranking-item__name" title="{{ $ministere->nom }}">{{ \Illuminate\Support\Str::limit($ministere->nom, 36) }}</span>
                        <span class="ranking-item__track"><span style="--width: {{ $ministere->biens_count / $maxMinistere * 100 }}%"></span></span>
                        <strong class="num">{{ $ministere->biens_count }}</strong>
                    </div>
                @empty
                    <p class="panel__empty">Aucun ministère enregistré.</p>
                @endforelse
            </div>
        </article>

        <article class="panel category-panel">
            <div class="panel__head">
                <div>
                    <p class="eyebrow">Inventaire</p>
                    <h2 class="panel__title">Catégories principales</h2>
                </div>
                <a class="text-link" href="{{ route('biens.index') }}">Parcourir les biens <x-icon name="arrow-right"/></a>
            </div>
            <div class="ranking-list">
                @forelse ($categories as $categorie)
                    <div class="ranking-item">
                        <span class="ranking-item__name" title="{{ $categorie->nom }}">{{ \Illuminate\Support\Str::limit($categorie->nom, 36) }}</span>
                        <span class="ranking-item__track ranking-item__track--gold"><span style="--width: {{ $categorie->biens_count / $maxCategorie * 100 }}%"></span></span>
                        <strong class="num">{{ $categorie->biens_count }}</strong>
                    </div>
                @empty
                    <p class="panel__empty">Aucune catégorie enregistrée.</p>
                @endforelse
            </div>
        </article>
    </section>
@endsection
