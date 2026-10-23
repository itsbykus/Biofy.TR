<?php

namespace App\Http\Middleware;

use App\Services\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorForStaff
{
    public function __construct(private readonly Settings $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $this->settings->bool('security.admin_require_2fa') && ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('security.index')->with('warning', __('security.staff_2fa_required'));
        }

        return $next($request);
    }
}
