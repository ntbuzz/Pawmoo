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
	public $ro_columns;		// read-only column => view column
	public $locale_columns;
	public $bind_columns;
	public $ViewSchema;
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
        $this->dbDriver = new $driver($this->DataTable,$this->Primary);        // connect Database Driver
    }
//==============================================================================
// Switch Schema Language
public function SchemaSetup() {
    $this->SchemaAnalyzer();
	debug_dump([             // DEBUG LOG information
		'SCHEMA' => $this->Schema,
		'VIEW-SCHEMA' => $this->ViewSchema,
		'TABLE' => $this->TableSchema,
		'FIELD' => $this->FieldSchema,
	]);
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
//	Constructor: Owner
public function SchemaAnalyzer() {
	$this->FieldSchema=$this->ViewSchema = [];
	$this->TableSchema = [];
	foreach($this->Schema as $key => $defs) {
		list($dtype,$flag,$width,$rel) = array_extract($defs,4);
		$dtype = strtolower($dtype);
		list($link,$def) = array_first_item($rel);
		if(!is_scalar($defs)) {
			if(!is_int($link)) $this->TableSchema[$key] =  [$dtype,$flag,$width];
			if((!is_int($link) || $width !== NULL)) {
				$this->FieldSchema[$key] = [$dtype,$flag,$width];
			}
		}
		if(is_array($rel)) {
			if(is_int($link)) {		// bind or multi-bind
				if(is_array($def)) {
					$this->ViewSchema[] = [ $key => $def];
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
				$this->ViewSchema[$link] = $view;
			}
		}
	}
}
//==============================================================================
//	Constructor: Owner
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

}