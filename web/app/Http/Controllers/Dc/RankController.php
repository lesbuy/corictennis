<?php

namespace App\Http\Controllers\Dc;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Scripts\Ssp;
use App;
use Config;
use App\Models\DcRank;

class RankController extends Controller
{

	protected $root = '/home/ubuntu';

	protected $primaryKey = "id";
	protected $table;
	protected $sql_details;
	protected $columns;

	public function index($lang, $eid, $year, $sextip) {

		App::setLocale($lang);
		$city = "";
		self::get_tour_info($city, $year, $eid);

		return view('dc.rank', [
			'eid' => $eid, 
			'year' => $year, 
			'sextip' => $sextip,
			'city' => $city,
			'title' => join(" ", [__('dc.dcRank'), '-', $year, translate_tour($city), __('draw.selector.' . $sextip)]),
			'pageTitle' => join(" ", [__('dc.dcRank'), '-', $year, translate_tour($city), __('draw.selector.' . $sextip)]),
			'pagetype1' => 'dc',
			'pagetype2' => join("_", ['rank', $year, $eid, $sextip]),
		]);
	}

	public function query(Request $req, $lang, $eid, $year, $sextip) {

		App::setLocale($lang);

		$this->table = 'dc_ranks';

		$this->sql_details = [
			'user' => env('DB_USERNAME'),
			'pass' => env('DB_PASSWORD'),
			'db' => env('DB_DATABASE'),
			'host' => env('DB_HOST') . ':' . env('DB_PORT'),
		];

		$this->columns = [
			[ 'db' => 'year', 'dt' => 'year' ],
			[ 'db' => 'eid', 'dt' => 'eid' ],
			[ 'db' => 'sextip', 'dt' => 'sextip' ],
			[ 'db' => 'username', 'dt' => 'username', 'formatter' => function ($d, $row) {return '<img class=login_img src="' . url(env('CDN') . '/images/login/' . Config::get('const.TYPE2STRING.' . $row['method']) . '.png') . '" />' . $d;} ],
			[ 'db' => 'rank', 'dt' => 'rank' ],
			[ 'db' => 'score', 'dt' => 'score' ],
			[ 'db' => 'matches', 'dt' => 'matches' ],
			[ 'db' => 'userid', 'dt' => 'link', 'formatter' => function ($d, $row) use($eid, $year, $sextip) {return url(join("/", [App::getLocale(), 'dc', $eid, $year, $sextip, $d]));} ],

			[ 'db' => 'userid', 'dt' => 'userid' ],
			[ 'db' => 'method', 'dt' => 'method' ],
		];


		return json_encode(
			Ssp::simple($req->all(), $this->sql_details, $this->table, $this->primaryKey, $this->columns)
		);

	}

	protected function get_tour_info(&$city, $year, $eid) {

		$file = join('/', [$this->root, 'store', 'calendar', $year, '*']);
		$cmd = "grep $'\t" . $eid . "\t' $file | sort -k5gr,5 | head -1";
		unset($r); exec($cmd, $r);

		$arr = explode("\t", @$r[0]);
		$city = @$arr[9];

	}

}
