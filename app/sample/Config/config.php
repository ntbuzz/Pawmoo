<?php
define('FORCE_REDIRECT', FALSE);

define('DEFAULT_CONTROLLER', 'index');
define('DEFAULT_LANG', 'ja');				// 言語ファイル

// それぞれのデータベース接続パラメータ定義
//--------------------------------------------
// SQLite3データベース
define('SQLITE_DB',__DIR__ . '/sample.db');
//--------------------------------------------
// PostgreSQLのサーバー定義
define('PG_HOST','localhost');
define('PG_PORT',  '5432');
define('PG_DB',  'pg_sample');
//--------------------------------------------
// MySQLのサーバー定義
define('MYSQL_DB',  'my_sample');

const DatabaseParameter  = [
	'Postgre' =>  array(
		'host' => PG_HOST,
		'port' => PG_PORT,
		'database' => PG_DB,
		'login' => 'postgres',
		'password' => 'postgres',
		'encoding' => 'utf8'
	),
	'SQLite' => array(
		'host' => 'localhost',
		'database' => SQLITE_DB,
		'login' => '',
		'password' => '',
		'encoding' => 'utf8'
	),
	'MySQL' => array(
		'host' => 'localhost',
		'database' => MYSQL_DB,
		'login' => 'admin',
		'password' => 'admin',
		'encoding' => 'utf8'
	),
];
