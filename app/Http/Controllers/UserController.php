<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOrganisation;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ResolvesOrganisation;
    public function index(Request $request): AnonymousResourceCollection
    {
        $orgId = $this->resolveOrgIdNullable($request);

        $users = User::where('organisation_id', $orgId)
            ->where('is_super_admin', false)
            ->with('roles')
            ->orderBy('name')
            ->get();

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $orgId = $this->resolveOrgIdNullable($request);

        $user = User::create([
            ...$request->safe()->except(['password', 'organisation_id', 'role_ids']),
            'password' => Hash::make($request->password),
            'organisation_id' => $orgId,
        ]);

        $user->roles()->sync($request->input('role_ids', []));

        return new UserResource($user->load('roles'));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $this->authorizeUserAccess($request, $user);

        $data = $request->safe()->except(['password', 'role_ids']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->roles()->sync($request->input('role_ids', []));

        return new UserResource($user->load('roles'));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorizeUserAccess($request, $user);

        $user->delete();

        return response()->json(null, 204);
    }

    private function authorizeUserAccess(Request $request, User $user): void
    {
        if ($request->user()->is_super_admin) {
            return;
        }

        if ((int) $user->organisation_id !== (int) $request->user()->organisation_id) {
            abort(403);
        }
    }
}
