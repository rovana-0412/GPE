<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoriqueOperation;
use App\Services\PerimetreScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoriqueOperationController extends Controller
{
    public function index(Request $request, PerimetreScope $scope): JsonResponse
    {
        $filters = $request->validate([
            'bien_id' => ['nullable', 'integer', 'min:1'],
            'bien_ids' => ['nullable', 'array', 'max:100'],
            'bien_ids.*' => ['integer', 'min:1'],
            'utilisateur_id' => ['nullable', 'integer', 'min:1'],
            'action' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $operations = $scope
            ->applyBien(HistoriqueOperation::query(), $request)
            ->when(isset($filters['bien_id']), fn ($query) => $query->where('bien_id', $filters['bien_id']))
            ->when(isset($filters['bien_ids']), fn ($query) => $query->whereIn('bien_id', $filters['bien_ids']))
            ->when(isset($filters['utilisateur_id']), fn ($query) => $query->where('utilisateur_id', $filters['utilisateur_id']))
            ->when(isset($filters['action']), fn ($query) => $query->where('action', $filters['action']))
            ->when(isset($filters['q']), fn ($query) => $query->where(fn ($search) => $search
                ->where('action', 'ilike', '%'.$filters['q'].'%')
                ->orWhere('description', 'ilike', '%'.$filters['q'].'%')))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return response()->json($operations);
    }
}
