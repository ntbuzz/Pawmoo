<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  デバッグ用のメッセージ出力処理関数
 */
if(!defined('DEBUG_LEVEL')) define('DEBUG_LEVEL', 10);

define('DBMSG_SYSTEM',  106);      // for Main, App, Controller
define('DBMSG_LOCALE',  105);      // for LangUI
define('DBMSG_VIEW',    104);      // for View, Helper
define('DBMSG_MODEL',   103);      // for Model
define('DBMSG_HANDLER', 102);      // for DB-Handler
define('DBMSG_RESOURCE',101);      // for Style/Script
define('DBMSG_ERROR',   100);      // for ERROR
define('DBMSG_LEVEL',   100);      // logging level
define('DBMSG_DUMP',    107);      // DUMP ONLY
define('DBMSG_NOLOG',   108);      // CLI dump ONLY
define('DBMSG_DIE',     109);      // die message
define('DBMSG_CLI',     256);      // CLI BIT Mask for CLI_DEBUG

const EMPTY_MSG = " EMPTY\n";
const EXCLUSION = [
    'debuglog' => 1,
    'password' => 1,
    'passwd' => 1,
];
/*
    アプリケーションデバッグ情報
*/
//$debug_log_str = [];
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
// ログレベルの分解
function sep_level($lvl) {
    if($lvl === FALSE) return FALSE;
    $mod = ($lvl % DBMSG_CLI);
    $cli = ($lvl - $mod)/DBMSG_CLI;
    if($mod > DBMSG_DIE) return FALSE;          // Invalid LEVEL
    if($mod >= DBMSG_LEVEL) $mod = -$mod;       // SystemLog
    else if($mod > DEBUG_LEVEL) return FALSE;   // out of LEVEL
    return [$cli,$mod];
}
//==========================================================================
// ログリセット
function log_reset($lvl) {
//    global $debug_log_str;
    $logging = sep_level($lvl);
    if($logging === FALSE) return;
    list($cli,$lvl) = $logging;
    if($lvl < -DBMSG_SYSTEM) return; // no-logging level
//    if(in_array($lvl,[DBMSG_DUMP, DBMSG_NOLOG, DBMSG_DIE, DBMSG_CLI])) return;      // no-logging level
//    unset($debug_log_str[$lvl]);
    MySession::set_paramIDs("debuglog.{$lvl}",NULL);
}
//==========================================================================
// コマンドラインログの表示
function debug_dump(...$items) {
    debug_log(DBMSG_CLI,$items);
}
//==========================================================================
// NULL値の表示
function get_null_value($arg) {
	if($arg === []) $val = '[]';
	else if($arg === NULL) $val = 'NULL';
	else if(is_bool($arg)) $val = 'FALSE';
<<<<<<< HEAD
	else if($arg === 0) $val = '0';
=======
	else if(is_int($arg)) $val = '0';
	else if($arg !== '') $val = '"0"';
>>>>>>> dev/master
	else $val = '""';
	return "{$val}\n";
}
//==========================================================================
// ログの記録または表示
function debug_log($lvl,...$items) {
<<<<<<< HEAD
    if(	!in_array($lvl,[DBMSG_DIE,DBMSG_DUMP]) ||
		(defined('CLI_SUPPRESS') && CLI_DEBUG) ||
		(!CLI_DEBUG && ($lvl === DBMSG_CLI)) ) return;    // WEB request && NOLOG will be RETURN
=======
	if(CLI_DEBUG) {
		if(defined('CLI_SUPPRESS')) return;		// dump suppress in command-line mode
	} else if($lvl === DBMSG_CLI) return;    	// not command-line invoked
>>>>>>> dev/master
    $logging = sep_level($lvl);
    if($logging === FALSE) return;
    list($cli,$lvl) = $logging;
    if($lvl < -DBMSG_DIE) return;
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
<<<<<<< HEAD
=======
//				echo "{$key}=NULL".get_null_value($val)."<br>\n";
>>>>>>> dev/master
                    $dmp .= get_null_value($val);
                } else if(is_array($val)) {
                    $dmp .= "array(" . count($val) . ")\n";
                    $dmp .= $dump_object($val,$indent+1);
                } else if(is_scalar($val)) {
                    if(is_int($val)) $dmp .= "{$val}\n";
                    else if(is_bool($val)) $dmp .= "TRUE\n";
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
                $arg = ($arg === NULL) ? 'NULL': wordwrap(control_escape($arg),86,"\n");
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
                        $dmp_msg .= "{$msg} : " . get_null_value($obj);
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
//    global $debug_log_str;
    $dmp_info = $dump_log_info($items);
    if(!empty($dmp_info)) {
        switch($lvl) {
        case -DBMSG_DIE:     die("<pre>\n{$dmp_info}\n</pre>\n");
        case -DBMSG_DUMP:    echo "<pre>\n{$dmp_info}\n</pre>\n"; break;
        case -DBMSG_NOLOG:   // $lvl < 0
        default:
            if((-DBMSG_LEVEL < $lvl && $lvl < 0) || (CLI_DEBUG && $cli !== 0)) {
                echo "{$dmp_info}\n";
            } else if(!CLI_DEBUG) {     // WEB Access logging $lvl, donot worry CLI_MODE
//                if(isset($debug_log_str[$lvl])) $dmp_info = $debug_log_str[$lvl] . $dmp_info;
//                MySession::set_paramIDs("debuglog.{$lvl}",$dmp_info);
				MySession::set_paramIDs("debuglog.{$lvl}",$dmp_info,TRUE);
//                $debug_log_str[$lvl] = $dmp_info;
            }
        }
    }
}
