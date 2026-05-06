<?php

namespace App\Http\Controllers;

use App\Http\Requests\Organisation\StoreOrganisationRequest;
use App\Http\Requests\Organisation\UpdateOrganisationRequest;
use App\Http\Resources\OrganisationResource;
use App\Models\Organisation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganisationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return OrganisationResource::collection(Organisation::orderBy('name')->get());
    }

    public function store(StoreOrganisationRequest $request): OrganisationResource
    {
        return new OrganisationResource(Organisation::create($request->validated()));
    }

    public function update(UpdateOrganisationRequest $request, Organisation $organisation): OrganisationResource
    {
        $organisation->update($request->validated());

        return new OrganisationResource($organisation);
    }

    public function destroy(Organisation $organisation): JsonResponse
    {
        $organisation->delete();

        return response()->json(null, 204);
    }
}
