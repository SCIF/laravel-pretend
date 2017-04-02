<?php declare(strict_types=1);

namespace Scif\LaravelPretend\Middleware;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Scif\LaravelPretend\Event\Impersonated;
use Scif\LaravelPretend\Service\Impersonator;

class ForbidImpersonation
{
    /** @var Impersonator $impersonator */
    protected $impersonator;

    /** @var Dispatcher $eventDispatcher */
    protected $eventDispatcher;

    public function __construct(Impersonator $impersonator, Dispatcher $eventDispatcher)
    {
        $this->impersonator = $impersonator;
        $this->eventDispatcher = $eventDispatcher;
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
        $this->eventDispatcher->listen(Impersonated::class, function () {
            abort(403, 'This route is forbidden to access as impersonated user');
        });

        return $next($request);
    }
}
