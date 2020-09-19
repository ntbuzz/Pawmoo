<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	SQLiteHandler: SQLite3 データベースへのアクセスハンドラ
 *
 */
//==============================================================================
//	SQLite3用の抽象メソッドを実装する
class SQLiteHandler extends SQLHandler {
//==============================================================================
//	コンストラクタ：　データベースのテーブルに接続する
	function __construct($table) {
		parent::__construct($table,'SQLite');
	}
//==============================================================================
//	Connect: テーブルに接続し、columns[] 配列にフィールド名をセットする
protected function Connect() {
	// テーブル属性を取得
	$sql = "PRAGMA table_info({$this->table});";
	$rows = $this->dbb->query($sql);
	$this->columns = array();
	while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
		$this->columns[$row['name']] = $row['name'];
	}
}
//==============================================================================
//	doQuery: 	SQLを発行する
public function doQuery($sql) {
	debug_log(3,['SQL' => $sql]);
	$this->rows = $this->dbb->query($sql);
	return $this->rows;
}
//==============================================================================
//	fetchDB: 	レコードを取得してカラム配列を返す
public function fetchDB() {
	return $this->rows->fetchArray(SQLITE3_ASSOC); //またはSQLITE3_NUM
}
//==============================================================================
//	getLastError: 	レコードを取得してカラム配列を返す
public function getLastError() {
//		return sqlite_last_error($this->rows);
}
//==============================================================================
//	レコードの追加 
//==============================================================================
public function insertRecord($row) {
	$this->sql_safequote($row);
	// UPDATE OR INSERT => REPLACE SQL生成
	$kstr = '"' . implode('","', array_keys($row)) . '"';
	$vstr = "'" . implode("','", $row) . "'";

	$sql = "INSERT INTO {$this->table} ({$kstr}) VALUES ({$vstr});";
	error_reporting(E_ERROR);
	$rows = $this->doQuery($sql);
	if(!$rows) {
		echo 'ERROR:'.$this->getLastError()."\n".$sql;
	}
}
//==============================================================================
//	レコードの更新 $row[key] value
//==============================================================================
public function updateRecord($wh,$row) {
	$this->sql_safequote($row);
	list($pkey,$pval) = array_first_item($wh);
	unset($row[$pkey]);			// プライマリキーは削除しておく
	$where = " WHERE \"{$pkey}\"={$pval}";		// プライマリキー名を取得
	$set = " SET"; $sep = " ";				// UPDATE する時の代入文
	foreach($row as $key => $val) {
		$set .= "{$sep}\"{$key}\"='{$val}'";
		$sep = ", ";
	}
	// UPSERT 文を生成
	$sql = "UPDATE \"{$this->table}\"{$set}{$where};";
	error_reporting(E_ERROR);
	$rows = $this->doQuery($sql);
	if(!$rows) {
		echo 'ERROR:'.$this->getLastError()."\n".$sql;
	}
}

}
