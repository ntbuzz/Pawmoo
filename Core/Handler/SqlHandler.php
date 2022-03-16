<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *	SQLHandler: COMMON SQL-DB HANDLER Class
 */
//==============================================================================
abstract class SQLHandler {	// extends SqlCreator {
	public $DateFormat = [
		'Date' => 'Y-m-d',
		'Time' => 'H:i:s',
		'TimeStamp' => 'Y-m-d H:i:s',
	];
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
	public $relations;		// relation tables
	private $LastSQL;		// Last Query WHERE term
	protected $Primary;		// for FUTURE
	protected $LastCond;	// for DEBUG
	protected $LastBuild;	// for DEBUG
	protected $LIKE_opr = 'LIKE';		// Ignore Upper/Lower case LIKE
	protected $NULL_ORDER = ' NULLS LAST';	// NULL seq
	private $register_callback = [ NULL,NULL];	// fetchDB Callback
//==============================================================================
//	abstruct method
	abstract protected function Connect($table);
	abstract protected function doQuery($sql);
	abstract protected function fetch_array();
	abstract protected function getLastError();
	abstract protected function updateRecord($wh, $row);	// INSERT or UPDATE
	abstract protected function reset_seq($table,$primary);	// Reset SEQ #
	abstract public function fieldConcat($sep,$arr);		// CONCAT()
	abstract public function drop_sql($kind,$table);		// DROP TABLE SQL
	abstract public function truncate_sql($table);			// TRUNCATE SQL
//==============================================================================v
//	Constructor( table name, DB Handler)
//==============================================================================
function __construct($table,$handler,$primary) {
		list($this->raw_table,$this->table) = (is_array($table))?$table:[$table,$table];
		$this->dbb = DatabaseHandler::get_database_handle($handler);
		$this->is_offset = DatabaseHandler::$have_offset;
		$this->load_columns();
		$this->handler = $handler;
		$this->Primary = $primary;
		$this->fieldAlias = new fieldAlias();
	}
//==============================================================================
// load columns info
private function load_columns() {
	$this->columns = $this->Connect($this->table);		// view-table column for Get-Record
	$this->raw_columns = ($this->raw_table === $this->table) ?
						$this->columns :
						$this->Connect($this->raw_table);	// write-table  column for Insert/Update
}
//==============================================================================
// Check Same of columns and bind key
public function bind_columns($data) {
	if(count($data) !== count($this->raw_columns)) {
		return false;		// column count miss-match
	}
	$keys = array_keys($this->raw_columns);
	$diff_arr = array_diff($data,$keys);
	if(empty($diff_arr)) return true;	// maybe CSV Header line
	return array_combine($keys,$data);
}
//==============================================================================
// fetchDB Callback method register
public function register_method($class,$method) {
	$cls = get_class($class);
	if(method_exists($class,$method)) {
		$this->register_callback = [$class,$method];
	} else {
		echo "'{$cls}' has not method '{$method}'\n";
	}
	// if (is_subclass_of($class, 'AppModel',false)) {
	// 	if(method_exists($class,$method)) {
	// 		$this->register_callback = [$class,$method];
	// 	}
	// } else {
	// 	echo "'{$cls}' is not sub-class AppModel\n";
	// }
}
//==============================================================================
// DEBUGGING for SQL Execute
	private function SQLdebug($sql,$where,$sort=[]) {
    	$dbg = debug_backtrace();
    	$func = $dbg[1]['function'];
		debug_log(DBMSG_HANDLER,["SQL-Execute ({$func} @ {$this->table})"=> [ 'COND'=>$this->LastBuild,'SORT'=>$sort,'SQL' => $sql,'WHERE'=>$where]]);
	}
//==============================================================================
// setupRelations: relation table reminder
public function setupRelations($relations) {
	$this->relations = $relations;
}
//==============================================================================
// reset primary seq value
public function resetPrimary() {
	$sql = $this->reset_seq($this->raw_table,$this->Primary);
	if($sql) $this->doQuery($sql);
}
//==============================================================================
// TRUNCATE TABLE
public function doTruncate() {
	$sql = $this->truncate_sql($this->raw_table);
    $this->execSQL($sql);
}
//==============================================================================
// fetchDB: get record data , and replace alias and bind column
public function fetchDB() {
	if($row = $this->fetch_array()) {
		$this->fieldAlias->to_alias_bind($row);
		list($obj,$method) = $this->register_callback;
		if ($obj !== NULL) $obj->$method($row);	// already checked by refistered
	}
	return $row;
}
//==============================================================================
// fetchDB: get record data , and replace alias and bind column
public function fetch_locale() {
	if($row = $this->fetch_array()) {
		$this->fieldAlias->to_lang_alias($row);
	}
	return $row;
}
//==============================================================================
// fetchDB: get record data , and replace alias and bind column
public function execSQL($sql,$logs = false) {
	if($logs) debug_log(DBMSG_HANDLER,['SQL' => $sql]);
	$this->doQuery($sql);
}
//==============================================================================
//	getValueLists: list-colum name, value-colums
public function getValueLists($table,$ref,$id,$cond=NULL) {
	if(empty($table)) $table = $this->table;
	$sql = $this->sql_QueryValues($table,$ref,$id,$cond);
	$this->execSQL($sql);
	$values = array();
//	debug_log(9,["VALUE-LIST" => [$table,$ref,$id,$sql,'REL'=>$this->relations,'ALIAS'=>$this->fieldAlias->GetAlias()]]);
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
	$this->execSQL($sql);
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
//	QUERY Command: generate SELECT DISTINCT
// private function QueryCommand($param) {
// //	return "SELECT DISTINCT on({$this->Primary} {$param}";
// 	return "SELECT DISTINCT {$param}";
// }
//==============================================================================
//	getRecordCount($cond) 
//		get $cond match record count
//==============================================================================
public function getRecordCount($cond) {
	$where = $this->sql_makeWHERE($cond);	// 検索条件
	$sql = "SELECT DISTINCT count(*) as \"total\" FROM {$this->table}";
//	$sql = $this->QueryCommand("count(*) as \"total\" FROM {$this->table}";
	$this->SQLdebug($sql,$where);
	$this->execSQL("{$sql}{$where};");
	$field = $this->fetch_array();
	return ($field) ? intval($field["total"]) : 0;
}
//==============================================================================
//	getRecordValue($cond,$use_relations) 
//	find $cond match record
//==============================================================================
public function getRecordValue($cond,$use_relations) {
	$where = $this->sql_makeWHERE($cond);		// 検索条件
	$sql = $this->sql_JoinTable($use_relations);
	$where .= ($this->is_offset) ? " offset 0 limit 1" : " limit 0,1";
	$this->SQLdebug($sql,$where);
	$sql .= "{$where};";
	$this->execSQL($sql);
	$row = $this->fetchDB();
	return ($row === FALSE) ? []:$row;
}
//==============================================================================
//	getMaxValueRecord($field_name) 
//		get MAX value into field_name by this-table
//==============================================================================
public function getMaxValueRecord($field_name) {
	$sql = "SELECT MAX({$field_name}) as \"max_val\" FROM {$this->table}";
	$this->execSQL($sql);
	$row = $this->fetchDB();
	return ($row) ? $row['max_val'] : 0;
}
//==============================================================================
//	getGroupCalcList($cond,$groups,$calc,$sortby,$max)
//		GROUP
//==============================================================================
public function getGroupCalcList($cond,$groups,$calc,$sortby,$max) {
	$where = $this->sql_makeWHERE($cond);
	$this->active_column = $fields = $groups;
	if(!empty($calc)) {
		foreach($calc as $func => $alias) {
			list($fn,$ff) = explode('.',$func);
			$fields[] = "{$ff}({$fn}) as {$alias}";
			$this->active_column[] = $alias;
		}
	}
	$sel = implode(',',$fields);
	$grp = implode(',',$groups);
	$sort = "";
	if($sortby !== []) {
		$col = [];
		foreach($sortby as $column => $seq) {
			$order = ($seq === SORTBY_DESCEND) ? "desc" : "asc";
			$col[]= "{$column} {$order}{$this->NULL_ORDER}";
		}
		$sort = " ORDER BY ".implode(',',$col);
	}
	$limit = ($max > 0) ? (($this->is_offset) ? " offset 0 limit {$max}" : " limit 0,{$max}"):'';
	$sql = "SELECT DISTINCT  {$sel} FROM {$this->raw_table}{$where} GROUP BY {$grp}{$sort}{$limit};";
	$this->execSQL($sql,false);
debug_log(DBMSG_HANDLER,["LOG-Aggregate" => [ 'COND' => $cond,'SQL'=>$sql]]);
	return $sql;
}
//==============================================================================
//	findRecord(cond): 
//	cond: query condition
//      [ AND... ] OR [ AND... ]
// pgSQL: SELECT *, count('No') over() as full_count FROM public.mydb offset 10 limit 50;
// SQLite3: SELECT *, count('No') over as full_count FROM public.mydb offset 10 limit 50;
//==============================================================================
public function findRecord($cond,$use_relations = FALSE,$sort = [],$raw=false) {
	$table = ($raw) ? $this->raw_table : $this->table;
	$where = $this->sql_makeWHERE($cond,$table);
	$sql = "SELECT DISTINCT  count(*) as \"total\" FROM {$table}";
	$this->execSQL("{$sql}{$where};",FALSE);		// No Logging
	$field = $this->fetch_array();
	$this->recordMax = ($field) ? $field["total"] : 0;
	$sql = $this->sql_JoinTable($use_relations,$table);
	if(!empty($sort)) {
		$orderby = "";
		foreach($sort as $key => $val) {
			$order = ($val === SORTBY_DESCEND) ? "desc" : "asc";
			$orderby .=  "{$table}.\"{$key}\" {$order}{$this->NULL_ORDER},";
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
	$this->SQLdebug($sql,$where,$sort);
	$sql .= "{$where};";
	$this->execSQL($sql);
}
//==============================================================================
//	firstRecord(cond,use-relation,sort): 
// returned col-data or FALSE
//==============================================================================
public function firstRecord($cond,$use_relations = FALSE,$sort) {
	$where = $this->sql_makeWHERE($cond);
	$sql = $this->sql_JoinTable($use_relations);
	if(!empty($sort)) {
		$orderby = "";
		foreach($sort as $key => $val) {
			$order = ($val === SORTBY_DESCEND) ? "desc" : "asc";
			$orderby .=  "{$this->table}.\"{$key}\" {$order}{$this->NULL_ORDER},";
		}
		$where .=  " ORDER BY ".trim($orderby,",");
	}
	$where .= " limit 1";
	$this->SQLdebug($sql,$where);
	$sql .= "{$where};";
	$this->execSQL($sql);
	$row = $this->fetchDB();
	return ($row === FALSE) ? FALSE:$row;
}
//==============================================================================
//	deleteRecord(wh): 
public function deleteRecord($wh) {
	$where = $this->sql_makeWHERE($wh,$this->raw_table);	// delete by real-table
	$sql = "DELETE FROM {$this->raw_table}{$where};";
	$this->execSQL($sql);
}
//==============================================================================
// Common SQL(SELECT ～ WHERE ～) generate
// SQlite3, PostgreSQL, mariaDB unique SQL command(update, insert, replace) will be generate instance class
//==============================================================================
// get value lists
	private function sql_QueryValues($table,$ref,$id,$cond) {
		$where = empty($cond) ? "" : $this->sql_makeWHERE($cond,$table);
		$alias = [];
		foreach([$ref,$id] as $field) {
			$key = $this->fieldAlias->get_lang_alias($field);
			$alias[$field] = $key;
		}
		$fields = array_map(function($k,$v)  {
			if($k === $v) return "\"{$v}\"";
			else return "\"{$v}\" AS \"{$k}\"";
		},array_keys($alias),array_values($alias));
		$sql = implode(',',$fields);
//		$groupby = (empty($grp)) ? '' : " GROUP BY \"{$grp}\"";
		$this->SQLdebug($sql,$where);
		$sql = "SELECT DISTINCT  {$sql} FROM {$table}{$where} ORDER BY \"{$id}\";";
		return $sql;
	}
//==============================================================================
//
	private function sql_GetRecordByKey($key,$val) {
		if(is_array($key)) {
			$expr = [];
			foreach(array_combine($key,$val) as $k => $v) $expr[] = "(\"{$k}\"='{$v}')";
			$sql = implode(' AND ',$expr);
		} else $sql = "\"{$key}\"='{$val}'";
		return "SELECT DISTINCT  * FROM {$this->table} WHERE {$sql};";
	}
//==============================================================================
// escape to single-quote(') in string value
protected function sql_str_quote($data,$find=["'",'\\'],$rep=["''",'\\\\']) {
	$row = array_map(function($v) use(&$find,&$rep) {
				return (gettype($v) === 'string') ? str_replace($find,$rep,$v):$v;
			},$data);
	return $row;
}
//==============================================================================
// Convert to columns type
protected function sql_safe_convert($data) {
	foreach($data as $key => $val) {
		if(array_key_exists($key,$this->raw_columns)) {
			switch($this->raw_columns[$key]) {
			case 'serial':
			case 'integer': $data[$key] = intval($val); break;
			case 'boolean': $data[$key] = is_bool_false($val) ? "'f'" : "'t'"; break;
			// case 'text':
			// 		$val = str_replace(["'",'\\'],["''",'\\\\'],$val);
			// 		$data[$key] = "'{$val}'";
			// 		break;
			// others, date, timestamp, etc...
			default: $data[$key] = (empty($val))?'NULL':"'{$val}'";
			}
		}
	}
	return $data;
}
//==============================================================================
// CONVERT FIELD TYPE
protected function fetch_convert($data) {
	if($data === false) return false;
	foreach($data as $key => $val) {
		if(array_key_exists($key,$this->raw_columns)) {
			switch($this->raw_columns[$key]) {
			case 'serial':
			case 'integer': $data[$key] = intval($val); break;
			case 'boolean': $data[$key] = is_bool_false($val) ? 'f' : 't'; break;
			// case 'text':
			// 		 $val = str_replace(["''",'\\\\'],["'",'\\'],$val);
			// 		 $data[$key] = $val;
			// 		break;
			}
		}
	}
	return $data;
}
//==============================================================================
// generate JOIN token
// relation table
//  [id] = [
//		[alias] = table.id.name
//		[alias] = [ ref_table, ref_name, rel_id, ref_id  ]	sub-relations
//		....
//	]
	private function sql_JoinTable($use_relations,$target_table=NULL) {
		if($target_table === NULL) $target_table = $this->table;
		$sql = "SELECT DISTINCT  {$target_table}.*";
		$frm = " FROM {$target_table}";
		$jstr = '';
		if($use_relations && !empty($this->relations)) {
			$join = [];//['L0'=>[],'L1'=>[]];
			foreach($this->relations as $key => $val) {
				foreach($val as $alias => $lnk) {
					if(is_array($lnk)) {	// [ refer]
						list($rel_tbl,$rel_fn,$fn,$table,$primary,$ref) = $lnk;
						$rel = "{$rel_tbl}.\"{$fn}\"={$table}.\"{$primary}\"";
						if(!isset($join[$table])) $join[$table] = $rel;			// duplicate-refer
					} else {
						list($table,$fn,$ref) = explode('.', $lnk);
						$rel = "{$target_table}.\"{$key}\"={$table}.\"{$fn}\"";
						if(!isset($join[$table])) $join = [$table => $rel] + $join;	// duplicate-refer
					}
					$sql .= ",{$table}.\"{$ref}\" AS \"{$alias}\"";
				}
			}
			foreach($join as $table => $val) $jstr .= " LEFT JOIN {$table} ON {$val}";
		}
		return "{$sql}{$frm}{$jstr}";
	}
//==============================================================================
// Re-Build Condition ARRAY, Create SQL-WHERE statement.
	private function sql_makeWHERE($cond,$target_table=NULL) {
		if($target_table === NULL) $target_table = $this->table;
		if($cond ===NULL) return $this->LastSQL;
		$this->LastCond = $cond;
		$this->LastBuild= $new_cond = re_build_array($cond);
		$sql = $this->makeExpr($new_cond,$target_table);
		if(strlen($sql)) $sql = ' WHERE '.$sql;
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
			// multi-columns f1+f2+f3...  OP val
			$multi_field = function($key,$op,$table,$val) {
				// field name arranged
				$expr = array_map( function($v) use(&$table) {
					$cmp = $this->fieldAlias->get_lang_alias($v);
					return "{$table}.\"{$cmp}\"";
				},str_explode('+',$key));
				if($op === NULL) {		// need LIKE or NOT LIKE
					// LIKE operation build
					$like_opstr = function($v) {
						if($v[0] == '-' && strlen($v) > 1) {
							$v = mb_substr($v,1);
							$op = "NOT {$this->LIKE_opr}";
						} else $op = $this->LIKE_opr;
						if(is_int($v) || trim($v,'%') === $v) $v = "%{$v}%";
						return (empty($v)) ? 'IS NULL' : "{$op} '{$v}'";
					};
					$opc = $this->concat_fields($expr);
					if(is_array($val)) {
						$lexpr = array_map( function($v) use(&$opc,&$like_opstr) {
							$x = $like_opstr($v);
							return "({$opc} {$x})";
						},$val);
						return implode(' OR ',$lexpr);
					} else {
						$opx = $like_opstr($val);
						return "{$opc} {$opx}";
					}
				}
				if(count($expr) > 1) {
					$expr = array_map(function($k) use(&$op,&$val) { return "({$k} {$op} {$val})"; },$expr);
					return implode(' OR ',$expr);
				}
				return "{$expr[0]} {$op} {$val}";
			};
			$opc = ''; $and_or_op = ['AND','OR','NOT'];
			foreach($items as $key => $val) {
				if(empty($key)) { echo "EMPTY({$val})!!!"; continue;}
				list($key,$op) = keystr_opr($key);
				if(empty($op) || $op === '%') {			// non-exist op or LIKE-op(%)
					if(is_array($val)) {
						if(in_array($key,$and_or_op,true)) {
							$opx = ($key === 'NOT') ? 'AND' : $key; 
							$opp = $dump_object($opx,$val,$table);
							if($key === 'NOT') $opp = "NOT ({$opp})";
						} else { // LIKE [ array ]
							$opp = $multi_field($key,NULL,$table,$val);
						}
					} else { // not have op code
						if(mb_strpos($val,'...') !== FALSE) {
							list($from,$to) = fix_explode('...',$val,2);
							if(empty($from)) {
								$op = '<=';
								$val = $to;
							} else if(empty($to)) {
								$op = '>=';
								$val = $from;
							} else {
								$op = 'BETWEEN';
								$val = "'{$from}' AND '{$to}'";
							}
						} else if(is_numeric($val) && empty($op)) {
							$op = '=';
						} else {
							$op = NULL;
						}
						$opp = $multi_field($key,$op,$table,$val);
					}
				} else if($op === '@') {	// SUBQUERY op
					if(array_key_exists($key,$this->relations)) {		// check exists relations
						$rel_defs = $this->relations[$key];
						list($cond_fn,$op) = keystr_opr(array_key_first($val));
						$rel_key = id_relation_name($key)."_{$cond_fn}";
						if(array_key_exists($rel_key,$rel_defs)) {		// exists relation-defs
							$rel = $rel_defs[$rel_key];
						} else {
							list($kk,$rel) = array_first_item($rel_defs);
							if(is_array($rel)) $rel = implode('.',$rel);	// force scalar-value
						}
						if(is_scalar($rel)) {
							list($tbl,$fn) = fix_explode('.',$rel,2);
							$ops = $dump_object('AND',$val,$tbl);
							$opp = "{$table}.\"{$key}\" IN (SELECT Distinct({$tbl}.\"{$fn}\") FROM {$tbl} WHERE ({$ops}))";
						} else {
							list($tbl,$tbl_prim,$tbl_rel,$rel_tbl,$rel_fn,$fn) = $rel;
							$fid = id_relation_name($tbl_rel)."_{$fn}";
							list($kk,$vv) = array_first_item($val);		// because each element will be same table,id
							$val = [str_replace($fid,$fn,$kk) => $vv]; // change rel-level field key
							$ops = $dump_object('AND',$val,$rel_tbl);
							$ops = "{$tbl}.\"{$tbl_rel}\" IN (SELECT Distinct({$rel_tbl}.\"{$rel_fn}\") FROM {$rel_tbl} WHERE ({$ops}))";
							$opp = "{$table}.\"{$key}\" IN (SELECT Distinct({$tbl}.\"{$tbl_prim}\") FROM {$tbl} WHERE ({$ops}))";
						}
					} else continue;
				} else if(is_array($val)) {
					$in_op = [ '=' => 'IN', '==' => 'IN', '<>' => 'NOT IN', '!=' => 'NOT IN'];
					if(array_key_exists($op,$in_op)) {
						$cmp = implode(',',array_map(function($v) { return is_numeric($v) ? $v:"'{$v}'";},$val));
						$opx = $in_op[$op];
						$opp = $multi_field($key,$opx,$table,"({$cmp})");
					} else {	// LIKE [ array ]
						$opp = $multi_field($key,NULL,$table,$val);
					}
				} else {
					if($val === NULL) {
						$in_op = [ '=' => 'IS', '==' => 'IS', '<>' => 'IS NOT', '!=' => 'IS NOT'];
						if(!array_key_exists($op,$in_op)) $op = '==';
						$op = $in_op[$op];
						$val = 'NULL';
					} else if(is_bool($val)) $val = ($val) ? "'t'" : "'f'";
					else if(!is_numeric($val)) $val = "'{$val}'";
					$opp = $multi_field($key,$op,$table,$val);
				}
				$opc = (empty($opc)) ? $opp : "({$opc}) {$opr} ({$opp})";
			}
			return $opc;
		};
		$sql = $dump_object('AND',$cond,$target_table);
		return $sql;
	}

}
