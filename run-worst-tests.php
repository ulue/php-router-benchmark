<?php

use Nice\Benchmark\BenchmarkCollection;

require __DIR__ . '/boot.php';

// $numIterations = 1000;
// $numRoutes = 1000;
// $numArgs = 9;

require __DIR__ . '/cases/worst-test-cases.php';
$worstCaseBenchmark = WorstCaseMatching\setupBenchmark($numIterations, $numRoutes, $numArgs);

$collection = new BenchmarkCollection();
$collection->addBenchmark($worstCaseBenchmark);
$collection->execute();
