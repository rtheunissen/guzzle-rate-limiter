<?php

namespace Concat\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * An object which manages rate data for a rate limiter, which uses the data to
 * determine wait duration. Keeps track of:
 *
 *  - Time at which the last request was made
 *  - The allowed interval between the last and next request
 */
interface RateLimitProvider
{

    /**
     * Returns when the last request was made (in microseconds).
     *
     * @return float|null When the last request was made (in microseconds) or
     *                    NULL if no such record exists.
     */
    function getLastRequestTime();

    /**
     * Used to set the current time (in microseconds) as the last request time
     * to be queried when the next request is attempted.
     */
    function setLastRequestTime();

    /**
     * Returns the minimum amount of time that is required to have passed since
     * the last request was made. This value is used to determine if the current
     * request should be delayed, based on when the last request was made.
     *
     * Returns the allowed  between the last request and the next, which
     * is used to determine if a request should be delayed and by how much.
     *
     * @param RequestInterface $request The pending request.
     *
     * @return float The minimum amount of time that is required to have passed
     *               since the last request was made (in microseconds).
     */
    function getRequestAllowance(RequestInterface $request);

    /**
     * Used to set the minimum amount of time that is required to pass between
     * this request and the next (in microseconds).
     *
     * @param ResponseInterface $response The resolved response.
     */
    function setRequestAllowance(ResponseInterface $response);
}
