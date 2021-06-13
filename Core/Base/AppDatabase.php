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
//==============================================================================
// setup table-name and view-table list
	function __construct($path) {
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
        $this->dbDriver = new $driver($this->DataTable);        // connect Database Driver
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
        list($model,$field) = explode('.', "{$db}...");
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
		if($exec) {
			echo "SQL: {$sql}\n";
			$this->dbDriver->execSQL($sql);
		} else  echo "{$sql}\n\n";
    }
//==============================================================================
// Execute Create TABLE,VIEW, and INTIAL DATA
//	before check Dependent Table
public function execute($cmd) {
	if(isset(static::$Dependent)) {
		foreach(static::$Dependent as $table) {
			$setuip_class = "{$table}Setup";
			$db = new $setuip_class($this->data_folder);
			if(empty($db->dbDriver->columns)) $db->execute($exec);
		}
	}
	switch($cmd) {
	case 'test':	$exeType = 0; break;
	case 'renew':	$exeType = 1; break;
	case NULL:		echo "NULL\n";
	case 'view':	$exeType = 2; break;
	case 'csv':		$exeType = 3; break;
	default: echo "BAD Command({$cmd})\n"; return;
	}
	$exec = ($exeType !== 0);
	// DROP in ViewSet views
	foreach($this->ViewSet as $view) {
		$sql = $this->dbDriver->drop_sql("VIEW",$view);
		$this->doSQL($exec,$sql);
	}
	if(in_array($exeType, [0,1] )) {	// re-create or TEST mode
		$sql = $this->dbDriver->drop_sql("TABLE",$this->MyTable);
		$this->doSQL($exec,$sql);
		// Create Table
		$fset = [];
		foreach($this->Schema as $key => $field) {
			list($ftype,$not_null) = $field;
			$str = "{$key} {$ftype}";
			if($not_null) $str .= " NOT NULL";
			$fset[] = $str;
		}
		$fset[] = "PRIMARY KEY ({$this->Primary})";
		$sql = "CREATE TABLE {$this->MyTable} (\n";
		$sql .= implode($fset,",\n") . "\n);";
		$this->doSQL($exec,$sql);
	}
	// IMPORT initial Table DATA, CSV load or TEST mode
	if(isset($this->InitCSV) && in_array($exeType, [0,1,3] )) {
		debug_log(DBMSG_NOLOG,["INITIAL DATA" => $this->InitCSV]);
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
	}
	if(in_array($exeType, [0,1,2] )) {	// View or TEST mode
		debug_log(DBMSG_NOLOG,["VIEW-DEFS" => $this->ViewSet]);
		foreach($this->ViewSet as $view) {
			$sql = $this->createSQL($this->MyTable,$view);
			$this->doSQL($exec,$sql);
		}
	}
}
//==============================================================================
// CSV file Load (must be UTF-8)
private function loadCSV($filename) {
	$path = "{$this->data_folder}{$filename}";
	if (($handle = fopen($path, "r")) !== FALSE) {
		$row_columns = array_keys($this->Schema);
		while (($data = fcsvget($handle))) {	// for Windows/UTF-8 trouble avoidance
			$row = array_combine($row_columns,$data);
		debug_log(DBMSG_NOLOG,["CSV DATA" => [$row_columns,$data, $row] ]);
			$this->dbDriver->insertRecord($row);
		}
		fclose($handle);
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
private function createSQL($table,$view) {
	$alias_sep = function($key) {
		if(substr($key, -1) === '.') $key .= ' ';
		list($alias,$sep) = explode('.',"{$key}.");
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
	$sql .= implode($join,",\n")."\n";
	$sql .= "FROM {$table}\n";
	$sql .= implode($left,"\n").";";
	return $sql;
}
}
