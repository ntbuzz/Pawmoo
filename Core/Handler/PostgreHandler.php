<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  PostgreHandler: PostgreSSQLデータベースの入出力
 */
//==============================================================================
//	PostgreSQL用の抽象メソッドを実装する
class PostgreHandler extends SQLHandler {
	protected $LIKE_opr = 'LIKE';		// 大文字小文字の無視比較
//==============================================================================
//	コンストラクタ： データベースのテーブルに接続する
	function __construct($table,$primary,$db=NULL) {
		parent::__construct($table,'Postgre',$primary,$db);
	}
//==============================================================================
//	Connect: テーブルに接続し、columns[] 配列にフィールド名をセットする
protected function Connect($table) {
	// テーブル属性を取得
	$sql = "SELECT * FROM information_schema.columns WHERE table_name = '{$table}' ORDER BY ordinal_position;";
	$result = pg_query($this->dbb, $sql);
	if(!$result) {
		die('Postgres QUERY失敗' . pg_last_error());
	}
	$columns = array();
	while ($row = pg_fetch_array($result,NULL,PGSQL_ASSOC)) {
		if(!$row) {
			die('Postgres QUERY失敗' . pg_last_error());
		}
		$columns[$row['column_name']] = $row['data_type'];
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
	$sql = "select setval('{$table}_{$primary}_seq',(select max({$primary}) from {$table}));";
	return $sql;
}
//==============================================================================
//	CONCAT FIELDS
protected function concat_fields($arr) {
	return (count($arr)>1) ? 'concat(' . implode(',',$arr) . ')' : $arr[0];
}
//==============================================================================
//	doQuery: 	SQLを発行する
public function doQuery($sql) {
//	debug_log(DBMSG_HANDLER,['SQL' => $sql]);
	$this->rows = pg_query($this->dbb, $sql);
	if(!$this->rows) {
		$res1 = pg_get_result($this->dbb);
		debug_log(DBMSG_DIE,[
			"ERROR" => pg_result_error($res1),
			"SQL" => $sql,
			"COND" => $this->LastCond,
			"BUILD" => $this->LastBuild,
			'QUERY失敗' => pg_last_error(),
			'PRIMARY' => $this->updatePrimary,
		]);
	}
	return $this->rows;
}
//==============================================================================
//	fetch_array: 	レコードを取得してカラム配列を返す
public function fetch_array() {
	$row = pg_fetch_array($this->rows,NULL,PGSQL_ASSOC);
	return $this->fetch_convert($row,false);	// 型変換
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
// pg_update($this->dbb,$this->raw_table,$row,$wh);
//==============================================================================
private function safe_convert($row,$func) {
	$aa = $this->sql_str_quote($row,'\\','\\\\');	// escape back-slash
	// PostgreSQLのデータ型に変換
	$aa = pg_convert($this->dbb,$this->raw_table,$aa,PGSQL_CONV_FORCE_NULL );
	if($aa === FALSE) {
		$res1 = pg_get_result($this->dbb);
		debug_log(DBMSG_ERROR,[
			"DBB"	=> $this->dbb,
			"TABLE" => $this->raw_table,
			"RESULT"=> $res1,
			"MESSAGE" => pg_result_error_field($res1,PGSQL_DIAG_MESSAGE_DETAIL),	// pg_result_error($res1),
			"{$func} CONVERT失敗" => pg_result_status($res1),	// pg_last_error(),
			"ROW" => $row,
		]);
		$id = $row[$this->Primary];
		echo "**** PG_CONV:ERROR({$id})\n";
		$aa = $this->sql_safe_convert($row);	// 自力で書き込み型変換
	}
	return $aa;
}
//==============================================================================
//	INSERT
// INSERT INTO test_table (id, name) VALUES (val_id, val_name)
// ON CONFLICT (id) DO UPDATE SET name = val_name；
// pg_update($this->dbb,$this->raw_table,$row,$wh);
//==============================================================================
public function insertRecord($row) {
	$aa = $this->safe_convert($row,'Insert');
	if($aa === false) return [];
	$kstr = implode(',', array_keys($aa));	// フィールド名リストを作成
	$vstr = implode(',', $aa);				// VALUES リストを作成
	$set = " SET"; $sep = " ";				// UPDATE する時の代入文
	foreach($aa as $key => $val) {
		$set .= "{$sep}{$key}={$val}";
		$sep = ",";
	}
	// UPSERT 文を生成
	$sql = "INSERT INTO \"{$this->raw_table}\" ({$kstr}) VALUES ({$vstr}) RETURNING *;";
	$this->doQuery($sql);
	$a = $this->fetch_array();		// 書込みはraw-tableなのでAlias無し
	//view が定義されていれば取り直す
	if($this->raw_table !== $this->table) {
		$a = $this->doQueryBy($this->Primary,$a[$this->Primary]);
	}
	return $a;
}
//==============================================================================
// UPDATE 暫定的に UPSERT動作
public function updateRecord($wh,$row) {
	return $this->upsertRecord($wh,$row);
}
//==============================================================================
// UPSERT 
public function upsertRecord($wh,$row) {
	$row = array_merge($wh,$row);			// INSERT 用にプライマリキー配列とデータ配列をマージ
	$aa = $this->safe_convert($row,'Update');
	if($aa === false) return [];
	$primary = '"' . key($wh) . '"';		// プライマリキー名を取得
	$kstr = implode(',', array_keys($aa));	// フィールド名リストを作成
	$vstr = implode(',', $aa);				// VALUES リストを作成
	$set = " SET"; $sep = " ";				// UPDATE する時の代入文
	foreach($aa as $key => $val) {
		$set .= "{$sep}{$key}={$val}";
		$sep = ",";
	}
	// UPSERT 文を生成 pg_convert()を通すとキーに""が付く
	$this->updatePrimary = $aa["\"{$this->Primary}\""];
	$sql = "INSERT INTO \"{$this->raw_table}\" ({$kstr}) VALUES ({$vstr}) ON CONFLICT ({$primary}) DO UPDATE {$set} RETURNING *;";
	$this->doQuery($sql);
	$a = $this->fetchDB();		// 言語alias&virtual処理を加える
	//view が定義されていれば取り直す
	if($this->raw_table !== $this->table) {
		$a = $this->doQueryBy($this->Primary,$a[$this->Primary]);
	}
	return $a;
}

}
