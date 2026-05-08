<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOrganisation;
use App\Http\Requests\Invitation\AcceptInvitationRequest;
use App\Http\Requests\Invitation\StoreInvitationRequest;
use App\Http\Resources\UserResource;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    use ResolvesOrganisation;

    public function index(Request $request): JsonResponse
    {
        $orgId = $this->resolveOrgId($request);

        $invitations = Invitation::where('organisation_id', $orgId)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->get(['id', 'email', 'role_ids', 'contracted_hours', 'is_admin', 'expires_at']);

        return response()->json(['data' => $invitations]);
    }

    public function destroy(Request $request, Invitation $invitation): JsonResponse
    {
        $this->authorizeOrgAccess($request, $invitation->organisation_id);

        $invitation->delete();

        return response()->json(null, 204);
    }

    public function store(StoreInvitationRequest $request): JsonResponse
    {
        $orgId = $this->resolveOrgId($request);
        $shared = [
            'organisation_id' => $orgId,
            'invited_by' => $request->user()->id,
            'role_ids' => $request->input('role_ids', []),
            'contracted_hours' => $request->contracted_hours,
            'is_admin' => $request->boolean('is_admin'),
            'expires_at' => now()->addDays(7),
        ];

        $sent = [];
        $skipped = [];

        foreach ($request->emails as $email) {
            $alreadyUser = User::where('email', $email)
                ->where('organisation_id', $orgId)
                ->exists();

            if ($alreadyUser) {
                $skipped[] = $email;
                continue;
            }

            Invitation::updateOrCreate(
                ['organisation_id' => $orgId, 'email' => $email],
                [...$shared, 'token' => Str::random(64), 'accepted_at' => null],
            );

            $invitation = Invitation::where('organisation_id', $orgId)->where('email', $email)->first();
            Mail::to($email)->send(new InvitationMail($invitation));
            $sent[] = $email;
        }

        return response()->json(['sent' => $sent, 'skipped' => $skipped]);
    }

    public function show(string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            abort(410, 'This invitation has expired or has already been used.');
        }

        return response()->json([
            'email' => $invitation->email,
        ]);
    }

    public function accept(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (! $invitation->isPending()) {
            abort(410, 'This invitation has expired or has already been used.');
        }

        $user = DB::transaction(function () use ($invitation, $request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $invitation->email,
                'password' => Hash::make($request->password),
                'organisation_id' => $invitation->organisation_id,
                'contracted_hours' => $invitation->contracted_hours,
                'is_admin' => $invitation->is_admin,
            ]);

            $user->roles()->sync($invitation->role_ids);
            $invitation->update(['accepted_at' => now()]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(new UserResource($user->loadMissing('organisation')));
    }
}
