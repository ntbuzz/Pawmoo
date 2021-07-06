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
list($appname,$app_uri,$module,$q_str) = get_routing_path($root);
parse_str($q_str, $query);
// URI: /logs/appname/cont/act
list($appname,$cont,$filter) = $module;
MySession::InitSession($appname,$cont);
$debug_logs = sysLog::last_logs(); // 他のモジュールで上書きされる前にログ取得
//log_dump(['MOD'=>[$cont,$method],'LOG'=>$debug_logs,'SESSION'=>MySession::$EnvData]);
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
//        echo "<li>ENV</li>\n";
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
//        echo "<li><div class=\"debug_srcollbox\">\n";
//        debug_log(-19,MySession::$EnvData['AppData']);
//        echo "</div></li>\n";
    }
?>
</ul>
</div>