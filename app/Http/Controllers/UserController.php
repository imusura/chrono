<?php

namespace App\Http\Controllers;

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
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::where('organisation_id', $request->user()->organisation_id)
            ->where('is_super_admin', false)
            ->orderBy('name')
            ->get();

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $user = User::create([
            ...$request->safe()->except('password'),
            'password' => Hash::make($request->password),
            'organisation_id' => $request->user()->organisation_id,
        ]);

        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $this->authorizeOrgAccess($request, $user);

        $data = $request->safe()->except('password');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return new UserResource($user);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorizeOrgAccess($request, $user);

        $user->delete();

        return response()->json(null, 204);
    }

    private function authorizeOrgAccess(Request $request, User $user): void
    {
        $authUser = $request->user();

        if ($authUser->is_super_admin) {
            return;
        }

        if ($user->organisation_id !== $authUser->organisation_id) {
            abort(403);
        }
    }
}
