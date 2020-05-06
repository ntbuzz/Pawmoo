<?php
/* -------------------------------------------------------------
 * Biscuitフレームワーク
 *  resource:    CSS/JSファイルのリクエストを受付け、定義ファイルの情報に従いファイル結合したものを応答する
 * 
 */

require_once('Class/session.php');

// デバッグ用のクラス
require_once('AppDebug.php');
APPDEBUG::INIT(0);

require_once('Common/appLibs.php');
require_once('Base/AppStyle.php');

date_default_timezone_set('Asia/Tokyo');

list($fwroot,$rootURI,$appname,$modname,$params,$q_str) = getFrameworkParameter(__DIR__);

$sysRoot = $rootURI;

list($category,$filename) = $params;
list($filename,$ext) = extractBaseName($filename);

MySession::InitSession();
// モジュール名と拡張子を使いテンプレートを決定する
$AppStyle = new AppStyle($appname,$sysRoot, $modname, $filename, $ext);
// =================================
/*
dump_debug("リソース", [
    'システム情報' => [
        "SYSROOT"   => $sysRoot,
        "MODULE"    => $modname,
        "FILE"      => $filename,
        "EXT"       => $ext,
    ]
]);
*/
// =================================

// ヘッダの出力
$AppStyle->ViewHeader();
// 結合ファイルの出力
$AppStyle->ViewStyle($filename);

MySession::CloseSession();
