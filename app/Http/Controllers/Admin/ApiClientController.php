<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreApiClientRequest;
use App\Http\Requests\Admin\UpdateApiClientRequest;
use App\Http\Resources\ApiClientResource;
use App\Http\Resources\ApiClientWithTokenResource;
use App\Models\ApiClient;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiClientController extends Controller
{
    public function index(Project $project): AnonymousResourceCollection
    {
        $clients = $project->apiClients()
            ->with('defaultTicketType')
            ->orderByDesc('created_at')
            ->get();

        return ApiClientResource::collection($clients);
    }

    public function store(StoreApiClientRequest $request, Project $project): JsonResponse
    {
        $token = ApiClient::generateToken();

        $client = $project->apiClients()->create([
            'name' => $request->validated('name'),
            'default_ticket_type_id' => $request->validated('default_ticket_type_id'),
            'token_hash' => $token['hash'],
            'is_active' => true,
        ]);

        $client->load('defaultTicketType');

        return (new ApiClientWithTokenResource($client, $token['plain']))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateApiClientRequest $request, Project $project, ApiClient $apiClient): ApiClientResource
    {
        abort_unless($apiClient->project_id === $project->id, 404);

        $apiClient->update($request->validated());
        $apiClient->load('defaultTicketType');

        return new ApiClientResource($apiClient);
    }

    public function rotate(Project $project, ApiClient $apiClient): ApiClientWithTokenResource
    {
        abort_unless($apiClient->project_id === $project->id, 404);

        $token = ApiClient::generateToken();
        $apiClient->update(['token_hash' => $token['hash']]);
        $apiClient->load('defaultTicketType');

        return new ApiClientWithTokenResource($apiClient, $token['plain']);
    }

    public function destroy(Project $project, ApiClient $apiClient): JsonResponse
    {
        abort_unless($apiClient->project_id === $project->id, 404);

        $apiClient->delete();

        return response()->json(['message' => 'API client deleted.']);
    }
}
