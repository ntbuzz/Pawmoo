<?php
define('SQLITE_DB',__DIR__ . '/testdb.db');
define('LOCK_DB',__DIR__ . '/lock.db');

define('MYSQL_DB',  'testdb');
define('PGSQL_DB',  'testdb');

//define('HANDLER',  'MySQL');
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
	'MySQL' => [
		'host' => 'localhost',
		'database' => MYSQL_DB,
		'login' => 'root',
		'password' => 'superman',
		'encoding' => 'utf8',
	],
'production' => [
	'USE_DEBUGGER'	=> true,
	'SESSION_LIMIT' => 'tomorrow 03:00:00',
	'SENDMAIL_DEBUG'=> '',
],
'development' => [
	'USE_DEBUGGER'	=> true,
//	'SESSION_LIMIT' => 'now +15 minute',	// for DEBUG
	'SENDMAIL_DEBUG'=> 'root@localhost',
],
];
