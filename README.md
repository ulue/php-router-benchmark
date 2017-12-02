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

## Test Router List

```
    "nikic/fast-route": "^1.2",
    "inhere/sroute": "dev-master",
    "phroute/phroute": "dev-master",
    "symfony/routing": "dev-master",
    "aura/router": "3.x-dev",
    "corneltek/pux": "dev-master",
    "altorouter/altorouter": "dev-master",
    "noahbuscher/macaw": "dev-master",
    "league/route": "dev-master",
    "dannyvankooten/php-router": "dev-master",
    "klein/klein": "dev-master"
```

- `nikic/fast-route` https://github.com/nikic/FastRoute
- `klein/klein` https://github.com/klein/klein.php
- `altorouter/altorouter` https://github.com/dannyvankooten/AltoRouter
- `noahbuscher/macaw` https://github.com/noahbuscher/Macaw
- `noodlehaus/dispatch` https://github.com/noodlehaus/dispatch
- `league/route` https://github.com/thephpleague/route
- `dannyvankooten/php-router` https://github.com/dannyvankooten/PHP-Router
- `phroute/phroute` https://github.com/mrjgreen/phroute
- `symfony/routing` https://github.com/symfony/routing
- `corneltek/pux` https://github.com/c9s/Pux
- `inhere/sroute` https://github.com/inhere/php-srouter

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

This benchmark matches the last route and unknown route. It generates a randomly prefixed and suffixed route in an attempt to thwart any optimization.
1,000 routes each with 9 arguments.

This benchmark consists of 22 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard d
eviations of the mean are discarded.

Test Name | Results | Time(ms) | + Interval | Change
--------- | ------- | ---- | ---------- | ------
inhere/sroute(SRouter) - unknown route (1000 routes) | 991 | 0.012111 | +0.000000 | baseline
inhere/sroute(ORouter) - unknown route (1000 routes) | 996 | 0.012150 | +0.000040 | baseline
inhere/sroute(SRouter) - last route (1000 routes) | 995 | 0.027694 | +0.015583 | 129% slower
inhere/sroute(ORouter) - last route (1000 routes) | 987 | 0.028067 | +0.015957 | 132% slower
Symfony2 Dumped - last route (1000 routes) | 993 | 0.036652 | +0.024541 | 203% slower
Symfony2 Dumped - unknown route (1000 routes) | 986 | 0.037629 | +0.025519 | 211% slower
phroute/phroute - unknown route (1000 routes) | 999 | 0.120728 | +0.108617 | 897% slower
phroute/phroute - last route (1000 routes) | 999 | 0.131651 | +0.119540 | 987% slower
FastRoute - unknown route (1000 routes) | 990 | 0.137105 | +0.124994 | 1032% slower
FastRoute(cached) - unknown route (1000 routes) | 982 | 0.137144 | +0.125033 | 1032% slower
FastRoute(cached) - last route (1000 routes) | 999 | 0.137838 | +0.125727 | 1038% slower
FastRoute - last route (1000 routes) | 999 | 0.161543 | +0.149432 | 1234% slower
Pux PHP - unknown route (1000 routes) | 979 | 0.865524 | +0.853413 | 7047% slower
Pux PHP - last route (1000 routes) | 999 | 0.929266 | +0.917155 | 7573% slower
AltoRouter - unknown route (1000 routes) | 980 | 2.257993 | +2.245882 | 18545% slower
AltoRouter - last route (1000 routes) | 991 | 2.306883 | +2.294772 | 18948% slower
Macaw - unknown route (1000 routes) | 988 | 2.640169 | +2.628059 | 21700% slower
Macaw - last route (1000 routes) | 999 | 2.696185 | +2.684074 | 22163% slower
Symfony2 - unknown route (1000 routes) | 988 | 4.328796 | +4.316685 | 35643% slower
Symfony2 - last route (1000 routes) | 999 | 4.388761 | +4.376650 | 36139% slower
noodlehaus/dispatch - unknown route (1000 routes) | 987 | 4.873466 | +4.861355 | 40141% slower
noodlehaus/dispatch - last route (1000 routes) | 999 | 4.971496 | +4.959385 | 40950% slower

## First route matching

```bash
php run-tests.php
```

This benchmark tests how quickly each router can match the first route. 1,000 routes each with 9 arguments.

This benchmark consists of 6 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.

Pux PHP - first route(1000) | 999 | 0.0000140631 | +0.0000000000 | baseline
FastRoute - first route(1000) | 998 | 0.0000143997 | +0.0000003366 | 2% slower
SRouter - first route(1000) | 996 | 0.0000171480 | +0.0000030848 | 22% slower
ORouter - first route(1000) | 996 | 0.0000182364 | +0.0000041733 | 30% slower
Symfony2 Dumped - first route | 991 | 0.0000339753 | +0.0000199121 | 142% slower
Symfony2 - first route | 998 | 0.0000688385 | +0.0000547753 | 389% slower
FastRoute(cached) - first route(1000) | 999 | 0.0001558740 | +0.0001418108 | 1008% slower
