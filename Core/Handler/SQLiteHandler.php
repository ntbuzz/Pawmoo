<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	SQLiteHandler: SQLite3 データベースへのアクセスハンドラ
 *
 */
//==============================================================================
//	SQLite3用の抽象メソッドを実装する
class SQLiteHandler extends SQLHandler {
	protected $NULL_ORDER = '';	// NULL の順序

//==============================================================================
//	コンストラクタ： データベースのテーブルに接続する
	function __construct($table,$primary) {
		parent::__construct($table,'SQLite',$primary);
	}
//==============================================================================
//	Connect: テーブルに接続し、columns[] 配列にフィールド名をセットする
protected function Connect($table) {
	// テーブル属性を取得
	$sql = "PRAGMA table_info({$table});";
	$rows = $this->dbb->query($sql);
	$columns = array();
	while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
		$columns[$row['name']] = strtolower($row['type']);
	}
	return $columns;
}
//==============================================================================
//	field concatiname
public function fieldConcat($sep,$arr) {
	$bind = array_map(function($fn) {return "IFNULL({$fn},'')";},$arr);
	$sep = (empty($sep)) ? '||' : "||'{$sep}'||";
	return implode($sep,$bind);
}
//==============================================================================
//	DROP TABLE/VIEW CASCADE
public function drop_sql($kind,$table) {
	return "DROP {$kind} IF EXISTS {$table};";
}
//==============================================================================
//	TRUNCATE TABLE
public function truncate_sql($table) {
	return "DELETE FROM {$table};VACUUM;";
}
//==============================================================================
//	RESET SEQ to PRIMARY
protected function reset_seq($table,$primary) {
	return FALSE;
}
//==============================================================================
//	CONCAT FIELDS
protected function concat_fields($arr) {
	return '('.implode('||',$arr).')';
}
//==============================================================================
//	doQuery: 	SQLを発行する
public function doQuery($sql) {
	$this->rows = $this->dbb->query($sql);
	return $this->rows;
}
//==============================================================================
//	fetch_array: 	レコードを取得してカラム配列を返す
public function fetch_array() {
	$row = $this->rows->fetchArray(SQLITE3_ASSOC); //またはSQLITE3_NUM
	return $this->fetch_convert($row,false);	// 型変換
}
//==============================================================================
//	getLastError: 	レコードを取得してカラム配列を返す
public function getLastError() {
	return "SQLite3 ERROR";
//		return sqlite_last_error($this->rows);
}
//==============================================================================
//	レコードの追加 
//==============================================================================
public function insertRecord($row) {
	$row = $this->sql_safe_convert($this->sql_str_quote($row,["'"],["''"]));	// 書き込み型変換
	// UPDATE OR INSERT => REPLACE SQL生成
	$kstr = '"' . implode('","', array_keys($row)) . '"';
	$vstr = implode(",", $row);

	$sql = "INSERT INTO {$this->raw_table} ({$kstr}) VALUES ({$vstr});";
	error_reporting(E_ERROR);
	$rows = $this->doQuery($sql);
	if(!$rows) {
		echo 'ERROR:'.$this->getLastError()."\n{$sql}\n";
		return FALSE;
	}
// SQLite3 old version cannot support 'RETURNING'.
//	return $this->fetchDB();
	return [];
}
//==============================================================================
//	レコードの更新 $row[key] value
//==============================================================================
public function updateRecord($wh,$row) {
	$row = $this->sql_safe_convert($this->sql_str_quote($row,["'"],["''"]));	// 書き込み型変換
	list($pkey,$pval) = array_first_item($wh);
	unset($row[$pkey]);			// プライマリキーは削除しておく
	$where = " WHERE \"{$pkey}\"={$pval}";		// プライマリキー名を取得
	$set = " SET"; $sep = " ";				// UPDATE する時の代入文
	foreach($row as $key => $val) {
		$set .= "{$sep}\"{$key}\"={$val}";
		$sep = ", ";
	}
	// UPSERT 文を生成
	$sql = "UPDATE \"{$this->raw_table}\"{$set}{$where};";
	error_reporting(E_ALL);
	$rows = $this->doQuery($sql);
	if(!$rows) {
		echo 'ERROR:'.$this->getLastError()."\n{$sql}\n";
		return FALSE;
	}
// SQLite3 old version cannot support 'RETURNING'.
	$a = $this->doQueryBy($pkey,$pval);
	return $a;
}
//==============================================================================
//	レコードの更新または追加 (REPLACE)
//	on confilict は SQLite 3.24.0 以降
public function upsertRecord($wh,$row) {
	$row = array_merge($wh,$row);			// INSERT 用にプライマリキー配列とデータ配列をマージ
	$row = $this->sql_safe_convert($this->sql_str_quote($row,["'"],["''"]));	// 書き込み型変換
	list($pkey,$pval) = array_first_item($wh);
	// UPDATE OR INSERT => REPLACE SQL生成
	$kstr = '"' . implode('","', array_keys($row)) . '"';
	$vstr = implode(',', $row);
	$sql = "REPLACE INTO \"{$this->raw_table}\" ({$kstr}) VALUES ({$vstr});";
	error_reporting(E_ALL);
	$rows = $this->doQuery($sql);
	if(!$rows) {
		echo 'ERROR:'.$this->getLastError()."\n{$sql}\n";
		return FALSE;
	}
	// 指定条件でレコードを取り出す
	$a = $this->doQueryBy($pkey,$pval);
	return $a;
}

}