<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  index:  リダイレクト処理
 */
define('DEBUGGER', TRUE);
ini_set('display_errors',0);

global $on_server;

$on_server = $_SERVER['SERVER_PORT'];
 // IISのmod_rewriteが勝手にSJISにデコードするようなのでUTF-8に強制的に戻す
 foreach(['REQUEST_URI', 'HTTP_REFERER'] as $id) {
	$url = $_SERVER[$id];
	$_SERVER[$id] = mb_convert_encoding($url,'UTF-8','sjis-win');
 }
// mod_rewrite の設定をシンプルにするため
if(preg_match('/\/(?:css|js)\/.*/', $_SERVER['REQUEST_URI'])) {
	define('DEBUG_LEVEL', 0);			// メッセージ出力許可レベル
	require_once('Core/resource.php');
} else {
	define('DEBUG_LEVEL', 10);			// メッセージ出力許可レベル
	require_once('Core/Main.php');
}
