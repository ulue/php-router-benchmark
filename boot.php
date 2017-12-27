<?php

error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

function null_handler()
{
    return '';
}

function calc_time_consuming($start, $end, $decimals = 3)
{
    return number_format(($end - $start) * 1000, $decimals);
}