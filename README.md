# Impersonate package for the Laravel Framework

## What is that?

Laravel has no default impersonation wrapper for low-level methods.  This package highly inspired by [Symfony impersonation](http://symfony.com/doc/current/security/impersonating_user.html) which looks much more flexible rather than several inspected Laravel implementations.
 Package fully implement GET-parameter-driven behavior. Also, this package does not restrict you in using custom user providers, guards and can be used with Twig as view templater. Some ideas inspired by existing impersonation packages for Laravel.
 
## Installation
 
 ```
 composer require scif/laravel-pretend
 ```

 Add service provider to `config/app.php` after Laravel service providers but before your own:
 
 ```php
     'providers' => [
        …
         Scif\LaravelPretend\LaravelPretendServiceProvider::class,
         /*
          * Application Service Providers...
          */
          …
      ]
 ```
 
 Add middleware handling impersonation:
  * to your `Kernel` class:
    ```php
    protected $middlewareGroups = [
        'web' => [
            …
            Scif\LaravelPretend\Middleware\Impersonate::class,
        ],
    ```
    This way is most common and covers all cases I can assume.
  * or by [any suitable methods](https://laravel.com/docs/5.4/middleware#registering-middleware) for some especial cases.
  
The latest step of installation is configuring authorization gate. Package bundled with gate called `impersonate`.
 This gate checks if your user model implements `Scif\LaravelPretend\Interfaces\Impersonable` and check method `canImpersonat(): bool`.
 
 So your model can looks like:
```php
class User extends Authenticatable implements Impersonable
{
…

    public function canImpersonate(): bool
    {
        return $this->isAdmin();
    }
}
``` 

## Configuration 

Configuration consist of just two options:

```php
return [
    'impersonate' => [
        'user_identifier' => 'email',
        'auth_check' => 'impersonate',
    ]
];
```

* `user_identifier` — this string will be used as name of field using to retrieve user object from user provider. The default value `email` makes your impersonation urls beauty: `?_switch_user=admin@site.com` is much clear rather than `?_switch_user=43`. But it's up to you
* `auth_check` — this string is a name of [Gate](https://laravel.com/docs/5.4/authorization#gates) used to check ability of user to impersonate.
In fact the default Gate could be easily overriden in `AuthServiceProvider` of your application.

## PHP7? Ugh! Wtf??

Yes, PHP7 is awesome! So, if you want to use it with PHP5 — [create issue](https://github.com/SCIF/laravel-pretend/issues) and I will create separate branch.

## TODO:

* describe using in templater
* create deny impersonation middleware