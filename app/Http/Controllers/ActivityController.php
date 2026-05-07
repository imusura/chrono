<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOrganisation;
use App\Http\Requests\Activity\StoreActivityRequest;
use App\Http\Requests\Activity\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityController extends Controller
{
    use ResolvesOrganisation;
    public function index(Request $request): AnonymousResourceCollection
    {
        $orgId = $this->resolveOrgId($request);

        $activities = Activity::where('organisation_id', $orgId)
            ->with('roles')
            ->orderBy('name')
            ->get();

        return ActivityResource::collection($activities);
    }

    public function store(StoreActivityRequest $request): ActivityResource
    {
        $orgId = $this->resolveOrgId($request);

        $activity = Activity::create([
            ...$request->safe()->except(['organisation_id', 'role_ids']),
            'organisation_id' => $orgId,
        ]);

        if ($request->filled('role_ids')) {
            $activity->roles()->sync($request->role_ids);
        }

        return new ActivityResource($activity->load('roles'));
    }

    public function update(UpdateActivityRequest $request, Activity $activity): ActivityResource
    {
        $this->authorizeOrgAccess($request, $activity->organisation_id);

        $activity->update($request->safe()->except('role_ids'));

        $activity->roles()->sync($request->input('role_ids', []));

        return new ActivityResource($activity->load('roles'));
    }

    public function destroy(Request $request, Activity $activity): JsonResponse
    {
        $this->authorizeOrgAccess($request, $activity->organisation_id);

        $activity->delete();

        return response()->json(null, 204);
    }

}
