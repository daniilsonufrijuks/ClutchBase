<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->display_name ?: $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'premium' => $user->hasPremium(),
                    'currency' => $user->currency,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'share_url' => fn () => $request->session()->get('share_url'),
            ],
            'appName' => config('clutchbase.name'),
        ];
    }
}
