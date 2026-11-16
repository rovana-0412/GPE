<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BienRequest;
use App\Models\Bien;
use App\Services\AuditWriter;
use App\Services\ServiceWriteScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class BienController extends Controller
{
    public function index(Request $request, ServiceWriteScope $scope): JsonResponse
    {
        $filters = $request->validate([
            'categorie_id' => ['nullable', 'integer', 'exists:categories,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'etat' => ['nullable', 'in:bon_etat,a_reparer,hors_service'],
            'q' => ['nullable', 'string', 'max:100'],
            'ids' => ['nullable', 'array', 'max:100'],
            'ids.*' => ['integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $biens = Bien::query()
            ->with(['categorie', 'service.ministere'])
            ->when($scope->serviceIds($request) !== null, fn ($query) => $scope->apply($query, $request))
            ->when($request->filled('categorie_id'), fn ($query) => $query->where('categorie_id', $filters['categorie_id']))
            ->when($request->filled('service_id'), fn ($query) => $query->where('service_id', $filters['service_id']))
            ->when($request->filled('etat'), fn ($query) => $query->where('etat', $filters['etat']))
            ->when(isset($filters['ids']), fn ($query) => $query->whereIn('id', $filters['ids']))
            ->when($request->filled('q'), function ($query) use ($filters): void {
                $term = '%'.$filters['q'].'%';
                $query->where(function ($search) use ($term): void {
                    $search->where('reference', 'ilike', $term)
                        ->orWhere('designation', 'ilike', $term)
                        ->orWhere('numero_serie', 'ilike', $term)
                        ->orWhere('localisation', 'ilike', $term);
                });
            })
            ->orderBy('reference')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($biens);
    }

    public function store(BienRequest $request, AuditWriter $audit, ServiceWriteScope $scope): JsonResponse
    {
        $scope->authorize($request, $request->validated('service_id') === null
            ? null
            : (int) $request->validated('service_id'));

        $bien = DB::transaction(function () use ($request, $audit): Bien {
            $bien = Bien::create($request->validated());
            $audit->enregistrer('bien', (int) $bien->id, 'bien.cree', null, $bien->toArray(), (int) $bien->id);

            return $bien;
        })->load(['categorie', 'service.ministere']);

        return response()->json(['data' => $bien], Response::HTTP_CREATED);
    }

    public function show(Request $request, Bien $bien, ServiceWriteScope $scope): JsonResponse
    {
        $scope->authorize($request, (int) $bien->service_id);

        return response()->json(['data' => $bien->load(['categorie', 'service.ministere'])]);
    }

    public function update(BienRequest $request, Bien $bien, AuditWriter $audit, ServiceWriteScope $scope): JsonResponse
    {
        $scope->authorize($request, (int) $bien->service_id);
        $scope->authorize($request, $request->validated('service_id') === null
            ? (int) $bien->service_id
            : (int) $request->validated('service_id'));

        DB::transaction(function () use ($request, $bien, $audit): void {
            $ancienneValeur = $bien->toArray();
            $bien->update($request->validated());
            $audit->enregistrer(
                'bien',
                (int) $bien->id,
                'bien.modifie',
                $ancienneValeur,
                $bien->fresh()->toArray(),
                (int) $bien->id,
            );
        });

        return response()->json(['data' => $bien->refresh()->load(['categorie', 'service.ministere'])]);
    }

    public function destroy(Request $request, Bien $bien, AuditWriter $audit, ServiceWriteScope $scope): Response
    {
        $scope->authorize($request, (int) $bien->service_id);

        DB::transaction(function () use ($bien, $audit): void {
            $ancienneValeur = $bien->toArray();
            $bien->delete();
            $audit->enregistrer(
                'bien.archive',
                (int) $bien->id,
                'bien.archive',
                $ancienneValeur,
                Bien::withTrashed()->find($bien->id)?->toArray(),
                (int) $bien->id,
            );
        });

        return response()->noContent();
    }
}
