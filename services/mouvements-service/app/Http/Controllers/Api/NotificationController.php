<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationMetier;
use App\Services\NotificationWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    public function store(Request $request, NotificationWriter $notifications): JsonResponse
    {
        $data = $request->validate([
            'utilisateur_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', 'max:80'],
            'titre' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:2000'],
            'donnees' => ['nullable', 'array'],
        ]);
        $notification = $notifications->envoyer(
            (int) $data['utilisateur_id'],
            $data['type'],
            $data['titre'],
            $data['message'],
            $data['donnees'] ?? [],
        );

        return response()->json(['data' => $notification], Response::HTTP_CREATED);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'non_lues' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $userId = $this->authenticatedUserId($request);
        $notifications = NotificationMetier::query()
            ->where('utilisateur_id', $userId)
            ->when(
                filter_var($filters['non_lues'] ?? false, FILTER_VALIDATE_BOOLEAN),
                fn ($query) => $query->whereNull('lue_le'),
            )
            ->latest('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return response()->json($notifications);
    }

    public function markAsRead(Request $request, int $notification): JsonResponse
    {
        $userId = $this->authenticatedUserId($request);
        $notification = NotificationMetier::query()
            ->where('utilisateur_id', $userId)
            ->findOrFail($notification);

        if ($notification->lue_le === null) {
            $notification->lue_le = now();
            $notification->save();
        }

        return response()->json(['data' => $notification], Response::HTTP_OK);
    }

    private function authenticatedUserId(Request $request): int
    {
        $userId = $request->attributes->get('jwt_payload')['sub'] ?? null;
        abort_unless(is_numeric($userId) && (int) $userId > 0, Response::HTTP_UNAUTHORIZED);

        return (int) $userId;
    }
}
