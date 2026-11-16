<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'utilisateur_id' => ['nullable', 'integer', 'min:1'],
            'action' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $logs = AuditLog::query()
            ->when(isset($filters['utilisateur_id']), fn ($query) => $query->where('acteur_id', $filters['utilisateur_id']))
            ->when(isset($filters['action']), fn ($query) => $query->where('action', $filters['action']))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 30)
            ->withQueryString();

        return response()->json($logs);
    }
}
