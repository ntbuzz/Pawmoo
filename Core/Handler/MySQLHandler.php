<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	MySQLHandler: MariaDB データベースへのアクセスハンドラ
 *
 */
//==============================================================================
//	MariaDB用の抽象メソッドを実装する
class MySQLHandler extends SQLHandler {
//==============================================================================
//	コンストラクタ： データベースのテーブルに接続する
	function __construct($table,$primary) {
		parent::__construct($table,'MariaDB',$primary);
	}
//==============================================================================
//	Connect: テーブルに接続し、columns[] 配列にフィールド名をセットする
protected function Connect($table) {
	// テーブル属性を取得
	$sql = "show columns from {$table};";
	$rows = $this->dbb->query($sql);
	if($rows === false) return [];
	$columns = array();
	$type_int = [
		'bigint'	=> 'integer',
		'tinyint'	=> 'integer',
		'int'		=> 'integer',
	];
	while ($row = $rows->fetch_assoc()) {
		list($name,$type) = array_keys_value($row,['Field','Type']);
		$decl = strtolower($type);
		foreach($type_int as $key => $tt) {
			if(strpos($decl,$key)===0) {
				$decl = $tt;
				break;
			}
		}
		$columns[$name] = $decl;
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
//	CONCAT FIELDS
protected function concat_fields($arr) {
	return (count($arr)>1) ? 'concat(' . implode(',',$arr) . ')' : $arr[0];
}
//==============================================================================
//	doQuery: 	SQLを発行する
public function doQuery($sql) {
	$this->rows = $this->dbb->query($sql);
	if($this->rows === false) {
		echo($this->dbb->error);
		debug_die(['MySQLi'=>$this->dbb,'RESULT'=>$this->rows,'SQL'=>$sql]);
	}
	return $this->rows;
}
//==============================================================================
//	fetchDB: 	レコードを取得してカラム配列を返す
public function fetch_array() {
	$row = mysqli_fetch_assoc($this->rows);
	return $this->fetch_convert($row,false);	// 型変換
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
	$row = $this->sql_safe_convert($this->sql_str_quote($row,["'"],["''"]));	// 書き込み型変換
	// UPDATE OR INSERT => REPLACE SQL生成
	$kstr = implode(',', array_keys($row));
	$vstr = implode(',', $row);
	$sql = "INSERT INTO {$this->raw_table} ({$kstr}) VALUES ({$vstr});";
	error_reporting(E_ERROR);
	$rows = $this->doQuery($sql);
	if(!$rows) {
		echo 'ERROR:'.$this->getLastError()."{$sql}\n";
	}
	return [];
}
//==============================================================================
//	レコードの更新 $row[key] value
//==============================================================================
public function updateRecord($wh,$row) {
	list($pkey,$pval) = array_first_item($wh);
	$row = array_merge($wh,$row);			// INSERT 用にプライマリキー配列とデータ配列をマージ
	$row = $this->sql_safe_convert($this->sql_str_quote($row,["'"],["''"]));	// 書き込み型変換
	unset($aa[$pkey]);					// プライマリキーは削除しておく
	if($aa === false) return [];
	$keys = array_keys($aa);
	$kstr = implode(',', $keys);	// フィールド名リストを作成
	$vstr = implode(',', $aa);				// VALUES リストを作成
	// INSERT/UPSERT 文を生成
	$set = array_map(function($k,$v) {return "{$k}={$v}";},$keys,$aa);
	$set_str = implode(',', $set);
	$sql = "INSERT INTO {$this->raw_table} ({$kstr}) VALUES ({$vstr}) ON DUPLICATE KEY UPDATE {$set_str};";
	error_reporting(E_ERROR);
	$rows = $this->doQuery($sql);
	if(!$rows) {
		echo 'ERROR:'.$this->getLastError()."\n".$sql;
		return false;
	}
// MySQL old version cannot support 'RETURNING'.
	$where = " WHERE \"{$pkey}\"={$pval}";		// プライマリキー名を取得
	$sql = "SELECT * FROM {$this->raw_table}{$where};";
	$rows = $this->doQuery($sql);
	if(!$rows) {
		echo 'ERROR:'.$this->getLastError()."\n{$sql}\n";
		return FALSE;
	}
	return $this->fetchDB();
}

}
