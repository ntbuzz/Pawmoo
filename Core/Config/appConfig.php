<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  appConfig: フレームワークのコンフィグレーション
 */
define('CURRENT_VERSION','0.20.0 2020-08-18');	// フレームワークのバージョン

define('TIME_ZONE','Asia/Tokyo');	// デフォルトのタイムゾーン

// OSタイプ
if (PHP_OS == "Linux") {
	define("OSDEP","UNIX");
	define("ZIPTEMP","/tmp/");
	define('OS_CODEPAGE','UTF8');
} else {
	define("OSDEP","WIN");
	define("ZIPTEMP","C:/tmp/");
	$host = gethostname();			// ホスト名を取出す
	$UTFhost = array( 'T440N',	'T410Dev' );	// UTF8のホスト名
//	define('OS_CODEPAGE',(in_array($host,$UTFhost) ? 'UTF8' : 'SJIS'));
	define('OS_CODEPAGE','UTF8');
}

const IgnoreFiles =[
		".","..","Thumbs.db","web.config","desktop.ini","files.txt",
		'$RECYCLE.BIN','System Volume Information'
];
