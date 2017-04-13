[![Build Status](https://travis-ci.org/SCIF/laravel-pretend.svg?branch=master)](https://travis-ci.org/SCIF/laravel-pretend)

# Impersonate package for the Laravel Framework

[На русском](README_RU.md)

## What is that?

Laravel has no default impersonation wrapper for low-level methods.  This package highly inspired by [Symfony impersonation](http://symfony.com/doc/current/security/impersonating_user.html) which looks much more flexible rather than several inspected Laravel implementations.
 Package fully implement GET-parameter-driven behavior. Also, this package does not restrict you in using custom [user providers](https://laravel.com/docs/master/authentication#the-user-provider-contract) (for instance, if you use [Propel](https://github.com/propelorm/PropelLaravel)), guards and can be used with [Twig](https://github.com/rcrowe/TwigBridge) as view templater.
 Some ideas inspired by existing impersonation packages for Laravel (:+1: thanks to those authors!).
 
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
  
The latest step of installation is a configuring authorization [gate](https://laravel.com/docs/5.3/authorization#gates). Package bundled with gate called `impersonate`.
 This gate checks if your user model implements `Scif\LaravelPretend\Interfaces\Impersonable` and check method `canImpersonate(): bool`.
 
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
 
 :point_up: You can use out of box implementation of this gate or override it in your own AuthServiceProvider. 
 You can override name of gate used to check permissions in configuration as well.

## Configuration 

Configuration file can be easily copied to your project by `vendor:publish` command:
 
 ```php
 php ./artisan  vendor:publish --provider=Scif\\LaravelPretend\\LaravelPretendServiceProvider --tag=config
 ```

Configuration consist of just two options:

```php
return [
    'impersonate' => [
        'user_identifier' => 'email',
        'auth_check' => 'impersonate',
    ]
];
```

* `user_identifier` — this string will be used as name of field using to retrieve user object from user provider (method `retrieveByCredentials()`).
The default value `email` makes your impersonation urls beauty: `?_switch_user=admin@site.com` is much clear rather than `?_switch_user=43`. But it's up to you
* `auth_check` — this string is a name of [Gate](https://laravel.com/docs/5.4/authorization#gates) used to check ability of user to impersonate.
In fact the default Gate could be easily overriden in `AuthServiceProvider` of your application.

## Usage

As mentioned above, this package repeats Symfony style of using GET-parameters to manage impersonation.

Blade using is pretty straightforward:

```php
// generates link with impersonation
{{ route('home', ['_switch_user' => 'admin@site.com']) }}

// exit impersonation
    @if ($app['impersonator']->isImpersonated())
        <a href="{{ route('home', ['_switch_user' => '_exit']) }}">Exit impersonation</a>
    @else
        <a href="{{ route('logout') }}">Logout</a>
    @endif
```

And here is a simple example using in twig:

```
// generates link with impersonation
{{ route('home', {'_switch_user': 'admin@site.com'}) }}

// more advance usage
                {% if auth_user() %}
                    {% if app.impersonator.impersonated %}
                        <a class="btn btn-default" href="{{ route('home', {'_switch_user': '_exit'}) }}">Exit impersonation</a>
                    {% else %}
                        <form action="{{ route('logout') }}" method="post">
                            {{ csrf_field() }}
                            <button class="btn btn-default">Logout</button>
                        </form>
                    {% endif %}
                {% endif %}
```


## Events

On entering and exitting impersontaion this package raises events: [`Scif\LaravelPretend\Event\Impersontated`](src/Event/Impersonated.php), [`Scif\LaravelPretend\Event\Unimpersontated`](src/Event/Unimpersonated.php).
Name of events is their fully qualified class names, so simplest event listener will looks like:

```php
use Scif\LaravelPretend\Event\Impersontated;
…
    Event::listen(Impersonated::class, function (Impersonated $event) {
        //
    });
```

## Forbid impersonation

You can use bundled [`ForbidImpersonation`](src/Middleware/ForbidImpersonation.php) middleware to forbid using of impersonation for some route groups, routes or controllers. 

## PHP7? Ugh! Wtf??

Yes, PHP7 is awesome! So, if you want to use it with PHP5 — [create an issue](https://github.com/SCIF/laravel-pretend/issues) and I will think about a separate branch or other suitable solution.

## Contribution

Any type of contributions is highly appreciated. Don't be a shy — help this project become even better!
