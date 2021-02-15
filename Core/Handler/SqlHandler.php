<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *	SQLHandler: COMMON SQL-DB HANDLER Class
 */
//==============================================================================
abstract class SQLHandler {	// extends SqlCreator {
	public  $DateStyle = 'Y-m-d';	// Date format
	public	$recordId;		// dummy for FMDB
	public	$table;			// connect table
	public	$columns;       // record column data
	public	$raw_table;		// target real table for INSERT/UPDATE
	public 	$raw_columns;   // target real column
	protected $dbb;  	    // DB handle
	protected $rows;
	protected $is_offset;	// OFFSET support?
	private	$startrec;		// start record number
	private	$limitrec;		// get record count
	private $handler;		// Databas Handler Name
	private $relations;		// relation tables
	private $LastSQL;		// Last Query WHERE term
	protected $LastCond;	// for DEBUG
	protected $LastBuild;	// for DEBUG
//==============================================================================
//	abstruct method
	abstract protected function Connect($table);
	abstract protected function doQuery($sql);
	abstract protected function fetch_array();
	abstract protected function getLastError();
	abstract protected function updateRecord($wh, $row);		// INSERT or UPDATE
//==============================================================================
//	Constructor( table name, DB Handler)
//==============================================================================
function __construct($table,$handler) {
		list($this->raw_table,$this->table) = (is_array($table))?$table:[$table,$table];
		$this->dbb = DatabaseHandler::get_database_handle($handler);
		$this->is_offset = DatabaseHandler::$have_offset;
		$this->columns = $this->Connect($this->table);		// view-table column for Get-Record
		$this->raw_columns = ($this->raw_table === $this->table) ?
							$this->columns :
							$this->Connect($this->raw_table);	// write-table  column for Insert/Update
		debug_log(FALSE,["Columns List" => $this->columns]);
		$this->handler = $handler;
		$this->fieldAlias = new fieldAlias();
	}
//==============================================================================
// setupRelations: relation table reminder
public function setupRelations($relations) {
	$this->relations = $relations;
//	debug_log(DBMSG_HANDLER,["RELATIONS" => $this->relations]);
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
public function getValueLists_callback($callback) {
    foreach($this->relations as $key => $val) {
		list($nm,$rel) = array_first_item($val);		// because each element will be same table,id
		list($tbl,$id) = explode('.',$rel);
		// re-build $val array
		array_walk($val,function(&$v,$k){list($t,$id,$fn) = explode('.', $v);$v=$fn;});
		$vv = [];
		foreach($val as $alias => $fn) $vv[] = "\"{$fn}\" as $alias";
		$ref_list = implode(',',$vv);
		$sql = "SELECT \"{$id}\",{$ref_list} FROM {$tbl} ORDER BY \"{$id}\";";
		$keys = array_keys($val);
		$bind = $this->fieldAlias->get_bind_ifexists($keys);
//debug_dump(['VAL'=>$val,'LIST'=>$ref_list,'BIND'=>$bind,'SQL'=>$sql]);
		$values = [];
		$this->doQuery($sql);
		while ($row = $this->fetch_array()) {	// other table refer is not bind!
			if($bind===FALSE) {
				foreach($val as $nm => $ref_fn) {
					$key = $row[$nm];
					if(!empty($key)) $values[$nm][$key] = $row[$id];
				}
			} else {
				$key = $this->fieldAlias->get_bind_key($row,$bind);
//				if(!empty($key)) 
				$values[$bind][$key] = $row[$id];
			}
		}
		if($bind===FALSE) {
			foreach($val as $nm => $ref_fn) {
				$callback($nm,$values[$nm]);
			}
		} else {
			$callback($bind,$values[$bind]);
		}
    }
}
//==============================================================================
//	getValueLists: list-colum name, value-colums
public function getValueLists($table,$ref,$id) {
	if(empty($table)) $table = $this->table;
	$sql = $this->sql_QueryValues($table,$ref,$id);
	$this->doQuery($sql);
	$values = array();
	debug_log(9,["VALUE-LIST" => [$table,$ref,$id,$sql,'REL'=>$this->relations,'ALIAS'=>$this->fieldAlias->GetAlias()]]);
	while ($row = $this->fetch_array()) {	// other table refer is not bind!
//		debug_log(-9,["Row" => $row]);
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
//	getRecordValue($cond,$use_relations) 
//	find $cond match record
//==============================================================================
public function getRecordValue($cond,$use_relations) {
	$where = $this->sql_makeWHERE($cond);		// 検索条件
	$sql = $this->sql_JoinTable($use_relations);
	$where .= ($this->is_offset) ? " offset 0 limit 1" : " limit 0,1";
	$sql .= "{$where};";
//	debug_log(DBMSG_HANDLER,[ "RecVal-SQL" => $sql]);
	$this->doQuery($sql);
	$row = $this->fetchDB();
	return ($row === FALSE) ? []:$row;
}
//==============================================================================
//	findRecord(cond): 
//	cond: query condition
//      [ AND... ] OR [ AND... ]
// pgSQL: SELECT *, count('No') over() as full_count FROM public.mydb offset 10 limit 50;
// SQLite3: SELECT *, count('No') over as full_count FROM public.mydb offset 10 limit 50;
//==============================================================================
public function findRecord($cond,$use_relations = FALSE,$sort = []) {
	$where = $this->sql_makeWHERE($cond);
	$sql = "SELECT count(*) as \"total\" FROM {$this->table}";
	$this->doQuery("{$sql}{$where};");
	$field = $this->fetch_array();
	debug_log(DBMSG_HANDLER,["Find" => "{$where}", "DATA" => $field]);
	$this->recordMax = ($field) ? $field["total"] : 0;
	$sql = $this->sql_JoinTable($use_relations);
	if(!empty($sort)) {
		$orderby = "";
		foreach($sort as $key => $val) {
			$order = ($val === SORTBY_DESCEND) ? "desc" : "asc";
			$orderby .=  "{$this->table}.\"{$key}\" {$order},";
		}
		$where .=  " ORDER BY ".trim($orderby,",");
	}
	if($this->limitrec > 0) {
		if($this->is_offset) {
			$where .= " offset {$this->startrec} limit {$this->limitrec}";
		} else {
			$where .= " limit {$this->startrec},{$this->limitrec}";
		}
	}
	$sql .= "{$where};";
	$this->doQuery($sql);
}
//==============================================================================
//	firstRecord(cond,use-relation,sort): 
//==============================================================================
public function firstRecord($cond,$use_relations = FALSE,$sort) {
	$where = $this->sql_makeWHERE($cond);
	$sql = $this->sql_JoinTable($use_relations);
	if(!empty($sort)) {
		$orderby = "";
		foreach($sort as $key => $val) {
			$order = ($val === SORTBY_DESCEND) ? "desc" : "asc";
			$orderby .=  "{$this->table}.\"{$key}\" {$order},";
		}
		$where .=  " ORDER BY ".trim($orderby,",");
	}
	$where .= " limit 1";
	$sql .= "{$where};";
	$this->doQuery($sql);
	$row = $this->fetchDB();
	return ($row === FALSE) ? []:$row;
}
//==============================================================================
//	deleteRecord(wh): 
public function deleteRecord($wh) {
	$where = $this->sql_makeWHERE($wh,$this->raw_table);	// delete by real-table
	$sql = "DELETE FROM {$this->raw_table}{$where};";
	$this->doQuery($sql);
}
//==============================================================================
// Common SQL(SELECT ～ WHERE ～) generate
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
		if(is_array($key)) {
			$expr = [];
			foreach(array_combine($key,$val) as $k => $v) $expr[] = "(\"{$k}\"='{$v}')";
			$sql = implode(' AND ',$expr);
		} else $sql = "\"{$key}\"='{$val}'";
		return "SELECT * FROM {$this->table} WHERE {$sql};";
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
// relation table
//  [id] = [
//		[alias] = table.id.name
//		....
//	]
	private function sql_JoinTable($use_relations) {
		$sql = "SELECT {$this->table}.*";
		$frm = " FROM {$this->table}";
		$jstr = '';
		if($use_relations) {
			foreach($this->relations as $key => $val) {
				foreach($val as $alias => $lnk) {
					list($table,$fn,$ref) = explode('.', $lnk);
					$sql .= ",{$table}.\"{$ref}\" AS \"{$alias}\"";
				}
				$jstr .= " LEFT JOIN {$table} ON {$this->table}.\"{$key}\" = {$table}.\"{$fn}\"";
			}
		}
		return "{$sql}{$frm}{$jstr}";
	}
//==============================================================================
// Re-Build Condition ARRAY, Create SQL-WHERE statement.
	private function sql_makeWHERE($cond,$target_table=NULL) {
		if($target_table === NULL) $target_table = $this->table;
		$re_build_array = function($cond) {
			$array_map_shurink = function($opr,$arr) use(&$array_map_shurink) {
				$array_merged = function($opr,&$arr,$val) use(&$array_merged) {
					if(is_array($val)) {
						foreach($val as $kk => $vv) {
							if($opr === $kk) {
								$array_merged($opr,$arr,$vv);
							} else {
								set_array_key_unique($arr,$kk,$vv);
							}
						}
					} else if(!empty($val)) $arr[] = $val;
				};
				$array_item_shurink = function($opr,$val) use(&$array_map_shurink) {
					return (is_array($val)) ? $array_map_shurink($opr,$val) : $val;
				};
				$AND_OR = [ 'AND' => TRUE, 'OR' => TRUE ];
				$wd = [];
				foreach($arr as $key => $val) {
					$child = $array_item_shurink((is_numeric($key))?$opr:$key,$val);
					if(is_numeric($key) || (isset($AND_OR[$key]) && (count($child)===1 || ($opr===$key)))) {
						$array_merged($opr,$wd,$child);
					} else {
						set_array_key_unique($wd,$key,$child);
					}
				}
				return $wd;
			};
			return $array_map_shurink('AND',$cond);
		};
		if($cond ===NULL) return $this->LastSQL;
		$this->LastCond = $cond;
		$this->LastBuild= $new_cond = $re_build_array($cond);
		$sql = $this->makeExpr($new_cond,$target_table);
		if(strlen($sql)) $sql = ' WHERE '.$sql;
		debug_log(DBMSG_HANDLER,['COND-INPUT'=>$cond,'RE-BUILD' => $new_cond,'WHERE' => $sql]);
		return ($this->LastSQL = $sql);
	}
//==============================================================================
// GENERATE WHERE token from ARRAY[] expression
// item := 
//   	AND => [ itenm, item,... ] | [ item, item,...]
//   	OR => [ itenm, item,... ] |
//   	NOT => [ itenm ] |
//		fieldkey => findvalue
	private function makeExpr($cond,$target_table) {
		$dump_object = function ($opr,$items,$table)  use (&$dump_object)  {
			// LIKE operation build
			$like_opstr = function($v) {
				if($v[0] == '-' && strlen($v) > 1) {
					$v = mb_substr($v,1);
					$op = 'NOT LIKE';
				} else $op = 'LIKE';
				return [$v,$op];
			};
			// multi-column LIKE op
			$like_object = function($key,$val,$table) use(&$like_opstr) {
				$expr = [];
				foreach(string_to_array('+',$key) as $cmp) {
					$cmp = $this->fieldAlias->get_lang_alias($cmp);
					$opk = "{$table}.\"{$cmp}\"";
					$cmp = array_map(function($v) use(&$opk,&$like_opstr) {
							list($v,$opx) = $like_opstr($v);
							return "({$opk} {$opx} '%{$v}%')";
						},$val);
					$expr[] = implode('OR',$cmp);
				}
				$opp = implode('OR',$expr);
				return (count($expr)===1) ? $opp : "({$opp})";
			};
			// multi-columns f1+f2+f3...  OP val
			$multi_field = function($key,$op,$table,$val) {
				$expr = [];
				foreach(string_to_array('+',$key) as $cmp) {
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
						if(in_array($key,$and_or_op,true)) {
							$opx = ($key === 'NOT') ? 'AND' : $key; 
							$opp = $dump_object($opx,$val,$table);
//							if(!empty($opp)) $opp = "({$opp})";
							if($key === 'NOT') $opp = "(NOT {$opp})";
						} else { // LIKE [ array ]
							$opp = $like_object($key,$val,$table);
						}
					} else { // not have op code
						if(mb_strpos($val,'...') !== FALSE) {
							$op = 'BETWEEN';
							list($from,$to) = string_to_array('...',$val);
							$val = "'{$from}' AND '{$to}'";
						} else if(is_numeric($val) && empty($op)) {
							$op = '=';
						} else {
							list($val,$op) = $like_opstr($val);
							$val = "'%{$val}%'";
						}
						$opp = $multi_field($key,$op,$table,$val);
					}
				} else if($op === '@') {	// SUBQUERY op
					if(array_key_exists($key,$this->relations)) {		// check exists relations
						$rel = $this->relations[$key];
						list($nm,$rel) = array_first_item($rel);		// because each element will be same table,id
						list($tbl,$fn) = explode('.',$rel);
						$ops = $dump_object('AND',$val,$tbl);
						$opp = "({$table}.\"{$key}\" IN (SELECT Distinct({$tbl}.\"{$fn}\") FROM {$tbl} WHERE {$ops}))";
					} else continue;
				} else if(is_array($val)) {
					$in_op = [ '=' => 'IN', '==' => 'IN', '<>' => 'NOT IN', '!=' => 'NOT IN'];
					if(array_key_exists($op,$in_op)) {
						$cmp = implode(',',array_map(function($v) { return "'{$v}'";},$val));
						$opx = $in_op[$op];
//						$opp = "({$table}.\"{$key}\" {$opx} ({$cmp}))";
						$opp = $multi_field($key,$opx,$table,"({$cmp})");
					} else {	// LIKE [ array ]
						$opp = $like_object($key,$val,$table);
					}
				} else {
					if($val === NULL) {
						$in_op = [ '=' => 'IS', '==' => 'IS', '<>' => 'IS NOT', '!=' => 'IS NOT'];
						if(!array_key_exists($op,$in_op)) $op = '==';
						$op = $in_op[$op];
						$val = 'NULL';
					} else if(!is_numeric($val)) $val = "'{$val}'";
					$opp = $multi_field($key,$op,$table,$val);
				}
				$opc = (empty($opc)) ? "{$opp}" : "({$opc}{$opr}{$opp})";
			}
			return (empty($opc)) ? '' : "{$opc}";	// ((count($items)===1) ? $opc : "({$opc})");
		};
		$sql = $dump_object('AND',$cond,$target_table);
		return $sql;
	}

}
