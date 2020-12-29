<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

/*
	从最新的wta单双打排名里的人，去wta官网下载headshot和portrait
*/

$files = [];
foreach (['s', 'd'] as $sd) {
	$files[] = join("/", [DATA, 'rank', 'wta', $sd, 'current']);
}

$cmd = "cat " . join(" ", $files) . " | cut -f1 | sort -u -g";
unset($r); exec($cmd, $r);

$fp1 = fopen(join("/", [TEMP, 'activity', 'wta', 'portrait']), 'w');
$fp2 = fopen(join("/", [TEMP, 'activity', 'wta', 'headshot']), 'w');

$pids = [];
$count = 0;
foreach ($r as $pid) {
	++$count;
	$pids[] = $pid;
	if ($count % 5 == 0) {
		$referenceExpression = join("%20or%20", array_map(function ($d) {return "TENNIS_PLAYER%3A" . $d;}, $pids));
		$url = "https://api.wtatennis.com/content/wta/photo/EN/?pageSize=100&tagNames=player-headshot&referenceExpression=" . $referenceExpression;
		$html = file_get_contents($url);

		$json = json_decode($html, true);

		foreach ($json['content'] as $line) {
			$pid = $line['references'][0]['id'];
			$name = preg_replace('/ -.*$/', '', $line['title']);
			$url = $line['onDemandUrl'];
			$width = $line['originalDetails']['width'];
			$height = $line['originalDetails']['height'];

			if (in_string($line['title'], 'Full')) {
				fputs($fp1, join("\t", [$pid, $url . "?height=603&width=379", $name, $width, $height]) . "\n");
			} else if (in_string($line['title'], 'Crop')) {
				fputs($fp2, join("\t", [$pid, $url . "?height=300&width=225", $name, $width, $height]) . "\n");
			}
		}
		$pids = [];
		sleep(3);
	}
}

fclose($fp1);
fclose($fp2);
