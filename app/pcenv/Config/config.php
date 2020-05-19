<?php

define('HOST_SPEC','http://172.27.132.244:80/');
define('SQLITE_DB',__DIR__ . '/pcenv.db');
define('PG_HOST','spider');
define('PG_PORT',  '5532');
define('PG_DB',  'pcmanager');

define("VIEWFILE",0);
define("POSTFILE",1);
define("DRAGFILE",2);

define('DEFAULT_CONTROLLER', 'index');
define('DEFAULT_LANG', 'ja');				// 言語ファイル

const DatabaseParameter  = [
	'Postgre' =>  array(
		'persistent' => false,
		'host' => PG_HOST,
		'login' => 'postgres',
		'port' => PG_PORT,
		'password' => 'postgres',
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
		'login' => 'admin',
		'password' => 'kiwi',
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
