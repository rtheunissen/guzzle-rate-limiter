<?php

namespace Concat\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Guzzle middleware which delays requests if they exceed a rate allowance.
 */
class RateLimiter
{
    /**
     * @var RateLimitProvider
     */
    private $provider;

    /**
     * Creates a callable middleware rate limiter.
     *
     * @param RateLimitProvider $provider A rate data provider.
     */
    public function __construct(RateLimitProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Called when the middleware is handled. Delays the request, then sets the
     * allowance for the next request.
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, $options) use ($handler) {

            // Delay the request
            $this->delay($request);

            // Set the allowance when the response was received
            return $handler($request, $options)->then($this->setAllowance());
        };
    }

    /**
     * Returns the delay duration for the given request (in microseconds).
     *
     * @param RequestInterface $request Rquest to get the delay duration for.
     *
     * @return float The delay duration (in microseconds).
     */
    private function getDelay(RequestInterface $request)
    {
        // The time at which the last request was made
        $lastRequestTime = $this->provider->getLastRequestTime();

        // Minimum time that had to have passed since the last request
        $allowance = $this->provider->getRequestAllowance($request);

        // If lastRequestTime is null|false, the max will be 0.
        return max(0, $allowance - (microtime(true) - $lastRequestTime));
    }

    /**
     * Delays the given request if the delay duration is greater than zero.
     *
     * @param RequestInterface $request Request to delay.
     */
    private function delay(RequestInterface $request)
    {
        // Delay the request if required
        if (($delay = $this->getDelay($request)) > 0) {
            usleep($delay);
        }

        $this->provider->setLastRequestTime();
    }

    /**
     * Returns a callable handler which allows the provider to set the request
     * allowance for the next request, using the current response.
     *
     * @return Closure Handler to set request allowance on the rate provider.
     */
    private function setAllowance()
    {
        return function (ResponseInterface $response) {
            $this->provider->setRequestAllowance($response);
            return $response;
        };
    }
}
