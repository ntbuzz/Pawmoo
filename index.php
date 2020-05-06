<?php
/* -------------------------------------------------------------
 * Biscuitフレームワーク
 *  index:  リダイレクト処理
 */
 define('DEBUGGER', TRUE);

// mod_rewrite の設定をシンプルにするため
if(preg_match('/\/(?:css|js)\/.*/', $_SERVER['REQUEST_URI'])) {
	require_once('Core/resource.php');
} else {
	require_once('Core/Main.php');
}
