<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategorieRequest;
use App\Models\Categorie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CategorieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Categorie::query()
            ->withCount('biens')
            ->orderBy('nom')
            ->paginate(min(max($request->integer('per_page', 15), 1), 100))
            ->withQueryString();

        return response()->json($categories);
    }

    public function store(CategorieRequest $request): JsonResponse
    {
        return response()->json([
            'data' => Categorie::create($request->validated()),
        ], Response::HTTP_CREATED);
    }

    public function show(Categorie $categorie): JsonResponse
    {
        return response()->json(['data' => $categorie->loadCount('biens')]);
    }

    public function update(CategorieRequest $request, Categorie $categorie): JsonResponse
    {
        $categorie->update($request->validated());

        return response()->json(['data' => $categorie->refresh()->loadCount('biens')]);
    }

    public function destroy(Categorie $categorie): JsonResponse|Response
    {
        if ($categorie->biens()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette catégorie est utilisée par des biens et ne peut pas être supprimée.',
            ], Response::HTTP_CONFLICT);
        }

        $categorie->delete();

        return response()->noContent();
    }
}
