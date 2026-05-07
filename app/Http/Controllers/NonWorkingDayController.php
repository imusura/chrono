<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOrganisation;
use App\Http\Requests\NonWorkingDay\StoreNonWorkingDayRequest;
use App\Http\Requests\NonWorkingDay\UpdateNonWorkingDayRequest;
use App\Http\Resources\NonWorkingDayResource;
use App\Models\NonWorkingDay;
use App\Models\Organisation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Http;

class NonWorkingDayController extends Controller
{
    use ResolvesOrganisation;

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $orgId = $this->resolveOrgIdNullable($request);
        $year = $request->integer('year');

        if (! $orgId) {
            return NonWorkingDayResource::collection(collect());
        }

        $org = Organisation::findOrFail($orgId);

        $days = NonWorkingDay::whereYear('date', $year)
            ->where(fn ($q) => $q
                ->where(fn ($q2) => $q2->whereNull('organisation_id')->where('country_code', $org->country_code))
                ->orWhere('organisation_id', $orgId)
            )
            ->orderBy('date')
            ->get();

        return NonWorkingDayResource::collection($days);
    }

    public function store(StoreNonWorkingDayRequest $request): NonWorkingDayResource
    {
        $orgId = $this->resolveOrgId($request);

        $day = NonWorkingDay::create([
            ...$request->validated(),
            'organisation_id' => $orgId,
        ]);

        return new NonWorkingDayResource($day);
    }

    public function update(UpdateNonWorkingDayRequest $request, NonWorkingDay $nonWorkingDay): NonWorkingDayResource
    {
        $this->authorizeNonPublic($request, $nonWorkingDay->organisation_id);

        $nonWorkingDay->update($request->validated());

        return new NonWorkingDayResource($nonWorkingDay);
    }

    public function destroy(Request $request, NonWorkingDay $nonWorkingDay): JsonResponse
    {
        $this->authorizeNonPublic($request, $nonWorkingDay->organisation_id);

        $nonWorkingDay->delete();

        return response()->json(null, 204);
    }

    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $orgId = $this->resolveOrgIdNullable($request);
        $year = $request->integer('year');

        if (! $orgId) {
            return response()->json(['message' => 'Organisation required.'], 422);
        }

        $org = Organisation::findOrFail($orgId);
        $countryCode = $org->country_code;

        $response = Http::get("https://date.nager.at/api/v3/PublicHolidays/{$year}/{$countryCode}");

        if ($response->failed()) {
            return response()->json(['message' => "Failed to fetch holidays: HTTP {$response->status()}"], 502);
        }

        $synced = 0;
        foreach ($response->json() as $holiday) {
            NonWorkingDay::updateOrCreate(
                ['organisation_id' => null, 'country_code' => $countryCode, 'date' => $holiday['date']],
                ['name' => $holiday['localName']],
            );
            $synced++;
        }

        return response()->json(['synced' => $synced]);
    }

    private function authorizeNonPublic(Request $request, ?int $orgId): void
    {
        if ($orgId === null) {
            abort(403, 'Public holidays cannot be modified.');
        }

        $this->authorizeOrgAccess($request, $orgId);
    }
}
