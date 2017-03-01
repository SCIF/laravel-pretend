<?php declare(strict_types=1);

namespace Scif\LaravelPretend\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Scif\LaravelPretend\Interfaces\Impersonable;
use Scif\LaravelPretend\Service\Impersonator;

class Impersonate
{
    /** @var Gate $gate */
    protected $gate;

    /** @var Repository $config */
    protected $config;

    /** @var Impersonator $impersonator */
    protected $impersonator;

    /** @var \Illuminate\Contracts\Auth\Authenticatable|null  */
    protected $user;

    const SESSION_NAME = 'pretend:_switch_user';

    public function __construct(Guard $guard, Gate $gate, Repository $config, Impersonator $impersonator)
    {
        $this->user         = $guard->user();
        $this->gate         = $gate;
        $this->config       = $config;
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
        $name = $request->query('_switch_user', null);

        if (null !== $name) {
            $this->checkPermission($name);

            if ('_exit' === $name) {
                $this->impersonator->exitImpersonation();
            } else {
                if ($this->impersonator->isImpersonated()) {
                    abort(403, 'Cannot use impersonation once you already done that');
                }

                $this->impersonator->enterImpersonation($name);
            }
        } elseif ($this->impersonator->isImpersonated()) {
            $this->checkPermission($this->impersonator->getImpersonatingIdentifier());
            $this->impersonator->continueImpersonation();
        }

        return $next($request);
    }

    protected function checkPermission(string $username)
    {
        $ability  = $this->config->get('pretend.impersonate.auth_check');

        if (!$this->gate->has($ability)) {

            $this->gate->define($ability, function ($user): bool {
                if (!$user instanceof Impersonable) {
                    return false;
                }

                return $user->canImpersonate();
            });
        }

        if (!$this->gate->forUser($this->user)->check($ability, [ $username] )) {
            abort(403, "Current user have no ability '{$ability}'");
        }
    }
}
