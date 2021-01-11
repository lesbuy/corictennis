<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use Config;

class PortraitController extends Controller
{
    //

	public function show($gender, $size, $minrank, $maxrank) {

		$sd = "s";
		if ($gender == "atpd") {
			$gender = "atp";
			$sd = "d";
		} else if ($gender == "wtad") {
			$gender = "wta";
			$sd = "d";
		}

		if ($size == "big") {
			$path = "portrait";
		} else {
			$path = "headshot";
		}

		$file = join("/", [Config::get('const.root'), 'temp', 'activity', $gender, $path]);
		if (file_exists($file)) {
			$fp = fopen($file, "r");
			while ($line = fgets($fp)) {
				$arr = explode("\t", trim($line));
				$por_new[$arr[0]] = $arr[1];
			}
			fclose($fp);
		}

		$file = join("/", [Config::get('const.root'), 'data', 'rank', $gender, $sd, 'current']);

		$fp = fopen($file, "r"); 
		while ($line = fgets($fp)) {
			$arr = explode("\t", trim($line));
			if (($arr[2] < $minrank) || ($arr[2] > $maxrank)) continue;
			$pid = $arr[0];
			$rank = $arr[2];
			$name = $arr[1];
			$pinfo = Redis::hmget(join("_", [$gender, "profile", $pid]), $path == "portrait" ? 'pt' : 'hs');
			$head = $pinfo[0];

			if ($head) {
				$_por = strpos($head, "http") === false ? url(join("/", ['images', $gender . '_' . $path, $head])) : $head;
			} else {
				$_por = "";
			}
			if (isset($por_new[$pid])) {
				$_por_new = strpos($por_new[$pid], "http") === false ? url(join("/", ['images', $gender . '_' . $path, $por_new[$pid]])) : $por_new[$pid];
			} else {
				$_por_new = "";
			}
			$ret[] = [$rank, $pid, $name, $_por, $_por_new, $_por_new ? $por_new[$pid] : "" ];
		}

//		return json_encode($ret);
		return view('admin.portrait', [
			'ret' => $ret,
		]);
	}
}
