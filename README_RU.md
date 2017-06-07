[![Coverage Status](https://coveralls.io/repos/github/SCIF/laravel-pretend/badge.svg?branch=master)](https://coveralls.io/github/SCIF/laravel-pretend?branch=master)
[![Тесты](https://travis-ci.org/SCIF/laravel-pretend.svg?branch=master)](https://travis-ci.org/SCIF/laravel-pretend)

# Имперсонализация для Laravel Framework

[In english](README.md)

## Что это?

Laravel не имеет обёртки над низкоувровневыми методами имперсонализации. Этот пакет был разработан под сильным впечатлением от [имперсонализации Symfony](http://symfony.com/doc/current/security/impersonating_user.html), которая является намного более гибкой, чем несколько просмотренных реализаций для Laravel. 
Пакет полностью реализует поведение управления имперсонализацией GET-параметрами. Также данный пакет не ограничивает Вас в использовании произвольных [user provider'ов](https://laravel.com/docs/master/authentication#adding-custom-user-providers) (например, можно работать с пользователями через [Propel](https://github.com/propelorm/PropelLaravel)), guard'ов и позволяет использовать в качестве шаблонизатора [Twig](https://github.com/rcrowe/TwigBridge). 
Некоторые идеи были заимствованы из других пакетов имперсонализации для Laravel (:+1: спасибо их авторам!).
 
## Установка
 
 ```
 composer require scif/laravel-pretend
 ```

 Добавьте сервис провайдер в `config/app.php` после сервисных провайдеров Laravel, но до своих собственных:
 
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
 
 Добавьте middleware, реализующий имперсонализацию одним из способов:
  * в свой класс `Kernel`:
    ```php
    protected $middlewareGroups = [
        'web' => [
            …
            Scif\LaravelPretend\Middleware\Impersonate::class,
        ],
    ```
    Это наиболее популярный способ и покрывает большинство случаев, которые я могу представить.
  * или любым другим [доступным способом](https://laravel.com/docs/5.4/middleware#registering-middleware), в случае особенных требований.
  
Заключительным шагом в установке является конфигурирование авторизации через [gate](https://laravel.com/docs/5.3/authorization#gates).
 В пакете поставляется gate называющий `impersonate`. Данный gate проверяет реализует ли модель вашего пользователя интерфейс `Scif\LaravelPretend\Interfaces\Impersonable` и вызывает метод `canImpersonate(): bool`. 
 
 В этом случае ваша модель может выглядеть так:
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
 
:point_up: По своему усмотрению Вы можете использовать штатную реализацию gate'а либо переопределить её в своём AuthServiceProvider'е,
также можно переопределить имя gate'а в конфигурации.

## Конфигурация

Скопировать конфигурационный файл к себе в проект можно стандартной командой `vendor:publish`:
 
 ```php
 php ./artisan  vendor:publish --provider=Scif\\LaravelPretend\\LaravelPretendServiceProvider --tag=config
 ```

Конфигурационный файл содержит всего две директивы:

```php
return [
    'impersonate' => [
        'user_identifier' => 'email',
        'auth_check' => 'impersonate',
    ]
];
```

* `user_identifier` — эта строка, будет использоваться как имя поля по которому запрашивается объект пользователя из user provider'а (метод `retrieveByCredentials()`).
 Значение по-умолчанию `email` позволяет создавать url для имперсонализации более красивыми: `?_switch_user=admin@site.com` намного понятней, чем `?_switch_user=43` при использовании `id`.
 Но это полностью на Ваше усмотрение.
* `auth_check` — эта строка, является именем [Gate](https://laravel.com/docs/5.4/authorization#gates), используемого для проверки наличия прав у пользователя для применения имперсонализации.
Вы можете легко переопределить имя gate'а и создать его в `AuthServiceProvider` своего приложения.

## Использование

Как было указано выше, этот пакет повторяет стиль Symfony по работе с GET-параметрами для управления имперсонализацией.

Использовать в `blade` весьма просто:

```php
// создаём ссылку для имперсонализации
{{ route('home', ['_switch_user' => 'admin@site.com']) }}

// выход из режима имперсонализации
    @if ($app['impersonator']->isImpersonated())
        <a href="{{ route('home', ['_switch_user' => '_exit']) }}">Exit impersonation</a>
    @else
        <a href="{{ route('logout') }}">Logout</a>
    @endif
```

А вот так можно использовать в `twig`:

```
// создаём ссылку для имперсонализации
{{ route('home', {'_switch_user': 'admin@site.com'}) }}

// более продвинутое использование
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


## События

Во время входа и выхода из режима имперсонализации этот пакет создаёт события: [`Scif\LaravelPretend\Event\Impersontated`](src/Event/Impersonated.php), [`Scif\LaravelPretend\Event\Unimpersontated`](src/Event/Unimpersonated.php).
Именами событий являются их полные имена классов (с namespace'ом), поэтому простейший обработчик событий будет выглядеть так:

```php
use Scif\LaravelPretend\Event\Impersontated;
…
    Event::listen(Impersonated::class, function (Impersonated $event) {
        //
    });
```

## Запрет имперсонализации

Вы можете использовать штатный middleware [`ForbidImpersonation`](src/Middleware/ForbidImpersonation.php) чтобы запретить использование имперсонализации, например для некоторых груп маршрутов, маршрутов или контроллеров. 

## PHP7? Эээ? Чо?!

Да, т.к. PHP7 потрясающ! Если есть большое желание использовать со старым PHP5 — [создайте тикет](https://github.com/SCIF/laravel-pretend/issues) и я подумаю о создании отдельной ветки или иного подходящего решения.

## Помощь в развитии

Любая помощь всегда приветствуется. Не стесняйтесь — помогите проекту стать ещё лучше!
