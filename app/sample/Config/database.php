<?php
// それぞれのデータベース接続パラメータ定義
//--------------------------------------------
define('HANDLER',  'SQLite');
define('T_BOOL',  't');

// SQLite3データベース
define('SQLITE_DB',__DIR__ . '/sample.db');
//--------------------------------------------

const GlobalConfig = [
'production' => [
	'USE_DEBUGGER'	=> false,
	'SESSION_LIMIT' => 'tomorrow 03:00:00',
	'Postgre' =>  [
		'host' => 'localhost',
		'port' => 5432,
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
	'MySQL' => [
		'host' => 'localhost',
		'database' => 'my_sample',
		'login' => 'admin',
		'password' => 'admin',
		'encoding' => 'utf8',
	],
],
'development' => [
	'USE_DEBUGGER'	=> true,
	'SESSION_LIMIT' => 'now +15 minute',	// for DEBUG
	'Postgre' =>  [
		// PostgreSQLの共通設定
		'host' => 'localhost',
		'database' => 'pg_sample',
		'login' => 'postgres',
		'password' => 'postgres',
		'encoding' => 'utf8',
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
	'SQLite' => [
		'host' => 'localhost',
		'database' => SQLITE_DB,
		'login' => '',
		'password' => '',
		'encoding' => 'utf8',
	],
	'MySQL' => [
		'host' => 'localhost',
		'database' => 'my_sample',
		'login' => 'admin',
		'password' => 'admin',
		'encoding' => 'utf8',
	],
],
];
