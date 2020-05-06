<?php
	$cwd = basename(__DIR__);
	$_SERVER['REQUEST_URI'] = "/{$cwd}/{$argv[1]}";	// getcwd() . 
	$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);	// 
	$_SERVER['SERVER_NAME'] = getHostByName(getHostName());
	$_SERVER['LOCAL_ADDR'] = $_SERVER['SERVER_NAME'];
	$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja;en';		// 言語受け入れリスト
	$_SERVER['HTTP_REFERER'] = "localhost";

	print_r($argv);
	print "========================================== START HERE ======================================\n";
	require_once('index.php');		// リソース呼出しとの振分けも行う
