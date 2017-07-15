<?php declare(strict_types=1);

namespace Scif\LaravelPretend\Tests;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Event;
use Scif\LaravelPretend\Event\Impersonated;
use Scif\LaravelPretend\Event\Unimprersonated;
use Scif\LaravelPretend\LaravelPretendServiceProvider;
use Scif\LaravelPretend\Middleware\Impersonate;
use Scif\LaravelPretend\Service\Impersonator;
use Scif\LaravelPretend\Tests\Stubs\User;

class Test extends TestCase
{
    const ABILITY = 'impersonate_test';

    protected $baseUrl = 'http://localhost';

    public function tearDown()
    {
        \Mockery::close();
    }

    public function testMain()
    {
        Event::fake();
        $admin = $this->getAdmin();
        $user = $this->getUser();

        \Auth::setProvider($this->mockUserProvider($user, 3));

        $this->assertFalse(
            $this->app->make(Impersonator::class)->isImpersonated(),
            'Impersonator service reports user has been impersonated before impersonate test start'
        );

        $this->expectsEvents(Impersonated::class);

        $response = $this->actingAs($admin)
                        ->get('test?_switch_user=' . $user->email);

        $this->eventDispatched(Impersonated::class, function (Impersonated $event) {
            $this->assertEquals(1, $event->getRealUser()->getAuthIdentifier());
            $this->assertEquals(2, $event->getImpersonationUser()->getAuthIdentifier());

            return true;
        });

        $response->assertRedirectedTo('test');

        $this->assertTrue(
            $this->app->make(Impersonator::class)->isImpersonated(),
            'Impersonator service reports failed impersonation status'
        );

        $this->assertEquals(
            $user,
            $this->app->make('auth')->guard()->user(),
            'Impersonated and expected users are different'
        );

        $response = $response->actingAs($admin)->followRedirects();
        $response->actingAs($admin)->visit('test?_switch_user=_exit');

        $this->eventDispatched(Unimprersonated::class, function (Unimprersonated $event) {
            $this->assertEquals(1, $event->getRealUser()->getAuthIdentifier());
            $this->assertEquals(2, $event->getImpersonationUser()->getAuthIdentifier());

            return true;
        });

        $this->assertFalse(
            $this->app->make(Impersonator::class)->isImpersonated(),
            'Impersonator service reports failed impersonation status when exit was done'
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

    public function testUavailableUser()
    {
        $admin = $this->getAdmin();

        \Auth::setProvider($this->mockUserProviderReturnNull());

        $this->doesntExpectEvents(Impersonated::class);

        $response = $this->actingAs($admin)
                        ->get('test?_switch_user=unavailable@email.tld');

        $response->assertResponseStatus(403);

        $this->assertSame(
            'Cannot find user by this credentials',
            $response->response->exception->getMessage(),
            'Incorrect message of exception thrown if user was not found in UserProvider'
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

    protected function mockUserProviderReturnNull(): UserProvider
    {
        $mock = \Mockery::mock(UserProvider::class);

        $mock->shouldReceive('retrieveByCredentials')
                ->with(['email' => 'unavailable@email.tld'])
                ->andReturn(null);

        return $mock;
    }
}
