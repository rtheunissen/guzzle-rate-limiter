<?php

namespace Concat\Http\Middleware\Test;

use \Mockery as m;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Concat\Http\Middleware\RateLimiter;
use GuzzleHttp\Promise\PromiseInterface;
use Concat\Http\Middleware\RateLimitProvider;
use Closure;
use ReflectionClass;

class RateLimiterTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        m::close();
    }

    private function getMethod($class, $name)
    {
        $class = new ReflectionClass($class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function providerTestGetDelay()
    {
            // allowance, last request, expected delay time in seconds.
        return [

            // Allowed 2 seconds between each, last was 1, now is 1, so wait 2
            [2, 1, 1, 2],

            // Allowed 1 second between each, last was 0, now is 1, so wait 0.
            [1, 0, 1, 0],

            // Allowed 0.1 second between each, last was 1, now is 1.05, so wait 0.05.
            [0.1, 1, 1.05, 0.05],

            // Check that no last time incurs no delay.
            [1, null, 10, 0],
        ];
    }

    /**
     * @dataProvider providerTestGetDelay
     */
    public function testGetDelay($allowance, $last, $current, $expected)
    {
        $request = m::mock(RequestInterface::class);
        $provider = m::mock(RateLimitProvider::class);

        $provider->shouldReceive('getRequestAllowance')
                 ->once()
                 ->with(m::type(RequestInterface::class))
                 ->andReturn($allowance);

        $provider->shouldReceive('getLastRequestTime')
                 ->once()
                 ->andReturn($last);

        $provider->shouldReceive('getRequestTime')
                 ->once()
                 ->andReturn($current);

        $method = $this->getMethod(RateLimiter::class, 'getDelay');
        $limiter = new RateLimiter($provider);
        $this->assertEquals($expected, $method->invoke($limiter, $request));
    }

    public function providerTestInvoke()
    {
        return [
            ['GET', 1, LogLevel::DEBUG],
        ];
    }

    /**
     * @dataProvider providerTestInvoke
     */
    public function testInvoke($method, $delay, $level)
    {
        $provider = m::mock(RateLimitProvider::class);
        $provider->shouldReceive('setRequestAllowance')->once()->with(m::type(ResponseInterface::class));
        $provider->shouldReceive('setLastRequestTime')->once();

        $promise = m::mock(PromiseInterface::class);
        $promise->shouldReceive('then')->once()->andReturnUsing(function ($a) {
            return $a;
        });

        $handler = function ($request, $options) use ($promise) {
            return $promise;
        };

        $request = m::mock(RequestInterface::class);
        $request->shouldReceive('getMethod')->andReturn($method);
        $request->shouldReceive('getUri')->andReturn('/');

        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('log')->with($level, m::type('string'), m::type('array'));

        $limiter = m::mock(RateLimiter::class . "[getDelay,delay]", [$provider, $logger]);
        $limiter->shouldAllowMockingProtectedMethods();

        $limiter->shouldReceive('getDelay')->once()->with(m::type(RequestInterface::class))->andReturn($delay);
        $limiter->shouldReceive('delay')->once()->with($delay);

        $callback = $limiter->__invoke($handler);
        $result = $callback->__invoke($request, []);

        $response = m::mock(ResponseInterface::class);
        $result->__invoke($response);
    }
}
