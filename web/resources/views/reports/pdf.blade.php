<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { color: #25332e; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { color: #153f33; font-size: 22px; margin-bottom: 4px; }
        p { color: #64736d; margin-top: 0; }
        table { border-collapse: collapse; margin-top: 22px; width: 100%; }
        th { background: #153f33; color: #fff; text-align: left; }
        th, td { border: 1px solid #dfe6e2; padding: 8px; }
        tr:nth-child(even) td { background: #f5f7f5; }
        .amount { color: #9c7a2d; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Rapport du patrimoine de l’État</h1>
    <p>République togolaise · Généré le {{ $rapport['genere_le'] }} · Montants en FCFA (XOF)</p>
    <table>
        <thead><tr><th>Indicateur</th><th>Valeur</th></tr></thead>
        <tbody>
            @foreach ($rapport['indicateurs'] as $indicateur)
                <tr>
                    <td>{{ $indicateur['Indicateur'] }}</td>
                    <td class="{{ str_contains($indicateur['Indicateur'], '(FCFA)') ? 'amount' : '' }}">
                        {{ is_numeric($indicateur['Valeur']) ? number_format((float) $indicateur['Valeur'], 0, ',', ' ') : $indicateur['Valeur'] }}
                        {{ str_contains($indicateur['Indicateur'], '(FCFA)') ? 'FCFA' : '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
