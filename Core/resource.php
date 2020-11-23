<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  resource:    CSS/JSファイルのリクエストを受付け、定義ファイルの情報に従いファイル結合したものを応答する
 * RewriteRule ^(css|js|images)/(.*)$       vendor/webroot/$1/$2 [END]
 *
 * テンプレートリソース(CSS/画像ファイル)
 *  (appname)/css/res/img/(.*)$     Core/Template/cssimg/$1 [END]
 *   /res/images/(.*)$              Core/Template/images/$1 [END]
 * アプリごとに異なるリダイレクト
 *  (appname)/(module)/css/img/(.*)$    app/$1/webroot/cssimg/$2 [END]
 *  (appname)/(css|js)/(.*)$            app/$1/webroot/$2/$3 [END]
 *  (appname)/images/(.*)$              app/$1/webroot/images/$2 [END]
 *  (appname)/(module)/images/(.*)$     app/$1/webroot/images/$2 [END]
 *      => .htaccess でリライトされるので考慮しない
 *  app/module/(css|js)/xxxx.css|.js
 *      module => module oe res
 *      method => css|js
 *      filter => xxxx.css|.js
 */
// デバッグ用のクラス
require_once('AppDebug.php');
// このファイルが依存している関数定義ファイル
require_once('Config/appConfig.php');
require_once('Common/coreLibs.php');
require_once('Common/appLibs.php');
require_once('Class/session.php');
require_once('Base/AppStyle.php');
require_once('Base/LangUI.php');           // static class

date_default_timezone_set('Asia/Tokyo');

list($appname,$app_uri,$module,$q_str) = get_routing_params(__DIR__);
parse_str($q_str, $query);
list($fwroot,$appRoot) = $app_uri;
list($controller,$category) = $module;
$files = $module[3][0];        // ファイル名はパラメータに入る
// ファイル名を拡張子と分離する
list($filename,$ext) = extract_base_name($files);
MySession::InitSession($appname);
// 言語ファイルの対応
if(array_key_exists('lang', $query)) {
    $lang = $query['lang'];
} else {
    $lang = MySession::get_LoginValue('LANG');
    if($lang === NULL) $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
}
$contfiles = ($controller=='Res')?'resource':['resource',$controller];
LangUI::construct($lang,"app/{$appname}/View/lang/",$contfiles);    // Load CORE lang and SET app-Folder
// モジュール名と拡張子を使いテンプレートを決定する
$AppStyle = new AppStyle($appname,$app_uri, $controller, $filename, $ext);
// ヘッダの出力
$AppStyle->ViewHeader();
// 結合ファイルの出力
$AppStyle->ViewStyle($filename);

MySession::CloseSession();
