<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	SQLiteHandler: SQLite3 データベースへのアクセスハンドラ
 *
 */
//==============================================================================
//	SQLite3用の抽象メソッドを実装する
class MySQLHandler extends SQLHandler {
//==============================================================================
//	コンストラクタ：　データベースのテーブルに接続する
	function __construct($table) {
		parent::__construct($table,'MySQL');
	}
//==============================================================================
//	Connect: テーブルに接続し、columns[] 配列にフィールド名をセットする
protected function Connect($table) {
	// テーブル属性を取得
	$sql = "PRAGMA table_info({$table});";
	$rows = $this->dbb->query($sql);
	$columns = array();
	while ($row = $rows->fetch_assoc()) {
		$columns[$row['name']] = $row['name'];
	}
	return $columns;
}
//==============================================================================
//	field concatiname
public function fieldConcat($sep,$arr) {
	return "concat_ws('{$sep}'," . implode(',',$arr) . ")";
}
//==============================================================================
//	DROP TABLE/VIEW CASCADE
public function drop_sql($kind,$table) {
	return "DROP {$kind} IF EXISTS {$table} CASCADE;";
}
//==============================================================================
//	TRUNCATE TABLE
public function truncate_sql($table) {
	return "TRUNCATE TABLE {$table};";
}
//==============================================================================
//	RESET SEQ to PRIMARY
protected function reset_seq($table,$primary) {
	return FALSE;
}
//==============================================================================
//	doQuery: 	SQLを発行する
public function doQuery($sql) {
	$this->rows = $this->dbb->query($sql);
	return $this->rows;
}
//==============================================================================
//	fetchDB: 	レコードを取得してカラム配列を返す
public function fetch_array() {
	return $this->rows->fetch_assoc(); //またはSQLITE3_NUM
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

	$sql = "INSERT INTO {$this->raw_table} ({$kstr}) VALUES ({$vstr}) RETURNING *;";
	error_reporting(E_ERROR);
	$rows = $this->doQuery($sql);
	if(!$rows) {
		echo 'ERROR:'.$this->getLastError()."\n".$sql;
		return [];
	} else	return $this->fetchDB();
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
	$sql = "UPDATE \"{$this->raw_table}\"{$set}{$where} RETURNING *;";
	error_reporting(E_ERROR);
	$rows = $this->doQuery($sql);
	if(!$rows) {
		echo 'ERROR:'.$this->getLastError()."\n".$sql;
		return [];
	} else	return $this->fetchDB();
}

}
