<?php

namespace App\Http\Controllers;

use App\Services\MicroserviceClient;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request, MicroserviceClient $client): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role_id' => ['nullable', 'integer', 'min:1'],
            'actif' => ['nullable', 'in:0,1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $response = $client->request('auth', 'GET', '/api/admin/users', [
            'query' => $filters + ['per_page' => 15],
        ]);
        abort_unless($response->successful(), $response->status());
        $roles = $client->request('auth', 'GET', '/api/admin/roles');
        abort_unless($roles->successful(), $roles->status());

        return view('administration.users.index', [
            'users' => $response->json('data', []),
            'pagination' => $response->json(),
            'roles' => $roles->json('data', []),
            'filters' => $filters,
        ]);
    }

    public function create(MicroserviceClient $client): View
    {
        return $this->formView($client, [], 'creation');
    }

    public function store(Request $request, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate($this->rules(true));
        $response = $client->request('auth', 'POST', '/api/admin/users', ['json' => $data]);

        if (! $response->created()) {
            return $this->apiError($response, 'Le compte n’a pas pu être créé.');
        }

        return redirect()->route('utilisateurs.index')->with('success', 'Le compte a été créé.');
    }

    public function edit(string $id, MicroserviceClient $client): View
    {
        $response = $client->request('auth', 'GET', '/api/admin/users/'.$id);
        abort_unless($response->successful(), $response->status());

        return $this->formView($client, $response->json('data', []), 'modification');
    }

    public function update(Request $request, string $id, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate($this->rules(false));

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $response = $client->request('auth', 'PUT', '/api/admin/users/'.$id, ['json' => $data]);

        if (! $response->successful()) {
            return $this->apiError($response, 'Le compte n’a pas pu être modifié.');
        }

        return redirect()->route('utilisateurs.index')->with('success', 'Le compte a été mis à jour.');
    }

    public function changeStatus(Request $request, string $id, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate(['actif' => ['required', 'boolean']]);
        $response = $client->request('auth', 'PATCH', '/api/admin/users/'.$id.'/status', ['json' => $data]);

        if (! $response->successful()) {
            return $this->apiError($response, 'Le statut du compte n’a pas pu être modifié.');
        }

        return redirect()->route('utilisateurs.index')->with('success', 'Le statut du compte a été mis à jour.');
    }

    private function formView(MicroserviceClient $client, array $user, string $mode): View
    {
        $roles = $client->request('auth', 'GET', '/api/admin/roles');
        abort_unless($roles->successful(), $roles->status());
        $services = $client->request('patrimoine', 'GET', '/api/services', [
            'query' => ['per_page' => 100],
        ]);
        abort_unless($services->successful(), $services->status());

        return view('administration.users.form', [
            'user' => $user,
            'roles' => $roles->json('data', []),
            'services' => $services->json('data', []),
            'mode' => $mode,
        ]);
    }

    private function rules(bool $creating): array
    {
        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'email' => [$creating ? 'required' : 'sometimes', 'email', 'max:255'],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:12', 'max:255'],
            'role_id' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1'],
            'service_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    private function apiError(Response $response, string $fallback): RedirectResponse
    {
        $errors = $response->json('errors', []);

        if (is_array($errors) && $errors !== []) {
            return back()->withErrors($errors)->withInput();
        }

        return back()->withErrors([
            'service' => $response->json('message', $fallback),
        ])->withInput();
    }
}
