<?php

namespace App\Http\Controllers\History;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Config;

class CustomController extends Controller
{
    //
	private $all_matches;
	private $key2idx;


	public function __construct() {
		
		foreach (Config::get('const.schema_activity_match') as $k => $v) {
			$this->key2idx[$v] = $k;
		}
	}

	public function query($lang, $gender) {

		if (!in_array($gender, ['atp', 'wta'])) return [];

		$conditions = [
			'id' => ['CG80'],
			'level' => ['GS', '1000', 'T1', 'PM', 'P5'],
			'sd' => ['S'],
			'games' => [['1', 'w']],
			'winorlose' => ['L'],
		];

		if (array_key_exists('id', $conditions)) {

			$file = join(" ", array_map(function ($d) use ($gender) {
				return join("/", [Config::get('const.root'), 'store', 'activity', $gender, $d]);
			}, $conditions['id']));

		} else {

			$file = join("/", [Config::get('const.root'), 'store', 'activity', $gender, '*']);

		}

		$cmd = "cat $file | awk -F\"\\t\" '$19 != 100' | sort -t$'\\t' -k1,1 -k4,4 -k6,6 -k7,7 -k19g,19";
		unset($r); exec($cmd, $r);
		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				$this->all_matches[] = $arr;
			}
		}

		foreach ($conditions as $k => $v) {

			if ($k == 'id') continue;
			else self::process($k, $v);

		}

		echo json_encode($this->all_matches);
	}

	private function process($key, $values) {

		$idx = $this->key2idx[$key];

		if ($idx === false) return false;

		if (in_array($key, ['ioc', 'year', 'tourname', 'level', 'sd', 'round', 'winorlose', 'oppoid', 'opponation'])) {

			$this->all_matches = array_filter($this->all_matches, function ($d) use ($values, $idx) {
				return in_array($d[$idx], $values);
			});
		} else if ($key == "games") {

			
		}
	}

}
