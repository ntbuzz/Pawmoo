<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  index:  リダイレクト処理
 */
define('DEBUGGER', TRUE);
ini_set('display_errors',0);

global $on_server;

$on_server = $_SERVER['SERVER_PORT'];
 
// mod_rewrite の設定をシンプルにするため
if(preg_match('/\/(?:css|js)\/.*/', $_SERVER['REQUEST_URI'])) {
	require_once('Core/resource.php');
} else {
	require_once('Core/Main.php');
}
