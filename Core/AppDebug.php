<?php
/* -------------------------------------------------------------
 * Biscuitフレームワーク
 *  AppDebug:    デバッグ用のメッセージ出力クラス
 */

define('DEBUG', false);
define('DEBUG_LEVEL', 10);
const EMPTY_MSG = " EMPTY\n";

/*
    アプリケーションデバッグ情報
*/
class APPDEBUG {
    private static $MsgLevel;          // 出力メッセージレベル
    private static $RunTime;           // 測定開始時刻
    public static $LevelMsg;           // レベルメッセージの配列

    //===============================================================================
    // メッセージ出力レベルの設定
    public static function INIT($lvl){
        self::$MsgLevel = $lvl;
        self::$LevelMsg = array('');
    }
    //==================================================================================================
    // 実行時間測定開始
    public static function RUN_START() {
        self::$RunTime = microtime(TRUE);
    }
    //==================================================================================================
    // 実行時間表示
    public static function RUN_FINISH($lvl) {
        $tm = round((microtime(TRUE) - self::$RunTime), 2);     // 少数2位まで
        $maxmem = round(memory_get_peak_usage()/(1024*1024),2);
        self::arraydump($lvl,[
            "実行時間" => "{$tm} 秒",
            "メモリ消費" => "最大: {$maxmem} MB",
        ]);
    }
    //==================================================================================================
    // メッセージ要素の並替え
    public static function MSG_SORT() {
        ksort( self::$LevelMsg );
    }
    //==================================================================================================
    // バックトレースから呼び出し元の情報を取得
    private static function backtraceinfo(){
        $dbinfo = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,3);    // 呼び出し元のスタックまでの数
        $dbpath = str_replace('\\','/',$dbinfo[1]['file']);             // Windowsパス対策
        list($pp,$fn) = extractFileName($dbpath);
        $fn .= "(".$dbinfo[1]['line'].")";
        $str = "{$fn}->" . $dbinfo[2]['function'];
        return $str;
    }
    //==================================================================================================
    // デバッグ用のメッセージ出力
    public static function MSG($lvl,$obj, $msg = ''){
        if(!DEBUGGER) return;
        $info = self::backtraceinfo();
        self::db_echo($lvl, "<pre>\n");
        if(is_scalar($obj)) {
            if($msg !== '') $msg .= ": ";
            if($obj ==='') $obj ='NULL'; else $obj= wordwrap($obj,86,"\n");
            self::db_echo($lvl,"{$info}\n{$msg}{$obj}\n");
        } else {
            $m = "{$info} obj dump\n======= {$msg} =======\n";
            self::db_echo($lvl,$m);
            if(empty($obj)) self::db_echo($lvl,EMPTY_MSG);
            else self::dumpobj($obj,0, $lvl);
        }
        self::db_echo($lvl, "</pre>\n");
    }
    //===============================================================================
    // デバッグダンプ
    public static function DumpMessage(){
        echo 'RunTime：' . (time() - self::$RunTime) . '秒';
        echo "================================================\n";
        foreach(self::$LevelMsg as $key => $msg) {
            echo "メッセージ{$key}\n{$msg}";
        }
    }
    //===============================================================================
    // メッセージ登録
    private static function db_echo($lvl,$msg) {
        if($lvl < 0) {
            echo $msg;
        } else if($lvl < self::$MsgLevel) {
            if(isset(self::$LevelMsg[$lvl])) self::$LevelMsg[$lvl] .= $msg;
            else self::$LevelMsg[$lvl] = $msg;
        }
    }
    //===============================================================================
    // デバッグ情報ダンプ
    public static function debug_dump($lvl,$arr = [] ,$mlvl = 0){
        if(!DEBUGGER || ($lvl <= 0)) return;
        self::db_echo($mlvl, "<pre>\n");
        foreach($arr as $msg => $obj) {
            self::db_echo($mlvl,"------- {$msg} -----\n");
            if(empty($obj)) self::db_echo($mlvl,EMPTY_MSG);
            else foreach($obj as $key => $val) {
                if(is_array($val)) {    // 配列出力
                    self::dumpobj($val,0,$mlvl);
                } else
                self::db_echo($mlvl,(is_numeric($key))? "{$val}\n": "{$key} = {$val}\n");
            }
        }
        self::db_echo($mlvl,"</pre>\n");
    }
    //===============================================================================
    // デバッグ配列のダンプ
    public static function arraydump($lvl,$arr=[]) {
        if(!DEBUGGER) return;
        $info = self::backtraceinfo();
        if(is_array($lvl)) {
            $arr = $lvl;
            $lvl = 0;
        }
        self::db_echo($lvl, "<pre>\n{$info}\n");
        if(is_array($arr)) {                        // 配列要素の出力
            foreach($arr as $msg => $obj) {
                self::db_echo($lvl, "***** {$msg} *****\n");
                if(empty($obj)) self::db_echo($lvl,EMPTY_MSG);
                else self::dumpobj($obj,0, $lvl);
                self::db_echo($lvl,"\n");
            }
        } else
            self::db_echo($lvl, $arr);              // スカラー要素の出力
        self::db_echo($lvl, "</pre>\n");
    }
    //===============================================================================
    // 配列のダンプ
    private static function dumpobj($obj,$indent,$level){
        if(is_array($obj)) {    // 配列出力
        foreach($obj as $key => $val) {
            self::db_echo($level, str_repeat(' ',$indent*2) . "[{$key}] = ");
            if($val == NULL) {
                self::db_echo($level, "NULL\n");
            } else if(is_scalar($val)) {
                $val = htmlspecialchars($val);
                self::db_echo($level, "'{$val}'\n");
            } else if(is_array($val)) {
                self::db_echo($level, "array(" . count($val) . ")\n");
                self::dumpobj($val,$indent+1,$level);
            } else {
                self::db_echo($level, gettype($val) . "\n");
            }
            }
        } else {    // スカラー出力
            self::db_echo($level, $obj);
        }
    }
}
// デバッグクラス
//global $DBGbgMSG;
//$DbgMSG = new APPDEBUG();
