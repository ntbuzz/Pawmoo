<?php
/*
 * Object Oriented PHP MVC Framework
 *  Comman Line Debugger
 */
$cwd = basename(__DIR__);
$_SERVER['REQUEST_URI'] = urldecode("/{$argv[1]}");	// getcwd() . 
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);	// 
$_SERVER['SERVER_NAME'] = getHostByName(getHostName());
$_SERVER['LOCAL_ADDR'] = $_SERVER['SERVER_NAME'];
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja;en;ja';		// 言語受け入れリスト
$_SERVER['HTTP_REFERER'] = "localhost";
$_SERVER['SERVER_PORT'] = '';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = 'localhost';
$_SERVER['HTTP_USER_AGENT'] = 'Chrome/94.0.4606.104';

require_once("Core/Common/arrayLibs.php");

$uri = $_SERVER['REQUEST_URI'];
// command line parameter: app/module/method/param?QUERY??POST
// POST check
list($url,$p_str) = fix_explode('??',$uri,2);
parse_str($p_str, $post);
$_POST = $post;
// GET check
list($url,$q_str) = fix_explode('?',$uri,2);
parse_str($q_str, $query);
$_GET =  $query;

$ln = str_repeat("=", 50);
$debvug_dump = [
	'ARG' => $argv,
	'GET' => $_GET,
	'POST' => $_POST,
];
print_r($debvug_dump);
echo "{$ln} START HERE ${ln}\n";

require_once('index.php');		// リソース呼出しとの振分けも行う
