<section class="panel">
    <div class="panel__head">
        <div>
            <p class="eyebrow">Dossier documentaire</p>
            <h2 class="panel__title">Pièces justificatives</h2>
        </div>
        <span class="badge badge--neutral">{{ $pagination['total'] ?? count($documents) }} document(s)</span>
    </div>

    @if ($peutGererPatrimoine)
        <form method="POST" action="{{ route('documents.store', [$cibleType, $cibleId]) }}" enctype="multipart/form-data" class="form-grid">
            @csrf
            <div class="field">
                <label for="type_document">Type de document</label>
                <select class="input" id="type_document" name="type_document" required>
                    <option value="">Choisir un type</option>
                    @foreach ($typesDocument as $value => $label)
                        <option value="{{ $value }}" @selected(old('type_document') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="titre">Titre</label>
                <input class="input" id="titre" name="titre" value="{{ old('titre') }}" maxlength="180" required>
            </div>
            <div class="field">
                <label for="reference">Référence du document</label>
                <input class="input" id="reference" name="reference" value="{{ old('reference') }}" maxlength="100">
            </div>
            <div class="field">
                <label for="date_document">Date du document</label>
                <input class="input" id="date_document" name="date_document" type="date" value="{{ old('date_document') }}">
            </div>
            <div class="field">
                <label for="description">Description</label>
                <textarea class="input" id="description" name="description" rows="2" maxlength="5000">{{ old('description') }}</textarea>
            </div>
            <div class="field">
                <label for="fichier">Fichier (PDF, image, Word ou Excel · 10 Mo maximum)</label>
                <input class="input" id="fichier" name="fichier" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" required>
            </div>
            <div class="form-actions">
                <button class="btn btn--primary" type="submit">Ajouter le justificatif</button>
            </div>
        </form>
    @else
        <x-read-only-notice/>
    @endif

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Titre et référence</th>
                    <th>Date</th>
                    <th>Fichier</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $document)
                    <tr>
                        <td>{{ $typesDocument[$document['type_document']] ?? 'Autre' }}</td>
                        <td>
                            <strong>{{ $document['titre'] }}</strong>
                            @if (!empty($document['reference']))
                                <span class="muted"> · {{ $document['reference'] }}</span>
                            @endif
                            @if (!empty($document['description']))
                                <div class="muted">{{ $document['description'] }}</div>
                            @endif
                        </td>
                        <td>{{ !empty($document['date_document']) ? substr($document['date_document'], 0, 10) : '—' }}</td>
                        <td>{{ $document['nom_original'] }}<br><span class="muted">{{ number_format(((int) ($document['taille_octets'] ?? 0)) / 1024, 0, ',', ' ') }} Ko</span></td>
                        <td class="row-actions">
                            <a class="btn btn--ghost btn--compact" href="{{ route('documents.download', [$cibleType, $cibleId, $document['id']]) }}">Télécharger</a>
                            @if ($peutGererPatrimoine)
                                <form method="POST" action="{{ route('documents.destroy', [$cibleType, $cibleId, $document['id']]) }}" onsubmit="return confirm('Supprimer ce document justificatif ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn--danger btn--compact" type="submit">Supprimer</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Aucun document justificatif n’est associé à cet élément.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (($pagination['last_page'] ?? 1) > 1)
        <nav class="pagination" aria-label="Pagination des documents">
            @if (($pagination['current_page'] ?? 1) > 1)
                <a href="{{ route('documents.index', [$cibleType, $cibleId, 'page' => $pagination['current_page'] - 1]) }}">Précédent</a>
            @endif
            <span>Page {{ $pagination['current_page'] }} sur {{ $pagination['last_page'] }}</span>
            @if (($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1))
                <a href="{{ route('documents.index', [$cibleType, $cibleId, 'page' => $pagination['current_page'] + 1]) }}">Suivant</a>
            @endif
        </nav>
    @endif
</section>
