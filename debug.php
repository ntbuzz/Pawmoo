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

	$uri = $_SERVER['REQUEST_URI'];
	list($url,$q_str) = (strpos($uri,'?')!==FALSE)?explode('?',$uri):[$uri,''];
	parse_str($q_str, $query);
	$_REQUEST =  $query;

	$ln = str_repeat("=", 50);
	print_r($argv);
	echo "{$ln} START HERE ${ln}\n";

	require_once('index.php');		// リソース呼出しとの振分けも行う
