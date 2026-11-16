<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceRequest;
use App\Models\Service;
use App\Services\AuditWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $services = Service::query()
            ->with('ministere')
            ->withCount('biens')
            ->when(isset($filters['q']), fn ($query) => $query->where('nom', 'ilike', '%'.$filters['q'].'%')
                ->orWhere('sigle', 'ilike', '%'.$filters['q'].'%'))
            ->orderBy('nom')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($services);
    }

    public function store(ServiceRequest $request, AuditWriter $audit): JsonResponse
    {
        $service = DB::transaction(function () use ($request, $audit): Service {
            $service = Service::create($request->validated());
            $audit->enregistrer('service', (int) $service->id, 'service.cree', null, $service->toArray());

            return $service;
        })->load('ministere');

        return response()->json(['data' => $service], Response::HTTP_CREATED);
    }

    public function show(Service $service): JsonResponse
    {
        return response()->json(['data' => $service->load('ministere')->loadCount('biens')]);
    }

    public function update(ServiceRequest $request, Service $service, AuditWriter $audit): JsonResponse
    {
        DB::transaction(function () use ($request, $service, $audit): void {
            $oldValue = $service->toArray();
            $service->update($request->validated());
            $audit->enregistrer('service', (int) $service->id, 'service.modifie', $oldValue, $service->fresh()->toArray());
        });

        return response()->json(['data' => $service->refresh()->load('ministere')->loadCount('biens')]);
    }

    public function destroy(Service $service, AuditWriter $audit): JsonResponse|Response
    {
        if ($service->biens()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce service est affecté à des biens et ne peut pas être supprimé.',
            ], Response::HTTP_CONFLICT);
        }

        DB::transaction(function () use ($service, $audit): void {
            $oldValue = $service->toArray();
            $service->delete();
            $audit->enregistrer('service', (int) $service->id, 'service.archive', $oldValue);
        });

        return response()->noContent();
    }
}
