<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Snake.php';

$param = ($argc > 1) ? $argv[1] : '';

$snakes = strstr($param, 'm') ? 2 : 1;
$keepAlive = strstr($param, 'k') ? true : false;

$snake = new Snake($snakes, $keepAlive);
$snake->run();
