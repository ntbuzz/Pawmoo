<?php

define('HOST_SPEC','http://172.27.132.244:80/');
define('SQLITE_DB',__DIR__ . '/mvcman.db');
define('PG_HOST','spider');
define('PG_PORT',  '5532');
define('PG_DB',  'pcmanager');

define("VIEWFILE",0);
define("POSTFILE",1);
define("DRAGFILE",2);

define('DEFAULT_CONTROLLER', 'help');
define('DEFAULT_LANG', 'ja');				// 言語ファイル

const DatabaseParameter  = [
	'Postgre' =>  array(
		'host' => PG_HOST,
		'port' => PG_PORT,
		'login' => 'postgres',
		'password' => 'postgres',
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
		'login' => 'admin',
		'password' => 'kiwi',
		'encoding' => 'utf8'
	),
	'Folder' => array(
		'host' => 'localhost',
		'login' => '',
		'password' => '',
		'encoding' => 'utf8'
	),
];
