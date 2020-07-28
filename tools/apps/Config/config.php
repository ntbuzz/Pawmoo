<?php

define("LOGIN_NEED",TRUE);		// ログインを要求する

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
//--------------------------------------------
// FileMakerのサーバー定義
define('HOST_SPEC','http://192.168.1.1/');

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
		'password' => 'superman',
		'encoding' => 'utf8'
	),
	'Filemaker' => array(
		'host' => HOST_SPEC,
		'database' => '',
		'login' => 'admin',
		'password' => 'kiwi',
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
