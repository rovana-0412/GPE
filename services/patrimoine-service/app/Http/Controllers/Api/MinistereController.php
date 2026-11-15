<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MinistereRequest;
use App\Models\Ministere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MinistereController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $ministeres = Ministere::query()
            ->withCount('services')
            ->orderBy('nom')
            ->paginate(min(max($request->integer('per_page', 15), 1), 100))
            ->withQueryString();

        return response()->json($ministeres);
    }

    public function store(MinistereRequest $request): JsonResponse
    {
        return response()->json([
            'data' => Ministere::create($request->validated()),
        ], Response::HTTP_CREATED);
    }

    public function show(Ministere $ministere): JsonResponse
    {
        return response()->json(['data' => $ministere->loadCount('services')]);
    }

    public function update(MinistereRequest $request, Ministere $ministere): JsonResponse
    {
        $ministere->update($request->validated());

        return response()->json(['data' => $ministere->refresh()->loadCount('services')]);
    }

    public function destroy(Ministere $ministere): JsonResponse|Response
    {
        if ($ministere->services()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce ministère contient des services et ne peut pas être supprimé.',
            ], Response::HTTP_CONFLICT);
        }

        $ministere->delete();

        return response()->noContent();
    }
}
