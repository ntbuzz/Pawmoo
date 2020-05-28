<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  resource:    CSS/JSファイルのリクエストを受付け、定義ファイルの情報に従いファイル結合したものを応答する
 *  app/(css|js)/xxxx.css|.js 
 *      => .htaccess で webroot にリライトされるので考慮しない
 *  app/module/(css|js)/xxxx.css|.js
 *      module => module oe res
 *      method => css|js
 *      filter => xxxx.css|.js
 */
require_once('Class/session.php');

// デバッグ用のクラス
require_once('AppDebug.php');
APPDEBUG::INIT(0);

require_once('Common/appLibs.php');
require_once('Base/AppStyle.php');
require_once('Base/LangUI.php');           // static class

date_default_timezone_set('Asia/Tokyo');

list($appname,$app_uri,$module,$q_str) = getRoutingParams(__DIR__);
list($fwroot,$appRoot) = $app_uri;
list($controller,$category,$files) = $module;
// ファイル名を拡張子と分離する
list($filename,$ext) = extractBaseName($files);
// 言語ファイルの対応
$lang = (isset($query['lang'])) ? $query['lang'] : $_SERVER['HTTP_ACCEPT_LANGUAGE'];
// コア用の言語ファイルを読み込む
LangUI::construct($lang,'');
MySession::InitSession();
// モジュール名と拡張子を使いテンプレートを決定する
$AppStyle = new AppStyle($appname,$appRoot, $controller, $filename, $ext);
// =================================
debug_dump(DEBUG_DUMP_NONE, [
    'システム情報' => [
        "APPROOT"   => $appRoot,
        "MODULE"    => $controller,
        "FILE"      => $filename,
        "EXT"       => $ext,
    ]
]);
// =================================

// ヘッダの出力
$AppStyle->ViewHeader();
// 結合ファイルの出力
$AppStyle->ViewStyle($filename);

MySession::CloseSession();
