<?php

define("LOGIN_NEED",TRUE);		// ログインを要求する

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
// FileMakerのサーバー定義
define('HOST_SPEC','http://192.168.1.1/');

const DatabaseParameter  = [
	'Postgre' =>  array(
		'persistent' => false,
		'host' => PG_HOST,
		'port' => PG_PORT,
		'login' => '',
		'password' => '',
		'database' => PG_DB,
		'prefix' => '',
		'encoding' => 'utf8'
	),
	'SQLite' => array(
		'persistent' => false,
		'host' => 'localhost',
		'login' => '',
		'password' => '',
		'database' => SQLITE_DB,
		'prefix' => '',
		'encoding' => 'utf8'
	),
	'Filemaker' => array(
		'persistent' => false,
		'host' => HOST_SPEC,
		'login' => '',
		'password' => '',
		'database' => '*',
		'prefix' => '',
		'encoding' => 'utf8'
	),
	'Folder' => array(
		'persistent' => false,
		'host' => 'localhost',
		'login' => '',
		'password' => '',
		'database' => OSDEP,
		'prefix' => '',
		'encoding' => 'utf8'
	),
];
