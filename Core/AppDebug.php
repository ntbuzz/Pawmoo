<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  デバッグ用のメッセージ出力処理関数
 */
if(!defined('DEBUG_LEVEL')) define('DEBUG_LEVEL', 10);

define('DBMSG_SYSTEM',  -105);      // for Main, App, Controller
define('DBMSG_LOCALE',  -104);      // for LangUI
define('DBMSG_VIEW',    -103);      // for View, Helper
define('DBMSG_MODEL',   -102);      // for Model
define('DBMSG_HANDLER', -101);      // for DB-Handler
define('DBMSG_RESOURCE',-100);      // for Style/Script
define('DBMSG_LEVEL',   -100);      // logging level
define('DBMSG_DIE',     -99);       // die message

const EMPTY_MSG = " EMPTY\n";
const EXCLUSION = [
    'debuglog' => 1,
    'password' => 1,
    'passwd' => 1,
];
/*
    アプリケーションデバッグ情報
*/
$debug_log_str = [];
$debug_run_time = 0;
//==============================================================================
//  デバッグログ出力
function get_debug_logs() {
    $current_log = MySession::get_paramIDs('debuglog');
    if($current_log !== NULL) ksort($current_log);
    return $current_log;
}
//==========================================================================
// 実行時間測定開始
function debug_run_start() {
    global $debug_run_time;
    $debug_run_time = microtime(TRUE);
}
//==========================================================================
// 実行時間表示
function debug_run_time($lvl) {
    global $debug_run_time;
    $tm = round((microtime(TRUE) - $debug_run_time), 2);     // 少数2位まで
    $maxmem = round(memory_get_peak_usage()/(1024*1024),2);
    $sec = LangUI::get_value('debug','.Second');
    debug_log($lvl,[
        "#ExecTime" => "{$tm} {$sec}",
        "#MaxMemory" => "{$maxmem} MB",
    ]);
}
//==========================================================================
// ログの表示
function debug_dump(...$items) {
    if(CLI_DEBUG) debug_log(-98,$items);
}
function debug(...$items) {
    debug_log(-98,$items);
}
function log_reset($lvl) {
    global $debug_log_str;
    unset($debug_log_str[$lvl]);
    MySession::set_paramIDs("debuglog.{$lvl}",NULL);
}
//==========================================================================
// ログの記録または表示
function debug_log($lvl,...$items) {
    if($lvl === FALSE || $lvl < DBMSG_SYSTEM || $lvl > DEBUG_LEVEL) return;
    // バックトレースから呼び出し元の情報を取得
    $dump_log_info = function($items) {
        $dbinfo = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,8);    // 呼び出し元のスタックまでの数
        $trace = "";
        foreach($dbinfo as $stack) {
            if(isset($stack['file'])) {
                $path = str_replace('\\','/',$stack['file']);             // Windowsパス対策
                list($pp,$fn,$ext) = extract_path_file_ext($path);
                if($fn !== 'AppDebug') {                            // 自クラスの情報は不要
                    $func = "{$fn}({$stack['line']})";
                    $trace = (empty($trace)) ? $func : "{$func}>{$trace}";
                }
            }
        }
        $sep = 	str_repeat("-", 30);
        $dmp_msg = "TRACE:: {$trace}\n";
        // 子要素のオブジェクトをダンプする関数
        $dump_object = function ($obj,$indent) use (&$dump_object) {
            $dmp = "";
            foreach($obj as $key => $val) {
                if(array_key_exists($key,EXCLUSION)) continue;      // not dump element check
                $dmp .= str_repeat(' ',$indent*2) . "[{$key}] = ";
                if(gettype($val)==='object') {
                    $dmp .= "[{$val->ClassName}]\n"; // print_r($val,true);
                } else if(empty($val)) {
                    $dmp .= (is_int($val)) ? "0\n" : "NULL\n";
                } else if(is_array($val)) {
                    $dmp .= "array(" . count($val) . ")\n";
                    $dmp .= $dump_object($val,$indent+1);
                } else if(is_scalar($val)) {
                    if(is_int($val)) $dmp .= "{$val}\n";
                    else {
                        $val = control_escape($val);
                        $dmp .= "'{$val}'\n";
                    }
                }
            }
            return $dmp;
        };
        foreach($items as $arg) {
            if(is_scalar($arg)) {
                if(empty($arg)) $arg ='NULL'; else $arg= wordwrap(control_escape($arg),86,"\n");
                $dmp_msg .= "{$arg}\n";
            } else if(is_array($arg)) {                        // 配列要素の出力
                foreach($arg as $msg => $obj) {
                    if($msg[0] === '#') {
                        $msg[0] = '.';
                        $msg = LangUI::get_value('debug',$msg);
                    }
                    if(gettype($obj)==='object') {
                        $dmp_msg .= "{$msg} : Class[{$obj->ClassName}]\n";// . print_r($obj,true);
                    } else if(empty($obj)) {
                        $dmp_msg .= "{$msg} : NULL\n";
                    } else if(is_scalar($obj)) {
                        $dmp_msg .= "{$msg} : {$obj}\n";
                    } else if(is_array($obj)) {
                        $dmp_msg .= "===== {$msg} =====\n";
                        if(empty($obj)) $dmp_msg .= EMPTY_MSG;
                        else $dmp_msg .= $dump_object($obj,1);
                    } else {
                        $dmp_msg .= "{$msg} : Object=".gettype($obj)."\n";
                        $dmp_msg .= print_r($obj,TRUE);
                    }
                }
            }
        }
        return "{$dmp_msg}\n";
    };
    global $debug_log_str;
    $dmp_info = $dump_log_info($items);
    if(!empty($dmp_info)) {
        if($lvl < 0 && $lvl > DBMSG_LEVEL) {
            if($lvl === -99) die("<pre>\n{$dmp_info}\n</pre>\n");
            else echo "{$dmp_info}\n";
        } else {
            if(isset($debug_log_str[$lvl])) $dmp_info = $debug_log_str[$lvl] . $dmp_info;
            MySession::set_paramIDs("debuglog.{$lvl}",$dmp_info);
            $debug_log_str[$lvl] = $dmp_info;
        }
    }
}
