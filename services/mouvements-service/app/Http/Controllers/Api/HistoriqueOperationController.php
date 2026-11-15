<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoriqueOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoriqueOperationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'bien_id' => ['nullable', 'integer', 'min:1'],
            'utilisateur_id' => ['nullable', 'integer', 'min:1'],
            'action' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $operations = HistoriqueOperation::query()
            ->when(isset($filters['bien_id']), fn ($query) => $query->where('bien_id', $filters['bien_id']))
            ->when(isset($filters['utilisateur_id']), fn ($query) => $query->where('utilisateur_id', $filters['utilisateur_id']))
            ->when(isset($filters['action']), fn ($query) => $query->where('action', $filters['action']))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return response()->json($operations);
    }
}
