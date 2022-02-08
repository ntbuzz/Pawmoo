<?php
// それぞれのデータベース接続パラメータ定義
//--------------------------------------------
define('HANDLER',  'SQLite');
define('T_BOOL',  't');

// SQLite3データベース
define('SQLITE_DB',__DIR__ . '/sample.db');
//--------------------------------------------

const GlobalConfig = [
// 共通設定
'Postgre' =>  [
	'host' => 'localhost',
	'database' => 'pg_sample',
	'login' => 'postgres',
	'password' => 'postgres',
	'encoding' => 'utf8',
],
'SQLite' => [
	'host' => 'localhost',
	'database' => SQLITE_DB,
	'login' => '',
	'password' => '',
	'encoding' => 'utf8',
],
'production' => [
	'USE_DEBUGGER'	=> false,
	'SESSION_LIMIT' => 'tomorrow 03:00:00',
	'Postgre' =>  [
		'port' => 5432,
	],
],
'development' => [
	'USE_DEBUGGER'	=> true,
	'SESSION_LIMIT' => 'now +15 minute',	// for DEBUG
	'SENDMAIL_DEBUG'=> 'root@localhost',
	'Postgre' =>  [
		'Linux' => [		// OS別(PHP_OS)の設定
			'port' => 5532,
		],
		'WINNT' => [		// OS別(PHP_OS)の設定
			'port' => 5432,
		],
		'hostname' => [		// ホスト別の設定
			'login' => 'admin',		// 共通設定の上書き
			'password' => 'admin',
		],
	],
],
];
