<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  Module or Resource switcher.
 */
define('DEBUGGER', TRUE);
ini_set('display_errors',0);

// It seems that mod_rewrite of IIS decodes to SJIS without permission, so forcibly return to UTF-8.
 foreach(['REQUEST_URI', 'HTTP_REFERER'] as $id) {
	$url = $_SERVER[$id];
	$_SERVER[$id] = mb_convert_encoding($url,'UTF-8','sjis-win');
 }
// To simplify mod_rewrite settings ...
if(preg_match('/\/(?:css|js)\/.*/', $_SERVER['REQUEST_URI'])) {
	define('DEBUG_LEVEL', 0);
	require_once('Core/resource.php');
} else {
	define('DEBUG_LEVEL', 10);
	require_once('Core/Main.php');
}
