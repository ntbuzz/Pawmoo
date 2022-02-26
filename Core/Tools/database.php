<?php
//define('SQLITE_DB',__DIR__ . '/pcenv.db');
define('SQLITE_DB',__DIR__ . '/testdb.db');
define('LOCK_DB',__DIR__ . '/lock.db');

define('MYSQL_DB',  'pcmanager');
//define('HANDLER',  'MySQL');
//define('HANDLER',  'Postgre');
define('HANDLER',  'SQLite');
define('T_BOOL',  't');

const GlobalConfig = [
	'Postgre' =>  [
		'host' => 'localhost',
		'login' => 'postgres',
		'password' => 'postgres',
		'encoding' => 'utf8',
		'Linux' => [
			'port' => 5532,
			'database' => 'testdb',
		],
		'WINNT' => [
			'port' => 5432,
			'database' => 'pcmanager',
		],
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
		'database' => 'pcmanager',
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
