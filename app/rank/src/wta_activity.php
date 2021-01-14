<?php

require_once("activity.php");

$gender = get_param($argv, 1, 'atp', ' atp wta ');
$sd = get_param($argv, 2, 's', ' s d ');
$pid = get_param($argv, 3, null);
$year = get_param($argv, 4, 'all');
$page = get_param($argv, 5, 'all');

if (!$pid) exit(-1);

$activity = new Activity("wta", $sd, $pid, $year, $page);