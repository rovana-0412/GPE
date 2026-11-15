@extends('layouts.app')

@section('title', $definition['title'])

@section('content')
    <section class="welcome">
        <p class="eyebrow">Administration du patrimoine</p>
        <h1>{{ $definition['title'] }}</h1>
        <p>Gérez les référentiels utilisés pour décrire les biens publics.</p>
    </section>

    @if (session('success'))
        <p class="alert alert--success" role="status">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>
    @endif

    <section class="panel">
        <div class="panel__head">
            <h2 class="panel__title">{{ $definition['title'] }} enregistrés</h2>
            <a class="btn btn--primary" href="{{ route($resource.'.create') }}">Ajouter</a>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        @foreach ($definition['columns'] as $column)
                            <th>{{ $column['label'] }}</th>
                        @endforeach
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            @foreach ($definition['columns'] as $column)
                                <td>{{ data_get($item, $column['key']) ?? '—' }}</td>
                            @endforeach
                            <td class="form-actions">
                                <a href="{{ route($resource.'.edit', $item['id']) }}">Modifier</a>
                                <form method="POST" action="{{ route($resource.'.destroy', $item['id']) }}"
                                      onsubmit="return confirm('Supprimer cet enregistrement ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn--ghost" type="submit">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($definition['columns']) + 1 }}">Aucun élément enregistré.</td></tr>
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
