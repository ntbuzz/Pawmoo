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
	public	$relations;		// relation tables
	public	$lang_alias = [];	// from fieldAlias:language field.
	public	$lang_alternate = FALSE; // from fieldAlias:use orijin field when lang-field empty.
	private $LastWHERE;		// Last COND WHWEW
	protected $Primary;		// for FUTURE
	protected $LastBuild;	// for COND re-buld DEBUG
	protected $LIKE_OPR = ['%'=>['LIKE','%'],'$'=>['LIKE','%']];
	protected $NULL_ORDER = ' NULLS LAST';	// NULL seq
	private $register_callback = [ NULL,NULL];	// fetchDB Callback
//==============================================================================
//	abstruct method
	abstract protected function Connect($table);
	abstract protected function doQuery($sql);
	abstract protected function fetch_array();
	abstract protected function getLastError();
	abstract protected function updateRecord($wh, $row);	// INSERT or UPDATE
	abstract protected function reset_seq($table);			// Reset SEQ #
	abstract public function fieldConcat($sep,$arr);		// CONCAT()
	abstract public function drop_sql($kind,$table);		// DROP TABLE SQL
	abstract public function truncate_sql($table);			// TRUNCATE SQL
//==============================================================================v
//	Constructor( table name, DB Handler)
//==============================================================================
function __construct($table,$handler,$primary,$db=NULL) {
		list($this->raw_table,$this->table) = (is_array($table))?$table:[$table,$table];
		$this->dbb = DatabaseHandler::get_database_handle($handler,$db);
		$this->is_offset = DatabaseHandler::$have_offset;
		$this->load_columns();
		$this->handler = $handler;
		$this->Primary = $primary;
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
}
//==============================================================================
// Move to here from fieldAlias class.
//	if exists LOCALE alias, get LOCALE fields name
public function get_lang_alias($field_name) {
     return (array_key_exists($field_name,$this->lang_alias)) ? $this->lang_alias[$field_name] : $field_name;
}
//==============================================================================
// ALIAS fields replace to standard field, and BIND-column to record field
public function to_lang_alias(&$row) {
    foreach($this->lang_alias as $key => $lang) {
        if(!empty($row[$lang]) || $this->lang_alternate === FALSE) $row[$key] = $row[$lang];
        unset($row[$lang]);
    }
}
//==============================================================================
// setupRelations: relation table reminder
public function setupFieldTransfer($alias,$relations=NULL) {
	$this->lang_alias = $alias;
	$this->relations = $relations;
}
//==============================================================================
// SETUP Paging Parmeter
// pagenum must be >=1 on call function
public function SetPaging($pagesize, $pagenum) {
	$this->startrec = $pagesize * ($pagenum - 1);		// start record num calc
	if($this->startrec < 0) $this->startrec = 0;
	$this->limitrec = $pagesize;		// get limit records
}
//==============================================================================
// reset primary seq value
public function resetPrimary() {
	$sql = $this->reset_seq($this->raw_table);
	if($sql) $this->doQuery($sql);
}
//==============================================================================
// TRUNCATE TABLE
public function doTruncate() {
	$sql = $this->truncate_sql($this->raw_table);
	$this->doQuery($sql);
}
//==============================================================================
// fetchDB: get record data , and replace alias and bind column
public function fetchDB() {
	if($row = $this->fetch_array()) {
		$this->to_lang_alias($row);
		list($obj,$method) = $this->register_callback;
		if ($obj !== NULL) $obj->$method($row);	// already checked by refistered
	}
	return $row;
}
//==============================================================================
// fetch_locale: get record data , and replace alias
public function fetch_locale() {
	if($row = $this->fetch_array()) {
		$this->to_lang_alias($row);
	}
	return $row;
}
//==============================================================================
// execSQL: Logging SQL, and execute SQL
private function executeSQL($build_sql,$logs = false) {
	$sql = "";
	foreach($build_sql as $cmd => $val) {
		if($val !== NULL) {
			$expr = (is_int($cmd)) ? $val : "{$cmd} {$val}";
			$sql = "{$sql} {$expr}";
		}
	}
	$sql = trim($sql).";";
	if($logs) {		// DEBUGGING LOG for SQL Execute
    	$dbg = debug_backtrace();
    	$func = $dbg[1]['function'];
		debug_log(DBMSG_HANDLER,["execute-SQL ({$func} @ {$this->table})"=> [ 'COND'=>$this->LastBuild,'CMD'=>$build_sql,'SQL'=>$sql]]);
	}
	$this->doQuery($sql);
}
//==============================================================================
//	getValueLists: list-colum name, value-colums
public function getValueLists($table,$ref,$id,$cond=NULL) {
	if(empty($table)) $table = $this->table;
	$alias = [];
	foreach([$ref,$id] as $field) {
		$key = $this->get_lang_alias($field);
		$alias[$field] = $key;
	}
	$fields = array_map(function($k,$v)  {
		if($k === $v) return "\"{$v}\"";
		else return "\"{$v}\" AS \"{$k}\"";
	},array_keys($alias),array_values($alias));
	$items = implode(',',$fields);
	$build_sql = [
		'SELECT' => $items,
		'FROM'	=> $table,
		'WHERE'	=> $this->sql_buildWHERE($cond,$table),
		'ORDER BY' => "\"{$id}\"",
	];
	$this->executeSQL($build_sql,true);
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
	if(is_array($key)) {
		$expr = [];
		foreach(array_combine($key,$val) as $k => $v) $expr[] = "(\"{$k}\"='{$v}')";
		$where = implode(' AND ',$expr);
	} else $where = "\"{$key}\"='{$val}'";
	$build_sql = [
		'SELECT' => "*",
		'FROM'	=> $this->table,
		'WHERE'	=> $where,
	];
	$this->executeSQL($build_sql,true);
	return $this->fetchDB();
}
//==============================================================================
//	getRecordCount($cond) 
//		get $cond match record count
//==============================================================================
public function getRecordCount($cond) {
	$build_sql = [
		'SELECT' => "count(*) as \"total\"",
		'FROM'	=> $this->table,
		'WHERE'	=> $this->sql_buildWHERE($cond,$this->table),
	];
	$this->executeSQL($build_sql,true);
	$field = $this->fetch_array();
	return ($field) ? intval($field["total"]) : 0;
}
//==============================================================================
//	getRecordValue($cond) 
//	find $cond match record
//==============================================================================
public function getRecordValue($cond) {
	$build_sql = [
		'SELECT'=> "{$this->table}.*",
		'FROM'	=> $this->table,
		'WHERE'	=> $this->sql_buildWHERE($cond,$this->table),
		($this->is_offset) ? "offset 0 limit 1" : "limit 0,1",
	];
	$this->executeSQL($build_sql,true);
	$row = $this->fetchDB();
	return ($row === FALSE) ? []:$row;
}
//==============================================================================
//	getMaxValueRecord($field_name) 
//		get MAX value into field_name by this-table
//==============================================================================
public function getMaxValueRecord($field_name) {
	$build_sql = [
		'SELECT'=> "MAX({$field_name}) as \"max_val\"",
		'FROM'	=> $this->table,
	];
	$this->executeSQL($build_sql,true);
	$row = $this->fetchDB();
	return ($row) ? $row['max_val'] : 0;
}
//==============================================================================
//	getGroupCalcList($cond,$groups,$calc,$sortby,$max)
//		GROUP
//==============================================================================
public function getGroupCalcList($cond,$groups,$calc,$sortby,$max) {
	$this->active_column = $fields = $groups;
	if(!empty($calc)) {
		foreach($calc as $func => $alias) {
			list($fn,$ff) = explode('.',$func);
			$fields[] = "{$ff}({$fn}) as {$alias}";
			$this->active_column[] = $alias;
		}
	}
	$sel = implode(',',$fields);
	$build_sql = [
		'SELECT'	=> $sel,
		'FROM'		=> $this->raw_table,
		'WHERE'		=> $this->sql_buildWHERE($cond,$this->raw_table),
		'GROUP BY'	=> implode(',',$groups),
		'ORDER BY'	=> $this->sql_sortby($sortby),
	];
	if($max > 0) {
		$build_sql[] = (($this->is_offset) ? " offset 0 limit {$max}" : " limit 0,{$max}");
	}
	$this->executeSQL($build_sql,true);
}
//==============================================================================
//	findRecord(cond): 
//	cond: query condition
//      [ AND... ] OR [ AND... ]
// pgSQL: SELECT *, count('No') over() as full_count FROM public.mydb offset 10 limit 50;
// SQLite3: SELECT *, count('No') over as full_count FROM public.mydb offset 10 limit 50;
//==============================================================================
public function findRecord($cond,$sort = [],$raw=false) {
	$table = ($raw) ? $this->raw_table : $this->table;
	// get record count
	$build_sql = [
		'SELECT'	=> "count(*) as \"total\"",
		'FROM'		=> $table,
		'WHERE'		=> $this->sql_buildWHERE($cond,$table),
		'ORDER BY'	=> NULL,
	];
	$this->executeSQL($build_sql,true);
	$field = $this->fetch_array();
	$this->recordMax = ($field) ? $field["total"] : 0;
	// re-make get all fields
	$build_sql['SELECT']   = "{$table}.*";
	$build_sql['ORDER BY'] = $this->sql_sortby($sort);
	if($this->limitrec > 0) {
		if($this->is_offset) {
			$build_sql[] = " offset {$this->startrec} limit {$this->limitrec}";
		} else {
			$build_sql[] = " limit {$this->startrec},{$this->limitrec}";
		}
	}
	$this->executeSQL($build_sql,true);
}
//==============================================================================
//	firstRecord(cond,sort): 
// returned col-data or FALSE
//==============================================================================
public function firstRecord($cond,$sort) {
	$build_sql = [
		'SELECT'	=> "{$this->table}.*",
		'FROM'		=> $this->table,
		'WHERE'		=> $this->sql_buildWHERE($cond,$this->table),
		'ORDER BY'	=> $this->sql_sortby($sort),
	];
	$build_sql[] = "limit 1";
	$this->executeSQL($build_sql,true);
	$row = $this->fetchDB();
	return ($row === FALSE) ? FALSE:$row;
}
//==============================================================================
//	deleteRecord(wh): 
public function deleteRecord($cond) {
	$build_sql = [
		'DELETE' => '',
		'FROM'	=> $this->raw_table,
		'WHERE'	=> $this->sql_buildWHERE($cond,$this->raw_table),
	];
	$this->executeSQL($build_sql,true);
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
			default:
                if(gettype($val) === 'string') $data[$key] = str_replace("\r\n","\n",$val);
			}
		}
	}
	return $data;
}
//==============================================================================
//	sql_sortby(sortby)
//		generate ORDER BY expr
//==============================================================================
private function sql_sortby($sortby,$table=NULL) {
	if($sortby === NULL || $sortby ===[]) return NULL;
	if(is_scalar($sortby)) $sortby = [ $sortby => SORTBY_ASCEND];
	$col = [];
	foreach($sortby as $column => $seq) {
		$order = ($seq === SORTBY_DESCEND) ? "desc" : "asc";
		$field = (empty($table)) ? $column : "{$table}.\"{$column}\"";
		$col[]= "{$field} {$order}{$this->NULL_ORDER}";
	}
	return implode(',',$col);
}
//==============================================================================
// Re-Build Condition ARRAY, Create SQL-WHERE statement.
	private function sql_buildWHERE($cond,$target_table=NULL) {
		if($target_table === NULL) $target_table = $this->table;
		if($cond ===NULL) return $this->LastWHERE;
		$this->LastBuild = re_build_array($cond);
		$where = $this->makeExpr($this->LastBuild,$target_table);
		if(empty($where)) $where = NULL;
		return ($this->LastWHERE = $where);
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
					$cmp = $this->get_lang_alias($v);
					return "{$table}.\"{$cmp}\"";
				},str_explode('+',$key));
				if(in_array($op,['%','$','',NULL])) {	// need LIKE/ILIKE or NOT LIKE/ILIKE
					$op_pat = (isset($this->LIKE_OPR[$op]))?$op:'%';
					list($like_opc,$pat) = $this->LIKE_OPR[$op_pat];
					// LIKE operation build
					$like_opstr = function($v) use(&$like_opc,&$pat){
						if($v[0] == '-' && strlen($v) > 1) {
							$v = mb_substr($v,1);
							$op = "NOT {$like_opc}";
						} else $op = $like_opc;
						if(is_int($v) || (is_string($v) && trim($v,$pat) === $v)) $v = "{$pat}{$v}{$pat}";
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
			$between_field = function($val) {
				list($from,$to) = fix_explode('...',$val,2);
				if(empty($from)) {
					$op = '<=';
					$val = "'{$to}'";
				} else if(empty($to)) {
					$op = '>=';
					$val = "'{$from}'";
				} else {
					$op = 'BETWEEN';
					if(is_numeric($from) && is_numeric($to))
						$val = "{$from} AND {$to}";
					else $val = "'{$from}' AND '{$to}'";
				}
				return [$op,$val];
			};
			$opc = ''; $and_or_op = ['AND','OR','NOT'];
			foreach($items as $key => $val) {
				if($key === REBUILD_MARK) continue;	// re-build mark
				if(empty($key)) { echo "EMPTY({$val})!!!"; continue;}
				list($key,$op) = keystr_opr($key);
				if(in_array($op,['%','$',''])) {	// non-exist op or LIKE(%,$)
					if(is_array($val)) {
						if(in_array($key,$and_or_op,true)) {
							$opx = ($key === 'NOT') ? 'AND' : $key; 
							$opp = $dump_object($opx,$val,$table);
							if($key === 'NOT') $opp = "NOT ({$opp})";
						} else { // LIKE [ array ]
							$opp = $multi_field($key,$op,$table,$val);
						}
					} else { // not have array
						if(mb_strpos($val,'...') !== FALSE) {
							list($op,$val) = $between_field($val);
						} else if(empty($op)) {
							$op = (is_numeric($val)) ? '=' : NULL;
						}
						$opp = $multi_field($key,$op,$table,$val);
					}
				} else if($op === '@') {	// SUBQUERY op
					if(array_key_exists($key,$this->relations)) {		// check exists relations
						$rel_defs = $this->relations[$key];
						list($cond_fn,$op) = keystr_opr(array_key_first($val));
						// V.2 New-Relations
						list($tbl,$fn) = array_first_item($rel_defs);
						$ops = $dump_object('AND',$val,$tbl);
						$opp = "{$table}.\"{$key}\" IN (SELECT Distinct({$tbl}.\"{$fn}\") FROM {$tbl} WHERE ({$ops}))";
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
					if(mb_strpos($val,'...') !== FALSE) {
						list($op,$val) = $between_field($val);
					} else if($val === NULL) {
						$in_op = [ '=' => 'IS', '==' => 'IS', '<>' => 'IS NOT', '!=' => 'IS NOT'];
						if(!array_key_exists($op,$in_op)) $op = '==';
						$op = $in_op[$op];
						$val = 'NULL';
					} else if(is_bool($val)) $val = ($val) ? "'t'" : "'f'";
					else if(!is_int($val)) $val = "'{$val}'";
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
