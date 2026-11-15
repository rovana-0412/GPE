<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceRequest;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = Service::query()
            ->with('ministere')
            ->withCount('biens')
            ->orderBy('nom')
            ->paginate(min(max($request->integer('per_page', 15), 1), 100))
            ->withQueryString();

        return response()->json($services);
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        $service = Service::create($request->validated())->load('ministere');

        return response()->json(['data' => $service], Response::HTTP_CREATED);
    }

    public function show(Service $service): JsonResponse
    {
        return response()->json(['data' => $service->load('ministere')->loadCount('biens')]);
    }

    public function update(ServiceRequest $request, Service $service): JsonResponse
    {
        $service->update($request->validated());

        return response()->json(['data' => $service->refresh()->load('ministere')->loadCount('biens')]);
    }

    public function destroy(Service $service): JsonResponse|Response
    {
        if ($service->biens()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce service est affecté à des biens et ne peut pas être supprimé.',
            ], Response::HTTP_CONFLICT);
        }

        $service->delete();

        return response()->noContent();
    }
}
