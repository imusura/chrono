<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserActivityController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $roleIds = $user->roles()->pluck('roles.id');

        $activities = \App\Models\Activity::where('organisation_id', $user->organisation_id)
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('roles.id', $roleIds))
            ->orderBy('name')
            ->get();

        return ActivityResource::collection($activities);
    }
}
