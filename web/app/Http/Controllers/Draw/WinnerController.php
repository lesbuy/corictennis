<?php

namespace App\Http\Controllers\Draw;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TourWinner;
use Config;

class WinnerController extends Controller
{

	public function update_winner() {

		$file = join('/', [Config::get('const.root'), 'share', 'down_result', 'tour_winners']);
		$schema = ['date', 'sextip', 'eid', 'city', 'winid', 'win'];

		$cmd = "cat $file";
		unset($r); exec($cmd, $r);
		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				if (count($arr) == 6) {
					$one = TourWinner::updateOrCreate(
						['date' => $arr[0], 'sextip' => $arr[1], 'eid' => $arr[2]],
						['city' => $arr[3], 'winid' => $arr[4], 'win' => $arr[5]]
					);
				}
			}
		}

	}

	public function update_trophy() {

		$ones = TourWinner::whereYear('date', '>=', 2018)->orderBy('date', 'desc')->get();
		$winners = [];
		foreach ($ones as $one) {
			$winners[] = [
				'id' => $one->id,
				'date' => $one->date, 
				'sextip' => $one->sextip, 
				'eid' => $one->eid, 
				'city' => $one->city, 
				'win' => $one->win,	
				'ori' => $one->ori,
				'pos' => intval($one->pos),
			];
		}
		return view('admin.trophy', ['winners' => $winners]);

	}

	public function save_trophy(Request $req) {

		$all = $req->all();
		$sum = 0;

		foreach ($all as $k => $v) {
			if (strpos($k, 'change') !== false && $v == 1) {
				$id = substr($k, 6);
				$ori = $req->input('image' . $id);
				$pos = $req->input('pos' . $id);

				$one = TourWinner::find($id);
				$one->ori = $ori;
				$one->big = NULL;
				$one->small = NULL;
				$one->pos = $pos;
				if ($one->save()) {
					$sum++;
				}
			}
		}
		return $sum . ' records updated';
	}
}
