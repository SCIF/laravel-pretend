<?php declare(strict_types=1);

namespace Scif\LaravelPretend\Event;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Queue\SerializesModels;

class Impersonated
{
    use SerializesModels;

    /** @var  Authenticatable */
    protected $realUser;

    /** @var  Authenticatable */
    protected $impersonationUser;

    public function __construct(Authenticatable $realUser, Authenticatable $impersonationUser)
    {
        $this->realUser          = $realUser;
        $this->impersonationUser = $impersonationUser;
    }

    public function getRealUser(): Authenticatable
    {
        return $this->realUser;
    }

    public function getImpersonationUser(): Authenticatable
    {
        return $this->impersonationUser;
    }

}