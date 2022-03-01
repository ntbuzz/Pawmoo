<?php
//==============================================================================
function array_extract($arr,$n) {
	if(is_array($arr)) {
		$slice = [];
		foreach($arr as $key => $val) {
			if(is_int($key)) $slice[] = $val;//(is_array($val))?[$val]:$val;
			else $slice[] = [$key => $val];
			--$n;
		}
	} else $slice = [$arr];
	while($n-- > 0)$slice[]=NULL;
	return $slice;
}
//==============================================================================
function oct_extract($val,$n) {
	$oct = [];
	while($n--) { $oct[] = ($val & 07); $val >>= 3; }
	return $oct;
}

//==============================================================================
// Databas Table Create Class
class AppSchema extends AppBase {
	public $FieldSchema;		// read-only column => view column
	public $TableSchema;		// read/write column => table column
	public $locale_columns;
	public $bind_columns;
	public $ViewSet;
	const typeXchanger  = [
		'Postgre' =>  [],
		'SQLite' => [
			'serial' => 'integer',
		],
	];
//==============================================================================
// setup table-name and view-table list
	function __construct() {
	    parent::__construct();                    // call parent
		$this->ClassName = get_class($this);
        foreach(static::$DatabaseSchema as $key => $val) $this->$key = $val;
		$viewset = (isset($this->DataView)) ? ((is_array($this->DataView)) ? $this->DataView : [$this->DataView]) : [];
		if(is_array($this->DataTable)) {
			list($table,$view) = $this->DataTable;
			if($table !== $view) array_unshift($viewset, $view);
		} else $table = $this->DataTable;
		$this->MyTable = $table;
		$this->ViewSet = $viewset;
        $driver = $this->Handler . 'Handler';
        $this->dbDriver = new $driver($this->MyTable,$this->Primary);        // connect Database Driver
		$this->SchemaSetup();
    }
//==============================================================================
// Switch Schema Language
private function SchemaSetup() {
    $this->SchemaAnalyzer();
	debug_dump([             // DEBUG LOG information
		'SCHEMA' => $this->Schema,
		'VIEW' => $this->ViewSet,
		'TABLE' => $this->TableSchema,
		'FIELD' => $this->FieldSchema,
	]);
}
//==============================================================================
//	スキーマ解析
public function SchemaAnalyzer() {
	$this->FieldSchema = $this->TableSchema = [];
	foreach($this->Schema as $key => $defs) {
		list($dtype,$flag,$width,$rel) = array_extract($defs,4);
		$dtype = strtolower($dtype);
		list($link,$def) = array_first_item($rel);
		if(is_array($defs)) {
			if(!is_int($link)) $this->TableSchema[$key] =  [$dtype,$flag,$width];
			if((!is_int($link) || $width !== NULL)) {
				$this->FieldSchema[$key] = [$dtype,$flag,$width];
			}
		} else $this->TableSchema[$key] = [$dtype,$flag,$width];
		if(is_array($rel)) {
			if(is_int($link)) {		// bind or multi-bind
				if(is_array($def)) {
					if($width !== NULL) $this->FieldSchema[$key] = ['bind',$flag,$width];
				} else if($width !== NULL) {
					$this->FieldSchema[$key] = ['bind',$flag,$width,$rel];
				}
			} else{
				$view = [];
				foreach($def as $kk => $vv) {
					list($alias,$flag,$width,$rel) = array_extract($vv,4);
					$atype = (is_array($alias))?'alias-bind':'alias';
					if($width!==NULL) $this->FieldSchema[$kk] = [$atype,$flag,$width];
					$view[$kk] = $alias;
				}
			}
		}
	}
}
//==============================================================================
//	言語リソースの再設定
protected function ResetLocation() {
	$this->locale_columns=[];
	foreach($this->FieldSchema as $key => $defs) {
		if($defs[4]) {
			$locale_name = "{$key}_" . LangUI::$LocaleName;
			if(array_key_exists($locale_name,$this->columns)) {
				$this->locale_columns[$key] = $locale_name;
			}
		}
	}
	$this->dbDriver->fieldAlias->SetupAlias($this->locale_columns,$this->bind_columns);
}
//==============================================================================
// execute SQL
	private function doSQL($exec,$sql) {
		if(empty($sql)) return;
		echo "SQL: {$sql}\n";
		if($exec) $this->dbDriver->execSQL($sql);
    }
//==============================================================================
//	データベーステーブルを作成
public function CreateDatabase($exec=false) {
	$sql = $this->dbDriver->drop_sql("TABLE",$this->MyTable);
	$this->doSQL($exec,$sql);
	// Create Table
	$fset = [];
	foreach($this->TableSchema as $key => $field) {
		list($ftype,$flag,$width) = (is_array($field)) ? $field : [$field,NULL,NULL];
		$lftype = strtolower($ftype);
		if(array_key_exists($lftype,static::typeXchanger[HANDLER])) $ftype = static::typeXchanger[HANDLER][$lftype];
		$str = "{$key} {$ftype}";
		if($lftype === 'serial') $str .= " NOT NULL";
		$fset[] = $str;
	}
	$fset[] = "PRIMARY KEY ({$this->Primary})";
	$sql = "CREATE TABLE {$this->MyTable} (\n";
	$sql .= implode(",\n",$fset) . "\n);";
	$this->doSQL($exec,$sql);
}
//==============================================================================
//	テーブルとビューを作成
public function CreateTableView($exec=false) {
	foreach($this->ViewSet as $view) {
		$sql = $this->dbDriver->drop_sql("VIEW",$view);
		$this->doSQL($exec,$sql);
	}
	foreach($this->ViewSet as $view) {
		debug_dump(["VIEW-DEFS" => $view]);
		$sql = $this->createView($this->MyTable,$view);
		$this->doSQL($exec,$sql);
	}
}
//==============================================================================
// extract DataTable or Alternate DataView
    private function model_view($db) {
		list($model,$field) = fix_explode('.',$db,2);
        if(preg_match('/(\w+)(?:\[(\d+)\])/',$model,$m)===1) {
            list($tmp,$model,$n) = $m;
        } else $n = NULL;
		$table = $this->$model->get_view_name($n);
        return [$table,$field];
    }
//==============================================================================
// extract DataTable or Alternate DataView
public function get_view_name($n) {
		if($n === NULL) {
			$table = (is_array($this->DataTable))? $this->DataTable[1]:$this->DataTable;
		} else if(isset($this->DataView)) {
			$table = $this->DataView[$n];
		} else $table = $this->MyTable;
        return $table;
    }
//==============================================================================
// createView: Create View Table
private function createView($table,$view) {
	$alias_sep = function($key) {
		if(substr($key, -1) === '.') $key .= ' ';
		list($alias,$sep) = fix_explode('.',$key,2);
		return [$alias,$sep];
	};
	if(empty($this->ViewSet)) return '';
	$sql = "CREATE VIEW {$view} AS SELECT\n";
	$join = $left = [];
	$tables = [ $table ];
	foreach($this->Schema as $column => $defs) {
		list($dtype,$flag,$wd,$rel) = array_extract($defs,4);
	 	if($rel===NULL) $join[] = "{$table}.\"{$column}\"";
		if(is_array($rel)) {
			list($key,$rels) = array_first_item($rel);
			if(is_int($key)) {		// bind
				list($alias,$sep) = $alias_sep($column);
				if(is_array($rels)) {		// multi-bind
					$bind = array_map(function($fn) {
						list($tbl,$nm) = $this->model_view($fn);
						return "{$tbl}.\"{$nm}\"";
					},$rels);
					$btbl = array_map(function($fn) {
						list($tbl,$nm) = $this->model_view($fn);
						return $tbl;
					},$rels);
					$tables[] = implode(",",$btbl);
		 			$join[] = $this->dbDriver->fieldConcat($sep,$bind) . " as {$alias}";
				} else {	// self-bind
					$bind = [];
					foreach($rel as $fn) $bind[] = "{$table}.\"{$fn}\"";
					$join[] = $this->dbDriver->fieldConcat($sep,$bind) . " as {$alias}";
				}
			} else {
				list($tbl,$id) = $this->model_view($key);
				foreach($rels as $alias => $val) {
					if(is_array($val)) {
						list($name,$flag,$wd) = $val;
						if(is_array($name)) {	// combine
							list($alias,$sep) = $alias_sep($alias);
							$bind = [];
							foreach($name as $fn) $bind[] = "{$tbl}.\"{$fn}\"";
							$join[] = $this->dbDriver->fieldConcat($sep,$bind) . " as {$alias}";
						} else {
							$join[] = "{$tbl}.\"{$name}\" as {$alias}";
						}
					} else {
						$join[] = "{$tbl}.\"{$val}\" as {$alias}";
					}
					if(!isset($left[$tbl])) $left[$tbl] = "LEFT JOIN {$tbl} on {$table}.\"{$column}\" = {$tbl}.{$id}";
				}
			}
		}
	}
xdebug_dump(['TABLE'=>$table,'VIEW'=>$view,'JOIN'=>$join,'LEFT'=>$left]);
	$sql .= implode(",\n",$join)."\n";
	$sql .= "FROM ".implode(",",$tables)."\n";
	$sql .= implode("\n",$left).";";
	return $sql;
}

}