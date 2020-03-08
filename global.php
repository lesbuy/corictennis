<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', ['', $dir_arr[0], $dir_arr[1]]));}

if (!defined('WEB')) {define('WEB', ROOT . '/web');}
if (!defined('APP')) {define('APP', ROOT . '/app');}
if (!defined('STORE')) {define('STORE', ROOT . '/store');}
if (!defined('DATA')) {define('DATA', ROOT . '/data');}
if (!defined('SCRIPT')) {define('SCRIPT', ROOT . '/script');}
if (!defined('SHARE')) {define('SHARE', ROOT . '/share');}
if (!defined('TEMP')) {define('TEMP', ROOT . '/temp');}

$db_conf = [
	'redis' => [
		'host' => '127.0.0.1',
		'port' => 6379,
	],
];
