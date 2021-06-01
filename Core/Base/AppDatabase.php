<?php
//==============================================================================
//
class AppDatabase {
//    protected $dbDriver;            // Database Driver
	protected $varList = [];
	public 	$raw_columns;   // target real column

	function __construct() {
		 $class = get_called_class();
		echo "CLASS: {$class}\n";
		foreach (get_class_vars($class) as $name => $default)
			if (isset($class::$$name))
				$this->varList[$name] = $default;
	}
//==============================================================================
//	setup objerct property
    protected function setProperty($props) {
        foreach($props as $key => $val) {
            $this->$key = $val;
        }
	debug_log(DBMSG_DUMP,['PROP'=>$this->Schema]);
    }
//==============================================================================
// Initializ Class Property
public function CreateMyView($schema) {
	$this->setProperty($schema);
	$driver = $this->Handler . 'Handler';
//    $this->dbDriver = new $driver($this->DataTable);        // connect Database Driver
	$viewset = (isset($this->DataView)) ? ((is_array($this->DataView)) ? $this->DataView : [$this->DataView]) : [];
	if(is_array($this->DataTable)) {
		list($table,$view) = $this->DataTable;
		if($table !== $view) array_unshift($viewset, $view);
	} else $table = $this->DataTable;
	foreach($viewset as $view) {
//		echo $this->dbDriver->createView($table,$view,$this->ViewSchema,true)."\n\n";
		echo $this->createSQL($table,$view)."\n\n";
	} 
}
//==============================================================================
//
   public function execute() {
		foreach(array_keys($this->varList) as $vname) {
			echo "TYPE({$vname}) = " . gettype(static::$$vname) ."\n";
			$this->CreateMyView(static::$$vname);
		}
    }
//==============================================================================
// createView: Create View Table
// Schema => [
//	 'name_list_id'	=> [
// 		'host_name'		=> 'name',
//	 	'bind_name'		=> [ 'source' , 'name'],	// BIND-FIELD
//	 ],
//	...
// 	'license_id'	=> ['integer',	false ,'licenses.id'],
// ]
// ViewSchema => [
//		...
//		'license_id'	=> [ 'os_license' => 'license' ],
//		[ 'bind_name.sep' => [ 'entity' , 'location'] ],		// SELF BIND-FIELD
//		[ 'bind_name.sep' => [ 'table1.refer' , 'ltable2.refer'] ],		// OTHER-TABLE BIND-FIELD
// ]
public function createSQL($table,$view) {
	$alias_sep = function($key) {
		if(substr($key, -1) === '.') $key .= ' ';
		list($alias,$sep) = explode('.',"{$key}.");
		$sep = (empty($sep)) ? '||' : "||\"{$sep}\"||";
		return [$alias,$sep];
	};
	$view_schema = $this->ViewSchema;
	$sql = "DROP VIEW IF EXISTS {$view};\n";
	$sql .= "CREATE VIEW {$view} AS SELECT\n";
	$join = $left = [];
	$raw_columns = array_keys($this->Schema);
	foreach($this->Schema as $column => $defs) {
	 	$join[] = "{$table}.\"{$column}\"";
		if(array_key_exists($column,$view_schema)) {
			list($key,$not_null,$rel) = $defs;
			list($tbl,$id) = explode('.',$rel);
			foreach($view_schema[$column] as $alias => $name) {
				if(is_array($name)) {	// combine
					list($alias,$sep) = $alias_sep($alias);
					$bind = [];
					foreach($name as $fn) $bind[] = "{$tbl}.\"{$fn}\"";
					$join[] = implode($bind,$sep) . " as {$alias}";
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
					list($tbl,$nm) = (strpos($fn,'.')===false) ? [$table, $fn] : explode('.',$fn);
					 return "{$tbl}.\"{$nm}\"";
				},$bind_fn);
				$join[] = implode($bind,$sep) . " as {$alias}";
			}
		}
	}
	$sql .= implode($join,",\n")."\n";
	$sql .= "FROM {$table}\n";
	$sql .= implode($left,"\n").";";
	return $sql;
}
}
