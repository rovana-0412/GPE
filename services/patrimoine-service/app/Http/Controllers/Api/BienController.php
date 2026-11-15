<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BienRequest;
use App\Models\Bien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BienController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'categorie_id' => ['nullable', 'integer', 'exists:categories,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'etat' => ['nullable', 'in:bon_etat,a_reparer,hors_service'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $biens = Bien::query()
            ->with(['categorie', 'service.ministere'])
            ->when($request->filled('categorie_id'), fn ($query) => $query->where('categorie_id', $filters['categorie_id']))
            ->when($request->filled('service_id'), fn ($query) => $query->where('service_id', $filters['service_id']))
            ->when($request->filled('etat'), fn ($query) => $query->where('etat', $filters['etat']))
            ->when($request->filled('q'), function ($query) use ($filters): void {
                $term = '%'.$filters['q'].'%';
                $query->where(function ($search) use ($term): void {
                    $search->where('reference', 'like', $term)
                        ->orWhere('designation', 'like', $term)
                        ->orWhere('numero_serie', 'like', $term)
                        ->orWhere('localisation', 'like', $term);
                });
            })
            ->orderBy('reference')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($biens);
    }

    public function store(BienRequest $request): JsonResponse
    {
        $bien = Bien::create($request->validated())->load(['categorie', 'service.ministere']);

        return response()->json(['data' => $bien], Response::HTTP_CREATED);
    }

    public function show(Bien $bien): JsonResponse
    {
        return response()->json(['data' => $bien->load(['categorie', 'service.ministere'])]);
    }

    public function update(BienRequest $request, Bien $bien): JsonResponse
    {
        $bien->update($request->validated());

        return response()->json(['data' => $bien->refresh()->load(['categorie', 'service.ministere'])]);
    }

    public function destroy(Bien $bien): Response
    {
        $bien->delete();

        return response()->noContent();
    }
}
