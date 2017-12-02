<?php

use Nice\Benchmark\BenchmarkCollection;

error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

function null_handler()
{
    return 'hello';
}

$numIterations = 1000;
$numRoutes = 1000;
$numArgs = 9;

require __DIR__ . '/cases/worst-case-tests.php';
$worstCaseBenchmark = WorstCaseMatching\setupBenchmark($numIterations, $numRoutes, $numArgs);

require __DIR__ . '/cases/first-test-cases.php';
$firstRouteBenchmark = FirstRouteMatching\setupBenchmark($numIterations, $numRoutes, $numArgs);

$collection = new BenchmarkCollection();
$collection->addBenchmark($worstCaseBenchmark);
$collection->addBenchmark($firstRouteBenchmark);
$collection->execute();
