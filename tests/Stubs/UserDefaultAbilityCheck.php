<?php declare(strict_types=1);

namespace Scif\LaravelPretend\Tests\Stubs;

use Scif\LaravelPretend\Interfaces\Impersonable;

class UserDefaultAbilityCheck extends User implements Impersonable
{
    public function isAdmin(): bool
    {
        throw new \Exception('This method should not be called');
    }

    public function canImpersonate(): bool
    {
        return parent::isAdmin();
    }
}