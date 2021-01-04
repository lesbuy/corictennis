<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Scripts\Ssp;
use App\Models\Diary;

class DiaryController extends Controller
{
    //

	public function patch_save() {

		$success_count = 0;
		$fail_count = 0;

		$file = env('ROOT') . "/diary";
		
		$fp = fopen($file, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			$date = $arr[0];
			$content = !isset($arr[1]) ? "" : $arr[1];
			$weekday = date('N', strtotime($date));

			$one = new Diary;
			$one->date = $date;
			$one->weekday = $weekday;
			$one->content = $content;
			if ($one->save()) {
				++$success_count;
			} else {
				++$fail_count;
			}
		}

		echo $success_count . " added. " . $fail_count . " failed.\n";
	}

	public function query(Request $req, $year = null, $month = null) {

		$table = "diaries";
		$primaryKey = 'id';

		$max_date_unix = strtotime(Diary::max('date'));
		$today_unix = strtotime(date('Y-m-d', time()));
		if ($max_date_unix < $today_unix) {
			for ($i = $max_date_unix + 86400; $i <= $today_unix; $i += 86400) {
				$one = new Diary;
				$one->date = date('Y-m-d', $i);
				$one->weekday = date('N', $i);
				$one->content = "";
				$one->save();
			}
		}

		$sql_details = [
			'user' => env('DB_USERNAME'),
			'pass' => env('DB_PASSWORD'),
			'db' => env('DB_DATABASE'),
			'host' => env('DB_HOST') . ':' . env('DB_PORT'),
		];

		$columns = [
			[ 'db' => 'id', 'dt' => 'id' ],
			[ 'db' => 'date', 'dt' => 'date', 'formatter' => function ($d, $row) {
				$arr = explode("-", $d);
				return $arr[0] . '<br>' . $arr[1] . $arr[2] . '/' . $row['weekday'];
			} ],
			[ 'db' => 'weekday', 'dt' => 'weekday' ],
			[ 'db' => 'content', 'dt' => 'content' ], 
		];

		return json_encode(
			Ssp::simple($req->all(), $sql_details, $table, $primaryKey, $columns)
		);
	}

	public function save(Request $req) {
		$id = $req->input('id');
		$content = $req->input('content');
		$one = Diary::find($id);
		$one->content = $content;
		if ($one->save()) {
			return 0;
		} else {
			return -1;
		}
	}

}
