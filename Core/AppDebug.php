<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  デバッグ用のメッセージ出力処理関数
 */
define('DBMSG_LEVEL',   100);      // logging level
define('DBMSG_ERROR',   100);      // for ERROR
define('DBMSG_DEBUG',	108);      // for Temporary DEBUG Message
define('DBMSG_HELPER',	109);      // for Helper
define('DBMSG_RESOURCE',110);      // for Style/Script
define('DBMSG_HANDLER', 111);      // for DB-Handler
define('DBMSG_MODEL',   112);      // for Model
define('DBMSG_VIEW',    113);      // for View, Helper
define('DBMSG_LOCALE',  114);      // for LangUI
define('DBMSG_SYSTEM',  115);      // for Main, App, Controller
define('DBMSG_DUMP',    116);      // DUMP ONLY
define('DBMSG_NOLOG',   117);      // CLI dump ONLY
define('DBMSG_STDERR',  118);      // STDERR output
define('DBMSG_DIE',     119);      // die message
define('DBMSG_NONE',    false);    // none
define('DBMSG_CLI',     256);      // CLI BIT Mask for CLI_DEBUG

if(!defined('DEBUG_LEVEL'))  define('DEBUG_LEVEL', 10);

const EMPTY_MSG = " EMPTY\n";
const EXCLUSION = [
    'Syslog' => 1,
    'password' => 1,
    'passwd' => 1,
];
/*
    アプリケーションデバッグ情報
*/
class sysLog {
    public static $AppName	= 'app';    // アプリケーション名
    public static $Controller='cont';   // 実行コントローラ名
    public static $Method	= 'auto';	// 呼出しメソッド名
	public static $run_time = 0;
//==============================================================================
// 静的クラスでのシステム変数初期化
public static function __Init($appname,$controller,$method) {
	static::$AppName 	= $appname;
	static::$Controller = $controller;
	static::$Method		= $method;
	// 設定される前に吐き出されたログを取込む
	MySession::syslog_RenameID(['app'=>$appname,'cont'=>$controller]);
}
//==============================================================================
// ログ識別子
public static function getLogName($id = '') {
	return implode('.',[static::$Controller,$id]);
}
//==============================================================================
// ログ取得URI
public static function getLogURI() {
	return implode('/',[static::$AppName,static::$Controller]);
}
//==========================================================================
// 実行時間測定開始
public static function run_start() {
    static::$run_time = microtime(TRUE);
}
//==========================================================================
// 実行時間表示
public static function run_time($lvl) {
    $tm = round((microtime(TRUE) - static::$run_time), 2);     // 少数2位まで
    $maxmem = round(memory_get_peak_usage()/(1024*1024),2);
    $sec = LangUI::get_value('debug','.Second');
    debug_log($lvl,[
        "#ExecTime" => "{$tm} {$sec}",
        "#MaxMemory" => "{$maxmem} MB",
    ]);
}
//==============================================================================
//  最終ログ
public static function last_logs() {
	$cont = MySession::getEnvIDs('sysVAR.controller');
    return self::get_logs($cont);
}
//==============================================================================
//  デバッグログ出力
public static function get_logs($cont) {
	$id_name = ucfirst(strtolower($cont));
    $current_log = MySession::syslog_GetData($id_name);
    if($current_log !== NULL) ksort($current_log);
    return $current_log;
}
//==========================================================================
// 強制ダンプ
public static function dump($items) {
    debug_log((CLI_DEBUG)?DBMSG_STDERR:DBMSG_DUMP,$items);
}
//==========================================================================
// デバッグレベルへのダンプ
public static function debugLvl($items) {
    debug_log(DBMSG_DEBUG,$items);
}
//==========================================================================
// コマンドラインログの表示
public static function debug($items) {
    debug_log(DBMSG_CLI,$items);
}
public static function halt($items) {
    debug_log(DBMSG_DIE,$items);
}
public static function stderr($items) {
    debug_log(DBMSG_STDERR,$items);
}

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
    $logging = sep_level($lvl);
    if($logging === FALSE) return;
    list($cli,$lvl) = $logging;
    if($lvl < -DBMSG_SYSTEM) return; // no-logging level
    MySession::syslog_SetData(sysLog::getLogName($lvl),NULL);
}
//==========================================================================
// NULL値の表示
function get_null_value($arg) {
	if($arg === []) $val = '[]';
	else if($arg === NULL) $val = 'NULL';
	else if(is_bool($arg)) $val = 'FALSE';
	else if(is_int($arg)) $val = '0';
	else if($arg !== '') $val = '"0"';
	else $val = "''";
	return "{$val}\n";
}
//==========================================================================
// sysLogクラスのエイリアス
function debug_dump($items) {
	sysLog::dump($items);
}
function debug_die($items) {
	sysLog::halt($items);
}
function debug_stderr($items) {
	sysLog::stderr($items);
}
function debug($items) {
	sysLog::debugLvl($items);
}
function stderr($str) {
	fputs(STDERR,"{$str}\n");
}
// dummy function
function xdebug_dump($items) {}
function xdebug_die($items) {}
function xdebug_log($items) {}
function reuire_debugbar() {
	$debug = __DIR__ . '/Template/View/debugbar.php';
	require_once($debug);
}
//==========================================================================
// ログの記録または表示
function debug_log($lvl,...$items) {
	if(CLI_DEBUG) {
		if(defined('CLI_SUPPRESS')) return;		// dump suppress in command-line mode
	} else if($lvl === DBMSG_CLI) return;    	// not command-line invoked
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
			$is_scalar_array = function($arr) {
				foreach($arr as $element) if(!is_scalar($element)) return FALSE;
				return TRUE;
			};
			if(array_values($obj) === $obj && $is_scalar_array($obj) && $indent>1) {
				$vals = implode(", ",array_map(function($v) {
					return (is_string($v)) ? str_replace(["\r\n","\r","\n","\t"],['\r\n', '\r', '\n', '\t'], $v) :$v;
				}, $obj));
				$dmp = str_repeat(' ',$indent*2) . "[ {$vals} ]\n";
			} else {
	            $dmp = "";
				foreach($obj as $key => $val) {
					if(array_key_exists($key,EXCLUSION)) continue;      // not dump element check
					$dmp .= str_repeat(' ',$indent*2) . "[{$key}] = ";
					if(gettype($val)==='object') {
						$clsnm = get_class($val);
						$dmp .= "[{$clsnm}]\n"; // print_r($val,true);
					} else if(empty($val)) {
						$dmp .= get_null_value($val);
					} else if(is_array($val)) {
						$dmp .= "array(" . count($val) . ")\n";
						$dmp .= $dump_object($val,$indent+1);
					} else if(is_scalar($val)) {
						if(is_int($val)) $dmp .= "{$val}\n";
						else if(is_bool($val)) $dmp .= "TRUE\n";
						else {
//							$val = htmlspecialchars(control_escape($val));
							$dmp .= "'{$val}'\n";
						}
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
					if(is_int($msg) && $msg < 0 && is_scalar($obj)) {
						if(mb_substr($obj,0,1) === '#') {
							$obj[0] = '.';
							$obj = LangUI::get_value('debug',$obj);
						}
                        $dmp_msg .= "********* {$obj} *********\n";
//						$dmp_msg .= str_repeat("-", 40)."\n";
					} else {
						if(mb_substr($msg,0,1) === '#') {
							$msg[0] = '.';
							$msg = LangUI::get_value('debug',$msg);
						}
						if(gettype($obj)==='object') {
							$clsnm = get_class($obj);
							$dmp_msg .= "{$msg} : Class[{$clsnm}]\n";// . print_r($obj,true);
						} else if(empty($obj)) {
							$dmp_msg .= "{$msg} : " . get_null_value($obj);
						} else if(is_scalar($obj)) {
							$dmp_msg .= "{$msg} : {$obj}\n";
						} else if(is_array($obj)) {
							$dmp_msg .= "===== {$msg} =====\n";
							if(empty($obj)) $dmp_msg .= EMPTY_MSG;
							else $dmp_msg .= $dump_object($obj,0);
						} else {
							$dmp_msg .= "{$msg} : Object=".gettype($obj)."\n";
							$dmp_msg .= print_r($obj,TRUE)."\n";
						}
					}
                }
            }
        }
        return "{$dmp_msg}\n";
    };
//    global $debug_log_str;
    $dmp_info = $dump_log_info($items);
    if(!empty($dmp_info)) {
		$pre_dump = (CLI_DEBUG) ? "\n{$dmp_info}\n" : "<pre>\n{$dmp_info}\n</pre>\n";
        switch($lvl) {
        case -DBMSG_STDERR:  stderr($dmp_info); break;
        case -DBMSG_DIE:    //Session::CloseSession(); // Session Close before die();
							die($pre_dump);
        case -DBMSG_DUMP:    echo $pre_dump; break;
        case -DBMSG_NOLOG:   $lvl  = -99;
        default:
            if((-DBMSG_LEVEL < $lvl && $lvl < 0) || (CLI_DEBUG && $cli !== 0)) {
                echo "{$dmp_info}\n";
            } else if(!CLI_DEBUG) {     // WEB Access logging $lvl, donot worry CLI_MODE
				MySession::syslog_SetData(sysLog::getLogName($lvl),$dmp_info,TRUE);
            }
        }
    }
}
