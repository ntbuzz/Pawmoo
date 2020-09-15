<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  AppDebug:    デバッグ用のメッセージ出力クラス
 */

const EMPTY_MSG = " EMPTY\n";

/*
    アプリケーションデバッグ情報
*/
class APPDEBUG {
    private static $MsgLevel;          // 出力メッセージレベル
    private static $RunTime;           // 測定開始時刻
    public static $LevelMsg;           // レベルメッセージの配列

    //==========================================================================
    // メッセージ出力レベルの設定
    public static function INIT($lvl){
        self::$MsgLevel = $lvl;
        self::$LevelMsg = array('');
    }
    //==========================================================================
    // 実行時間測定開始
    public static function RUN_START() {
        self::$RunTime = microtime(TRUE);
    }
    //==========================================================================
    // 実行時間表示
    public static function RUN_FINISH($lvl) {
        $tm = round((microtime(TRUE) - self::$RunTime), 2);     // 少数2位まで
        $maxmem = round(memory_get_peak_usage()/(1024*1024),2);
//        self::LOG($lvl,[
        self::LOG($lvl,[
                "実行時間" => "{$tm} 秒",
            "メモリ消費" => "最大: {$maxmem} MB",
        ]);
    }
    //==========================================================================
    // バックトレースから呼び出し元の情報を取得
    private static function backtraceinfo($stop=FALSE){
        $dbinfo = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,6);    // 呼び出し元のスタックまでの数
        array_shift($dbinfo);   // 自クラスの情報は不要
        if($stop) { 
            echo "TYPE:".get_class($dbinfo[2]['object'])."\n";
            var_dump($dbinfo[2]['object']);
            exit;
        }
        $str = "";
        foreach($dbinfo as $stack) {
            $path = str_replace('\\','/',$stack['file']);             // Windowsパス対策
            list($pp,$fn,$ext) = extract_path_file_ext($path);
            $func = "{$fn}({$stack['line']})";
            $str = (empty($str)) ? $func : "{$func}->{$str}";
        }
        return "[TRACE]::{$str}";
    }
    //==========================================================================
    // メッセージ要素の並替え
    public static function LOG_SORT() {
        ksort( self::$LevelMsg );
    }
    //==============================================================================
    //  デバッグログ出力
    public static function LOG($lvl,...$items) {
        if(!DEBUGGER) return;
        $info = self::backtraceinfo($lvl > 100);
        self::dbEcho($lvl, "<pre>\n{$info}\n");
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
                    $dmp .= "'{$val}'\n";
                }
            }
            return $dmp;
        };
        foreach($items as $arg) {
            if(is_scalar($arg)) {
                if(empty($arg)) $arg ='NULL'; else $arg= wordwrap($arg,86,"\n");
                self::dbEcho($lvl,"{$arg}\n");
            } else if(is_array($arg)) {                        // 配列要素の出力
                foreach($arg as $msg => $obj) {
                    if(is_scalar($obj)) {
                        self::dbEcho($lvl, "{$msg} : {$obj}\n");
                    } else {
                        self::dbEcho($lvl, "===== {$msg} =====\n");
                        if(empty($obj)) self::dbEcho($lvl,EMPTY_MSG);
                        else self::dbEcho($lvl,$dump_object($obj,0)."\n",TRUE);
                    }
                }
            }
        }
        self::dbEcho($lvl, "\n</pre>\n");
        if($lvl === -99) exit;
    }
    //==========================================================================
    // メッセージ登録
    private static function dbEcho($lvl,$msg,$is_safe = FALSE) {
        if(empty($msg)) return;
        if($is_safe && !empty($msg)) $msg = htmlspecialchars($msg);
        if($lvl < 0) {
            echo $msg;
        } else if($lvl < self::$MsgLevel) {
            if(isset(self::$LevelMsg[$lvl])) self::$LevelMsg[$lvl] .= $msg;
            else self::$LevelMsg[$lvl] = $msg;
        }
    }

}
