@extends('layouts.app')

@section('title', 'Rapports statistiques')

@section('content')
    <section class="welcome page-heading">
        <div>
            <p class="eyebrow">Pilotage · Données consolidées</p>
            <h1>Rapports statistiques</h1>
            <p>Les indicateurs sont lus en temps réel depuis les services métier, sans données simulées.</p>
        </div>
        <div class="row-actions">
            <a class="btn btn--ghost" href="{{ route('rapports.pdf') }}">Télécharger PDF</a>
            <a class="btn btn--primary" href="{{ route('rapports.excel') }}">Télécharger Excel</a>
        </div>
    </section>

    <p class="list-meta">Rapport généré le {{ $rapport['genere_le'] }} · Les montants sont exprimés en FCFA (XOF).</p>

    <section class="stat-grid" aria-label="Indicateurs du patrimoine">
        <article class="stat-card"><span>Biens enregistrés</span><strong>{{ number_format($rapport['biens'], 0, ',', ' ') }}</strong></article>
        <article class="stat-card"><span>Valeur recensée</span><strong>{{ number_format($rapport['valeur_totale'], 0, ',', ' ') }} FCFA</strong></article>
        <article class="stat-card"><span>Transferts en attente</span><strong>{{ number_format($rapport['transferts_en_attente'], 0, ',', ' ') }}</strong></article>
        <article class="stat-card"><span>Maintenances à suivre</span><strong>{{ number_format($rapport['maintenances_en_cours'], 0, ',', ' ') }}</strong></article>
        <article class="stat-card"><span>Acquisitions en cours</span><strong>{{ number_format($rapport['acquisitions_en_cours'], 0, ',', ' ') }}</strong></article>
        <article class="stat-card"><span>Sorties en cours</span><strong>{{ number_format($rapport['sorties_en_cours'], 0, ',', ' ') }}</strong></article>
        <article class="stat-card"><span>Inventaires ouverts</span><strong>{{ number_format($rapport['inventaires_en_cours'], 0, ',', ' ') }}</strong></article>
    </section>

    <section class="panel">
        <div class="panel__head">
            <div><p class="eyebrow">Synthèse du registre</p><h2 class="panel__title">Indicateurs disponibles</h2></div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Indicateur</th><th>Valeur</th></tr></thead>
                <tbody>
                    @foreach ($rapport['indicateurs'] as $indicateur)
                        <tr>
                            <td>{{ $indicateur['Indicateur'] }}</td>
                            <td>{{ is_numeric($indicateur['Valeur']) ? number_format((float) $indicateur['Valeur'], 0, ',', ' ') : $indicateur['Valeur'] }}{{ str_contains($indicateur['Indicateur'], '(FCFA)') ? ' FCFA' : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
