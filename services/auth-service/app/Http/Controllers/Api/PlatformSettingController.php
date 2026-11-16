<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\AuditWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PlatformSettingController extends Controller
{
    private const DEFAULTS = [
        'nom_plateforme' => 'GPE',
        'nom_institution' => 'République togolaise',
        'contact_support' => null,
    ];

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->values()]);
    }

    public function update(Request $request, AuditWriter $audit): JsonResponse
    {
        $data = $request->validate([
            'nom_plateforme' => ['required', 'string', 'min:2', 'max:80'],
            'nom_institution' => ['required', 'string', 'min:2', 'max:120'],
            'contact_support' => ['nullable', 'email', 'max:255'],
        ]);
        if ($data['contact_support'] === '') {
            $data['contact_support'] = null;
        }

        $payload = $request->attributes->get('jwt_payload', []);
        $result = DB::transaction(function () use ($data, $payload, $audit): array {
            $oldValue = $this->values();
            foreach ($data as $key => $value) {
                PlatformSetting::query()->updateOrCreate(
                    ['cle' => $key],
                    ['valeur' => $value, 'modifie_par' => (int) $payload['sub']],
                );
            }
            $newValue = $this->values();
            $audit->parametres($oldValue, $newValue);

            return $newValue;
        });

        return response()->json(['data' => $result], Response::HTTP_OK);
    }

    private function values(): array
    {
        $stored = PlatformSetting::query()->pluck('valeur', 'cle')->all();
        return array_replace(self::DEFAULTS, $stored);
    }
}
