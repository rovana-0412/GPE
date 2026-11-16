<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MinistereRequest;
use App\Models\Ministere;
use App\Services\AuditWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MinistereController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $ministeres = Ministere::query()
            ->withCount('services')
            ->when(isset($filters['q']), fn ($query) => $query
                ->where('nom', 'ilike', '%'.$filters['q'].'%')
                ->orWhere('sigle', 'ilike', '%'.$filters['q'].'%'))
            ->orderBy('nom')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($ministeres);
    }

    public function store(MinistereRequest $request, AuditWriter $audit): JsonResponse
    {
        $ministere = DB::transaction(function () use ($request, $audit): Ministere {
            $ministere = Ministere::create($request->validated());
            $audit->enregistrer('ministere', (int) $ministere->id, 'ministere.cree', null, $ministere->toArray());

            return $ministere;
        });

        return response()->json(['data' => $ministere], Response::HTTP_CREATED);
    }

    public function show(Ministere $ministere): JsonResponse
    {
        return response()->json(['data' => $ministere->loadCount('services')]);
    }

    public function update(MinistereRequest $request, Ministere $ministere, AuditWriter $audit): JsonResponse
    {
        DB::transaction(function () use ($request, $ministere, $audit): void {
            $oldValue = $ministere->toArray();
            $ministere->update($request->validated());
            $audit->enregistrer('ministere', (int) $ministere->id, 'ministere.modifie', $oldValue, $ministere->fresh()->toArray());
        });

        return response()->json(['data' => $ministere->refresh()->loadCount('services')]);
    }

    public function destroy(Ministere $ministere, AuditWriter $audit): JsonResponse|Response
    {
        if ($ministere->services()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce ministère contient des services et ne peut pas être supprimé.',
            ], Response::HTTP_CONFLICT);
        }

        DB::transaction(function () use ($ministere, $audit): void {
            $oldValue = $ministere->toArray();
            $ministere->delete();
            $audit->enregistrer('ministere', (int) $ministere->id, 'ministere.archive', $oldValue);
        });

        return response()->noContent();
    }
}
