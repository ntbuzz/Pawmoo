<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  index:  リダイレクト処理
 */
 define('DEBUGGER', TRUE);

 $scheme = $_SERVER['REQUEST_SCHEME'];

// mod_rewrite の設定をシンプルにするため
if(preg_match('/\/(?:css|js)\/.*/', $_SERVER['REQUEST_URI'])) {
	require_once('Core/resource.php');
} else {
	require_once('Core/Main.php');
}
