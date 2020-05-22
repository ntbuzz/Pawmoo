<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  resource:    CSS/JSファイルのリクエストを受付け、定義ファイルの情報に従いファイル結合したものを応答する
 * 
 */
global $scheme;

require_once('Class/session.php');

// デバッグ用のクラス
require_once('AppDebug.php');
APPDEBUG::INIT(0);

require_once('Common/appLibs.php');
require_once('Base/AppStyle.php');
require_once('Base/LangUI.php');           // static class

date_default_timezone_set('Asia/Tokyo');

list($fwroot,$rootURI,$appname,$modname,$params,$q_str) = getFrameworkParameter(__DIR__);

$appRoot = $rootURI;
if(count($params) === 1) {  // アプリ直下のcss|js
    array_unshift($params, strtolower($modname));
    $modname = 'Res';
}

list($category,$filename) = $params;
list($filename,$ext) = extractBaseName($filename);
//echo "FILE:{$filename}\n";
//echo "EXT:{$ext}\n";
// 言語ファイルの対応
$lang = (isset($query['lang'])) ? $query['lang'] : $_SERVER['HTTP_ACCEPT_LANGUAGE'];
// コア用の言語ファイルを読み込む
LangUI::construct($lang,'');
MySession::InitSession();
// モジュール名と拡張子を使いテンプレートを決定する
$AppStyle = new AppStyle($appname,$appRoot, $modname, $filename, $ext);
// =================================
debug_dump(DEBUG_DUMP_NONE, [
    'システム情報' => [
        "APPROOT"   => $appRoot,
        "MODULE"    => $modname,
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
