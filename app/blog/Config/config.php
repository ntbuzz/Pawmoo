<?php

define("LOGIN_NEED",FALSE);		// ログインを要求する
define('FORCE_REDIRECT', TRUE);

define('DEFAULT_CONTROLLER', 'index');
define('DEFAULT_LANG', 'ja');				// 言語ファイル

// それぞれのデータベース接続パラメータ定義
//--------------------------------------------
// SQLite3データベース
define('SQLITE_DB',__DIR__ . '/blog.db');
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
		'host' => PG_HOST,
		'port' => PG_PORT,
		'login' => '',
		'password' => '',
		'encoding' => 'utf8'
	),
	'SQLite' => array(
		'host' => 'localhost',
		'login' => '',
		'password' => '',
		'encoding' => 'utf8'
	),
	'Filemaker' => array(
		'host' => HOST_SPEC,
		'login' => '',
		'password' => '',
		'encoding' => 'utf8'
	),
	'Folder' => array(
		'host' => 'localhost',
		'login' => '',
		'password' => '',
		'encoding' => 'utf8'
	),
];
