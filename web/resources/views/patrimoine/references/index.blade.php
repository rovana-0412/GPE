@extends('layouts.app')

@section('title', $definition['title'])

@section('content')
    @php
        $titresRegistre = [
            'categories' => 'Catégories enregistrées',
            'ministeres' => 'Ministères enregistrés',
            'services' => 'Services enregistrés',
        ];
    @endphp

    <section class="welcome page-heading">
        <div>
            <p class="eyebrow">Organisation · Référentiels</p>
            <h1>{{ $definition['title'] }}</h1>
            <p>Gérez les référentiels utilisés pour décrire les biens publics.</p>
        </div>
        @if ($peutGererPatrimoine)
            <a class="btn btn--primary" href="{{ route($resource.'.create') }}">
                <x-icon name="plus"/> Ajouter
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
                <p class="eyebrow">Registre administratif</p>
                <h2 class="panel__title">{{ $titresRegistre[$resource] }}</h2>
            </div>
        </div>
        @unless ($peutGererPatrimoine)
            <x-read-only-notice/>
        @endunless

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        @foreach ($definition['columns'] as $column)
                            <th>{{ $column['label'] }}</th>
                        @endforeach
                        @if ($peutGererPatrimoine)
                            <th><span class="sr-only">Actions</span></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            @foreach ($definition['columns'] as $column)
                                <td>{{ data_get($item, $column['key']) ?? '—' }}</td>
                            @endforeach
                            @if ($peutGererPatrimoine)
                                <td class="row-actions">
                                    <a class="table-link" href="{{ route($resource.'.edit', $item['id']) }}">Modifier</a>
                                    <form method="POST" action="{{ route($resource.'.destroy', $item['id']) }}"
                                          onsubmit="return confirm('Supprimer cet enregistrement ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn--ghost btn--compact" type="submit">Supprimer</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($definition['columns']) + ($peutGererPatrimoine ? 1 : 0) }}">Aucun élément enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (($pagination['last_page'] ?? 1) > 1)
            <nav class="pagination" aria-label="Pagination des {{ mb_strtolower($definition['title']) }}">
                @if (($pagination['current_page'] ?? 1) > 1)
                    <a href="{{ route($resource.'.index', ['page' => $pagination['current_page'] - 1]) }}">Précédent</a>
                @endif
                <span>Page {{ $pagination['current_page'] }} sur {{ $pagination['last_page'] }}</span>
                @if (($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1))
                    <a href="{{ route($resource.'.index', ['page' => $pagination['current_page'] + 1]) }}">Suivant</a>
                @endif
            </nav>
        @endif
    </section>
@endsection
