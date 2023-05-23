<?php

require_once __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL);

set_error_handler(function ($severity, $message, $file, $line) {
    throw new \ErrorException($message, $severity, $severity, $file, $line);
});

use Nzm\AmpCacheUrl\Generator;

$domainSuffix = 'cdn.ampproject.org';
$generator = new Generator();

$cacheUrl = $generator->Generate($domainSuffix, 'https://ex-zero.web.net');

echo $cacheUrl . PHP_EOL;