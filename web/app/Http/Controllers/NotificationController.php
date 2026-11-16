<?php

namespace App\Http\Controllers;

use App\Services\MicroserviceClient;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, MicroserviceClient $client): View
    {
        $filters = $request->validate([
            'non_lues' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $response = $client->request('mouvements', 'GET', '/api/notifications', [
            'query' => $filters + ['per_page' => 20],
        ]);
        abort_unless($response->successful(), $response->status());

        return view('notifications.index', [
            'notifications' => $response->json('data', []),
            'pagination' => $response->json(),
            'filters' => $filters,
        ]);
    }

    public function markAsRead(string $id, MicroserviceClient $client): RedirectResponse
    {
        $response = $client->request('mouvements', 'PATCH', '/api/notifications/'.$id.'/lu');

        if (! $response->successful()) {
            return $this->apiError($response);
        }

        return back()->with('success', 'La notification a été marquée comme lue.');
    }

    private function apiError(Response $response): RedirectResponse
    {
        return back()->withErrors([
            'service' => $response->json('message', 'La notification n’a pas pu être mise à jour.'),
        ]);
    }
}
