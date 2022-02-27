<?php

define('FORCE_REDIRECT', TRUE);		// 強制リダイレクト
define('DEFAULT_CONTROLLER', 'index');
define('DEFAULT_LANG', 'ja');				// 言語ファイル
define('DEFAULT_REGION', 'jp');				// 国記号

define('SQLITE_DB',__DIR__ . '/testdb.db');
define('PGSQL_DB',  'testdb');

//define('HANDLER',  'Postgre');
define('HANDLER',  'SQLite');

const GlobalConfig = [
	'Postgre' =>  [
		'host' => 'localhost',
		'login' => 'postgres',
		'password' => 'postgres',
		'encoding' => 'utf8',
		'port' => 5532,
		'database' => PGSQL_DB,
	],
	'SQLite' => [
		'host' => 'localhost',
		'database' => SQLITE_DB,
		'login' => '',
		'password' => '',
		'encoding' => 'utf8',
	],
'production' => [
	'USE_DEBUGGER'	=> true,
	'SESSION_LIMIT' => 'tomorrow 03:00:00',
	'SENDMAIL_DEBUG'=> '',
],
'development' => [
	'USE_DEBUGGER'	=> true,
	'SENDMAIL_DEBUG'=> 'root@localhost',
],

];
