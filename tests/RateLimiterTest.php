<?php

namespace Concat\Http\Middleware\Test;

use \Mockery as m;
use Psr\Http\Message\RequestInterface;
use Concat\Http\Middleware\RateLimiter;

class RateLimiterTest extends \PHPUnit_Framework_TestCase
{

    private function seconds($micro)
    {
        return $micro * 1000 * 1000;
    }

    private function rate($allowance, $last, $expected)
    {
        $provider = m::mock('Concat\Http\Middleware\RateLimitProvider');

        $limiter = new RateLimiter($provider);

        $provider->shouldReceive('getRequestAllowance')->andReturn($allowance);
        $provider->shouldReceive('getLastRequestTime')->andReturn($last);
        $provider->shouldReceive('setLastRequestTime');

        $promise = m::mock('GuzzleHttp\Promise\PromiseInterface');
        $promise->shouldReceive('then')->once()->andReturnUsing(function($a) {
            return $a;
        });

        $request = m::mock('Psr\Http\Message\RequestInterface');
        $response = m::mock('Psr\Http\Message\ResponseInterface');

        //
        $handler = function ($request, $options) use ($promise){
            return $promise;
        };

        $time = time();

        $allowance = $limiter($handler)->__invoke($request, []);

        //
        $provider->shouldReceive('setRequestAllowance')->once();

        //
        $allowance($response);

        $this->assertEquals($expected, time() - $time);
    }


    public function testLimiting()
    {
        $this->rate($this->seconds(1), microtime(true), 1);
    }

    public function testNotLimiting()
    {
        $this->rate($this->seconds(1), microtime(true) - $this->seconds(2), 0);
    }
}
