@extends('layouts.app')

@section('title', 'Service indisponible')

@section('content')
    @php
        $labels = [
            'patrimoine' => 'le service de gestion du patrimoine',
            'mouvements' => 'le service des mouvements',
            'auth' => 'le service d’authentification',
        ];
    @endphp
    <section class="panel" role="alert">
        <p class="eyebrow">Erreur temporaire</p>
        <h1>Service momentanément indisponible</h1>
        <p>{{ $labels[$service] ?? 'Un service de la plateforme' }} ne répond pas actuellement. Vos données n’ont pas été modifiées. Veuillez réessayer dans quelques instants.</p>
        <a class="btn btn--primary" href="{{ url()->previous() }}">Réessayer</a>
    </section>
@endsection
