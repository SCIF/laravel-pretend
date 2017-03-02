<?php declare(strict_types=1);

namespace Scif\LaravelPretend\Middleware;

use Closure;
use Illuminate\Http\Request;
use Scif\LaravelPretend\Service\Impersonator;

class ForbidImpersonation
{
    /** @var Impersonator $impersonator */
    protected $impersonator;

    public function __construct(Impersonator $impersonator)
    {
        $this->impersonator = $impersonator;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->impersonator->forbidImpersonation();

        return $next($request);
    }
}
