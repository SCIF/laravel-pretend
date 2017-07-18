<?php declare(strict_types=1);

namespace Scif\LaravelPretend\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForbidImpersonation
{
    /**
     * Handle an incoming request.
     * @codeCoverageIgnore
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
