<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ResolvesOrganisation
{
    protected function resolveOrgId(Request $request): int
    {
        return $request->user()->is_super_admin
            ? $request->integer('organisation_id')
            : (int) $request->user()->organisation_id;
    }

    protected function resolveOrgIdNullable(Request $request): ?int
    {
        return $request->user()->is_super_admin
            ? $request->integer('organisation_id') ?: null
            : (int) $request->user()->organisation_id;
    }

    protected function authorizeOrgAccess(Request $request, int $orgId): void
    {
        if ($request->user()->is_super_admin) {
            return;
        }

        if ($orgId !== (int) $request->user()->organisation_id) {
            abort(403);
        }
    }
}
