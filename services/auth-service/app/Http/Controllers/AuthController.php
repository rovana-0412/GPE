<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PlatformSetting;
use App\Services\AuditWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Login et émission de JWT
     */
    public function login(Request $request, AuditWriter $auditWriter)
    {
        $request->merge(['email' => Str::lower((string) $request->input('email'))]);
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with('role')->where('email', $request->email)->first();

        if (!$user || ! $user->actif || ! Hash::check($request->password, $user->password)) {
            $auditWriter->authentification('authentification.echouee', $user);

            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides',
            ], 401);
        }

        $roleSlug = $user->role?->slug() ?? 'unknown';

        $payload = [
            'sub' => $user->id,
            'role' => $roleSlug,
            'role_id' => $user->role_id,
            'service_id' => $user->service_id,
            'iat' => time(),
            'exp' => time() + (2 * 60 * 60), // 2 heures
        ];

        $jwt = JWT::encode($payload, config('jwt.secret'), 'HS256');
        $auditWriter->authentification('authentification.reussie', $user);

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'token' => $jwt,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_slug' => $roleSlug,
                    'role_id' => $user->role_id,
                    'service_id' => $user->service_id,
                ],
                'settings' => PlatformSetting::query()->pluck('valeur', 'cle')->all(),
            ],
        ]);
    }

    /**
     * Récupérer l'utilisateur connecté
     */
    public function me(Request $request)
    {
        $user = $request->user;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'service_id' => $user->service_id,
                'actif' => $user->actif,
            ],
        ]);
    }
}
