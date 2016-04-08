# Guzzle middleware to delay requests

[![Author](http://img.shields.io/badge/author-@rudi_theunissen-blue.svg?style=flat-square)](https://twitter.com/rudi_theunissen)
[![License](https://img.shields.io/packagist/l/rtheunissen/guzzle-rate-limiter.svg?style=flat-square)](https://packagist.org/packages/rtheunissen/guzzle-rate-limiter)
[![Latest Version](https://img.shields.io/packagist/v/rtheunissen/guzzle-rate-limiter.svg?style=flat-square)](https://packagist.org/packages/rtheunissen/guzzle-rate-limiter)
[![Build Status](https://img.shields.io/travis/rtheunissen/guzzle-rate-limiter.svg?style=flat-square&branch=master)](https://travis-ci.org/rtheunissen/guzzle-rate-limiter)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/rtheunissen/guzzle-rate-limiter.svg?style=flat-square)](https://scrutinizer-ci.com/g/rtheunissen/guzzle-rate-limiter/)
[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/rtheunissen/guzzle-rate-limiter.svg?style=flat-square)](https://scrutinizer-ci.com/g/rtheunissen/guzzle-rate-limiter/)

## Installation

```bash
composer require rtheunissen/guzzle-rate-limiter
```

## Usage

There is currently no default implementation for `RateLimitProvider`.

```php
use Concat\Http\Middleware\RateLimiter;

$handlerStack->push(new RateLimiter($rateLimitProvider));
```

## Example

```php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Concat\Http\Middleware\RateLimitProvider;

/**
 * An object which manages rate data for a rate limiter, which uses the data to
 * determine wait duration. Keeps track of:
 *
 *  - Time at which the last request was made
 *  - The allowed interval between the last and next request
 */
class Provider implements RateLimitProvider
{
    /**
     * Returns when the last request was made.
     *
     * @return float|null When the last request was made.
     */
    public function getLastRequestTime()
    {
        // This is just an example, it's up to you to store the time of the
        // most recent request, whether it's in a database or cache driver.
        return Cache::get('last_request_time');
    }

    /**
     * Used to set the current time as the last request time to be queried when
     * the next request is attempted.
     */
    public function setLastRequestTime()
    {
        // This is just an example, it's up to you to store the time of the
        // most recent request, whether it's in a database or cache driver.
        return Cache::put('last_request_time', microtime(true));
    }

    /**
     * Returns what is considered the time when a given request is being made.
     *
     * @param RequestInterface $request The request being made.
     *
     * @return float Time when the given request is being made.
     */
    public function getRequestTime(RequestInterface $request)
    {
        //
        return microtime(true);
    }

    /**
     * Returns the minimum amount of time that is required to have passed since
     * the last request was made. This value is used to determine if the current
     * request should be delayed, based on when the last request was made.
     *
     * Returns the allowed time between the last request and the next, which
     * is used to determine if a request should be delayed and by how much.
     *
     * @param RequestInterface $request The pending request.
     *
     * @return float The minimum amount of time that is required to have passed
     *               since the last request was made (in microseconds).
     */
    public function getRequestAllowance(RequestInterface $request)
    {
        // This is just an example, it's up to you to store the request 
        // allowance, whether it's in a database or cache driver.
        return Cache::get('request_allowance');
    }

    /**
     * Used to set the minimum amount of time that is required to pass between
     * this request and the next (in microseconds).
     *
     * @param ResponseInterface $response The resolved response.
     */
    public function setRequestAllowance(ResponseInterface $response)
    {
        // Let's also assume that the response contains two headers:
        //     - ratelimit-remaining
        //     - ratelimit-window
        //
        // The first header tells us how many requests we have left in the 
        // current window, the second tells us how many seconds are left in the
        // window before it expires.
        $requests = $response->getHeader('ratelimit-remaining');
        $seconds  = $response->getHeader('ratelimit-window');

        // The allowance is therefore how many requests we are allowed to make
        // over a given period of time. This is the value we need to store to
        // determine if a future request should be delayed or not.
        $allowance = (float) $requests / $seconds;
    
        // This is just an example, it's up to you to store the request 
        // allowance, whether it's in a database or cache driver.
        Cache::put('request_allowance', $allowance);
    }
}
```
