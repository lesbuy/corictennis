<?php

namespace App\Http\Controllers\Dcpk;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App;
use App\Scripts\Ssp;
use DB;

class CalendarController extends Controller
{

	protected $primaryKey = "id";
	protected $table;
	protected $sql_details;
	protected $columns;

	public function index($lang, $year) {
		App::setLocale($lang);
		return view('dcpk.calendar', [
			'year' => $year,
			'pageTitle' => $year . ' ' . __('dcpk.title.schedule'),
			'title' => $year . ' ' . __('dcpk.title.schedule'),
			'pagetype1' => 'dcpk',
			'pagetype2' => 'calendar',
		]);
	}

	public function query(Request $req, $lang) {

		App::setLocale($lang);

		$this->table = 'dcpk_winners';

		$this->sql_details = [
			'user' => env('DB_USERNAME'),
			'pass' => env('DB_PASSWORD'),
			'db' => env('DB_DATABASE'),
			'host' => env('DB_HOST') . ':' . env('DB_PORT'),
		];

		$this->columns = [
			[ 'db' => 'year', 'dt' => 'year' ],
			[ 'db' => 'week', 'dt' => 'week' ],
			[ 'db' => 'start', 'dt' => 'start', 'formatter' => function ($d, $row) {return date('Y-m-d', strtotime($d)) . " ~ " . date('Y-m-d', strtotime($row['end']));} ],
			[ 'db' => 'end', 'dt' => 'end' ],
			[ 'db' => 'tour', 'dt' => 'tour', 'formatter' => function ($d, $row) {return translate_tour($d);} ],
			[ 'db' => 'level', 'dt' => 'level' ],
			[ 'db' => 'itglwin', 'dt' => 'itgl', 'formatter' => function ($d, $row) {return "<a href=\"" . url(App::getLocale() . '/guess/rank/itgl/week/' . $row['year'] . "_" . ($row['week'] < 10 ? 0 . $row['week'] : $row['week'])) . "\" >" . $d . "</a>";} ],
			[ 'db' => 'dcpkwin', 'dt' => 'dcpk', 'formatter' => function ($d, $row) {return "<a href=\"" . url(App::getLocale() . '/draw/D' . ($row['week'] < 10 ? 0 . $row['week'] : $row['week']) . '/' . $row['year']) . "\" >" . $d . "</a>";} ],
		];


		return json_encode(
			Ssp::simple($req->all(), $this->sql_details, $this->table, $this->primaryKey, $this->columns)
		);
	}

	public function add_calendar($lang, $year) {


	}
}
