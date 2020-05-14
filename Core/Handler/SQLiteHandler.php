<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	SQLiteHandler: SQLite3 データベースへのアクセスハンドラ
 *
 */
//==================================================================================================
//	SQLite3用の抽象メソッドを実装する
class SQLiteHandler extends SQLHandler {
//==================================================================================================
//	コンストラクタ：　データベースのテーブルに接続する
	function __construct($database,$table) {
		parent::__construct($database,$table,'SQLite');
	}
//==================================================================================================
//	Connect: テーブルに接続し、columns[] 配列にフィールド名をセットする
protected function Connect() {
	APPDEBUG::MSG(11,$this->table);
	// テーブル属性を取得
	$sql = "PRAGMA table_info({$this->table});";
	$rows = $this->dbb->query($sql);
	$this->columns = array();
	while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
		$this->columns[$row['name']] = $row['name'];
	}
	APPDEBUG::MSG(11,$this->columns);
}
//==================================================================================================
//	doQuery: 	SQLを発行する
public function doQuery($sql) {
	APPDEBUG::MSG(9,$sql);
	$this->rows = $this->dbb->query($sql);
	return $this->rows;
}
//==================================================================================================
//	fetchDB: 	レコードを取得してカラム配列を返す
public function fetchDB() {
	return $this->rows->fetchArray(SQLITE3_ASSOC); //またはSQLITE3_NUM
}
//==================================================================================================
//	getLastError: 	レコードを取得してカラム配列を返す
public function getLastError() {
//		return sqlite_last_error($this->rows);
}
//==================================================================================================
//	レコードの追加 
//==================================================================================================
public function insertRecord($row) {
	APPDEBUG::MSG(19, $row );
	// UPDATE OR INSERT => REPLACE SQL生成
	$kstr = '"' . implode('","', array_keys($row)) . '"';
	$vstr = "'" . implode("','", $row) . "'";

	$sql = "INSERT INTO {$this->table} ({$kstr}) VALUES ({$vstr});";
	error_reporting(E_ERROR);
	APPDEBUG::MSG(19, $sql );
	$rows = $this->doQuery($sql);
	if(!$rows) {
		echo 'ERROR:'.$this->getLastError()."\n";
	}
}
//==================================================================================================
//	レコードの更新 $row[key] value
//==================================================================================================
public function replaceRecord($wh,$row) {
	APPDEBUG::MSG(19, $row );
	$row = array_merge($wh,$row);				// 配列をマージ
	// UPDATE OR INSERT => REPLACE SQL生成
	$kstr = '"' . implode('","', array_keys($row)) . '"';
	$vstr = "'" . implode("','", $row) . "'";

	$sql = "REPLACE INTO {$this->table} ({$kstr}) VALUES ({$vstr});";
	error_reporting(E_ERROR);
	APPDEBUG::MSG(19, $sql );
	$rows = $this->doQuery($sql);
	if(!$rows) {
		echo 'ERROR:'.$this->getLastError()."\n";
	}
}

}
