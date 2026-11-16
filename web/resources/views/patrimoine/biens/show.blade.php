@extends('layouts.app')

@section('title', $bien['designation'])

@section('content')
    <section class="welcome record-heading">
        <div>
            <p class="eyebrow">Fiche de patrimoine · {{ $bien['reference'] }}</p>
            <h1>{{ $bien['designation'] }}</h1>
            <p>{{ $bien['description'] ?: 'Aucune description renseignée.' }}</p>
        </div>
        <span class="badge badge--{{ $bien['etat'] === 'bon_etat' ? 'success' : ($bien['etat'] === 'a_reparer' ? 'warning' : 'danger') }}">
            {{ ucfirst(str_replace('_', ' ', $bien['etat'])) }}
        </span>
    </section>

    @if (session('success'))
        <p class="success" role="status">{{ session('success') }}</p>
    @endif

    <section class="panel record-panel">
        <div class="panel__head">
            <div>
                <p class="eyebrow">Registre patrimonial</p>
                <h2 class="panel__title">Informations du bien</h2>
            </div>
            <div class="form-actions">
                @if ($peutGererPatrimoine)
                    <a class="btn btn--primary" href="{{ route('biens.edit', $bien['id']) }}">Modifier</a>
                @endif
                <a class="btn btn--ghost" href="{{ route('biens.index') }}">Retour à la liste</a>
        </div>

        <dl class="ledger record-ledger">
            <div class="ledger__row"><dt>Catégorie</dt><dd>{{ data_get($bien, 'categorie.nom', '—') }}</dd></div>
            <div class="ledger__row"><dt>Service responsable</dt><dd>{{ data_get($bien, 'service.nom', '—') }}</dd></div>
            <div class="ledger__row"><dt>Ministère</dt><dd>{{ data_get($bien, 'service.ministere.nom', '—') }}</dd></div>
            <div class="ledger__row"><dt>Numéro de série</dt><dd>{{ $bien['numero_serie'] ?: '—' }}</dd></div>
            <div class="ledger__row"><dt>Date d’acquisition</dt><dd>{{ $bien['date_acquisition'] ? \Illuminate\Support\Carbon::parse($bien['date_acquisition'])->locale('fr')->translatedFormat('j F Y') : '—' }}</dd></div>
            <div class="ledger__row"><dt>Valeur d’acquisition</dt><dd class="num">{{ isset($bien['valeur_acquisition']) ? number_format((float) $bien['valeur_acquisition'], 0, ',', ' ').' FCFA' : '—' }}</dd></div>
            <div class="ledger__row"><dt>État</dt><dd>{{ str_replace('_', ' ', $bien['etat']) }}</dd></div>
            <div class="ledger__row"><dt>Statut</dt><dd>{{ str_replace('_', ' ', $bien['statut']) }}</dd></div>
            <div class="ledger__row"><dt>Localisation</dt><dd>{{ $bien['localisation'] ?: '—' }}</dd></div>
        </dl>

        @if ($peutGererPatrimoine)
            <form method="POST" action="{{ route('biens.destroy', $bien['id']) }}" class="record-danger-zone"
                  onsubmit="return confirm('Archiver ce bien ?');">
                @csrf
                @method('DELETE')
                <div>
                    <strong>Archivage</strong>
                    <span>Le bien sera retiré des éléments actifs du registre.</span>
                </div>
                <button type="submit" class="btn btn--danger">Archiver le bien</button>
            </form>
        @else
            <x-read-only-notice/>
        @endif
    </section>

    @include('documents.partials.panel', [
        'documents' => $documents,
        'cibleType' => 'bien',
        'cibleId' => $bien['id'],
        'typesDocument' => $typesDocument,
        'pagination' => $documentsPagination,
    ])
@endsection
