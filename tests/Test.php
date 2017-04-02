<?php declare(strict_types=1);

namespace Scif\LaravelPretend\Tests;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Config\Repository;
use Scif\LaravelPretend\Event\Impersonated;
use Scif\LaravelPretend\LaravelPretendServiceProvider;
use Scif\LaravelPretend\Middleware\Impersonate;
use Scif\LaravelPretend\Service\Impersonator;
use Scif\LaravelPretend\Tests\Stubs\User;

class Test extends TestCase
{
    const ABILITY = 'impersonate_test';

    protected $baseUrl = 'http://localhost';

    public function testMain()
    {
        $admin = $this->getAdmin();
        $user = $this->getUser();

        \Auth::setProvider($this->mockUserProvider($user));

        $this->assertFalse(
            $this->app->make(Impersonator::class)->isImpersonated(),
            'Impersonator service reports user has been impersonated before impersonate test start'
        );

        $this->expectsEvents(Impersonated::class);

        $response = $this->actingAs($admin)
                        ->get('test?_switch_user=' . $user->email);

        $response->assertRedirectedTo('test');

        $this->assertTrue(
            $this->app->make(Impersonator::class)->isImpersonated(),
            'Impersonator service reports user has not been impersonated'
        );

        $this->assertEquals(
            $user,
            $this->app->make('auth')->guard()->user(),
            'Impersonated and expected users are different'
        );
    }

    public function testSameUser()
    {
        $admin = $this->getAdmin();

        \Auth::setProvider($this->mockUserProvider($admin));

        $this->doesntExpectEvents(Impersonated::class);

        $response = $this->actingAs($admin)
                        ->get('test?_switch_user=' . $admin->email);

        $response->assertResponseStatus(403);

        $this->assertSame(
            'Cannot impersonate yourself',
            $response->response->exception->getMessage(),
            'Incorrect text of same user impersonation or incorrect exception thrown'
        );
    }

    public function testNotEnoughPermission()
    {
        $user = $this->getUser();
        $admin = $this->getAdmin();

        \Auth::setProvider($this->mockUserProvider($user, 0));

        $this->doesntExpectEvents(Impersonated::class);

        $response = $this->actingAs($user)
                        ->get('test?_switch_user=' . $admin->email);

        $response->assertResponseStatus(403);
        $this->assertSame(
            "Current user have no ability 'impersonate_test'",
            $response->response->exception->getMessage(),
            'Incorrect text of lack user impersonation rights or incorrect exception thrown'
        );
    }

    protected function getAdmin(): User
    {
        return new User(1, true);
    }

    protected function getUser(): User
    {
        return new User(2, false);
    }

    protected function setUp()
    {
        parent::setUp();
        $this->withoutEvents();

        $this->app->register(LaravelPretendServiceProvider::class);

        $config = $this->app->make(Repository::class);
        $config->set('pretend.impersonate', [
            'user_identifier' => 'email',
            'auth_check' => self::ABILITY,
        ]);

        $gate = $this->app->make(Gate::class);
        $gate->define(self::ABILITY, function(User $user): bool {
            return $user->isAdmin();
        });

        $this->registerRoutes();
    }

    protected function registerRoutes()
    {
        $this->app->make('router')->get('test',
            [
                'as' => 'test',
                function(): string {
                    return 'passed';
                }
            ]
        )->middleware([Impersonate::class]);
    }

    protected function mockUserProvider(User $user, int $times = 1): UserProvider
    {
        $mock = \Mockery::mock(UserProvider::class);

        $mock->shouldReceive('retrieveByCredentials')
                ->times($times)
                ->with(['email' => $user->email])
                ->andReturn($user);

        return $mock;
    }
}
