<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *	SQLHandler: SQLデータベース用のベースハンドラ
 *
 */
//==============================================================================
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
	public  $DateStyle = 'Y-m-d';
	private $relations;
//==============================================================================
//	抽象メソッド：継承先クラスで定義する
	abstract protected function Connect();
	abstract protected function doQuery($sql);
	abstract protected function fetch_array();
	abstract protected function getLastError();
	abstract protected function updateRecord($wh, $row);		// INSERT or UPDATE
//==============================================================================
//	コンストラクタ：　テーブル名
//==============================================================================
function __construct($table,$handler) {
		$this->table = $table;
		$this->dbb = DatabaseHandler::get_database_handle($handler);
		$this->Connect();
		debug_log(FALSE,["フィールド名リスト" => $this->columns]);
		$this->handler = $handler;
		$this->fieldAlias = new fieldAlias();
	}
//==============================================================================
// setupRelations: リレーション情報を記憶する
public function setupRelations($relations) {
	$this->relations = $relations;
	debug_log(3,["RELATIONS" => $this->relations]);
}
//==============================================================================
// fetchDB: レコードを取得して言語エイリアスとカラム連結を適用する
public function fetchDB() {
	if($row = $this->fetch_array()) {
		$this->fieldAlias->to_alias_field($row);
	}
	return $row;
}
//==============================================================================
//	getValueLists: 抽出カラム名, 値カラム名、グルーピングカラム名
public function getValueLists($table,$ref,$id) {
	$sql = $this->sql_QueryValues($table,$ref,$id);
	$this->doQuery($sql);
	$values = array();
	while ($row = $this->fetchDB()) {
		$key = $row[$ref];
		if(!empty($key)) $values[$key] = $row[$id];
	}
	ksort($values,SORT_STRING | SORT_FLAG_CASE);
	return $values;
}
//==============================================================================
//	doQueryBy: キー列名と値で検索しフィールド配列を返す
public function doQueryBy($key,$val) {
	$sql = $this->sql_GetRecordByKey($key,$val);
	$this->doQuery($sql);
	return $this->fetchDB();
}
//==============================================================================
// ページングでレコードを読み込むためのパラメータ
// pagenum は１以上になることを呼び出し側で保証する
public function SetPaging($pagesize, $pagenum) {
	$this->startrec = $pagesize * ($pagenum - 1);		// 開始レコード番号
	if($this->startrec < 0) $this->startrec = 0;
	$this->limitrec = $pagesize;		// 取得レコード数
	debug_log(FALSE,["size" => $pagesize, "limit" => $this->limitrec, "start" => $this->startrec, "page" => $pagenum]);
}
//==============================================================================
//	getRecordCount($cond) 
//	$cond 条件に一致したレコード数を返す
//==============================================================================
public function getRecordCount($cond) {
	$where = $this->sql_makeWHERE($cond);	// 検索条件
	$sql = "SELECT count(*) as \"total\" FROM {$this->table}";
	$this->doQuery("{$sql}{$where};");
	$field = $this->fetch_array();
	return ($field) ? $field["total"] : 0;
}
//==============================================================================
//	getRecordValue($cond,$relations) 
//	$cond 条件に一致したレコードデータを返す
//==============================================================================
public function getRecordValue($cond,$relations) {
	$where = $this->sql_makeWHERE($cond);		// 検索条件
	$sql = $this->sql_JoinTable($relations);
	$where .= ($this->handler == 'SQLite') ? " limit 0,1" : " offset 0 limit 1";		// 取得レコード数
	$sql .= "{$where};";
	$this->doQuery($sql);
	return $this->fetchDB();
}
//==============================================================================
//	findRecord(row): 
//	row 配列を条件にレコード検索する
//      [ AND条件... ] OR条件 [ AND条件... ]
// pgSQL: SELECT *, count('No') over() as full_count FROM public.mydb offset 10 limit 50;
// SQLite3: SELECT *, count('No') over as full_count FROM public.mydb offset 10 limit 50;
//==============================================================================
public function findRecord($cond,$relations = NULL,$sort = []) {
	$where = $this->sql_makeWHERE($cond);	// 検索条件
	// 全体件数を取得する
	$sql = "SELECT count(*) as \"total\" FROM {$this->table}";
	debug_log(3,['SQL' => $sql]);
	$this->doQuery("{$sql}{$where};");
	$field = $this->fetch_array();
debug_log(3,["SQL" => "{$sql}{$where};", "DATA" => $field]);
	$this->recordMax = ($field) ? $field["total"] : 0;
	// 実際のレコード検索
	$sql = $this->sql_JoinTable($relations);
	if(!empty($sort)) {
		$orderby = "";
		foreach($sort as $key => $val) {
			$order = ($val === SORTBY_DESCEND) ? "desc" : "asc";
			$orderby .=  "{$this->table}.\"{$key}\" {$order},";
		}
		$where .=  " ORDER BY ".trim($orderby,",");
	}
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
//==============================================================================
//	deleteRecord(wh): 
//	wh 配列を条件にレコードを1件だけ削除する
public function deleteRecord($wh) {
	$where = $this->sql_makeWHERE($wh);
	$sql = "DELETE FROM {$this->table}{$where};";
	$this->doQuery($sql);
}
//==============================================================================
// 汎用SQL(SELECT 〜 WHERE 〜)コマンドの発行
// SQlite3, PostgreSQL, mariaDB 固有のSQLコマンド(update, insert, replace)は継承クラスで実装する
//==============================================================================
// 値リストを取得、重複はGROUP BY で除外したいけど...
	private function sql_QueryValues($table,$ref,$id) {
//		$groupby = (empty($grp)) ? '' : " GROUP BY \"{$grp}\"";
		return "SELECT \"{$id}\",\"{$ref}\" FROM {$table} ORDER BY \"{$id}\";";
	}
//==============================================================================
//
	private function sql_GetRecordByKey($key,$val) {
		return "SELECT * FROM {$this->table} WHERE \"{$key}\"='{$val}';";
	}
//==============================================================================
// シングルクオートをエスケープする
protected function sql_safequote(&$value) {
	foreach($value as $key => $val) {
		$value[$key] = str_replace("'","''",$val);
	}
}
//==============================================================================
// テーブルをジョインしてSELECT
	private function sql_JoinTable($Relations) {
		$sql = "SELECT {$this->table}.*";
		$frm = " FROM {$this->table}";
		$jstr = '';
		if(!empty($Relations)) {
			foreach($Relations as $key => $val) {
				$kk = (substr($key,-3)==='_id') ? substr($key,0,strlen($key)-3) : $key;
				$alias= "\"{$key}\"";
				if(is_array($val)) {
					foreach($val as $refer => $lnk) {
						list($table,$fn,$ref) = explode('.', $lnk);
						$sql .= ",{$table}.\"{$ref}\" AS \"{$kk}_{$refer}\"";
					}
					$jstr .= " LEFT JOIN {$table} ON {$this->table}.{$alias} = {$table}.\"{$fn}\"";
				} else {
					list($table,$fn, $ref) = explode('.', $val);
					$sql .= ",{$table}.\"{$ref}\" AS \"{$kk}\"";
					$jstr .= " LEFT JOIN {$table} ON {$this->table}.{$alias} = {$table}.\"{$fn}\"";
				}
			}
		}
		return "{$sql}{$frm}{$jstr}";
	}
//==============================================================================
// 配列要素からのWHERE句を作成
	private function sql_makeWHERE($cond) {
		$sql = $this->makeExpr($cond);
		if(!empty($sql)) $sql = ' WHERE '.$sql;
		return $sql;
	}
//==============================================================================
// 配列要素から論理演算式を生成
// item := 
//   	AND => [ itenm, item,... ] | [ item, item,...]
//   	OR => [ itenm, item,... ] |
//   	NOT => [ itenm ] |
//		fieldkey => findvalue
	private function makeExpr($cond) {
		// LIKE 演算子の生成関数を定義
		$like_object = function ($key,$val,$table) {
			$opk = "{$table}.\"{$key}\"";
			$cmp = array_map(function($v) use(&$opk) {
					if($v[0] === '-') {
						$v = mb_substr($v,1);
						$opx = 'NOT LIKE';
					} else $opx = 'LIKE';
					return "({$opk} {$opx} '%{$v}%')";
				},$val);
			return implode('OR',$cmp);
		};
		// 条件句(WHERE) の論理式を生成する関数
		$dump_object = function ($opr,$items,$table)  use (&$dump_object,&$like_object)  {
			$opc = '';
			foreach($items as $key => $val) {
				if(empty($val)) continue;
				list($key,$op) = keystr_opr($key);		// キー名の最後に関係演算子
				if(empty($op) || $op === '%') {	// 演算子がない：配列なら論理式 または LIKE(マルチ)、スカラー：BETWEENまたはLIKEまたは数値比較(=)
					if(is_array($val)) {
						if(in_array($key,['AND','OR','NOT'])) {
							$opx = ($key === 'NOT') ? 'AND' : $key; 
							$opp = $dump_object($opx,$val,$table);
							if($key === 'NOT') $opp = "(NOT {$opp})";
						} else { // LIKE [ 配列 ]
							$opp = $like_object($key,$val,$table);
						}
					} else { // 演算子がないスカラー値
						if(mb_strpos($val,'...') !== FALSE) {
							$op = 'BETWEEN';
							list($from,$to) = array_explode('...',$val);
							$val = "'{$from}' AND '{$to}'";
						} else if(is_numeric($val) && empty($op)) {
							$op = '=';
						} else {
							$op = 'LIKE';
							if($val[0] == '-') {
								$val = mb_substr($val,1);
								$op = 'NOT LIKE';
							}
							$val = "'%{$val}%'";
						}
						$expr = [];
						foreach(array_explode('+',$key) as $cmp) {
							$cmp = $this->fieldAlias->get_lang_alias($cmp);
							$expr[] = "({$table}.\"{$cmp}\" {$op} {$val})";
						}
						$opp = implode('OR',$expr);
					}
				} else if($op === '@') {	// サブクエリー
					// リレーション定義済みかを確かめる
					if(array_key_exists($key,$this->relations)) {
						$rel = $this->relations[$key];
						if(is_array($rel)) list($nm,$rel) = array_first_item($rel);
						list($tbl,$fn) = explode('.',$rel);
						$ops = $dump_object('AND',$val,$tbl);
						$opp = "({$table}.\"{$key}\" IN (SELECT Distinct({$tbl}.\"{$fn}\") FROM {$tbl} WHERE {$ops}))";
					} else continue;
				} else if(is_array($val)) {
					$in_op = [ '=' => 'IN', '==' => 'IN', '<>' => 'NOT IN', '!=' => 'NOT IN'];
					if(array_key_exists($op,$in_op)) {
						$cmp = implode(',',array_map(function($v) { return "'{$v}'";},$val));
						$opx = $in_op[$op];
						$opp = "{$table}.\"{$key}\" {$opx} ({$cmp})";
					} else {	// LIKE [ 配列 ]
						$opp = $like_object($key,$val,$table);
					}
				} else {
					if(!is_numeric($val)) $val = "'{$val}'";
					$expr = [];
					foreach(array_explode('+',$key) as $cmp) {
						$cmp = $this->fieldAlias->get_lang_alias($cmp);
						$expr[] = "({$table}.\"{$cmp}\" {$op} {$val})";
					}
					$opp = implode('OR',$expr);
				}
				$opc = (empty($opc)) ? "{$opp}" : "({$opc}){$opr}{$opp}";
			}
			return (empty($opc)) ? '' : "{$opc}";	// ((count($items)===1) ? $opc : "({$opc})");
		};
		$sql = $dump_object('AND',$cond,$this->table);
		return $sql;
	}

}
