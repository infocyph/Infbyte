<?php

declare(strict_types=1);

putenv('INFBYTE_TESTING=1');
putenv('CACHE_STORE=memory');

$_ENV['INFBYTE_TESTING'] = '1';
$_ENV['CACHE_STORE'] = 'memory';
$_SERVER['INFBYTE_TESTING'] = '1';
$_SERVER['CACHE_STORE'] = 'memory';
