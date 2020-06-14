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
        self::DebugDump($lvl,[
            "実行時間" => "{$tm} 秒",
            "メモリ消費" => "最大: {$maxmem} MB",
        ]);
    }
    //==========================================================================
    // メッセージ要素の並替え
    public static function MSG_SORT() {
        ksort( self::$LevelMsg );
    }
    //==========================================================================
    // バックトレースから呼び出し元の情報を取得
    private static function backtraceinfo($stop=FALSE){
        $dbinfo = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,3);    // 呼び出し元のスタックまでの数
        if($stop) { 
            echo "TYPE:".get_class($dbinfo[2]['object'])."\n";
            var_dump($dbinfo[2]['object']);
            exit;
        }
        $dbpath = str_replace('\\','/',$dbinfo[1]['file']);             // Windowsパス対策
        list($pp,$fn) = extract_path_filename($dbpath);
        $fn .= "(".$dbinfo[1]['line'].")";
        if(isset($dbinfo[2]['object'])) {
            $pp = get_class($dbinfo[2]['object']);  // 呼出し元のクラス名
            if(substr($fn,0,strlen($pp)) !== $pp) $fn = "{$pp}::{$fn}";
        }
        $str = "{$fn}->" . $dbinfo[2]['function'];
        return $str;
    }
    //==========================================================================
    // デバッグ用のメッセージ出力
    public static function MSG($lvl,$obj, $msg = '',$stop=FALSE){
        if(!DEBUGGER) return;
        $info = self::backtraceinfo($stop);
        self::dbEcho($lvl, "<pre>\n");
        if(is_scalar($obj)) {
            if($msg !== '') $msg .= ": ";
            if($obj ==='') $obj ='NULL'; else $obj= wordwrap($obj,86,"\n");
            self::dbEcho($lvl,"{$info}\n{$msg}{$obj}\n");
        } else {
            $m = "{$info} obj dump\n======= {$msg} =======\n";
            self::dbEcho($lvl,$m);
            if(empty($obj)) self::dbEcho($lvl,EMPTY_MSG);
            else self::dumpObject($obj,0, $lvl);
        }
        self::dbEcho($lvl, "\n</pre>\n");
    }
    //==========================================================================
    // デバッグダンプ
    public static function DumpMessage(){
        echo 'RunTime：' . (time() - self::$RunTime) . '秒';
        echo str_repeat("=", 30)."\n";
        foreach(self::$LevelMsg as $key => $msg) {
            echo "メッセージ{$key}\n{$msg}";
        }
    }
    //==========================================================================
    // メッセージ登録
    private static function dbEcho($lvl,$msg,$is_safe = FALSE) {
        if(empty($msg)) return;
        if($is_safe && $msg !== '') $msg = htmlspecialchars($msg);
        if($lvl < 0) {
            echo $msg;
        } else if($lvl < self::$MsgLevel) {
            if(isset(self::$LevelMsg[$lvl])) self::$LevelMsg[$lvl] .= $msg;
            else self::$LevelMsg[$lvl] = $msg;
        }
    }
    //==========================================================================
    // デバッグ配列のダンプ
    public static function DebugDump($lvl,$arr=[]) {
        if(!DEBUGGER) return;
        $info = self::backtraceinfo();
        if(is_array($lvl)) {
            $arr = $lvl;
            $lvl = 0;
        }
        self::dbEcho($lvl, "<pre>\n{$info}\n");
        if(is_array($arr)) {                        // 配列要素の出力
            foreach($arr as $msg => $obj) {
                self::dbEcho($lvl, "***** {$msg} *****\n");
                if(empty($obj)) self::dbEcho($lvl,EMPTY_MSG);
                else self::dumpObject($obj,0, $lvl);
                self::dbEcho($lvl,"\n");
            }
        } else
            self::dbEcho($lvl, $arr,TRUE);              // スカラー要素の出力
        self::dbEcho($lvl, "</pre>\n");
    }
    //==========================================================================
    // 配列のダンプ
    private static function dumpObject($obj,$indent,$level){
        if(is_array($obj)) {    // 配列出力
        foreach($obj as $key => $val) {
            self::dbEcho($level, str_repeat(' ',$indent*2) . "[{$key}] = ");
            if($val == NULL) {
                self::dbEcho($level, "NULL\n");
            } else if(is_scalar($val)) {
                self::dbEcho($level, "'{$val}'\n",TRUE);
            } else if(is_array($val)) {
                self::dbEcho($level, "array(" . count($val) . ")\n");
                self::dumpObject($val,$indent+1,$level);
            } else {
                self::dbEcho($level, gettype($val) . "\n",TRUE);
            }
            }
        } else if(is_scalar($obj)) {    // スカラー出力
            self::dbEcho($level, $obj,TRUE);
        } else {
            self::dbEcho($level, 'UNKNOW $obj',TRUE);
        }
    }
}
