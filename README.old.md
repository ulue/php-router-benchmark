# PHP Router Benchmark

The intent here is to benchmark different PHP routing solutions. This is a micro-optimization, done purely out of 
dumb curiosity.

## Question

class `Nice\Benchmark` 87-89 line(bug):

```php
    $start = microtime(1);
    $test->run($this->parameters);
    $testResults[] = round(microtime(1) - $start, 10);
```

## Installation

Clone the repo, run `composer install`, run `php run-tests.php`.

You can install the [Pux](https://github.com/c9s/pux) extension to test that as well. If the extension is not
installed, the tests will fallback to the pure PHP implementation of Pux.

To test the [R3 library](https://github.com/c9s/php-r3), you also need to install that extension. If the extension is
not installed, the tests for R3 will be skipped.

## Currently

The current test creates 1000 routes, each with a randomized prefix and postfix, with 9 parameters each.

It was run with the [Pux](https://github.com/c9s/pux) and [R3](https://github.com/c9s/php-r3) extensions enabled.

An example route: `/9b37eef21e/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/bda37e9f9b`

## Worst-case matching

```bash
php run-worst-tests.php
```

This benchmark matches the last route and unknown route. It generates a randomly prefixed and suffixed route in an attempt to thwart any optimization. 1,000 routes each with 9 arguments.

This benchmark consists of 12 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.


Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
SRouter - unknown route (1000 routes) | 961 | 0.0000113283 | +0.0000000000 | baseline
ORouter - unknown route (1000 routes) | 975 | 0.0000113920 | +0.0000000638 | 1% slower
SRouter - last route (1000 routes) | 982 | 0.0000312148 | +0.0000198866 | 176% slower
ORouter - last route (1000 routes) | 985 | 0.0000339114 | +0.0000225831 | 199% slower
Symfony2 Dumped - unknown route (1000 routes) | 974 | 0.0000427746 | +0.0000314463 | 278% slower
Symfony2 Dumped - last route (1000 routes) | 991 | 0.0000480992 | +0.0000367710 | 325% slower
FastRoute - unknown route (1000 routes) | 971 | 0.0001323029 | +0.0001209747 | 1068% slower
FastRoute(cached) - unknown route (1000 routes) | 975 | 0.0001375061 | +0.0001261779 | 1114% slower
FastRoute(cached) - last route (1000 routes) | 999 | 0.0001393305 | +0.0001280022 | 1130% slower
FastRoute - last route (1000 routes) | 999 | 0.0001744816 | +0.0001631533 | 1440% slower
Pux PHP - unknown route (1000 routes) | 988 | 0.0013841902 | +0.0013728620 | 12119% slower
Pux PHP - last route (1000 routes) | 999 | 0.0015292509 | +0.0015179226 | 13399% slower
Symfony2 - unknown route (1000 routes) | 978 | 0.0041153194 | +0.0041039912 | 36228% slower
Symfony2 - last route (1000 routes) | 999 | 0.0041823036 | +0.0041709754 | 36819% slower

## First route matching

```bash
php run-tests.php
```

This benchmark tests how quickly each router can match the first route. 1,000 routes each with 9 arguments.

This benchmark consists of 6 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.


Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
Pux PHP - first route(1000) | 999 | 0.0000140631 | +0.0000000000 | baseline
FastRoute - first route(1000) | 998 | 0.0000143997 | +0.0000003366 | 2% slower
SRouter - first route(1000) | 996 | 0.0000171480 | +0.0000030848 | 22% slower
ORouter - first route(1000) | 996 | 0.0000182364 | +0.0000041733 | 30% slower
Symfony2 Dumped - first route | 991 | 0.0000339753 | +0.0000199121 | 142% slower
Symfony2 - first route | 998 | 0.0000688385 | +0.0000547753 | 389% slower
FastRoute(cached) - first route(1000) | 999 | 0.0001558740 | +0.0001418108 | 1008% slower
