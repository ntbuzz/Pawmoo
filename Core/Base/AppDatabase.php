<?php
/**
 * Autoloader for Database Table Creator ONLY!
 */
class SetupLoader {
	public static $AliasClass = [];
    private static $LoadDirs = [];
//==============================================================================
    private static function loadClass($className) {
		foreach(static::$AliasClass as $fname => $classes) {
			if(in_array($className,$classes)) {
				$className = $fname;
				break;
			}
		}
        foreach(static::$LoadDirs as $directory) {
            $file_name = "{$directory}/{$className}.php";
            if(is_file($file_name)) {
                require_once($file_name);
                return true;
            }
        }
		echo "NOT FOUND:{$className}\n";
    }
//==============================================================================
// Setup専用のローダー
public static function Setup($appname,$alias) {
    static::$LoadDirs = [
        'Core/Class',
        "app/{$appname}/Config/Setup",
    ];
	static::$AliasClass = $alias;
    spl_autoload_register(array('SetupLoader','loadClass'));
}

}
//==============================================================================
// Databas Table Create Class
class AppDatabase {
	private $MyTable = '';
	private $ViewSet = [];
    protected $dbDriver;            // Database Driver
    protected static $Database = [];
	private $data_folder;
	private $ClassName;
	const typeXchanger  = [
		'Postgre' =>  [],
		'SQLite' => [
			'serial' => 'integer',
		],
	];
//==============================================================================
// setup table-name and view-table list
	function __construct($path) {
		$this->ClassName = get_class($this);
        foreach(static::$Database as $key => $val) $this->$key = $val;
		$viewset = (isset($this->DataView)) ? ((is_array($this->DataView)) ? $this->DataView : [$this->DataView]) : [];
		if(is_array($this->DataTable)) {
			list($table,$view) = $this->DataTable;
			if($table !== $view) array_unshift($viewset, $view);
		} else $table = $this->DataTable;
		$this->data_folder = $path;
		$this->MyTable = $table;
		$this->ViewSet = $viewset;
        $driver = $this->Handler . 'Handler';
        $this->dbDriver = new $driver($this->DataTable,$this->Primary);        // connect Database Driver
    }
//==============================================================================
// dynamic construct OBJECT Class for 'modules'
public function __get($PropName) {
    if(isset($this->$PropName)) return $this->$PropName;
    $prop_name = "{$PropName}Setup";
	$this->$PropName = new $prop_name($this->data_folder);
	return $this->$PropName;
}
//==============================================================================
// extract DataTable or Alternate DataView
    private function model_view($db) {
		list($model,$field) = fix_explode('.',$db,2);
        if(preg_match('/(\w+)(?:\[(\d+)\])/',$model,$m)===1) {
            $model = $m[1];
            $table = (is_array($this->$model->DataView))
                        ? $this->$model->DataView[$m[2]]    // View Element Index
                        : $this->$model->dbDriver->table;	// illegal define
        } else $table = $this->$model->dbDriver->table;
        return [$table,$field];
    }
//==============================================================================
// execute SQL
	private function doSQL($exec,$sql) {
		if(empty($sql)) return;
		echo "SQL: {$sql}\n";
		if($exec) $this->dbDriver->execSQL($sql);
    }
//==============================================================================
// reset columns,raw_columns type
	private function set_driver_columns() {
		$columns = [];
		foreach($this->Schema as $key => $field) {
			list($ftype,$not_null) = $field;
			$columns[$key] = strtolower($ftype);
		}
		$this->dbDriver->columns = $columns;
		$this->dbDriver->raw_columns = $columns;
    }
//==============================================================================
// Execute Create TABLE,VIEW, and INTIAL DATA
//	before check Dependent Table
public function execute($cmd) {
	$exec_types = [
		'viewtest' =>	-1,	// view test-mode
		'test' =>	0,		// create test-mode
		'new' =>	1,		// re-create table & view
		'renew' =>	1,		// 'new' alias
		'view' =>	2,		// re-create view only
		'csv' =>	3,		// self model CSV import 
		'self' =>	4,		// re-create self VIEW only
	];
	if(array_key_exists($cmd,$exec_types)) {
		$exeType  = $exec_types[$cmd];
		$exec = ($exeType > 0);
	} else {
		echo "BAD Command({$cmd})\n";
		return;
	}
	if($exeType === 3) {
		$this->createTableSet($exec,TRUE);
		return;
	}
	$setupLinks = array_reverse($this->DependView([]));	// associate array topdown seq change to bottomup seq.
	$setupLinks[$this->ClassName] = NULL;	// except SELF Class for after process
	if($exeType >= 4) $setupLinks = [];		// Self Only, don't exec Depend class
	debug_dump(['SETUP'=>$setupLinks]);
	// DROP in ViewSet views CASCADE for SQLite3
	$this->dropViewCascade($exec,$setupLinks);
	// execute if TABLE modified command
	if(in_array($exeType, [0,1,3] )) {	// re-create or TEST mode
		$this->createTableSet($exec,FALSE,$setupLinks);
	}
	// RE-CREATE VIEW
	$this->createViewSet($exec,$setupLinks);
}
//==============================================================================
// DependView Module Class
public function DependView($links) {
	$links[$this->ClassName] = $this;
	if(isset(static::$Dependent)) {
		foreach(static::$Dependent as $table) {
			$setup_class = "{$table}Setup";
			if(array_key_exists($setup_class,$links)) {
				debug_dump(['SETUP'=>$this->ClassName,'Conflict'=>$setup_class]);
				continue;
			}
			$dp = new $setup_class($this->data_folder);
			$links = $dp->DependView($links);
		}
	}
	return $links;
}
//==============================================================================
// dropViewCascade: DROP View related table
public function dropViewCascade($exec,$depend=[]) {
	foreach($depend as $setup => $db) {
		if($db !== NULL) {
			$db->dropViewCascade($exec);
		}
	}
	foreach($this->ViewSet as $view) {
		$sql = $this->dbDriver->drop_sql("VIEW",$view);
		$this->doSQL($exec,$sql);
	}
}
//==============================================================================
// createTableSet: create Table, and IMPORT CSV
public function createTableSet($exec,$csv_only,$depend=[]) {
	foreach($depend as $setup => $db) {
		if($db !== NULL) {
			$db->createTableSet($exec,$csv_only);
		}
	}
	if(!$csv_only) {
		$sql = $this->dbDriver->drop_sql("TABLE",$this->MyTable);
		$this->doSQL($exec,$sql);
		// Create Table
		$fset = [];
		foreach($this->Schema as $key => $field) {
			list($ftype,$not_null) = $field;
			$lftype = strtolower($ftype);
			if(array_key_exists($lftype,static::typeXchanger[HANDLER])) $ftype = static::typeXchanger[HANDLER][$lftype];
			$str = "{$key} {$ftype}";
			if($not_null) $str .= " NOT NULL";
			$fset[] = $str;
		}
		$fset[] = "PRIMARY KEY ({$this->Primary})";
		$sql = "CREATE TABLE {$this->MyTable} (\n";
		$sql .= implode(",\n",$fset) . "\n);";
		$this->doSQL($exec,$sql);
		$this->set_driver_columns();
	}
	// IMPORT initial Table DATA, CSV load or TEST mode
	if(isset($this->InitCSV)) {
		$sql = $this->dbDriver->truncate_sql($this->MyTable);
		$this->doSQL($exec,$sql);
		if(is_array($this->InitCSV)) {
			if($exec) {
				$row_columns = array_keys($this->Schema);
				foreach($this->InitCSV as $csv) {
					$data = str_csvget($csv);		// for Windows/UTF-8 trouble avoidance
					$row = array_combine($row_columns,$data);
					$this->dbDriver->insertRecord($row);
				}
			}
		} else {
			echo "Load CSV from '{$this->InitCSV}'\n";
			if($exec) $this->loadCSV($this->InitCSV);
		}
		list($ftype,$not_null) = $this->Schema[$this->Primary];
		if(strtolower($ftype) === 'serial') {
			$this->dbDriver->resetPrimary();
		}
	}
}
//==============================================================================
// CSV file Load (must be UTF-8)
private function loadCSV($filename) {
	$path = "{$this->data_folder}{$filename}";
	if (($handle = fcsvopen($path, "r")) !== FALSE) {
		$row_columns = array_keys($this->Schema);
		while (($data = fcsvget($handle))) {	// for Windows/UTF-8 trouble avoidance
			if(count($data) !== count($row_columns)) {
				debug_die(['CHECK-CSV'=>['FILE'=>$path,'COL'=>$row_columns,'CSV'=>$data]]);
			} else {
				$diff_arr = array_diff($data,$row_columns);
				if(empty($diff_arr)) continue;	// maybe CSV Header line
				// if(count($diff_arr) !== count($row_columns)) {
				// 	debug_die(['CHECK-HEADER'=>['FILE'=>$path,'COL'=>$row_columns,'DAT'=>$data,'DIFF'=>$diff_arr]]);
				// }
			}
			$row = array_combine($row_columns,$data);
			list($primary,$id) = array_first_item($row);
			$this->dbDriver->updateRecord([$primary=>$id],$row);
//			$this->dbDriver->insertRecord($row);
		}
		fclose($handle);
	}
}
//==============================================================================
// createViewSet: create Self View group for other model
public function createViewSet($exec,$depend=[]) {
	foreach($depend as $setup => $db) {
		if($db !== NULL) {
			$db->createViewSet($exec);
		}
	}
	foreach($this->ViewSet as $view) {
		debug_log(DBMSG_NOLOG,["VIEW-DEFS" => $view]);
		$sql = $this->createView($this->MyTable,$view);
		$this->doSQL($exec,$sql);
	}
}
//==============================================================================
// createView: Create View Table
// Schema => [
// 	'hostname'		=> ['text',	false],						// NORMAL Field(TEXT)
// 	'license_id'	=> ['integer',	false ,'licenses.id'],	// Relation Link LicesesSetup Class
// ]
// ViewSchema => [
//		...
//		'license_id'	=> [ 'os_license' => 'license' ],		// license_id = Licenses.id => Licenses.license as 'os_license' 
//		[ 'bind_name.sep' => [ 'entity' , 'location'] ],		// BIND-FIELD on My-Table field
//		[ 'bind_name.sep' => [ 'table1.refer' , 'ltable2.refer'] ],		// OTHER-TABLE BIND-FIELD
// ]
private function createView($table,$view) {
	$alias_sep = function($key) {
		if(substr($key, -1) === '.') $key .= ' ';
		list($alias,$sep) = fix_explode('.',$key,2);
		return [$alias,$sep];
	};
	if(!isset($this->ViewSchema)) return '';
	$view_schema = $this->ViewSchema;
	$sql = "CREATE VIEW {$view} AS SELECT\n";
	$join = $left = [];
	$raw_columns = array_keys($this->Schema);
	foreach($this->Schema as $column => $defs) {
	 	$join[] = "{$table}.\"{$column}\"";
		if(array_key_exists($column,$view_schema)) {
			list($key,$not_null,$rel) = $defs;
			list($tbl,$id) = $this->model_view($rel);
			foreach($view_schema[$column] as $alias => $name) {
				if(is_array($name)) {	// combine
					list($alias,$sep) = $alias_sep($alias);
					$bind = [];
					foreach($name as $fn) $bind[] = "{$tbl}.\"{$fn}\"";
					$join[] = $this->dbDriver->fieldConcat($sep,$bind) . " as {$alias}";
				} else {
					$join[] = "{$tbl}.\"{$name}\" as {$alias}";
				}
			}
			if(!isset($left[$tbl])) $left[$tbl] = "LEFT JOIN {$tbl} on {$table}.\"{$column}\" = {$tbl}.{$id}";
		}
	}
	// relation field Others BIND
	$bind = array_filter($view_schema,function($k) { return is_numeric($k);},ARRAY_FILTER_USE_KEY );
	foreach($bind as $bind_names) {
		foreach($bind_names as $key => $bind_fn) {
			if(is_array($bind_fn)) {	// combine
				list($alias,$sep) = $alias_sep($key);
				$bind = array_map(function($fn) use (&$table) {
					list($tbl,$nm) = (strpos($fn,'.')===false) ? [$table, $fn] : $this->model_view($fn);
					 return "{$tbl}.\"{$nm}\"";
				},$bind_fn);
				$join[] = $this->dbDriver->fieldConcat($sep,$bind) . " as {$alias}";
			}
		}
	}
	$sql .= implode(",\n",$join)."\n";
	$sql .= "FROM {$table}\n";
	$sql .= implode("\n",$left).";";
	return $sql;
}
}
