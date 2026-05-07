<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOrganisation;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleController extends Controller
{
    use ResolvesOrganisation;
    public function index(Request $request): AnonymousResourceCollection
    {
        $orgId = $this->resolveOrgId($request);

        $roles = Role::where('organisation_id', $orgId)
            ->with('activities')
            ->orderBy('name')
            ->get();

        return RoleResource::collection($roles);
    }

    public function store(StoreRoleRequest $request): RoleResource
    {
        $orgId = $this->resolveOrgId($request);

        $role = Role::create([
            ...$request->safe()->except('organisation_id'),
            'organisation_id' => $orgId,
        ]);

        return new RoleResource($role);
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $this->authorizeOrgAccess($request, $role->organisation_id);

        $role->update($request->validated());

        return new RoleResource($role);
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        $this->authorizeOrgAccess($request, $role->organisation_id);

        $role->delete();

        return response()->json(null, 204);
    }

}
