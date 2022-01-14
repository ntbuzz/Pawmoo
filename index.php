<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  Module or Resource switcher.
 */
// framework config
require_once('site-config.php');

// It seems that mod_rewrite of IIS decodes to SJIS without permission, so forcibly return to UTF-8.
 foreach(['REQUEST_URI', 'HTTP_REFERER'] as $id) {
	if(isset($_SERVER[$id])) {
		$url = $_SERVER[$id];
		$_SERVER[$id] = mb_convert_encoding($url,'UTF-8','sjis-win');
	}
 }
preg_match('/\/(css|js|logs)\/.*/',$_SERVER['REQUEST_URI'], $m);
$dispatch = [
	'logs'	=> 'debuglog.php',
	'css'	=> 'resource.php',
	'js'	=> 'resource.php',
];
$inc = (count($m) === 2) ? $m[1] : 'main';
$reqfile = (array_key_exists($inc,$dispatch)) ? $dispatch[$inc] : 'Main.php';
require_once("Core/{$reqfile}");
