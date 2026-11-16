@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <section class="welcome">
        <p class="eyebrow">Traçabilité · Alertes personnelles</p>
        <h1>Notifications</h1>
        <p>Suivez les décisions concernant les opérations que vous avez initiées.</p>
    </section>

    @if (session('success'))<p class="alert alert--success" role="status">{{ session('success') }}</p>@endif
    @if ($errors->any())<p class="alert alert--danger" role="alert">{{ $errors->first() }}</p>@endif

    <section class="panel">
        <form method="GET" action="{{ route('notifications.index') }}" class="form-grid">
            <label class="field">
                <span>Affichage</span>
                <select class="input" name="non_lues">
                    <option value="">Toutes les notifications</option>
                    <option value="1" @selected(filter_var($filters['non_lues'] ?? false, FILTER_VALIDATE_BOOLEAN))>Non lues uniquement</option>
                </select>
            </label>
            <div class="form-actions"><button class="btn btn--ghost" type="submit">Filtrer</button></div>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Date</th><th>Notification</th><th>État</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse ($notifications as $notification)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($notification['created_at'])->locale('fr')->translatedFormat('d/m/Y H:i') }}</td>
                            <td><strong>{{ $notification['titre'] }}</strong><br>{{ $notification['message'] }}</td>
                            <td><span class="badge badge--{{ $notification['lue_le'] ? 'neutral' : 'warning' }}">{{ $notification['lue_le'] ? 'Lue' : 'Non lue' }}</span></td>
                            <td>
                                @if (! $notification['lue_le'])
                                    <form method="POST" action="{{ route('notifications.read', $notification['id']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn--ghost btn--compact" type="submit">Marquer comme lue</button>
                                    </form>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Aucune notification ne correspond à ce filtre.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
