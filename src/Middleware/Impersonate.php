<?php declare(strict_types=1);

namespace Scif\LaravelPretend\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
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

    /** @var Redirector */
    protected $redirect;

    const SESSION_NAME = 'pretend:_switch_user';

    public function __construct(
        Guard $guard,
        Gate $gate,
        Repository $config,
        Impersonator $impersonator,
        Redirector $redirect
    )
    {
        $this->user         = $guard->user();
        $this->gate         = $gate;
        $this->config       = $config;
        $this->impersonator = $impersonator;
        $this->redirect     = $redirect;
    }

    /**
     * Handle an incoming request.
     *
     * @throws \HttpException In event of double attempt to impersonate
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

            if (!$request->isXmlHttpRequest() && $request->isMethod('GET')) {
                $input = $request->input();
                unset($input['_switch_user']);
                $input += $request->route()->parameters();

                return $this->redirect->route(
                    $request->route()->getName(),
                    $input
                );
            }
        } elseif ($this->impersonator->isImpersonated()) {
            $this->checkPermission($this->impersonator->getImpersonatingIdentifier());
            $this->impersonator->continueImpersonation();
        }

        return $next($request);
    }

    /**
     * @throws \HttpException In event of lack required abilities will be throw 403 exception
     *
     * @param string $username Username of impersonable user
     */
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
