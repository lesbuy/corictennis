<?php

if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php');


$gender = get_param($argv, 1, "atp", " atp wta ");
$sd = get_param($argv, 1, "s", " s d ");

