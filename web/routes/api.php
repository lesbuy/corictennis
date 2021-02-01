<?php

use Illuminate\Http\Request;
use App\Http\Middleware\CheckAccount;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/

Auth::routes();

Route::options('/{all}', function(Request $request) {
    $origin = $request->header('ORIGIN', '*');
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 2592000");
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
    header('Access-Control-Allow-Headers: Origin, Access-Control-Request-Headers, SERVER_NAME, Access-Control-Allow-Headers, cache-control, token, X-Requested-With, Content-Type, Accept, Connection, User-Agent, Cookie, X-CSRFtoken');
})->where(['all' => '([a-zA-Z0-9-]|/)+']);

Route::middleware('cors')->group(function () {
	Route::group(['namespace' => 'Api'], function() {

		Route::get('match', 'ApiController@ByMatchid');
		Route::get('match_pbp', 'ApiController@PbpByMatchid');

		Route::post('zh/v1/admin/fin/select/{column}', 'MoneyController@select');
		Route::post('zh/v1/admin/fin/select', 'MoneyController@select_all');
		Route::post('zh/v1/admin/fin/save', 'MoneyController@save');
		Route::post('zh/v1/admin/fin/query', 'MoneyController@query');
		Route::post('zh/v1/admin/fin/del', 'MoneyController@delete');
		Route::post('zh/v1/admin/fin/sum/{account}', 'MoneyController@sum');
		Route::post('zh/v1/admin/fin/sum', 'MoneyController@sum_all');
		Route::post('zh/v1/admin/fin/modify', 'MoneyController@modify');
		Route::post('zh/v1/admin/fin/patch_edit', 'MoneyController@patch_edit');

		Route::get('{lang}/v1/menu', 'FrameController@menu');
		Route::get('{lang}/user', 'FrameController@user');
		Route::get('{lang}/result/{date}', 'ResultController@date')->where('date', '^20[1-2][0-9]-[0-1][0-9]-[0-3][0-9]$');
		Route::get('{lang}/result/{date}/tz={tz}', 'ResultController@date')->where('date', '^20[1-2][0-9]-[0-1][0-9]-[0-3][0-9]$');
		Route::get('{lang}/result/{date}/joint_eid={joint_eid}', 'ResultController@eid')->where('date', '^20[1-2][0-9]-[0-1][0-9]-[0-3][0-9]$');
		Route::get('{lang}/result/{date}/eid={eid}/tz={tz}', 'ResultController@eid')->where('date', '^20[1-2][0-9]-[0-1][0-9]-[0-3][0-9]$');
		Route::get('{lang}/result/live/{ts}', 'ResultController@get_live');

		Route::get('{lang}/matchdetail/{date}/{eid}/{matchid}', 'DetailController@index')->where('date', '^20[1-2][0-9]-[0-1][0-9]-[0-3][0-9]$');

		Route::get('{lang}/select/{gender}/name/{str}', 'SelectController@byname');

		Route::get('{lang}/h2h/query/{gender}/{sd}/{homes}/{aways}', 'H2HController@query')->where('gender', '^(atp|wta)$')->where('sd', '^(s|d)$');

		Route::post('{lang}/draw/{eid}/{year}', 'DrawController@query')->where(['year' => '^196[8-9]|19[7-9][0-9]|200[0-9]|201[0-9]|202[0-9]$', 'eid' => '^((?!list).)*$']);

		Route::group(['prefix' => '{lang}/i18n'], function () {
			Route::get('', 'I18nController@fetch');
			Route::get('null', 'I18nController@fetch_null');
			Route::get('{path}', 'I18nController@fetch');
			Route::get('{path}/null', 'I18nController@fetch_null');
		});

		Route::get('{lang}/stat/{home}/{away}', 'StatController@query');
		Route::get('{lang}/pbp/{home}/{away}', 'PbPController@query');

		Route::get('{lang}/calendar/{year}', 'CalendarController@getCalendarByYear');
	});
});
