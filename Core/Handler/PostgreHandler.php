<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  PostgreHandler: PostgreSSQLデータベースの入出力
 */

//==============================================================================
//	PostgreSQL用の抽象メソッドを実装する
class PostgreHandler extends SQLHandler {
//==============================================================================
//	コンストラクタ：　データベースのテーブルに接続する
	function __construct($table) {
		parent::__construct($table,'Postgre');
	}
//==============================================================================
//	Connect: テーブルに接続し、columns[] 配列にフィールド名をセットする
protected function Connect() {
	// テーブル属性を取得
	$sql = "SELECT * FROM information_schema.columns WHERE table_name = '{$this->table}' ORDER BY ordinal_position;";
	$result = pg_query($this->dbb, $sql);
	if(!$result) {
		die('Postgres QUERY失敗' . pg_last_error());
	}
	$this->columns = array();
	while ($row = pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
		if(!$row) {
			die('Postgres QUERY失敗' . pg_last_error());
		}
		$this->columns[$row['column_name']] = $row['column_name'];
	}
}
//==============================================================================
//	doQuery: 	SQLを発行する
public function doQuery($sql) {
	debug_log(3,['SQL' => $sql]);
	$this->rows = pg_query($this->dbb, $sql);
	if(!$this->rows) {
		$res1 = pg_get_result($this->dbb);
		echo "ERROR:" . pg_result_error($res1) . "<br>\n";
		echo "SQL:{$sql}\n";
		die('Postgres QUERY失敗' . pg_last_error());
	}
	return $this->rows;
}
//==============================================================================
//	fetchDB: 	レコードを取得してカラム配列を返す
public function fetchDB() {
	return pg_fetch_array($this->rows,NULL,PGSQL_ASSOC);
}
//==============================================================================
//	getLastError: 	レコードを取得してカラム配列を返す
public function getLastError() {
	return pg_last_error($this->dbb);
}
//==============================================================================
//	INSERT or UPDATE
// INSERT INTO test_table (id, name) VALUES (val_id, val_name)
// ON CONFLICT (id) DO UPDATE SET name = val_name；
// pg_update($this->dbb,$this->table,$row,$wh);
//==============================================================================
public function updateRecord($wh,$row) {
	$this->sql_safequote($row);
	$row = array_merge($wh,$row);			// INSERT 用にプライマリキー配列とデータ配列をマージ
	// \ をエスケープする
	foreach($row as $key => $val) {
		$row[$key] = str_replace('\\', '\\\\', $val);
	}
	// PostgreSQLのデータ型に変換
	$aa = pg_convert($this->dbb,$this->table,$row);
	if($aa === FALSE) {
		$res1 = pg_get_result($this->dbb);
		echo "ERROR:" . pg_result_error($res1) . "<br>\n";
		die('Postgres CONVERT失敗' . pg_last_error());
	}
	$primary = '"' . key($wh) . '"';		// プライマリキー名を取得
	$kstr = implode(',', array_keys($aa));	// フィールド名リストを作成
	$vstr = implode(',', $aa);				// VALUES リストを作成
	$set = " SET"; $sep = " ";				// UPDATE する時の代入文
	foreach($aa as $key => $val) {
		$set .= "{$sep}{$key}={$val}";
		$sep = ",";
	}
	// UPSERT 文を生成
	$sql = "INSERT INTO \"{$this->table}\" ({$kstr}) VALUES ({$vstr}) ON CONFLICT ({$primary}) DO UPDATE {$set};";
	$res = $this->doQuery($sql);
}
//==============================================================================
//	INSERT
// INSERT INTO test_table (id, name) VALUES (val_id, val_name)
// ON CONFLICT (id) DO UPDATE SET name = val_name；
// pg_update($this->dbb,$this->table,$row,$wh);
//==============================================================================
public function insertRecord($row) {
	$this->sql_safequote($row);
	// \ をエスケープする
//	foreach($row as $key => $val) {
//		$row[$key] = str_replace('\\', '\\\\', $val);
//	}
	// PostgreSQLのデータ型に変換
	$aa = pg_convert($this->dbb,$this->table,$row);
	if($aa === FALSE) {
		$res1 = pg_get_result($this->dbb);
		echo "ERROR:" . pg_result_error($res1) . "<br>\n";
		die('Postgres CONVERT失敗' . pg_last_error());
	}
	$kstr = implode(',', array_keys($aa));	// フィールド名リストを作成
	$vstr = implode(',', $aa);				// VALUES リストを作成
	$set = " SET"; $sep = " ";				// UPDATE する時の代入文
	foreach($aa as $key => $val) {
		$set .= "{$sep}{$key}={$val}";
		$sep = ",";
	}
	// UPSERT 文を生成
	$sql = "INSERT INTO \"{$this->table}\" ({$kstr}) VALUES ({$vstr});";
	$res = $this->doQuery($sql);
}

}
