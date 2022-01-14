<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  LogFiles:   ログファイルを取得する
 */
// デバッグ用のクラス
require_once('AppDebug.php');
// このファイルが依存している関数定義ファイル
// オートローダーは使わないので必要なものは全てrequireする
require_once('Config/appConfig.php');
require_once('Common/coreLibs.php');
require_once('Class/session.php');
require_once('Class/Parser.php');
require_once('Base/LangUI.php');           // static class

date_default_timezone_set('Asia/Tokyo');
$root = basename(dirname(__DIR__));        // Framework Folder
list($appname,$app_uri,$module) = get_routing_path($root);
$query	 = xchange_Boolean($_GET);		// query string into $_GET

if(!defined('DEFAULT_LANG'))	 define('DEFAULT_LANG', 'ja');				// Language
if(!defined('DEFAULT_REGION'))	 define('DEFAULT_REGION', 'jp');			// Region code

// URI: /logs/appname/cont
list($appname,$cont,$filter) = $module;
MySession::InitSession($appname,$cont);
$debug_logs = sysLog::get_logs($cont); // コントローラーログの取得
// 言語ファイルの対応
if(array_key_exists('lang', $query)) {
    $lang = $query['lang'];
} else {
    $lang = MySession::get_LoginValue('LANG');
    if($lang === NULL) $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
}
LangUI::construct($lang,NULL,NULL);    // Load CORE lang ONLY
?>
<div class='debugtab'>
<span class="closeButton"></span>
<ul id="debugMenu">
<?php
    $level_msg = LangUI::get_value(NULL,"debug.Level");
    $category_msg = LangUI::get_value(NULL,"debug.Level.Category",TRUE);
    if(!empty($debug_logs)) {
        foreach($debug_logs as $key => $msg) {
            $title = (isset($category_msg[$key])) ? $category_msg[$key]:"{$level_msg}:{$key}";
            echo "<li>{$title}</li>\n";
        }
        echo "</ul>\n";
        echo "</div>\n";
        echo "<div class='debug-panel'>\n";
        echo "<ul class='dbcontent'>\n";
        if($debug_logs !== NULL)
        foreach($debug_logs as $key => $msg) {
            echo "<li><div class=\"debug_srcollbox\">\n";
            $msg = htmlspecialchars($msg);
            echo "<pre>\n{$msg}\n</pre>\n";
            echo "</div></li>\n";
        }
    }
?>
</ul>
</div>