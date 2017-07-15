<?php

namespace Scif\LaravelPretend\Tests;

use Illuminate\Support\Facades\Event;
use Laravel\BrowserKitTesting\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function eventDispatched(string $event, callable $callback)
    {
        if (version_compare('5.4', $this->app->version(), '<')) {
            Event::assertDispatched($event, $callback);
        } else {
            Event::assertFired($event, $callback);
        }
    }
}
