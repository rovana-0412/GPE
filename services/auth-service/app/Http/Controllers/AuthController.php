<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends Controller
{
    /**
     * Login et émission de JWT
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides',
            ], 401);
        }

        $roleSlug = $user->role ? strtolower(str_replace(' ', '-', $user->role->nom)) : 'unknown';

        $payload = [
            'sub' => $user->id,
            'role' => $roleSlug,
            'role_id' => $user->role_id,
            'service_id' => $user->service_id,
            'iat' => time(),
            'exp' => time() + (2 * 60 * 60), // 2 heures
        ];

        $jwt = JWT::encode($payload, config('jwt.secret'), 'HS256');

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
            ],
        ]);
    }
}
