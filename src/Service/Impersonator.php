<?php declare(strict_types=1);

namespace Scif\LaravelPretend\Service;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Session\Store;
use Scif\LaravelPretend\Event\Impersonated;
use Scif\LaravelPretend\Event\Unimprersonated;

class Impersonator
{
    /** @var  Guard $guard */
    protected $guard;

    /** @var Repository $config */
    protected $config;

    /** @var  UserProvider */
    protected $userProvider;

    /** @var Store $session */
    protected $session;

    /** @var Dispatcher $eventDispatcher */
    protected $eventDispatcher;

    /** @var  Authenticatable */
    protected $realUser;

    /** @var  Authenticatable */
    protected $impersonationUser;

    /** @var  bool */
    protected $isForbidden;

    const SESSION_NAME = 'pretend:_switch_user';

    public function __construct(AuthManager $auth, Repository $config, UserProvider $userProvider, Store $session, Dispatcher $eventDispatcher)
    {
        $this->guard           = $auth->guard();
        $this->realUser        = $this->guard->user();
        $this->config          = $config;
        $this->userProvider    = $userProvider;
        $this->session         = $session;
        $this->eventDispatcher = $eventDispatcher;
        $this->isForbidden     = false;
    }

    public function exitImpersonation()
    {
        $username = $this->session->get(static::SESSION_NAME);

        if (null === $username) {
            return;
        }

        $this->session->remove(static::SESSION_NAME);

        $user     = $this->retrieveUser($username);
        $realUser = $this->guard->user();

        $event = new Unimprersonated($realUser, $user);
        $this->eventDispatcher->fire($event);
    }

    /**
     * @throws \HttpException Throw 403 exception if cannot find user
     *
     * @param string $username
     *
     * @return Authenticatable
     */
    protected function retrieveUser(string $username): Authenticatable
    {
        $conditions = [
            $this->config->get('pretend.impersonate.user_identifier') => $username,
        ];

        $user = $this->userProvider->retrieveByCredentials($conditions);

        if (null === $user) {
            abort(403, 'Cannot find user by this credentials');
        }

        return $user;
    }

    /**
     * @throws \HttpException Throw 403 exception if you try to impersonate yourself
     *
     * @param string $username Username of user you want to enter impersonate
     */
    public function enterImpersonation(string $username)
    {
        $user     = $this->retrieveUser($username);
        $realUser = $this->guard->user();

        if ($user->getAuthIdentifier() === $realUser->getAuthIdentifier()) {
            abort(403, 'Cannot impersonate yourself');
        }

        $this->impersonationUser = $user;
        $this->guard->setUser($user);

        if (!$this->session->has(static::SESSION_NAME)) {
            $this->session->put(static::SESSION_NAME, $username);

            $event = new Impersonated($realUser, $user);
            $this->eventDispatcher->fire($event);
        }
    }

    public function isImpersonated(): bool
    {
        return $this->session->has(static::SESSION_NAME);
    }

    /**
     * @throws \HttpException Throw 403 exception if cannot find data in session
     */
    public function continueImpersonation()
    {
        $name = $this->getImpersonatingIdentifier();

        if (null === $name) {
            throw new \RuntimeException('Cannot find impersonating data in session');
        }

        $this->enterImpersonation($name);
    }

    public function getImpersonatingIdentifier(): string
    {
        return $this->session->get(static::SESSION_NAME, '');
    }
}
