<?php

require_once("activity.php");

$pid = get_param($argv, 1, null);
$sd = get_param($argv, 2, 's', ' s d ');
$year = get_param($argv, 3, 'all');
$page = get_param($argv, 4, 'all');

if (!$pid) exit(-1);

$activity = new Activity("atp", $sd, $pid, $year, $page);
