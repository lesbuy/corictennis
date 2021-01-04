<?php

use App\Http\Middleware\CheckAccount;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/*
Route::get('/', function () {
    return view('welcome');
});
*/

Auth::routes();

//Route::get('/', 'HomeController@index')->name('home');
//Route::get('/', function () {
//	return redirect()->route('home', ['lang' => 'zh']);
//});

Route::get('/blank', function () {
	return view('blank');
});

Route::post('/foo/foo/foo/foo', function () {
    return date("Y-m-d H:i:s");
});

Route::get('/test-sql', function() {
	DB::enableQueryLog();
	$ones = DB::table('panel_searches')->select('pid', 'first', 'last', 'ioc', DB::raw('count(pid) as ct'))->where('created_at', '>=', '2018-12-27')->groupBy(['pid', 'first', 'last', 'ioc'])->orderBy('ct')->get();
	return response()->json(DB::getQueryLog());
});

Route::get('/testtest', 'TestController@test');

Route::get('/resume', function () {
	return view('resume');
});

Route::get('/welcome', 'HomeController@welcome');

Route::view('/uuid', 'stat.uuid');

Route::get('now', function () {
    return date("Y-m-d H:i:s");
});

Route::get('test', function () {
	return view('test.test');
});

Route::group(['domain' => 'www.rank-tennis.com'], function () {

	Route::group(['namespace' => 'Admin', 'prefix' => 'admin'], function() {
		Route::get('shortmsg', 'ShortMsgController@index');
		Route::post('shortmsg/query', 'ShortMsgController@query');
		Route::post('shortmsg/save', 'ShortMsgController@save');
		Route::get('headshot/change/{sex}', 'HeadShotChangeController@change');
		Route::get('update/topplayer', 'TopPlayerController@update_top_player');
		Route::get('portrait/show/{gender}/{size}/{minrank}/{maxrank}', 'PortraitController@show');
		Route::get('iqiyi/list', 'IqiyiController@list');

		Route::group(['prefix' => 'money', 'middleware' => ['isadmin']], function() {
			Route::get('/', 'MoneyController@index');
			Route::post('query', 'MoneyController@query');
			Route::post('select/{column}', 'MoneyController@select');
			Route::get('sum/{account}', 'MoneyController@sum');
			Route::get('month', 'MoneyController@month');
			Route::post('save', 'MoneyController@save');
			Route::post('delete', 'MoneyController@delete');
			Route::get('patch', 'MoneyController@patch_save');
		});

		Route::group(['prefix' => 'diary', 'middleware' => ['isme']], function() {
			Route::get('/', function () {
				return view('admin.diary');
			});
			Route::post('query', 'DiaryController@query');
			Route::post('query/{year}/{month}', 'DiaryController@query');
			Route::get('patch', 'DiaryController@patch_save');
			Route::post('save', 'DiaryController@save');
		});

		Route::group(['prefix' => 'name', 'middleware' => ['isme']], function() {
			Route::get('update', 'NameController@update_name');
		});

		Route::get('calcbet', function() {
			return view('admin.calcbet');
		});

		Route::get('pre-week-predict/{gender}/{event}/{year}', 'CorporateController@PreWeekPredict');
	});

	Route::group(['namespace' => 'Tool', 'prefix' => 'tool'], function() {
		Route::get('unicode', 'UnicodeController@query');
		Route::get('unicode/{prefix}', 'UnicodeController@query')->where('prefix', '^([0-9A-Fa-f]{1,3}|10[0-9A-Fa-f]{2}|region|ioc)$');
		Route::get('unicode/{prefix}/{suffix}', 'UnicodeController@query')->where('prefix', '^[0-9A-Fa-f]{1,3}$')->where('suffix', '^[0-9A-Fa-f]{1,2}$');
	});


	Route::get('articles', 'ArticleController@show');
	Route::get('/articles/{id}', 'ArticleController@read');

	// coric.top

	Route::group(['namespace' => 'Auth'], function() {
		Route::get('login/corictop', function () {
			return view('auth.login');
		});
		Route::get('login/{method}', 'LoginController@redirectToProvider');
		Route::get('login/{method}/callback', 'LoginController@handleProviderCallback');
		Route::get('login/private/{id}', 'LoginController@special')->where('id', '^[0-9]+$');

		Route::get('auth/weixin/coric', 'WeixinController@valid');
	//	Route::post('auth/weixin/coric', 'WeixinController@message');

	//	Route::get('auth/weixin/slazenger', 'SlazengerController@valid');
		Route::post('auth/weixin/slazenger', 'SlazengerController@message');
	});

	Route::group(['namespace' => 'Home'], function() {
		Route::get('{lang}/home', 'HomeController@home')->name('home');
		Route::post('{lang}/player/{gender}/{id}', 'HomeController@panel')->where('gender', '^[aw]t[pa]$')->where('id', '^[0-9]{5,6}|[A-Z][0-9A-Z]{3}$');
		Route::post('{lang}/player/{gender}/{id}/stat/{year}', 'HomeController@stat')->where('gender', '^[aw]t[pa]$')->where('id', '^[0-9]{5,6}|[A-Z][0-9A-Z]{3}$')->where('year', '^0|[0-9]{4}$');
		Route::post('{lang}/player/{gender}/{id}/match/{sd}/{filter}', 'HomeController@match')->where('gender', '^[aw]t[pa]$')->where('id', '^[0-9]{5,6}|[A-Z][0-9A-Z]{3}$')->where('sd', '^s|d$');

		Route::get('{lang}/history/evolv', 'EvolutionController@index');
		Route::get('{lang}/history/evolv/query/{gender}/{topn}/{start}/{end}/{freq}', 'EvolutionController@content');

		Route::group(['prefix' => 'admin', 'middleware' => ['isadmin']], function() {
			Route::get('update/breakthrough', 'HomeController@add_bt');
			Route::post('update/breakthrough/post', 'HomeController@add_bt_post');
		});

	});

	Route::group(['namespace' => 'Help'], function() {
		Route::get('{lang}/work-as-one', 'LangSetController@index');
		Route::get('{lang}/work-as-one/{lang2}', 'LangSetController@show');
		Route::post('{lang}/work-as-one/{lang2}/submit', 'LangSetController@submit');

		Route::get('{lang}/test', 'LangSetController@test');

		Route::get('{lang}/msgboard', function($lang) {
			App::setLocale($lang);
			return view('help.msgboard');
		});
		Route::post('{lang}/msgboard/submit', 'ShortMsgController@save');
		Route::get('{lang}/msgboard/show', 'ShortMsgController@show');

		Route::get('{lang}/help/translation/name', function($lang) {
			App::setLocale($lang);
			return view('help.name_translation_explanation');
		});
		Route::get('{lang}/help/rule/dcpk', function($lang) {
			App::setLocale($lang);
			return view('help.dcpk_rule');
		});
	});

	Route::group(['namespace' => 'Draw'], function() {
		Route::get('{lang}/draw/{eid}/{year}', 'DrawController@index')->where(['year' => '^196[8-9]|19[7-9][0-9]|200[0-9]|201[0-9]|202[0-9]$', 'eid' => '^((?!list).)*$'])->name('draw');
		Route::get('{lang}/draw/GS', function ($lang) {
			return redirect()->route('draw', ['lang' => $lang, 'eid' => 'UO', 'year' => '2017']);
		});
		Route::get('{lang}/draw/list', 'ListController@index');
		Route::get('{lang}/calendar/{year}', 'ListController@byyear');
		Route::post('{lang}/draw/{eid}/{year}', 'DrawController@query')->where(['year' => '^196[8-9]|19[7-9][0-9]|200[0-9]|201[0-9]|202[0-9]$', 'eid' => '^((?!list).)*$']);
		Route::post('{lang}/draw/{eid}/{year}/road/{sextip}/{pid}', 'DrawController@road');

		Route::group(['prefix' => 'admin', 'middleware' => ['isadmin']], function() {
			Route::get('update/winner', 'WinnerController@update_winner');
			Route::get('update/trophy', 'WinnerController@update_trophy');
			Route::post('update/trophy/submit', 'WinnerController@save_trophy');
		});
	});

	Route::group(['namespace' => 'Dcpk'], function() {
		Route::get('{lang}/guess', 'GuessController@guess');
		Route::get('{lang}/guess/{date}', 'GuessController@guess')->where(['date' => '^20[1-2][0-9]-[0-9]{2}-[0-9]{2}']);
		Route::get('{lang}/guess/{date}/{userid}', 'GuessController@guess')->where(['date' => '^20[1-2][0-9]-[0-9]{2}-[0-9]{2}$', 'userid' => '^\d+$']);
		Route::post('{lang}/guess/submit', 'GuessController@submit');

	//	Route::get('/admin/update/dcpkwinner/{year}', 'GuessController@add_new_start_of_year');
	//	Route::get('/admin/update/dcpkwinner/{year}', 'GuessController@add_new_year');

		Route::get('{lang}/guess/rank/{sd}/{gran}/{date}', 'RankController@index')->name('guess');
		Route::post('{lang}/guess/rank/{sd}/{gran}/{date}/query', 'RankController@query');

		Route::get('{lang}/guess/rank/itgl/day', function ($lang) {
			return redirect()->route('guess', ['lang' => $lang, 'sd' => 'itgl', 'gran' => 'day', 'date' => date('Y-m-d', time(NULL)), ]);
		});
		Route::get('{lang}/guess/rank/itgl/week', function ($lang) {
			return redirect()->route('guess', ['lang' => $lang, 'sd' => 'itgl', 'gran' => 'week', 'date' => date('Y_W', time(NULL)), ]);
		});

		Route::get('{lang}/guess/select/{date}', 'GuessController@select')->where(['date' => '^20[1-2][0-9]-[0-9]{2}-[0-9]{2}'])->name('dcpkSelect');
		Route::get('{lang}/guess/select', function ($lang) {
			return redirect()->route('dcpkSelect', ['lang' => $lang, 'date' => date('Y-m-d', strtotime("+10 hours"))]);
		});
		Route::post('{lang}/guess/select/{date}/save', 'GuessController@save')->where(['date' => '^20[1-2][0-9]-[0-9]{2}-[0-9]{2}']);
		Route::post('{lang}/guess/select/{date}/saveOne', 'GuessController@saveOne')->where(['date' => '^20[1-2][0-9]-[0-9]{2}-[0-9]{2}']);
		Route::post('{lang}/guess/select/{date}/delete', 'GuessController@delete')->where(['date' => '^20[1-2][0-9]-[0-9]{2}-[0-9]{2}']);
		Route::post('{lang}/guess/select/{date}/abandon', 'GuessController@abandon')->where(['date' => '^20[1-2][0-9]-[0-9]{2}-[0-9]{2}']);

		Route::get('{lang}/guess/calendar/{year}', 'CalendarController@index')->name('guessCalendar');
		Route::post('{lang}/guess/calendar/query', 'CalendarController@query');
		Route::get('{lang}/guess/calendar', function ($lang) {
			return redirect()->route('guessCalendar', ['lang' => $lang, 'year' => 2019]);
		});

		Route::get('{lang}/guess/rule', function ($lang) {
			App::setLocale($lang);
			return view('dcpk.rule', ['title' => __('dcpk.title.rule'),]);
		});

		Route::get('{lang}/guess/sign/{year}/{week}', 'SignController@index')->name('guessSign');
		Route::get('{lang}/guess/sign', function ($lang) {
			return redirect()->route('guessSign', ['lang' => $lang, 'year' => 2019, 'week' => 46]);
		});
		Route::post('{lang}/guess/sign/query', 'SignController@query');
		Route::post('{lang}/guess/sign/submit', 'SignController@save');
		Route::get('{lang}/guess/sign/mend/{year}/{week}', 'SignController@mend_point');
	});

	Route::group(['namespace' => 'Rank'], function() {
		Route::post('{lang}/rank/{type}/{sd}/custom/{st}/{et}/query', 'CustomController@query');
		Route::get('{lang}/rank/custom', 'CustomController@index');

		Route::get('{lang}/rank/{type}/{sd}/{period}', 'RankController@index')->name('rank');
		Route::post('{lang}/rank/{type}/{sd}/{period}/query', 'RankController@new_query');

		Route::get('{lang}/entrylist/{type}', 'ScheduleController@index');
		Route::post('{lang}/entrylist/{type}/query', 'ScheduleController@query');

		Route::get('{lang}/profile/{type}', 'ProfileController@index');
		Route::post('{lang}/profile/{type}/query', 'ProfileController@query');

		Route::get('{lang}/rank/{type}/52weeks', function ($lang, $type) {
			return redirect()->route('rank', ['lang' => $lang, 'type' => $type, 'sd' => 's', 'period' => 'year']);
		});
		Route::get('rank/{type}/52weeks', function ($type) {
			return redirect()->route('rank', ['lang' => 'zh', 'type' => $type, 'sd' => 's', 'period' => 'year']);
		});
		Route::get('rank/{type}/champ', function ($type) {
			return redirect()->route('rank', ['lang' => 'zh', 'type' => $type, 'sd' => 's', 'period' => 'race']);
		});
		Route::get('rank/{type}/52weeks_d', function ($type) {
			return redirect()->route('rank', ['lang' => 'zh', 'type' => $type, 'sd' => 'd', 'period' => 'year']);
		});
	});

	Route::group(['namespace' => 'Select'], function() {
		Route::get('{lang}/select/{type}/{sd}/{period}/byyear', 'SelectController@byyear');
		Route::get('{lang}/select/{type}/{sd}/{period}/bycountry', 'SelectController@bycountry');
		Route::get('{lang}/select/{type}/{sd}/{period}/bytour', 'SelectController@bytour');
		Route::get('{lang}/select/{type}/byyear', 'SelectController@byyear');
		Route::get('{lang}/select/{type}/bycountry', 'SelectController@bycountry');
		Route::post('select/byname', 'SelectController@byname');
		Route::post('select/bynation', 'SelectController@bynation');
		Route::post('select/byseason', 'SelectController@byseason');
	});

	Route::group(['namespace' => 'History'], function () {
		Route::get('{lang}/history/activity', 'ActivityController@index');
		Route::post('{lang}/history/activity/query', 'ActivityController@new_query');

		Route::get('{lang}/history/official', 'OfficialController@index');
		Route::post('{lang}/history/official/query', 'OfficialController@query');

		Route::get('{lang}/history/topn', 'TopNController@index');
		Route::post('{lang}/history/topn/query', 'TopNController@query');

		Route::get('{lang}/history/gst1', 'GSRecordController@index');
		Route::post('{lang}/history/gs/gender/{sex}/{round}', 'GSRecordController@gender');
		Route::post('{lang}/history/t1/gender/{sex}/{round}', 'GSRecordController@t1gender');

		Route::get('{lang}/history/query/{gender}', 'CustomController@query');
	});

	Route::group(['namespace' => 'Breakdown'], function() {
		Route::post('{lang}/breakdown/{type}/{sd}/{period}/query', 'BreakdownQueryController@query');
	});

	Route::group(['namespace' => 'H2H'], function() {
		Route::get('{lang}/h2h', 'H2HController@index')->name('h2h');
		Route::post('{lang}/h2h/query', 'H2HController@query');

		Route::get('h2h', function () {
			return redirect()->route('h2h', ['lang' => 'zh']);
		});
	});

	Route::group(['namespace' => 'Result'], function() {
		Route::get('{lang}/result/{date}', 'ResultController@date')->where('date', '^20[1-2][0-9]-[0-1][0-9]-[0-3][0-9]$')->name('result');
		Route::get('{lang}/newresult/{date}', 'ResultController@date');
		Route::get('{lang}/result', function($lang) {
			return redirect()->route('result', ['lang' => $lang, 'date' => date('Y-m-d', time() - 3600 * 9)]);
		});
		Route::get('{lang}/live', 'ResultController@live')->name('live');
		Route::post('{lang}/result/{date}', 'ResultController@eid')->where('date', '^20[1-2][0-9]-[0-1][0-9]-[0-3][0-9]$');
		Route::get('{lang}/result/live/{ts}', 'ResultController@get_live');
		Route::get('{lang}/result/wslive', 'ResultController@get_live');

		Route::get('{lang}/oop', 'ResultController@oop_date');
		Route::get('{lang}/oop/{date}', 'ResultController@oop_date')->where('date', '^20[1-2][0-9]-[0-1][0-9]-[0-3][0-9]$');

		Route::post('{lang}/oop/{date}/{unixtime}', 'ResultController@unixdate')->where('date', '^20[1-2][0-9]-[0-1][0-9]-[0-3][0-9]$')->where('unixtime', '^[0-9]{10}$');
		Route::post('{lang}/oop/{date}/{unixtime}/{eid}', 'ResultController@unixdate_event')->where('date', '^20[1-2][0-9]-[0-1][0-9]-[0-3][0-9]$')->where('unixtime', '^[0-9]{10}$');

		Route::get('{lang}/schedule/{eid}/{year}', 'ResultController@ByEid')->where(['year' => '^196[8-9]|19[7-9][0-9]|200[0-9]|201[0-9]|202[0-9]$'])->name('schedule');


		Route::get('live', function () {
			return redirect()->route('live', ['lang' => 'zh']);
		});

		Route::get('score/{date}', function ($date) {
			return redirect()->route('result', ['lang' => 'zh', 'date' => $date]);
		});
		Route::get('score', function() {
			return redirect()->route('result', ['lang' => 'zh', 'date' => date('Y-m-d', time() - 28800)]);
		});
		Route::get('completed', function() {
			return redirect()->route('result', ['lang' => 'zh', 'date' => date('Y-m-d', time() - 28800)]);
		});


		Route::get('{lang}/ctalive/{date}', 'CtaController@date')->where('date', '^20[1-2][0-9]-[0-1][0-9]-[0-3][0-9]$')->name('ctalive');
		Route::get('{lang}/ctalive', function($lang) {
			return redirect()->route('ctalive', ['lang' => $lang, 'date' => date('Y-m-d', time() - 3600 * 11)]);
		});
		Route::post('{lang}/ctalive/{date}', 'CtaController@eid')->where('date', '^20[1-2][0-9]-[0-1][0-9]-[0-3][0-9]$');
		Route::get('{lang}/ctalive/live/{ts}', 'CtaController@get_live');

		Route::group(['prefix' => 'admin', 'middleware' => ['isadmin']], function() {
			Route::get('hl/list/{year}', 'ResultController@hl_list')->name('hllist');
			Route::get('hl/detail/{year}/{date}/{eid}/{city}', 'ResultController@hl_detail');
			Route::post('hl/save', 'ResultController@hl_save');
		});
	});

	Route::group(['namespace' => 'Dc'], function() {
		Route::get('{lang}/dc/{eid}/{year}/{sextip}', 'DcController@index');
		Route::post('{lang}/dc/{eid}/{year}/{sextip}/save', 'DcController@save');
		Route::get('{lang}/dc/{eid}/{year}/{sextip}/calcrank', 'DcController@calc_rank');
		Route::get('{lang}/dc/{eid}/{year}/{sextip}/rank', 'RankController@index');
		Route::post('{lang}/dc/{eid}/{year}/{sextip}/rank/query', 'RankController@query');

		Route::get('{lang}/dc/{eid}/{year}/{sextip}/distribution', 'DcController@get_distribution');

		Route::get('{lang}/dc/{eid}/{year}/{sextip}/{userid}', 'DcController@index')->where('userid', '^[0-9]+$');

	});

	Route::group(['namespace' => 'Stat'], function() {
		Route::post('{lang}/stat/query', 'StatController@query');
		Route::post('{lang}/pbp/query', 'PbPController@query');
	});


	Route::get('{lang}', function ($lang) {
		if (in_array($lang, ['zh', 'ja']))
			return redirect()->route('home', ['lang' => $lang]);
		else
			return redirect()->route('home', ['lang' => 'en']);
	});

	Route::get('index.php', function () {
		return redirect()->route('home', ['lang' => 'zh']);
	});
	Route::get('/', function () {
		return redirect()->route('home', ['lang' => 'zh']);
	});

});

