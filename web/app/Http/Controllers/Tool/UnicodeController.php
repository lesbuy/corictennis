<?php

namespace App\Http\Controllers\Tool;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Config;

class UnicodeController extends Controller
{
    //

	public function query($prefix = "0", $suffix = null) {

		if ($prefix == "region") {
			$ret = [];
			$file = join("/", [Config::get('const.root'), 'share', 'dict', 'all_nations']);
			$fp = fopen($file, "r");
			while ($line = trim(fgets($fp))) {
				$arr = explode("\t", $line);
				if ($arr[4] < 3) {
					$iso3 = $arr[1];
					$ioc = $arr[3];
					$eng = $arr[5];
					$chn = $arr[7];
					$valid = $arr[4];
					$ENG = $arr[6];
					$CHN = $arr[8];
					$ret[$arr[0]] = [$iso3, $ioc, $eng, $chn, $valid, $ENG, $CHN];
				}
			}
			fclose($fp);

			return view('tool.unicode_region', [
				'ret' => $ret,
			]);
		}

		if ($prefix == "ioc") {
			$ret = [];
			$file = join("/", [Config::get('const.root'), 'share', 'dict', 'flag_bit_map']);
			$fp = fopen($file, "r");
			while ($line = trim(fgets($fp))) {
				$arr = explode("\t", $line);
				$ret[$arr[0]][$arr[1]] = $arr[2];
			}
			fclose($fp);

			return view('tool.unicode_ioc', [
				'ret' => $ret,
			]);
		}

		$prefix = strtoupper($prefix);
		if ($suffix === null) {
			$row = null; $col = null;
		} else {
			$suffix = strtoupper($suffix);
			if (strlen($suffix) == 1) $suffix = "0" . $suffix;
			$row = ord(substr($suffix, 0, 1));
			$col = ord(substr($suffix, 1, 1));
		}

		if ($prefix == "0" || $prefix == "00" || $prefix == "000") {
			$prev = null;
		} else {
			$prev = base_convert((base_convert($prefix, 16, 10) - 1), 10, 16);
		}

		if ($prefix == "10ff") {
			$next = null;
		} else {
			$next = base_convert((base_convert($prefix, 16, 10) + 1), 10, 16);
		}

		if (strlen($prefix) == 1) {
			$plain = "0";
			$x = "0";
			$y = $prefix;
		} else if (strlen($prefix) == 2) {
			$plain = "0";
			$x = substr($prefix, 0, 1);
			$y = substr($prefix, 1, 1);
		} else if (strlen($prefix) == 3) {
			$plain = substr($prefix, 0, 1);
			$x = substr($prefix, 1, 1);
			$y = substr($prefix, 2, 1);
		} else {
			$plain = substr($prefix, 0, 2);
			$x = substr($prefix, 2, 1);
			$y = substr($prefix, 3, 1);
		}

		$plain = base_convert($plain, 16, 10);
		$x = base_convert($x, 16, 10);
		$y = base_convert($y, 16, 10);

		return view('tool.unicode', [
			'prefix' => $prefix,
			'row' => $row,
			'col' => $col,
			'prev' => $prev,
			'next' => $next,
			'plain' => $plain,
			'x' => $x,
			'y' => $y,
		]);
	}
}
