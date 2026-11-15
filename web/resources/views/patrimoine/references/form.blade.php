@extends('layouts.app')

@section('title', ucfirst($mode).' '.$definition['singular'])

@section('content')
    <section class="welcome">
        <p class="eyebrow">Administration du patrimoine</p>
        <h1>{{ $mode === 'creation' ? 'Créer une '.$definition['singular'] : 'Modifier '.$definition['singular'] }}</h1>
        <p>Complétez les informations du référentiel.</p>
    </section>

    @if ($errors->any())
        <p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>
    @endif

    <section class="panel">
        <form method="POST"
              action="{{ $mode === 'creation' ? route($resource.'.store') : route($resource.'.update', $item['id']) }}"
              class="form-grid">
            @csrf
            @if ($mode === 'modification')
                @method('PUT')
            @endif

            @foreach ($definition['fields'] as $field)
                @php $value = old($field['name'], data_get($item, $field['name'], $field['name'] === 'actif' ? true : '')); @endphp
                <div class="field">
                    @if ($field['type'] === 'checkbox')
                        <label for="{{ $field['name'] }}">
                            <input type="hidden" name="{{ $field['name'] }}" value="0">
                            <input id="{{ $field['name'] }}" type="checkbox" name="{{ $field['name'] }}" value="1" @checked((bool) $value)>
                            {{ $field['label'] }}
                        </label>
                    @else
                        <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
                        @if ($field['type'] === 'textarea')
                            <textarea class="input" id="{{ $field['name'] }}" name="{{ $field['name'] }}" rows="4">{{ $value }}</textarea>
                        @elseif ($field['type'] === 'select')
                            <select class="input" id="{{ $field['name'] }}" name="{{ $field['name'] }}" @required($field['required'] ?? false)>
                                <option value="">Choisir</option>
                                @foreach ($field['options'] as $optionValue => $label)
                                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $label }}</option>
                                @endforeach
                            </select>
                        @elseif ($field['type'] === 'ministere')
                            <select class="input" id="{{ $field['name'] }}" name="{{ $field['name'] }}" required>
                                <option value="">Choisir un ministère</option>
                                @foreach ($ministeres as $ministere)
                                    <option value="{{ $ministere['id'] }}" @selected((string) $value === (string) $ministere['id'])>{{ $ministere['nom'] }}</option>
                                @endforeach
                            </select>
                        @else
                            <input class="input" id="{{ $field['name'] }}" name="{{ $field['name'] }}" type="{{ $field['type'] }}"
                                   value="{{ $value }}" @required($field['required'] ?? false)>
                        @endif
                    @endif
                    @error($field['name'])<span class="field__error">{{ $message }}</span>@enderror
                </div>
            @endforeach

            <div class="form-actions">
                <button class="btn btn--primary" type="submit">{{ $mode === 'creation' ? 'Créer' : 'Enregistrer' }}</button>
                <a class="btn btn--ghost" href="{{ route($resource.'.index') }}">Annuler</a>
            </div>
        </form>
    </section>
@endsection
