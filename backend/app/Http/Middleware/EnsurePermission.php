<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        $hasAny = $user && collect($permissions)->contains(fn (string $permission) => $user->hasPermission($permission));

        abort_unless(
            $user && ($user->hasRole(Role::SUPER_ADMIN) || $hasAny),
            403,
            'Anda tidak memiliki izin untuk mengakses sumber daya ini.',
        );

        return $next($request);
    }
}
