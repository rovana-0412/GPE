<?php

namespace App\Http\Controllers;

use App\Services\MicroserviceClient;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferenceController extends Controller
{
    private const RESOURCES = [
        'categories' => [
            'title' => 'Catégories',
            'singular' => 'catégorie',
            'columns' => [
                ['key' => 'nom', 'label' => 'Nom'],
                ['key' => 'type', 'label' => 'Type'],
                ['key' => 'biens_count', 'label' => 'Biens liés'],
            ],
            'fields' => [
                ['name' => 'nom', 'label' => 'Nom', 'type' => 'text', 'required' => true],
                ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'options' => ['immobilier' => 'Immobilier', 'mobilier' => 'Mobilier']],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'actif', 'label' => 'Catégorie active', 'type' => 'checkbox'],
            ],
        ],
        'ministeres' => [
            'title' => 'Ministères',
            'singular' => 'ministère',
            'columns' => [
                ['key' => 'nom', 'label' => 'Nom'],
                ['key' => 'sigle', 'label' => 'Sigle'],
                ['key' => 'services_count', 'label' => 'Services'],
            ],
            'fields' => [
                ['name' => 'nom', 'label' => 'Nom', 'type' => 'text', 'required' => true],
                ['name' => 'sigle', 'label' => 'Sigle', 'type' => 'text'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'adresse', 'label' => 'Adresse', 'type' => 'text'],
                ['name' => 'telephone', 'label' => 'Téléphone', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Adresse e-mail', 'type' => 'email'],
                ['name' => 'actif', 'label' => 'Ministère actif', 'type' => 'checkbox'],
            ],
        ],
        'services' => [
            'title' => 'Services',
            'singular' => 'service',
            'columns' => [
                ['key' => 'nom', 'label' => 'Nom'],
                ['key' => 'sigle', 'label' => 'Sigle'],
                ['key' => 'ministere.nom', 'label' => 'Ministère'],
                ['key' => 'biens_count', 'label' => 'Biens liés'],
            ],
            'fields' => [
                ['name' => 'ministere_id', 'label' => 'Ministère', 'type' => 'ministere', 'required' => true],
                ['name' => 'nom', 'label' => 'Nom', 'type' => 'text', 'required' => true],
                ['name' => 'sigle', 'label' => 'Sigle', 'type' => 'text'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'adresse', 'label' => 'Adresse', 'type' => 'text'],
                ['name' => 'telephone', 'label' => 'Téléphone', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Adresse e-mail', 'type' => 'email'],
                ['name' => 'actif', 'label' => 'Service actif', 'type' => 'checkbox'],
            ],
        ],
    ];

    public function index(Request $request, MicroserviceClient $client): View
    {
        $resource = $this->definition($request->route('resource'));
        $response = $client->request('patrimoine', 'GET', '/api/'.$request->route('resource'), [
            'query' => ['page' => $request->integer('page', 1), 'per_page' => 15],
        ])->json();

        return view('patrimoine.references.index', [
            'resource' => $request->route('resource'),
            'definition' => $resource,
            'items' => $response['data'] ?? [],
            'pagination' => $response,
        ]);
    }

    public function create(Request $request, MicroserviceClient $client): View
    {
        return $this->formView($request->route('resource'), [], $client, 'creation');
    }

    public function store(Request $request, MicroserviceClient $client): RedirectResponse
    {
        $resource = $request->route('resource');
        $this->definition($resource);
        $data = $request->validate($this->rules($resource));
        $response = $client->request('patrimoine', 'POST', '/api/'.$resource, ['json' => $data]);

        if (! $response->created()) {
            return $this->apiValidationError($response);
        }

        return redirect()->route($resource.'.index')->with('success', 'L’enregistrement a été créé.');
    }

    public function edit(Request $request, string $id, MicroserviceClient $client): View
    {
        $resource = $request->route('resource');
        $response = $client->request('patrimoine', 'GET', '/api/'.$resource.'/'.$id);

        abort_unless($response->successful(), $response->notFound() ? 404 : 500);

        return $this->formView($resource, $response->json('data', []), $client, 'modification');
    }

    public function update(Request $request, string $id, MicroserviceClient $client): RedirectResponse
    {
        $resource = $request->route('resource');
        $this->definition($resource);
        $data = $request->validate($this->rules($resource));
        $response = $client->request('patrimoine', 'PUT', '/api/'.$resource.'/'.$id, ['json' => $data]);

        if (! $response->successful()) {
            return $this->apiValidationError($response);
        }

        return redirect()->route($resource.'.index')->with('success', 'L’enregistrement a été mis à jour.');
    }

    public function destroy(Request $request, string $id, MicroserviceClient $client): RedirectResponse
    {
        $resource = $request->route('resource');
        $this->definition($resource);
        $response = $client->request('patrimoine', 'DELETE', '/api/'.$resource.'/'.$id);

        if (! $response->noContent()) {
            return $this->apiValidationError($response, 'La suppression est impossible.');
        }

        return redirect()->route($resource.'.index')->with('success', 'L’enregistrement a été supprimé.');
    }

    private function formView(string $resource, array $item, MicroserviceClient $client, string $mode): View
    {
        $definition = $this->definition($resource);
        $ministeres = [];

        if ($resource === 'services') {
            $ministeres = $client->request('patrimoine', 'GET', '/api/ministeres', [
                'query' => ['per_page' => 100],
            ])->json('data', []);
        }

        return view('patrimoine.references.form', compact('resource', 'definition', 'item', 'ministeres', 'mode'));
    }

    private function definition(?string $resource): array
    {
        abort_unless(is_string($resource) && isset(self::RESOURCES[$resource]), 404);

        return self::RESOURCES[$resource];
    }

    private function rules(string $resource): array
    {
        $rules = [
            'categories' => [
                'nom' => ['required', 'string', 'max:100'],
                'type' => ['required', 'in:immobilier,mobilier'],
                'description' => ['nullable', 'string'],
                'actif' => ['required', 'boolean'],
            ],
            'ministeres' => [
                'nom' => ['required', 'string', 'max:150'],
                'sigle' => ['nullable', 'string', 'max:30'],
                'description' => ['nullable', 'string'],
                'adresse' => ['nullable', 'string', 'max:255'],
                'telephone' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:255'],
                'actif' => ['required', 'boolean'],
            ],
            'services' => [
                'ministere_id' => ['required', 'integer'],
                'nom' => ['required', 'string', 'max:150'],
                'sigle' => ['nullable', 'string', 'max:30'],
                'description' => ['nullable', 'string'],
                'adresse' => ['nullable', 'string', 'max:255'],
                'telephone' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:255'],
                'actif' => ['required', 'boolean'],
            ],
        ];

        return $rules[$resource];
    }

    private function apiValidationError(Response $response, string $fallback = 'Les données saisies sont invalides.'): RedirectResponse
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
