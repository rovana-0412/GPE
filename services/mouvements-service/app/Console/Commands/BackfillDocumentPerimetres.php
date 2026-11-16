<?php

namespace App\Console\Commands;

use App\Models\DocumentJustificatif;
use App\Services\DocumentAccessService;
use App\Services\PatrimoineClient;
use Firebase\JWT\JWT;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BackfillDocumentPerimetres extends Command
{
    protected $signature = 'documents:backfill-perimetres';

    protected $description = 'Ajoute les périmètres service/ministère manquants aux justificatifs existants';

    public function handle(DocumentAccessService $access, PatrimoineClient $patrimoine): int
    {
        $secret = config('jwt.secret');
        if (! is_string($secret) || strlen($secret) < 32) {
            $this->error('JWT_SECRET est absent ou trop court.');

            return self::FAILURE;
        }

        $now = now()->timestamp;
        $token = JWT::encode([
            'sub' => '1',
            'role' => 'administrateur-general',
            'service_id' => null,
            'iat' => $now,
            'exp' => $now + 300,
        ], $secret, 'HS256');
        $traites = 0;
        $sansPerimetre = 0;
        $erreurs = 0;

        DocumentJustificatif::query()
            ->whereDoesntHave('perimetres')
            ->orderBy('id')
            ->chunkById(100, function ($documents) use (
                $access,
                $patrimoine,
                $token,
                &$traites,
                &$sansPerimetre,
                &$erreurs,
            ): void {
                foreach ($documents as $document) {
                    try {
                        $context = $access->resolveTarget(
                            $document->cible_type,
                            (int) $document->cible_id,
                            $token,
                            $patrimoine,
                        );
                    } catch (ValidationException $exception) {
                        $this->warn('Document #'.$document->id.' : cible ou périmètre introuvable.');
                        $erreurs++;
                        continue;
                    }

                    if ($context['perimetres'] === []) {
                        $this->warn('Document #'.$document->id.' : aucun service rattaché à la cible.');
                        $sansPerimetre++;
                        continue;
                    }

                    DB::transaction(function () use ($document, $context): void {
                        $locked = DocumentJustificatif::query()->lockForUpdate()->findOrFail($document->id);
                        if (! $locked->perimetres()->exists()) {
                            $locked->perimetres()->createMany($context['perimetres']);
                        }
                    });
                    $traites++;
                }
            });

        $this->info("Périmètres ajoutés : {$traites}.");
        $this->info("Documents sans service : {$sansPerimetre}.");
        $this->info("Cibles non résolues : {$erreurs}.");

        return $sansPerimetre === 0 && $erreurs === 0 ? self::SUCCESS : self::FAILURE;
    }
}
