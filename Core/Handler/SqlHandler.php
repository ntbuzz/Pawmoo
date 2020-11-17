<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *	SQLHandler: COMMON SQL-DB HANDLER Class
 */
//==============================================================================
abstract class SQLHandler {	// extends SqlCreator {
	protected	$table;		// connect table
	protected $dbb;  	    // DB handle
	protected $rows;
	public $columns;        // record column data
	public	$recordId;		// dummy
	private	$startrec;		// start record number
	private	$limitrec;		// get record count
	private $handler;		// Databas Handler Name
	public  $DateStyle = 'Y-m-d';	// Date format
	private $relations;		// relation tables
	private $LastCond;		// Last Query Condithin(re-builded)
//==============================================================================
//	abstruct method
	abstract protected function Connect();
	abstract protected function doQuery($sql);
	abstract protected function fetch_array();
	abstract protected function getLastError();
	abstract protected function updateRecord($wh, $row);		// INSERT or UPDATE
//==============================================================================
//	Constructor( table name, DB Handler)
//==============================================================================
function __construct($table,$handler) {
		$this->table = $table;
		$this->dbb = DatabaseHandler::get_database_handle($handler);
		$this->Connect();
		debug_log(FALSE,["Columns List" => $this->columns]);
		$this->handler = $handler;
		$this->fieldAlias = new fieldAlias();
	}
//==============================================================================
// setupRelations: relation table reminder
public function setupRelations($relations) {
	$this->relations = $relations;
	debug_log(DBMSG_HANDLER,["RELATIONS" => $this->relations]);
}
//==============================================================================
// fetchDB: get record data , and replace alias and bind column
public function fetchDB() {
	if($row = $this->fetch_array()) {
		$this->fieldAlias->to_alias_field($row);
	}
	return $row;
}
//==============================================================================
//	getValueLists: list-colum name, value-colums
public function getValueLists($table,$ref,$id) {
	$sql = $this->sql_QueryValues($table,$ref,$id);
	$this->doQuery($sql);
	$values = array();
	while ($row = $this->fetch_array()) {	// other table refer is not bind!
		$key = $row[$ref];
		if(!empty($key)) $values[$key] = $row[$id];
	}
	ksort($values,SORT_STRING | SORT_FLAG_CASE);
	return $values;
}
//==============================================================================
//	doQueryBy: query by KEY-NAME
public function doQueryBy($key,$val) {
	$sql = $this->sql_GetRecordByKey($key,$val);
	$this->doQuery($sql);
	return $this->fetchDB();
}
//==============================================================================
// SETUP Paging Parmeter
// pagenum must be >=1 on call function
public function SetPaging($pagesize, $pagenum) {
	$this->startrec = $pagesize * ($pagenum - 1);		// start record num calc
	if($this->startrec < 0) $this->startrec = 0;
	$this->limitrec = $pagesize;		// get limit records
	debug_log(FALSE,["size" => $pagesize, "limit" => $this->limitrec, "start" => $this->startrec, "page" => $pagenum]);
}
//==============================================================================
//	getRecordCount($cond) 
//		get $cond match record count
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
//	find $cond match record
//==============================================================================
public function getRecordValue($cond,$relations) {
	$where = $this->sql_makeWHERE($cond);		// 検索条件
	$sql = $this->sql_JoinTable($relations);
	$where .= ($this->handler == 'SQLite') ? " limit 0,1" : " offset 0 limit 1";
	$sql .= "{$where};";
	$this->doQuery($sql);
	return $this->fetchDB();
}
//==============================================================================
//	findRecord(cond): 
//	cond: query condition
//      [ AND... ] OR [ AND... ]
// pgSQL: SELECT *, count('No') over() as full_count FROM public.mydb offset 10 limit 50;
// SQLite3: SELECT *, count('No') over as full_count FROM public.mydb offset 10 limit 50;
//==============================================================================
public function findRecord($cond,$relations = NULL,$sort = []) {
	$where = $this->sql_makeWHERE($cond);
	$sql = "SELECT count(*) as \"total\" FROM {$this->table}";
	$this->doQuery("{$sql}{$where};");
	$field = $this->fetch_array();
	debug_log(DBMSG_HANDLER,["SQL" => "{$sql}{$where};", "DATA" => $field]);
	$this->recordMax = ($field) ? $field["total"] : 0;
	$sql = $this->sql_JoinTable($relations);
	if(!empty($sort)) {
		$orderby = "";
		foreach($sort as $key => $val) {
			$order = ($val === SORTBY_DESCEND) ? "desc" : "asc";
			$orderby .=  "{$this->table}.\"{$key}\" {$order},";
		}
		$where .=  " ORDER BY ".trim($orderby,",");
	}
	if($this->limitrec > 0) {
		if($this->handler == 'SQLite') {
			$where .= " limit {$this->startrec},{$this->limitrec}";
		} else {
			$where .= " offset {$this->startrec} limit {$this->limitrec}";
		}
	}
	$sql .= "{$where};";
	$this->doQuery($sql);
}
//==============================================================================
//	deleteRecord(wh): 
public function deleteRecord($wh) {
	$where = $this->sql_makeWHERE($wh);
	$sql = "DELETE FROM {$this->table}{$where};";
	$this->doQuery($sql);
}
//==============================================================================
// Common SQL(SELECT 〜 WHERE 〜) generate
// SQlite3, PostgreSQL, mariaDB unique SQL command(update, insert, replace) will be generate instance class
//==============================================================================
// get value lists
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
// escape to single-quote(')
protected function sql_safequote(&$value) {
	array_walk($value,function(&$v,$k){$v=str_replace("'","''",$v);});
//	foreach($value as $key => $val) {
//		$value[$key] = str_replace("'","''",$val);
//	}
}
//==============================================================================
// generate JOIN token
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
// Re-Build Condition ARRAY, Create SQL-WHERE statement.
	private function sql_makeWHERE($cond) {
		$re_build_array = function($cond) {
			$reduce_array = function($opr,$cond) use(&$reduce_array) {
				$wd = [];
				foreach($cond as $key => $val) {
					if(is_array($val)) {
						$val_s = $val;
						$val = $reduce_array(is_numeric($key)?$opr:$key,$val);
					}
					if(is_array($val) && (is_numeric($key) || $opr === $key || (($key==='AND' || $key==='OR') && count($val)===1))) {
						foreach($val as $kk => $vv) $wd[$kk] =$vv;
					} else $wd[$key] =$val;
				}
				return $wd;
			};
			$sort_array = function($arr) use(&$sort_array) {
				$wd = array_filter($arr, function($vv) {return is_scalar($vv);});
				foreach($arr as $key => $val) {
					if(is_array($val)) $wd[$key] = $sort_array($val);
				}
				return $wd;
			};
			return $sort_array($reduce_array('AND',$cond));
		};
		$new_cond = ($cond===NULL) ? $this->LastCond : ($this->LastCond = $re_build_array($cond));
		$sql = $this->makeExpr($new_cond);
		if(strlen($sql)) $sql = ' WHERE '.$sql;
		debug_log(DBMSG_HANDLER,['COND-INPUT'=>$cond,'RE-BUILD' => $new_cond,'WHERE' => $sql]);
		return $sql;
	}
//==============================================================================
// GENERATE WHERE token from ARRAY[] expression
// item := 
//   	AND => [ itenm, item,... ] | [ item, item,...]
//   	OR => [ itenm, item,... ] |
//   	NOT => [ itenm ] |
//		fieldkey => findvalue
	private function makeExpr($cond) {
		$dump_object = function ($opr,$items,$table)  use (&$dump_object)  {
			// LIKE operation build
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
			// multi-columns f1+f2+f3...  OP val
			$multi_field = function($key,$op,$table,$val) {
				$expr = [];
				foreach(trim_explode('+',$key) as $cmp) {
					$cmp = $this->fieldAlias->get_lang_alias($cmp);
					$expr[] = "({$table}.\"{$cmp}\" {$op} {$val})";
				}
				$opp = implode('OR',$expr);
				return (count($expr)===1) ? $opp : "({$opp})";
			};
			$opc = ''; $and_or_op = ['AND','OR','NOT'];
			foreach($items as $key => $val) {
				if(empty($key)) { echo "EMPTY!!!"; continue;}
				list($key,$op) = keystr_opr($key);
				if(empty($op) || $op === '%') {			// non-exist op or LIKE-op(%)
					if(is_array($val)) {
						if(in_array($key,$and_or_op)) {
							$opx = ($key === 'NOT') ? 'AND' : $key; 
							$opp = $dump_object($opx,$val,$table);
							if(!empty($opp)) $opp = "({$opp})";
							if($key === 'NOT') $opp = "(NOT {$opp})";
						} else { // LIKE [ array ]
							$opp = $like_object($key,$val,$table);
						}
					} else { // not have op code
						if(mb_strpos($val,'...') !== FALSE) {
							$op = 'BETWEEN';
							list($from,$to) = trim_explode('...',$val);
							$val = "'{$from}' AND '{$to}'";
						} else if(is_numeric($val) && empty($op)) {
							$op = '=';
						} else {
							if($val[0] == '-') {
								$val = mb_substr($val,1);
								$op = 'NOT LIKE';
							} else $op = 'LIKE';
							$val = "'%{$val}%'";
						}
						$opp = $multi_field($key,$op,$table,$val);
					}
				} else if($op === '@') {	// SUBQUERY op
					// check exists relations
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
						$opp = "({$table}.\"{$key}\" {$opx} ({$cmp}))";
					} else {	// LIKE [ array ]
						$opp = $like_object($key,$val,$table);
					}
				} else {
					if(!is_numeric($val)) $val = "'{$val}'";
					$opp = $multi_field($key,$op,$table,$val);
				}
				$opc = (empty($opc)) ? "{$opp}" : "{$opc}{$opr}{$opp}";
			}
			return (empty($opc)) ? '' : "{$opc}";	// ((count($items)===1) ? $opc : "({$opc})");
		};
		$sql = $dump_object('AND',$cond,$this->table);
		return $sql;
	}

}
