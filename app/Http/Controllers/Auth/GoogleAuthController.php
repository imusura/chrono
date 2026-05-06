<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect('/login?error=google');
        }

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        $isNewUser = false;

        if ($user === null) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: $googleUser->getEmail(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'password' => Str::random(64),
                'can_create_projects' => true,
            ]);
            $isNewUser = true;
        } else {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $user->avatar_url ?: $googleUser->getAvatar(),
            ])->save();
        }

        if ($isNewUser) {
            event(new Registered($user));
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect('/');
    }
}
