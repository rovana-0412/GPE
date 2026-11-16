@extends('layouts.app')

@section('title', 'Documents justificatifs')

@section('content')
    <section class="welcome page-heading">
        <div>
            <p class="eyebrow">Pièces justificatives · {{ $targetLabel }}</p>
            <h1>Documents</h1>
            <p>{{ $targetTitle }}</p>
        </div>
        <a class="btn btn--ghost" href="{{ $returnUrl }}">Retour</a>
    </section>

    @if (session('success'))
        <p class="alert alert--success" role="status">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>
    @endif

    @include('documents.partials.panel', [
        'documents' => $documents,
        'cibleType' => $cibleType,
        'cibleId' => $cibleId,
        'typesDocument' => $typesDocument,
        'pagination' => $pagination,
    ])
@endsection
