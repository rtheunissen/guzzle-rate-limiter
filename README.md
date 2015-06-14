# Guzzle middleware to delay requests

[![Author](http://img.shields.io/badge/author-@rudi_theunissen-blue.svg?style=flat-square)](https://twitter.com/rudi_theunissen)
[![Build Status](https://img.shields.io/travis/rtheunissen/guzzle-rate-limiter.svg?style=flat-square&branch=master)](https://travis-ci.org/rtheunissen/guzzle-rate-limiter)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/rtheunissen/guzzle-rate-limiter.svg?style=flat-square)](https://scrutinizer-ci.com/g/rtheunissen/guzzle-rate-limiter/)
[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/rtheunissen/guzzle-rate-limiter.svg?style=flat-square)](https://scrutinizer-ci.com/g/rtheunissen/guzzle-rate-limiter/)
[![Latest Version](https://img.shields.io/packagist/v/rtheunissen/guzzle-rate-limiter.svg?style=flat-square)](https://packagist.org/packages/rtheunissen/guzzle-rate-limiter)
[![License](https://img.shields.io/packagist/l/rtheunissen/guzzle-rate-limiter.svg?style=flat-square)](https://packagist.org/packages/rtheunissen/guzzle-rate-limiter)

## Installation

```bash
composer require rtheunissen/guzzle-rate-limiter
```

## Usage

There is currently no default implementation for `RateLimitProvider`.

```php
$handlerStack->push(new \Concat\Http\Middleware\RateLimiter($rateLimitProvider));
```
