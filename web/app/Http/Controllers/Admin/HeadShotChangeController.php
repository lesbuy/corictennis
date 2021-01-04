<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Config;

class HeadShotChangeController extends Controller
{
    //
	public function change($sex){

		$cmd = "cat " . join("/", [Config::get('const.root'), $sex, 'player_headshot']);
		unset($r); exec($cmd, $r);

		if ($r) {
			foreach ($r as $line) {
				$arr = explode("\t", $line);
				$name[$arr[0]] = $arr[1];
				$ori[$arr[0]] = $arr[2];
			}
		}

		$cmd = "cat " . join("/", [Config::get('const.root'), $sex, $sex . '_list']);
		unset($r); exec($cmd, $r);

		if ($r) {
			foreach ($r as $line) {
				$arr = explode("\t", $line);
				$id = $arr[0];
				$name[$id] = $arr[1];
				$des[$id] = $arr[2];
			}
		}

		$ret = [];

		ksort($name);
		foreach ($name as $k => $v) {
			if (isset($ori[$k], $des[$k]) && strpos($des[$k], $ori[$k]) === false)
				$ret[] = [$k, $v, $ori[$k], $des[$k]];
		}

		return view('admin.head_change', ['ret' => $ret, 'sex' => $sex]);

	}
	

}
