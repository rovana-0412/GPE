<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'service_id' => ['nullable', 'integer', 'min:1'],
            'actif' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = User::query()
            ->select(['id', 'name', 'email', 'role_id', 'service_id', 'actif', 'created_at'])
            ->with('role:id,nom,description')
            ->when(isset($filters['q']), function ($query) use ($filters): void {
                $term = '%'.$filters['q'].'%';
                $query->where(fn ($search) => $search
                    ->where('name', 'ilike', $term)
                    ->orWhere('email', 'ilike', $term));
            })
            ->when(isset($filters['role_id']), fn ($query) => $query->where('role_id', $filters['role_id']))
            ->when(isset($filters['service_id']), fn ($query) => $query->where('service_id', $filters['service_id']))
            ->when(array_key_exists('actif', $filters), fn ($query) => $query->where('actif', $filters['actif']))
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($users);
    }

    public function roles(): JsonResponse
    {
        return response()->json([
            'data' => Role::query()->orderBy('id')->get(['id', 'nom', 'description']),
        ]);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => $user->load('role:id,nom,description')]);
    }

    public function store(UserRequest $request, AuditWriter $audit): JsonResponse
    {
        $user = DB::transaction(function () use ($request, $audit): User {
            $user = User::create($request->validated());
            $audit->utilisateur('utilisateur.cree', null, $user);

            return $user;
        })->load('role:id,nom,description');

        return response()->json(['data' => $user], Response::HTTP_CREATED);
    }

    public function update(UserRequest $request, User $user, AuditWriter $audit): JsonResponse
    {
        $data = $request->validated();
        $currentUser = $request->user;

        if (
            $currentUser->is($user)
            && isset($data['role_id'])
            && (int) $data['role_id'] !== (int) $user->role_id
        ) {
            abort(Response::HTTP_FORBIDDEN, 'Un administrateur ne peut pas modifier son propre rôle.');
        }

        DB::transaction(function () use ($user, $data, $audit): void {
            $ancien = $user->replicate();
            $ancien->id = $user->id;
            $user->update($data);
            $audit->utilisateur('utilisateur.modifie', $ancien, $user->fresh());
        });

        return response()->json(['data' => $user->refresh()->load('role:id,nom,description')]);
    }

    public function changeStatus(Request $request, User $user, AuditWriter $audit): JsonResponse
    {
        $data = $request->validate(['actif' => ['required', 'boolean']]);
        $currentUser = $request->user;

        if ($currentUser->is($user) && ! $data['actif']) {
            abort(Response::HTTP_FORBIDDEN, 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        DB::transaction(function () use ($data, $user, $audit): void {
            if ($user->actif && ! $data['actif'] && $user->role?->slug() === 'administrateur-general') {
                $adminRoleIds = Role::query()
                    ->get()
                    ->filter(fn (Role $role): bool => $role->slug() === 'administrateur-general')
                    ->modelKeys();
                $activeAdmins = User::query()
                    ->whereIn('role_id', $adminRoleIds)
                    ->where('actif', true)
                    ->lockForUpdate()
                    ->get();

                if ($activeAdmins->count() <= 1) {
                    throw ValidationException::withMessages([
                        'actif' => 'Le dernier compte administrateur actif ne peut pas être désactivé.',
                    ]);
                }
            }

            $ancien = $user->replicate();
            $ancien->id = $user->id;
            $user->update(['actif' => $data['actif']]);
            $audit->utilisateur($data['actif'] ? 'utilisateur.reactive' : 'utilisateur.desactive', $ancien, $user->fresh());
        });

        return response()->json(['data' => $user->refresh()->load('role:id,nom,description')]);
    }
}
