<?php
define('FORCE_REDIRECT', FALSE);
define('DEFAULT_CONTROLLER', 'index');
define('DEFAULT_LANG', 'ja');				// 言語ファイル

// それぞれのデータベース接続パラメータ定義
//--------------------------------------------
// SQLite3データベース
define('SQLITE_DB',__DIR__ . '/sqlite3.db');
//--------------------------------------------
// PostgreSQLのサーバー定義
define('PG_HOST','localhost');
define('PG_PORT',  '5432');
define('PG_DB',  'pg_database');
//--------------------------------------------
// MySQLのサーバー定義
define('MYSQL_DB',  'pcmanager');

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
		'login' => 'root',
		'password' => 'root',
		'encoding' => 'utf8'
	),
	'Folder' => array(
		'host' => 'localhost',
		'database' => '',
		'login' => '',
		'password' => '',
		'encoding' => 'utf8'
	),
];
