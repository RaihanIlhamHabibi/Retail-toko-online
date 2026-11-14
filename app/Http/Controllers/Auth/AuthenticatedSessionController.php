<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        $previousUrl = url()->previous();
        $isAdminLogin = $this->isAdminUrl($request->session()->get('url.intended'))
            || $this->isAdminUrl($previousUrl);

        $request->session()->put(
            'url.intended',
            $isAdminLogin ? airoute('aimeos_shop_admin') : $previousUrl
        );

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user->can('admin', [
            \Aimeos\Shop\Controller\AdminController::class,
            config('shop.roles', ['admin', 'editor']),
        ])) {
            $request->session()->forget('url.intended');

            return redirect(airoute('aimeos_shop_admin'));
        }

        if ($this->isAdminUrl($request->session()->get('url.intended'))) {
            $request->session()->forget('url.intended');

            return redirect(airoute('aimeos_home'));
        }

        return redirect()->intended(airoute( 'aimeos_home' ));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect(airoute( 'aimeos_home' ));
    }

    private function isAdminUrl(?string $url): bool
    {
        $path = parse_url($url ?? '', PHP_URL_PATH);

        return is_string($path) && ($path === '/admin' || str_starts_with($path, '/admin/'));
    }
}
