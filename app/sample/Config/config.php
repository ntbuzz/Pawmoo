<?php
require_once(__DIR__ . '/database.php');

define('FORCE_REDIRECT', FALSE);

define('DEFAULT_CONTROLLER', 'index');
define('DEFAULT_LANG', 'ja');				// 言語ファイル

define('LOCALE_REGION', [
	'ja'	=> 'ja.jp',			// Japan
	'en'	=> 'en.us',			// United State
	'fr'	=> 'en.fr',			// France
	'de'	=> 'en.de',			// German
	'zh'	=> 'en.cn',			// Chinese
]);

define('USE_QUERY_LANG',true);			// use query lang= region=
