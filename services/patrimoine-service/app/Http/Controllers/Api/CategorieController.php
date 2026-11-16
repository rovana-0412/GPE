<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategorieRequest;
use App\Models\Categorie;
use App\Services\AuditWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CategorieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $categories = Categorie::query()
            ->withCount('biens')
            ->when(isset($filters['q']), fn ($query) => $query->where('nom', 'ilike', '%'.$filters['q'].'%'))
            ->orderBy('nom')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($categories);
    }

    public function store(CategorieRequest $request, AuditWriter $audit): JsonResponse
    {
        $categorie = DB::transaction(function () use ($request, $audit): Categorie {
            $categorie = Categorie::create($request->validated());
            $audit->enregistrer('categorie', (int) $categorie->id, 'categorie.creee', null, $categorie->toArray());

            return $categorie;
        });

        return response()->json(['data' => $categorie], Response::HTTP_CREATED);
    }

    public function show(Categorie $categorie): JsonResponse
    {
        return response()->json(['data' => $categorie->loadCount('biens')]);
    }

    public function update(CategorieRequest $request, Categorie $categorie, AuditWriter $audit): JsonResponse
    {
        DB::transaction(function () use ($request, $categorie, $audit): void {
            $oldValue = $categorie->toArray();
            $categorie->update($request->validated());
            $audit->enregistrer('categorie', (int) $categorie->id, 'categorie.modifiee', $oldValue, $categorie->fresh()->toArray());
        });

        return response()->json(['data' => $categorie->refresh()->loadCount('biens')]);
    }

    public function destroy(Categorie $categorie, AuditWriter $audit): JsonResponse|Response
    {
        if ($categorie->biens()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette catégorie est utilisée par des biens et ne peut pas être supprimée.',
            ], Response::HTTP_CONFLICT);
        }

        DB::transaction(function () use ($categorie, $audit): void {
            $oldValue = $categorie->toArray();
            $categorie->delete();
            $audit->enregistrer('categorie', (int) $categorie->id, 'categorie.archivee', $oldValue);
        });

        return response()->noContent();
    }
}
