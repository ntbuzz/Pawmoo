<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  resource:    Viewで生成したリソースをセッション経由で受け取り応答を返す
静的リダイレクト
	/(css|js|images)/*				vendor/webroot/$1/*
	/res/images/*					Core/webroot/images/*
	/res/css/img/*					Core/webroot/cssimg/*
	/app/css/(css|js|images)/*		app/webroot/cssimg/*
	/app/.../css/img/*				app/webroot/cssimg/*
動的リダイレクト (本スクリプトで処理)
	/res/(css|js)/*					Core/Template/res or Core/webroot/$1/*
	/app/(css|js)/*					app/View/res
	/res/module/(css|js)/*			app/module/res
 */
// デバッグ用のクラス
require_once('AppDebug.php');
// このファイルが依存している関数定義ファイル
// オートローダーは使わないので必要なものは全てrequireする
require_once('Config/appConfig.php');
require_once('Common/coreLibs.php');
require_once('Class/session.php');

date_default_timezone_set('Asia/Tokyo');
$path = $_SERVER['REQUEST_URI'];
$pval = explode('/',$path);
$appname = $pval[1];
$ext = extract_extension($path);
$Mime = [
	'css' => 'text/css',
	'js'  => 'text/javascript',
];
$mtype = $Mime[$ext];
MySession::InitSession($appname);
$res = MySession::resource_GetData($path);
header("Content-Type: {$mtype}");
//header('Content-Length:' . strlen($res));
//debug_dump(['MIME'=>$mtype,'PATH'=>$path,'APP'=>$appname,'RES'=>$res]);
echo $res;
