<?php
/*
    レコードロックが必要なアプリケーションで使用する
    データベースは SQLite3 の app/config/lock.db 固定
    コアシステムとは独立しているのでSQLを直接扱う
*/
class LockDB {
    const LOCK_TABLE = 'table_lock';
    private static $dbb;                    // SQLite3 ハンドラ
    private static $columns;
    private static $owner;                  // ロックオーナー
//---------------------------------------------------------------------------------------------
static public function LockStart($owner = NULL) {
    if(defined('LOCK_DB') && file_exists(LOCK_DB)) {
        static::$owner = $owner;
        static::$dbb = new SQLite3(LOCK_DB);
        $lock_tbl = self::LOCK_TABLE;
        //	Connect: テーブルに接続し、columns[] 配列にフィールド名をセットする
    	$sql = "PRAGMA table_info({$lock_tbl});";
    	$rows = static::$dbb->query($sql);
	    static::$columns = array();
    	while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
	    	static::$columns[$row['name']] = $row['name'];
	    }
    } else static::$dbb = NULL;
}
//---------------------------------------------------------------------------------------------
static public function SetOwner($owner) {
    static::$owner = (empty($owner)) ? 'Guest' : $owner;
}
//---------------------------------------------------------------------------------------------
static public function GetOwner() {
    return static::$owner;
}
//---------------------------------------------------------------------------------------------
static public function LockEnd() {
    if(static::$dbb) static::$dbb->close();
}
//---------------------------------------------------------------------------------------------
//  $table  指定テーブル
//  $row    プライマリーキー
//  $owner  Lockをかけるオーナー
//  $limit  Lockの有効期間
//  ロックできたらTRUE,失敗なら FALSE を返す
static public function Locked($table,$pkey,$limit) {
    if(static::$dbb === NULL || static::$owner === NULL) return FALSE;
    $lock_tbl = self::LOCK_TABLE;
    $where = "WHERE (table='{$table}') AND (row={$pkey})";
    $sql = "SELECT * FROM '{$lock_tbl}' {$where};";
    $rows = static::$dbb->query($sql);
    $row = $rows->fetchArray(SQLITE3_ASSOC);
    $now = time();      // 現在時刻
    if($row !== false) {
        // 別のオーナーがロックをかけている最中
        if((static::$owner !== $row['owner']) && ($now >= $row['limit'])) {
            return FALSE;
        }
        $sql = "UPDATE \"{$lock_tbl}\" SET \"owner\"='{$this->owner}',\"limit\"='{$limit}' {$where};";
    } else {
    	$sql = "INSERT INTO \"{$lock_tbl}\" ('table','row','owner','limit') VALUES ({$table},{$pkey},{$this->owner},{$limit});";
    }
}
//---------------------------------------------------------------------------------------------
static public function UnLocke($table,$pkey) {
    if(static::$dbb === NULL || static::$owner === NULL) return FALSE;
    $where = "WHERE (table='{$table}') AND (row={$pkey})";
    $sql = "SELECT * FROM '{$lock_tbl}' {$where};";
    $rows = static::$dbb->query($sql);
    $row = $rows->fetchArray(SQLITE3_ASSOC);
    if($row !== false) {
        // 自分がロックをかけている または別のオーナーがかけたロックが時間切れ
        if(static::$owner === $row['owner']) {
            // レコードデリート
        }
    }
}

}
