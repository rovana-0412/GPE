<?php

namespace App\Services;

class ReportDataService
{
    public function __construct(private readonly MicroserviceClient $client) {}

    public function rapport(): array
    {
        $patrimoine = $this->request('patrimoine', '/api/stats');
        $mouvements = $this->request('mouvements', '/api/stats');
        $indicateurs = [
            ['Indicateur' => 'Biens enregistrés', 'Valeur' => (int) ($patrimoine['biens'] ?? 0)],
            ['Indicateur' => 'Valeur totale recensée (FCFA)', 'Valeur' => (float) ($patrimoine['valeur_totale'] ?? 0)],
            ['Indicateur' => 'Catégories', 'Valeur' => (int) ($patrimoine['categories'] ?? 0)],
            ['Indicateur' => 'Ministères', 'Valeur' => (int) ($patrimoine['ministeres'] ?? 0)],
            ['Indicateur' => 'Services', 'Valeur' => (int) ($patrimoine['services'] ?? 0)],
            ['Indicateur' => 'Acquisitions en cours', 'Valeur' => (int) ($patrimoine['acquisitions_en_cours'] ?? 0)],
            ['Indicateur' => 'Sorties en cours', 'Valeur' => (int) ($patrimoine['sorties_en_cours'] ?? 0)],
            ['Indicateur' => 'Transferts en attente', 'Valeur' => (int) ($mouvements['transferts_en_attente'] ?? 0)],
            ['Indicateur' => 'Maintenances à suivre', 'Valeur' => (int) ($mouvements['maintenances_en_cours'] ?? 0)],
            ['Indicateur' => 'Inventaires ouverts', 'Valeur' => (int) ($mouvements['inventaires_en_cours'] ?? 0)],
            ['Indicateur' => 'Coût des maintenances (FCFA)', 'Valeur' => (float) ($mouvements['cout_total_maintenances'] ?? 0)],
        ];

        foreach ($patrimoine['biens_par_categorie'] ?? [] as $categorie) {
            $indicateurs[] = [
                'Indicateur' => 'Biens · catégorie · '.($categorie['nom'] ?? 'Non renseignée'),
                'Valeur' => (int) ($categorie['biens_count'] ?? 0),
            ];
        }

        foreach ($patrimoine['biens_par_ministere'] ?? [] as $ministere) {
            $indicateurs[] = [
                'Indicateur' => 'Biens · ministère · '.($ministere['nom'] ?? 'Non renseigné'),
                'Valeur' => (int) ($ministere['biens_count'] ?? 0),
            ];
        }

        return [
            'genere_le' => now()->locale('fr')->translatedFormat('d F Y à H:i'),
            'indicateurs' => $indicateurs,
            'biens' => (int) ($patrimoine['biens'] ?? 0),
            'valeur_totale' => (float) ($patrimoine['valeur_totale'] ?? 0),
            'categories' => (int) ($patrimoine['categories'] ?? 0),
            'ministeres' => (int) ($patrimoine['ministeres'] ?? 0),
            'services' => (int) ($patrimoine['services'] ?? 0),
            'acquisitions_en_cours' => (int) ($patrimoine['acquisitions_en_cours'] ?? 0),
            'sorties_en_cours' => (int) ($patrimoine['sorties_en_cours'] ?? 0),
            'transferts_en_attente' => (int) ($mouvements['transferts_en_attente'] ?? 0),
            'maintenances_en_cours' => (int) ($mouvements['maintenances_en_cours'] ?? 0),
            'inventaires_en_cours' => (int) ($mouvements['inventaires_en_cours'] ?? 0),
            'cout_total_maintenances' => (float) ($mouvements['cout_total_maintenances'] ?? 0),
        ];
    }

    private function request(string $service, string $path): array
    {
        $response = $this->client->request($service, 'GET', $path);
        abort_unless($response->successful(), $response->status());

        return $response->json('data', []);
    }
}
