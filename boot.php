<?php

error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

global $argv;
$numIterations = isset($argv[1]) ? (int)$argv[1] : 1000;
$numArgs       = isset($argv[2]) ? (int)$argv[2] : 9;
$numRoutes     = $numIterations;

function null_handler()
{
    return '';
}

function calc_time_consuming($start, $end, $decimals = 3)
{
    return number_format(($end - $start) * 1000, $decimals);
}
