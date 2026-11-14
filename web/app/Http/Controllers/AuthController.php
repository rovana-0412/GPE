<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /**
     * Afficher le formulaire de connexion.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Vérifier les identifiants de connexion via auth-service.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Appeler l'API auth-service
        $response = Http::post(config('services.auth.url') . '/api/login', [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ]);

        if ($response->successful() && $response->json('success')) {
            $data = $response->json('data');

            // Stocker le JWT et les infos utilisateur en session
            session([
                'jwt_token' => $data['token'],
                'user' => $data['user'],
            ]);

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Les identifiants fournis sont incorrects.',
        ])->onlyInput('email');
    }

    /**
     * Déconnecter l'utilisateur.
     */
    public function logout(Request $request)
    {
        // Supprimer le JWT et les infos utilisateur de la session
        session()->forget(['jwt_token', 'user']);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
