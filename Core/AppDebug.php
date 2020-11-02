<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  デバッグ用のメッセージ出力処理関数
 */
if(!defined('DEBUG_LEVEL')) define('DEBUG_LEVEL', 10);

define('DBMSG_SYSTEM',  0);      // for Main, App, Controller
define('DBMSG_LOCALE',  1);      // for LangUI
define('DBMSG_VIEW',    2);      // for View, Helper
define('DBMSG_MODEL',   3);      // for Model
define('DBMSG_HANDLER', 4);      // for DB-Handler
// over 5 level for Application

const EMPTY_MSG = " EMPTY\n";
/*
    アプリケーションデバッグ情報
*/
//==============================================================================
//  デバッグログ出力
$debug_log_str = [];
$debug_run_time = 0;
function get_debug_logs() {
    global $debug_log_str;
    ksort($debug_log_str);
    return $debug_log_str;
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
    if(CLI_DEBUG) debug_log(-99,$items);
}
function log_reset($lvl) {
    global $debug_log_str;
    unset($debug_log_str[$lvl]);
}
//==========================================================================
// ログの記録または表示
function debug_log($lvl,...$items) {
    if($lvl === FALSE || abs($lvl) > 100) return;
    if($lvl > DEBUG_LEVEL) return;
    // バックトレースから呼び出し元の情報を取得
    $dump_log_info = function($items) {
        $dbinfo = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,8);    // 呼び出し元のスタックまでの数
        array_shift($dbinfo);   // 自クラスの情報は不要
//        array_shift($dbinfo);   // 自クラスの情報は不要
        $trace = "";
        foreach($dbinfo as $stack) {
            $path = str_replace('\\','/',$stack['file']);             // Windowsパス対策
            list($pp,$fn,$ext) = extract_path_file_ext($path);
            $func = "{$fn}({$stack['line']})";
            $trace = (empty($trace)) ? $func : "{$func}>{$trace}";
        }
        $sep = 	str_repeat("-", 30);
        $dmp_msg = "TRACE:: {$trace}\n";
        // 子要素のオブジェクトをダンプする関数
        $dump_object = function ($obj,$indent) use (&$dump_object) {
            $dmp = "";
            foreach($obj as $key => $val) {
                $dmp .= str_repeat(' ',$indent*2) . "[{$key}] = ";
                if(empty($val)) {
                    $dmp .= "NULL\n";
                } else if(is_array($val)) {
                    $dmp .= "array(" . count($val) . ")\n";
                    $dmp .= $dump_object($val,$indent+1);
                } else if(is_scalar($val)) {
                    $val = control_escape($val);
                    $dmp .= "'{$val}'\n";
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
                    if(empty($obj)) {
                        $dmp_msg .= "{$msg} : NULL\n";
                    } else if(is_scalar($obj)) {
                        $obj = control_escape($obj);
                        $dmp_msg .= "{$msg} : {$obj}\n";
                    } else if(is_array($obj)) {
                        $dmp_msg .= "===== {$msg} =====\n";
                        if(empty($obj)) $dmp_msg .= EMPTY_MSG;
                        else $dmp_msg .= $dump_object($obj,0);
                    } else {
                        $dmp_msg .= "{$msg} : Object=".gettype($obj)."\n";
                        $dmp_msg .= print_r($obj,TRUE);
                    }
                }
            }
        }
        return $dmp_msg;
    };
    global $debug_log_str;
    $dmp_info = $dump_log_info($items);
    if(!empty($dmp_info)) {
        if(!CLI_DEBUG) {
            $dmp_info = htmlspecialchars($dmp_info);
            $dmp_info = "<pre>\n{$dmp_info}\n</pre>\n";
        }
        if($lvl < 0) echo "{$dmp_info}\n";
        else {
            if(isset($debug_log_str[$lvl])) $debug_log_str[$lvl] .= $dmp_info;
            else $debug_log_str[$lvl] = $dmp_info;
        }
    }
    if(abs($lvl) > 1000) exit;
}
