<?php declare(strict_types=1);

namespace Scif\LaravelPretend\Tests\Stubs;

use Illuminate\Contracts\Auth\Authenticatable;

class User implements Authenticatable
{
    use \Illuminate\Auth\Authenticatable;

    public $id;
    public $password;
    public $email;
    protected $isAdmin;

    public function __construct(int $id, bool $isAdmin)
    {
        $this->id = $id;
        $this->password = "password{$id}";
        $this->email = "email{$id}@domain.tld";
        $this->isAdmin = $isAdmin;
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    public function getKey(): int
    {
        return $this->{$this->getKeyName()};
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

}