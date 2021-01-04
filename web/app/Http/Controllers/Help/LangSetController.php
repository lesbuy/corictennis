<?php

namespace App\Http\Controllers\Help;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App;
use Config;
use Storage;

class LangSetController extends Controller
{

	public function index($lang) {

		App::setLocale($lang);

		$all_lang = Config::get('const.translate');

		return view('work-as-one.index', ['ret' => $all_lang]);

	}

	public function show($lang, $lang2) {

		App::setLocale($lang2);

		$fallback_lang = 'en';
		$cmd = 'ls ' . resource_path('lang') . '/' . $fallback_lang;
		unset($r); exec($cmd, $r);

		$ret = [];

		foreach ($r as $file) {

			if (!in_array($file, ['courtname.php', 'shortname.php', 'longname.php', 'tourname.php', 'nationname.php', 'dc.php', 'dcpk.php'])) {

				$src = require_once(resource_path('lang') . '/' . $fallback_lang . '/' . $file);
				$file = str_replace(".php", "", $file);

				$ret[$file] = [];
				foreach ($src as $k1 => $v1) {

					$key = $k1;
					if (is_array($v1)) {
						foreach ($v1 as $k2 => $v2) {
							$key = join('.', [$k1, $k2]);
							if (is_array($v2)) {
								foreach ($v2 as $k3 => $v3) {
									$key = join('.', [$k1, $k2, $k3]);
									if (is_array($v3)) {
										foreach ($v3 as $k4 => $v4) {
											$key  = join('.', [$k1, $k2, $k3, $k4]);
											if (is_array($v4)) {
												foreach ($v4 as $k5 => $v5) {
													$key  = join('.', [$k1, $k2, $k3, $k4, $k5]);
													$value = self::get_lang2_translation($file, $key, $v5);
													$ret[$file][$key] = [self::get_bg($file, $key), $v5, $value];
												}
											} else {
												$value = self::get_lang2_translation($file, $key, $v4);
												$ret[$file][$key] = [self::get_bg($file, $key), $v4, $value];
											}
										}
									} else {
										$value = self::get_lang2_translation($file, $key, $v3); 
										$ret[$file][$key] = [self::get_bg($file, $key), $v3, $value];
									}
								}
							} else {
								$value = self::get_lang2_translation($file, $key, $v2); 
								$ret[$file][$key] = [self::get_bg($file, $key), $v2, $value];
							}
						}
					} else {
						$value = self::get_lang2_translation($file, $key, $v1); 
						$ret[$file][$key] = [self::get_bg($file, $key), $v1, $value];
					}
									
				}

			}

		}

		App::setLocale($lang);
		$status = 0;
		
		if (!in_array($lang2, array_keys(Config::get('const.translate')))) {
//			$status = -1;
		}

		return view('work-as-one.show', ['ret' => $ret, 'lang' => App::getLocale(), 'lang2' => $lang2, 'status' => $status]);

	}

	protected function get_lang2_translation($file, $key, $value_eng) {

		$trans = __($file . '.' . $key);
		if ($value_eng == $trans) return '';
		return $trans;

	}

	protected function get_bg($file, $key) {

		if ($file == 'frame') {
			if (strpos($key, 'menu') === 0 || strpos($key, 'datePicker') === 0 || strpos($key, 'level') === 0) {
				return 'cWorkAsOneLimitRow';
			}
		} else if ($file == 'rank') {
			if (strpos($key, 'table.head') === 0 || strpos($key, 'piechart') === 0) {
				return 'cWorkAsOneLimitRow';
			}
		} else if ($file == 'result') {
			if (strpos($key, 'notice') === 0) {
				return 'cWorkAsOneLimitRow';
			}
		} else if (in_array($file, ['roundname', 'sexname'])) {
			return 'cWorkAsOneLimitRow';
		}

		return '';
	}

	public function submit(Request $req, $lang, $lang2) {

		App::setLocale($lang);

		if (!in_array($lang2, array_keys(Config::get('const.translate')))) {
			return __('work-as-one.button.unsupport');
		}

		$fileContents = '';
		foreach ($req->all() as $k => $v) {

			if ($k == '_token') continue;
			$fileContents .= join("\t", [str_replace("_", " ", str_replace("`", ".", $k)), $v]) . "\n";

		}

		$authid = \Auth::check() ? \Auth::id() : 0;
		$newfile = join("_", [$lang2, $authid, time()]);
		Storage::put('lang/' . $newfile, $fileContents);

		return __('work-as-one.button.submitted');

	}

	public function test($lang) {

//		Storage::disk('ftp')->put('upload/1', "hello\n");

		echo "OK";

	}

}
