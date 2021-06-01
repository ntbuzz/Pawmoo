<?php

require_once('Core/Config/appConfig.php');
if(!defined('CLI_DEBUG')) define('CLI_DEBUG',FALSE);

require_once('Core/AppDebug.php');
require_once('Core/Common/appLibs.php');
//require_once('Core/Class/session.php');
require_once('Core/Common/coreLibs.php');
//require_once('Core/Handler/DatabaseHandler.php');
require_once('Core/Base/AppDatabase.php');

date_default_timezone_set('Asia/Tokyo');
list($appname,$defs) = explode('/',$argv[1]);

$defsfile = "app/{$appname}/Config/Setup/{$defs}.php";
// is enabled application name
if(!file_exists($defsfile)) {
	echo "Not Exist '{$defs}.php' file in {$appname} Config Setup.\n";
	exit;
}
require_once($defsfile);

//MySession::InitSession($appname);

$db = new DatabaseSetup();
$db->execute();

//MySession::CloseSession();
//DatabaseHandler::CloseConnection();
