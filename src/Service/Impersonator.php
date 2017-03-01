<?php declare(strict_types=1);

namespace Scif\LaravelPretend\Service;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Session\SessionInterface;

class Impersonator
{
    /** @var  Guard $guard */
    protected $guard;

    /** @var Repository $config */
    protected $config;

    /** @var  UserProvider */
    protected $userProvider;

    /** @var SessionInterface $session */
    protected $session;

    const SESSION_NAME = 'pretend:_switch_user';

    public function __construct(AuthManager $auth, Repository $config, UserProvider $userProvider, SessionInterface $session)
    {
        $this->guard        = $auth->guard();
        $this->config       = $config;
        $this->userProvider = $userProvider;
        $this->session      = $session;
    }

    public function exitImpersonation()
    {
        $this->session->remove(static::SESSION_NAME);
    }

    public function enterImpersonation(string $username)
    {
        $conditions = [
            $this->config->get('pretend.impersonate.user_identifier') => $username,
        ];

        $user = $this->userProvider->retrieveByCredentials($conditions);

        if (null === $user) {
            abort(403, 'Cannot find user by this credentials');
        }

        $this->guard->setUser($user);

        $this->session->set(static::SESSION_NAME, $username);
    }

    public function isImpersonated(): bool
    {
        return $this->session->has(static::SESSION_NAME);
    }

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
