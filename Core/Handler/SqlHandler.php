<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *	SQLHandler: SQLデータベース用のベースハンドラ
 *
 */
//==================================================================================================
//	アプリケーション内で共通のDBハンドラ
abstract class SQLHandler {	// extends SqlCreator {
	protected	$table;		// 接続テーブル
	protected $dbb;  	    // データベースハンドル
	protected $rows;
	public $columns;        // レコードフィールドの値
	public	$recordId;		// ダミー
	private	$startrec;		// 開始レコード番号
	private	$limitrec;		// 取得レコード数
	private $handler;		// SQLハンドラー
//==================================================================================================
//	抽象メソッド：継承先クラスで定義する
	abstract protected function Connect();
	abstract protected function doQuery($sql);
	abstract protected function fetchDB();
	abstract protected function getLastError();
	abstract protected function replaceRecord($wh, $row);		// INSERT or UPDATE
//==================================================================================================
//	コンストラクタ：　テーブル名
//==================================================================================================
	function __construct($database,$table,$handler) {
//		parent::__construct($table);
		$this->table = $table;
		$this->dbb = DatabaseHandler::getDataSource($handler);
		$this->Connect();
		APPDEBUG::MSG(9,$this->columns,"フィールド名リスト");
		$this->handler = $handler;
	}
//==================================================================================================
//	getValueLists: 抽出カラム名, 値カラム名
public function getValueLists($table,$ref,$id) {
	$sql = $this->sql_QueryValues($table,$ref,$id);
	$this->doQuery($sql);
	$values = array();
	while ($row = $this->fetchDB()) {
		$values[$row[$ref]] = $row[$id];
	}
	ksort($values,SORT_STRING | SORT_FLAG_CASE);
	return $values;
}
//==================================================================================================
//	doQueryBy: キー列名と値で検索しフィールド配列を返す
public function doQueryBy($key,$val) {
	$sql = $this->sql_GetRecordByKey($key,$val);
	$this->doQuery($sql);
	return $this->fetchDB();
}
//===============================================================================
// ページングでレコードを読み込むためのパラメータ
// pagenum は１以上になることを呼び出し側で保証する
public function SetPaging($pagesize, $pagenum) {
	$this->startrec = $pagesize * ($pagenum - 1);		// 開始レコード番号
	if($this->startrec < 0) $this->startrec = 0;
	$this->limitrec = $pagesize;		// 取得レコード数
	APPDEBUG::arraydump(9,["size" => $pagesize, "limit" => $this->limitrec, "start" => $this->startrec, "page" => $pagenum]);
}
//==================================================================================================
//	findRecord(row): 
//	row 配列を条件にレコード検索する
//      [ AND条件... ] OR条件 [ AND条件... ]
// pgSQL: SELECT *, count('No') over() as full_count FROM public.mydb offset 10 limit 50;
// SQLite3: SELECT *, count('No') over as full_count FROM public.mydb offset 10 limit 50;
//==================================================================================================
public function getRecordValue($row,$relations) {
	// 検索条件
	$where = $this->sql_makeWHERE($row);
	// 実際のレコード検索
	$sql = $this->sql_JoinTable($relations);
	$where .= ($this->handler == 'SQLite') ? " limit 0,1" : " offset 0 limit 1";		// 取得レコード数
	$sql .= "{$where};";
	$this->doQuery($sql);
	return $this->fetchDB();
}
//==================================================================================================
//	findRecord(row): 
//	row 配列を条件にレコード検索する
//      [ AND条件... ] OR条件 [ AND条件... ]
// pgSQL: SELECT *, count('No') over() as full_count FROM public.mydb offset 10 limit 50;
// SQLite3: SELECT *, count('No') over as full_count FROM public.mydb offset 10 limit 50;
//==================================================================================================
public function findRecord($row,$relations,$sort = '') {
	// 検索条件
	$where = $this->sql_makeWHERE($row);
	// 全体件数を取得する
	$sql = "SELECT count(*) as \"total\" FROM {$this->table}";
	$this->doQuery("{$sql}{$where}");
	$field = $this->fetchDB();
	$this->recordMax = ($field) ? $field["total"] : 0;
	// 実際のレコード検索
	$sql = $this->sql_JoinTable($relations);
	if(isset($sort)) $where .=  " ORDER BY {$this->table}.\"{$sort}\"";
	if($this->limitrec > 0) {		// 取得レコード数
		if($this->handler == 'SQLite') {
			$where .= " limit {$this->startrec},{$this->limitrec}";		// 取得レコード数
		} else {
			$where .= " offset {$this->startrec} limit {$this->limitrec}";		// 取得レコード数
		}
	}
	$sql .= "{$where};";
	$this->doQuery($sql);
}
//==================================================================================================
//	fdeleteRecord(wh): 
//	wh 配列を条件にレコードを1件だけ削除する
public function deleteRecord($wh) {
	$where = $this->sql_makeWHERE($wh);
//	$where .= ($this->handler==='SQLite') ? "":" offset 0 limit 1";		// 取得レコード数
	$sql = "DELETE FROM {$this->table}{$where};";
	$this->doQuery($sql);
}
//-------------------------------------------------------------------------------
// 汎用SQL(SELECT 〜 WHERE 〜)コマンドの発行
// SQlite3, PostgreSQL, mariaDB 固有のSQLコマンド(update, insert, replace)は継承クラスで実装する
//===============================================================================
//
	private function sql_QueryValues($table,$ref,$id) {
		return "SELECT \"{$id}\",\"{$ref}\" FROM {$table} ORDER BY \"{$id}\";";
	}
//===============================================================================
//
	private function sql_GetRecordByKey($key,$val) {
		return "SELECT * FROM {$this->table} WHERE \"{$key}\"='{$val}';";
	}
//===============================================================================
// シングルクオートをエスケープする
protected function sql_safequote(&$value) {
	foreach($value as $key => $val) {
		$value[$key] = str_replace("'","''",$val);
	}
}
//===============================================================================
// テーブルをジョインしてSELECT
	private function sql_JoinTable($Relations) {
		$sql = "SELECT {$this->table}.*";
		$frm = " FROM {$this->table}";
		$jstr = '';
		foreach($Relations as $key => $val) {
			list($table,$fn, $ref) = explode('.', $val);
			$kk = (substr($key,-3)==='_id') ? substr($key,0,strlen($key)-3) : $key;
			$alias= "\"{$key}\"";
			$sql .= ",{$table}.\"{$ref}\" AS \"{$kk}\"";
			$jstr .= " LEFT JOIN {$table} ON {$this->table}.{$alias} = {$table}.\"{$fn}\"";
		}
		return "{$sql}{$frm}{$jstr}";
	}
//===============================================================================
// 配列要素からのWHERE句を作成
	private function sql_makeWHERE($row) {
		APPDEBUG::MSG(11, $row );
		$n = array_depth($row);
		if($n == 1) {
			$sql = $this->SingleSQL('AND', $row);
		} else {
			$sql = ''; $or_op = '';
			foreach($row as $opr => $andval ) {
				$sql .= $or_op . $this->SingleSQL($opr, $andval);
				$or_op = ' OR ';
			}
		}
		if($sql !== '') $sql = ' WHERE '.$sql;
		return $sql;
	}
//===============================================================================
// 配列要素からのSQL生成
// AND => [ フィールド名 => 検索条件, フィールド名 => 検索条件, ... ] 全て一致する
// NAND/NOT => [ フィールド名 => 検索条件, フィールド名 => 検索条件, ... ] 全て一致するものを除外 NOT AND
// OR => [ フィールド名 => 検索条件, フィールド名 => 検索条件, ... ] どれかが一致する
// NOR => [ フィールド名 => 検索条件, フィールド名 => 検索条件, ... ] どれかが一致するものを除外 NOT OR
	private function SingleSQL($opr, $row) {
		APPDEBUG::MSG(11, $row );
		$and_sql = ''; $and_op = '';
		$opr_str = ($opr==='OR' || $opr==='NOR') ? ' OR ' : ' AND ';
		foreach($row as $key => $val) {
			$and_sql .= $and_op;
			if($val[0] == '-') {
				$vval = substr($val,1);
				$LIKE_str = ' NOT LIKE ';
				$vopr = '<>';
			} else {
				$vval = $val;
				$LIKE_str = ' LIKE ';
				$vopr = '=';
			}
			if($vval[0] == '%') {
				$and_sql .= "({$this->table}.\"{$key}\"{$LIKE_str}{$vval})";
			} else if(strpos('<>=',$vval[0]) !== FALSE) {
				$and_sql .= "({$this->table}.\"{$key}\"{$vval})";
			} else {
				$and_sql .= "({$this->table}.\"{$key}\"{$vopr}'{$vval}')";
			}
			$and_op = $opr_str;
		}
		if($opr == 'NAND' || $opr == 'NOR' || $opr == 'NOT') $and_sql = "NOT ({$and_sql})";
		return $and_sql;
	}

}
