<?php

namespace App\Http\Controllers;

use App\Services\MicroserviceClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSettingsController extends Controller
{
    public function index(MicroserviceClient $client): View
    {
        $response = $client->request('auth', 'GET', '/api/admin/settings');
        abort_unless($response->successful(), $response->status());

        return view('administration.parametres.index', [
            'settings' => array_replace(
                config('platform.defaults', []),
                $response->json('data', []),
            ),
        ]);
    }

    public function update(Request $request, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate([
            'nom_plateforme' => ['required', 'string', 'min:2', 'max:80'],
            'nom_institution' => ['required', 'string', 'min:2', 'max:120'],
            'contact_support' => ['nullable', 'email', 'max:255'],
        ]);
        if ($data['contact_support'] === '') {
            $data['contact_support'] = null;
        }

        $response = $client->request('auth', 'PUT', '/api/admin/settings', ['json' => $data]);
        if (! $response->successful()) {
            $errors = $response->json('errors', []);
            if (is_array($errors) && $errors !== []) {
                return back()->withErrors($errors)->withInput();
            }

            return back()->withErrors([
                'service' => $response->json('message', 'Les paramètres n’ont pas pu être enregistrés.'),
            ])->withInput();
        }

        $settings = $response->json('data');
        if (! is_array($settings)) {
            abort(502, 'La réponse des paramètres est invalide.');
        }

        session(['platform_settings' => $settings]);

        return redirect()->route('administration.parametres')
            ->with('success', 'Les paramètres de la plateforme ont été enregistrés.');
    }
}
