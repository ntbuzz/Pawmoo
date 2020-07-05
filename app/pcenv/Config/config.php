<?php

define('HOST_SPEC','http://172.27.132.244:80/');
define('SQLITE_DB',__DIR__ . '/pcenv.db');
define('PG_HOST','localhost');
define('PG_PORT',  '5532');
define('PG_DB',  'pcmanager');
define('MYSQL_DB',  'pcmanager');

define("VIEWFILE",0);
define("POSTFILE",1);
define("DRAGFILE",2);

define("LOGIN_NEED",TRUE);		// ログインを要求する
define('FORCE_REDIRECT', TRUE);		// 強制リダイレクト
define('DEFAULT_CONTROLLER', 'index');
define('DEFAULT_LANG', 'ja');				// 言語ファイル

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
