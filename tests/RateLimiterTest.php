<?php

namespace Concat\Http\Middleware\Test;

use Mockery as m;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Concat\Http\Middleware\RateLimiter;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\TransferStats;
use GuzzleHttp\RequestOptions;
use Concat\Http\Middleware\RateLimitProvider;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use ReflectionClass;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;

class RateLimiterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

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
                 ->with(m::type(RequestInterface::class))
                 ->andReturn($last);

        $provider->shouldReceive('getRequestTime')
                 ->once()
                 ->with(m::type(RequestInterface::class))
                 ->andReturn($current);

        $method = $this->getMethod(RateLimiter::class, 'getDelay');

        $limiter = new RateLimiter($provider);

        $this->assertEquals(
            sprintf($expected),
            sprintf($method->invoke($limiter, $request))
        );
    }

    public function providerTestInvoke()
    {
        return [
            ['GET', 1, LogLevel::DEBUG],
            ['GET', 1, null],
            ['GET', 1, function() { return LogLevel::DEBUG; }],
        ];
    }

    /**
     * @dataProvider providerTestInvoke
     */
    public function testInvoke($method, $delay, $level)
    {
        $provider = m::mock(RateLimitProvider::class);
        $provider->shouldReceive('setRequestAllowance')->once()->with(m::type(ResponseInterface::class));
        $provider->shouldReceive('setLastRequestTime')->once()->with(m::type(RequestInterface::class));

        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('log')->with(LogLevel::DEBUG, m::type('string'), m::type('array'));

        $limiter = m::mock(RateLimiter::class . "[getDelay]", [$provider, $logger]);
        $limiter->shouldAllowMockingProtectedMethods();

        if ($level) {
            $limiter->setLogLevel($level);
        }

        $limiter->shouldReceive('getDelay')->once()->with(m::type(RequestInterface::class))->andReturn($delay);

        $middleware = function(callable $handler) use ($delay) {
            return function ($request, $options) use ($handler, $delay) {
                $this->assertSame($options[RequestOptions::DELAY] ?? 0, $delay * 1000);
                return $handler($request, $options);
            };
        };

        $stack = new HandlerStack();
        $stack->setHandler(new MockHandler([new Response(200, [])]));
        $stack->unshift($middleware);
        $stack->unshift($limiter);

        $client = new Client([
            'handler' => $stack,
        ]);
        $client->send(new Request($method, '/'));
    }
}
