# PHP Router Benchmark

The intent here is to benchmark different PHP routing solutions. This is a micro-optimization, done purely out of 
dumb curiosity.

- the test script is forked form https://github.com/tyler-sommer/php-router-benchmark
- test frmaework is: https://github.com/ulue/nice-bench. it is forked form `nice/bench`
- **Test time** 2017.12.03

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

The current test creates **1000** routes, each with a randomized prefix and postfix, with **9** parameters each.

It was run with the [Pux](https://github.com/c9s/pux) and [R3](https://github.com/c9s/php-r3) extensions enabled.

An example route: `/9b37eef21e/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}/{arg6}/{arg7}/{arg8}/{arg9}/bda37e9f9b`

## Collect Routes Time(ms):

- nikic/fast-route: 69.603 ms
- nikic/fast-route(cached): 3.734 ms
- inhere/sroute(SRouter): 29.704 ms
- inhere/sroute(ORouter): 29.164 ms
- Symfony2/routing: 16.976 ms
- Symfony2/routing(cached): 17.666 ms
- corneltek/pux(php): 92.823 ms
- altorouter/altorouter: 5.203 ms
- phroute/phroute: 91.662 ms
- noahbuscher/macaw: 8.854 ms
- noodlehaus/dispatch: 7.203 ms

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
inhere/sroute(ORouter) - unknown route (1000 routes) | 987 | 0.010222 | +0.000000 | baseline
inhere/sroute(SRouter) - unknown route (1000 routes) | 984 | 0.012239 | +0.002017 | 20% slower
inhere/sroute(SRouter) - last route (1000 routes) | 999 | 0.024386 | +0.014820 | 155% slower
inhere/sroute(ORouter) - last route (1000 routes) | 975 | 0.024554 | +0.014989 | 157% slower
Symfony2 Cached - last route (1000 routes) | 997 | 0.029091 | +0.019525 | 204% slower
Symfony2 Cached - unknown route (1000 routes) | 985 | 0.037226 | +0.027661 | 289% slower
FastRoute - unknown route (1000 routes) | 988 | 0.089904 | +0.080338 | 840% slower
FastRoute(cached) - unknown route (1000 routes) | 988 | 0.091358 | +0.081792 | 855% slower
FastRoute(cached) - last route (1000 routes) | 999 | 0.092567 | +0.083001 | 868% slower
FastRoute - last route (1000 routes) | 999 | 0.113668 | +0.104103 | 1088% slower
phroute/phroute - unknown route (1000 routes) | 987 | 0.168871 | +0.159305 | 1665% slower
phroute/phroute - last route (1000 routes) | 999 | 0.169914 | +0.160348 | 1676% slower
Pux PHP - unknown route (1000 routes) | 981 | 0.866280 | +0.856714 | 8956% slower
Pux PHP - last route (1000 routes) | 999 | 0.941322 | +0.931757 | 9741% slower
AltoRouter - unknown route (1000 routes) | 982 | 2.245384 | +2.235819 | 23373% slower
AltoRouter - last route (1000 routes) | 979 | 2.281995 | +2.272429 | 23756% slower
Symfony2 - unknown route (1000 routes) | 984 | 2.488247 | +2.478681 | 25912% slower
Symfony2 - last route (1000 routes) | 999 | 2.540170 | +2.530605 | 26455% slower
Macaw - unknown route (1000 routes) | 982 | 2.617635 | +2.608069 | 27265% slower
Macaw - last route (1000 routes) | 999 | 2.700128 | +2.690562 | 28127% slower
noodlehaus/dispatch - unknown route (1000 routes) | 972 | 4.729197 | +4.719632 | 49339% slower
noodlehaus/dispatch - last route (1000 routes) | 999 | 4.942050 | +4.932484 | 51564% slower

## First route matching

```bash
php run-first-tests.php
```

This benchmark tests how quickly each router can match the first route. 1,000 routes each with 9 arguments.

This benchmark consists of 11 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.

Test Name | Results | Time(ms) | + Interval | Change
--------- | ------- | ---- | ---------- | ------
Pux PHP - first route(1000) | 997 | 0.006587 | +0.000000 | baseline
FastRoute - first route(1000) | 999 | 0.008751 | +0.002165 | 33% slower
phroute/phroute - first route (1000 routes) | 999 | 0.021902 | +0.015315 | 233% slower
Symfony2 Dumped - first route | 997 | 0.022254 | +0.015667 | 238% slower
ORouter - first route(1000) | 993 | 0.025026 | +0.018440 | 280% slower
SRouter - first route(1000) | 997 | 0.025553 | +0.018967 | 288% slower
noodlehaus/dispatch - first route (1000 routes) | 989 | 0.030126 | +0.023540 | 357% slower
AltoRouter - first route (1000 routes) | 994 | 0.041488 | +0.034902 | 530% slower
Symfony2 - first route | 991 | 0.047335 | +0.040748 | 619% slower
FastRoute(cached) - first route(1000) | 999 | 0.092703 | +0.086117 | 1307% slower
Macaw - first route (1000 routes) | 999 | 2.710132 | +2.703545 | 41047% slower
