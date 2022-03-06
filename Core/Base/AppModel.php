<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  AppModel:    Database IN/OUT abstraction Class
 *              Handle of SQLite3, PostgreSQL, MariaDB
 */
require_once('Core/Handler/DatabaseHandler.php');

//==============================================================================
class AppModel extends AppObject {
    static $DatabaseSchema = [
        'Handler' => 'Null',
        'DataTable' => '',
        'Primary' => '',
        'LoginID' => '',
        'Schema' => [],
        'PostRenames' => [],
        'Selection' => [],
		'Lang_Alternate' => FALSE,
    ];
    protected $dbDriver;            // Database Driver
    protected $fields;              // Record-Data all fields value
    public $pagesize = 0;           // get record count per PAGE
    public $page_num = 0;           // get Page Number
    public $record_max = 0;         // Total records count
    public $row_number = 0;         // near record in target record row
    public $AliasMode = TRUE;       // Language Alias Enable

    public $RecData = NULL;          // ROW record data (no JOIN)
    public $Select = NULL;           // Select List for relation table field
    public $Records = NULL;          // get records lists (with JOIN field)
    public $OnGetRecord = NULL;      // for feature FUNCTION
    public $HeaderSchema = [];       // Display Header List [ field_name => [disp_name, align, sort_flag ]
    public $DateFormat;              // Date format for Database
    public $TimeFormat;              // Time format for Database
    public $DateTimeFormat;          // TimeStamp format for Database
    public $SortDefault = SORTBY_ASCEND;    // findRecord Default Sort Sequence
    protected $FieldSchema = [];       // Pickup Record fields columns [ref_name, org_name]
    protected $Relations = [];         // Table Relation
    protected $SelectionDef = [];      // Selection JOIN list
	private $virtual_columns = [];
//==============================================================================
//	Constructor: Owner
//==============================================================================
	function __construct($owner) {
	    parent::__construct($owner);                    // call parent constructor
        $this->setProperty(self::$DatabaseSchema);      // Set Default Database Schema Property
        $this->setProperty(static::$DatabaseSchema);    // Set Instance Property from Database Schema Array
		if(empty($this->Schema) && $this->Handler !== 'Null') {
			debug_stderr(["BAD Schema"=>static::$DatabaseSchema,"CLASS"=>$this->ClassName]);
		}
		if(empty($this->Primary)) $this->Primary = 'id';	// default primary name
        if(isset($this->ModelTables)) {                 // Multi-Language Tabele exists
            $db_key = (array_key_exists(LangUI::$LocaleName,$this->ModelTables)) ? LangUI::$LocaleName : '*';
            $this->DataTable = $this->ModelTables[$db_key]; // DataTable SWITCH
        }
        $this->fields = [];
        $driver = $this->Handler . 'Handler';
        $this->dbDriver = new $driver($this->DataTable,$this->Primary); // connect Database Driver
        $this->DateFormat = $this->dbDriver->DateStyle;         // Date format from DB-Driver
        $this->TimeFormat = $this->dbDriver->TimeStyle;         // Time format from DB-Driver
        $this->DateTimeFormat = "{$this->DateFormat} {$this->TimeFormat}"; // DateTime
		$this->dbDriver->fieldAlias->lang_alternate = $this->Lang_Alternate;
		if(method_exists($this,'virtual_field')) {
			$this->dbDriver->register_method($this,'virtual_field');
		}
	}
//==============================================================================
// Initializ Class Property
    protected function class_initialize() {
        $this->ResetSchema();                   // Schema Initialize
        parent::class_initialize();
    }
//==============================================================================
// column exist check
public function is_exist_column($name) {
	return array_key_exists($name,$this->dbDriver->columns);
}
//==============================================================================
// get exist columns list
public function get_exist_columns($names) {
	return array_filter($names,function($v) { return array_key_exists($v,$this->dbDriver->columns);});
}
//==============================================================================
// Switch Schema Language
public function ResetSchema() {
    $this->SchemaAnalyzer();
    $this->RelationSetup();
    $this->SelectionSetup();
	$this->virtual_columns = array_values(array_unique(array_merge(array_keys($this->FieldSchema),array_keys($this->HeaderSchema))));
    debug_log(DBMSG_CLI|DBMSG_MODEL,[             // DEBUG LOG information
        $this->ModuleName => [
            "Header"    => $this->HeaderSchema,
			'Virtual'	=> $this->virtual_columns,
			'Handler'	=> $this->Handler,
            "Field"     => $this->FieldSchema, 
            "Relations"     => $this->dbDriver->relations,
            "Locale-Bind"   => $this->dbDriver->fieldAlias->GetAlias(),
//            "Select-Defs"   => $this->SelectionDef,
        ]
    ]);
}
//==============================================================================
// Schema Define Analyzer
    protected function SchemaAnalyzer() {
        $header = $relation = $locale = $bind = $field = [];
        foreach($this->Schema as $key => $defs) {
            $ref_key = $key;
            list($disp_name,$disp_flag,$width,$relations,$binds) = array_alternative($defs,5);
			if($disp_flag < 0) continue;
            list($accept_lang,$disp_align,$disp_head) = [intdiv($disp_flag,100),intdiv($disp_flag%100,10), $disp_flag%10];
            if(!empty($relations)) {
                $relation[$key] = $relations;
            }
            if(!empty($binds)) {
                $bind[$ref_key] = $binds;
                $key = NULL;
            }
            $field[$ref_key] = $key;
            if($disp_head !== 0) {
                if(empty($disp_name)) $disp_name = $ref_key;
                else if($disp_name[0] === '.') $disp_name = $this->_(".Schema{$disp_name}");
                $header[$ref_key] = [$disp_name,$disp_align,$disp_head,$width];
            }
            if($accept_lang) {
                $ref_name = "{$ref_key}_" . LangUI::$LocaleName;
                if(array_key_exists($ref_name,$this->dbDriver->columns)) {
                    $locale[$ref_key] = $ref_name;
                }
            }
        }
        $this->HeaderSchema = $header;
        $this->FieldSchema = $field;
        $this->Relations = $relation;
        $this->dbDriver->fieldAlias->SetupAlias($locale,$bind);
    }
//==============================================================================
// extract DataTable or Alternate DataView
    private function model_view($db) {
		list($model,$field,$refer) = fix_explode('.',$db,3);
        if(preg_match('/(\w+)(?:\[(\d+)\])/',$model,$m)===1) {
            $model = $m[1];
            $table = (is_array($this->$model->DataView))
                        ? $this->$model->DataView[$m[2]]            // View Element Index
                        : $this->$model->dbDriver->table;                 // illegal define
        } else $table = $this->$model->dbDriver->table;
        return [$model,$table,$field,$refer];
    }
//==============================================================================
// nested relation model
// CALL by Other Model Relation Analyzer
    protected function JoinDefinition($table,$field,$rel_key) {
        if(is_array($this->dbDriver->relations)) {
            foreach($this->dbDriver->relations as $key => $refs) {
                if(array_key_exists($field,$refs)) {
                    $lnk = explode('.',$refs[$field]);
                    return [
                        $table,         // rel_table
						$rel_key,		//   rel_key
                        $key,           //   rel_filed
                        $lnk[0],        // sub_table
                        $lnk[1],        //   rel_id
                        $lnk[2],        //   ref_name
                    ];
                }
            }
        }
        return $field;
    }
//==============================================================================
// Table Relation setup DBMSG_MODEL
protected function RelationSetup() {
        $new_Relations = [];
        foreach($this->Relations as $key => $rel) {
            $base_name = id_relation_name($key);
            $rel_array = is_array($rel);
            if($rel_array) {            // multi-column refer
                list($db,$ref_list) = array_first_item($rel);
                if(is_numeric($db)) {       // maybe 'Model.id.Field'
                    if(!is_scalar($ref_list)) continue;
					list($d,$f,$r) = fix_explode('.',$ref_list,3);
                    $db = "{$d}.{$f}";
                    $ref_list = $r;
                }
                list($model,$table,$field) = $this->model_view($db);
                $link = "{$table}.{$field}";
                if(is_scalar($ref_list)) $ref_list = [$ref_list];    // force array
				$rel_def = array_key_first($rel);
            } else {
                list($model,$table,$field,$refer) = $this->model_view($rel);
                $link = "{$table}.{$field}";
                $ref_list = [ $refer ];
				$rel_def = $rel;
            }
            $sub_rel = [];
			list($rel_tbl,$primary) = fix_explode('.',$rel_def,2);
            foreach($ref_list as $refer) {
                $sub_ref = $this->$model->JoinDefinition($table,$refer,$primary);
                $alias_name = "{$base_name}_".id_relation_name($refer);
                $this->FieldSchema[$alias_name] = NULL;   // $key; import MUST!
                // locale if exist in relation-db
                // $ref_name = "{$refer}_" . LangUI::$LocaleName;
                // if(!$this->$model->dbDriver->fieldAlias->exists_locale($ref_name)) $ref_name = $refer;
                $ref_name = $this->$model->dbDriver->fieldAlias->get_lang_alias($refer);
                $sub_rel[$alias_name] = (is_array($sub_ref)) ? $sub_ref : "{$link}.{$ref_name}";
            }
            $new_Relations[$key] =  $sub_rel;
        }
        $this->dbDriver->setupRelations($new_Relations);
    }
//==============================================================================
// Selection Table Relation setup
protected function SelectionSetup() {
        $new_Selection = [];
        $separate_rel_cond = function($defs) {
            $rel = $cond = [];
            foreach($defs as $key => $val) {
                if(is_int($key)) {
                    if(is_array($val)) {
		                list($kk,$vv) = array_first_item($val);
						if(is_int($kk)) $rel = $val;
						else $cond = $val;
					} else $rel = $val;
                } else {
                    $rel[$key] = $val;
                }
            }
            return [$rel,$cond];
        };
        foreach($this->Selection as $key_name => $seldef) {
            $lnk = [];
            if(is_scalar($seldef)) {
                $lnk[0] = $seldef;
                $cond = [];
            } else {
                list($target,$cond) = $separate_rel_cond($seldef);
                list($model,$ref_list) = array_first_item($target);
                if($model === 0) {
                    $lnk = $target;
                } else {
                    list($model,$table,$field) = $this->model_view($model);
                    if(is_scalar($ref_list)) {
						if($ref_list[0]==='.') {
							$ref_list = mb_substr($ref_list,1);
							if(mb_strpos($ref_list,'.') === false) {
								$key_name = "{$key_name}_{$ref_list}";
							}
						}
						if(empty($field)) {
							if(substr_count($ref_list, '.') !== 1) $ref_list = "{$this->Primary}.{$ref_list}";
						} else $ref_list = "{$field}.{$ref_list}";
                        $lnk = [ $model => $ref_list];
                    } else {
                        if(!empty($field)) array_unshift($ref_list,$field);
                        $lnk[$model] = $ref_list;
                    }
                }
            }
            $new_Selection[$key_name] =  [$lnk,$cond];
        }
        $this->SelectionDef = $new_Selection;
    }
//==============================================================================
// Paging Parameter Setup
public function SetPage($pagesize,$pagenum) {
    $this->pagesize = $pagesize;
    $this->page_num = ($pagenum <= 0) ? 1 : $pagenum;
    $this->dbDriver->SetPaging($this->pagesize,$this->page_num);
}
//==============================================================================
// find Select key by value
public function get_selectvalue_of_key($sel_name,$value) {
	foreach($this->Select[$sel_name] as $key => $val) {
		if($value == $val) return $key;
	}
	return "";
}
//==============================================================================
// Make Empty Record
public function makeEmptyRecord() {
	$col_keys = array_keys($this->dbDriver->columns);
	$row = array_combine($col_keys,array_fill(0,count($col_keys),NULL));
    return $row;
}
//==============================================================================
// Get ROW-RECORD by Primarykey
// Result:   $this->fields in Column Data
public function getRecordByKey($id) {
    return $this->getRecordBy($this->Primary,$id);
}
//==============================================================================
// Get ROW-RECORD by Field Name
// Result:   $this->fields in Column Data
public function getRecordBy($key,$value) {
	$row = $this->dbDriver->doQueryBy($key,$value);
	$this->fields = ($row === FALSE) ? [] : $row;
    return $this->fields;
}
//==============================================================================
// Get Primary Value by Field Name
// Result:   Primary Value or FALSE
public function getPrimaryOf($key,$value,$default=false) {
	if(empty($value)) return 0;		// not-allow NULL value
	$row = $this->dbDriver->doQueryBy($key,$value);
	if($row === false) return $default;
    return $row[$this->Primary];
}
//==============================================================================
// Get Record Data by primary-key,and JOIN data by $join is TRUE.
// Result:   $this->RecData in Column Data
public function GetRecord($num,$join=FALSE,$values=FALSE) {
    if($join) {
        if($num === '') $this->fields = array();
        else $this->fields = $this->dbDriver->getRecordValue([$this->Primary => $num],TRUE);
    } else $this->getRecordBy($this->Primary,$num);
    $this->RecData= $this->fields;
    if($values) $this->GetValueList();
}
//==============================================================================
// Selection define condition change.
public function ChangeSelectCondition($name,$cond) {
	$this->SelectionDef[$name][1] = $cond;
}
//==============================================================================
//   Get Relation Table fields data list.
//	a. key-name => [ Model.id => [ name, pid],	[ cond ] ]		for ChainSelect() [ id,name,pid ]
//	b. key-name => [ [ name, pid],				[ cond ] ]		for ChainSelect() by SELF [ Primary,name,pid ]
//	c. key-name => [ Model.id => name,			[ cond ] ]		for Select() in Model [nam] = id
//	d. key-name => [ Model. => number.title,	[ cond ] ]		for Select() in Model [title] = number|Primary
//	e. key-name => [ id.title,					[ cond ] ]		for Select() by SELF [title] = number|Primary
//	f. key-name => [ Model. => [ Method => argument],[ cond ] ]		Chain() ir Select() value,depend on Model Method returned.
public function LoadSelection($key_names, $sort_val = false,$opt_cond=[]) {
	$selections = (is_array($key_names)) ? $key_names : [$key_names];
    foreach($selections as $key_name) {
		list($target,$cond) = $this->SelectionDef[$key_name];
		$cond = array_override($cond,$opt_cond);	// additional cond
		list($model,$ref_list) = array_first_item($target);
		if(is_int($model)) {        // self list
			// case b., e.
			$this->Select[$key_name] = $this->SelectFinder(is_array($target),$target,$cond);
		} else if(is_array($ref_list)) {
			// case a., f.
			list($method,$args) = array_first_item($ref_list);
			if(is_numeric($method)) {
				// case a.
				$this->Select[$key_name] = $this->$model->SelectFinder(true,$ref_list,$cond);
			} else if(method_exists($this->$model,$method)) {	// case f.
				$method_val = $this->$model->$method($args,$cond);
//				ksort($method_val,SORT_FLAG_CASE | SORT_STRING );
				$this->Select[$key_name] = $method_val;
			}
		} else {
			// case c., d.
			$ref_list = array_filter(explode('.', $ref_list), "strlen" );
			$this->Select[$key_name] = $this->$model->SelectFinder(false,$ref_list,$cond);
		}
		if($sort_val !== false) {
			switch($sort_val) {
			case true:
			case SORTBY_ASCEND:	asort($this->Select[$key_name]); break;
			case SORTBY_DESCEND:arsort($this->Select[$key_name]); break;
			default: debug_log(8,['SORT-NONE'=>$this->Select[$key_name]]);
			}
		}
	}
	debug_log(8,['Select'=>$this->Select]);
}
//==============================================================================
//   Get Relation Table fields data list.
// Result:   $this->Select (Relations)
public function GetValueList() {
    $this->Select= [];
	$keyset = array_keys($this->SelectionDef);
	$this->LoadSelection($keyset);
}
//==============================================================================
//   Get Field Value List
// Result:  Select array
public function getFieldValues($id,$field, $cond = NULL) {
    $select = $this->dbDriver->getValueLists(NULL,$field,$id,$cond);
	return $select;
}
//==============================================================================
// Get Record Field(s) by field-key without JOIN fields.
// multi fields is separate by SPC(DOT), ARRAY (COMMA)
// Result:   field-data
public function getRecordField($key,$value,$field) {
    $this->getRecordBy($key,$value);
	$keys = explode(',',$field);
	$vals = array_map(function($fn) {
			$dot = explode('.',$fn);
			return implode(' ',array_keys_value($this->fields,$dot));
		},$keys);
	if(count($vals)===1) return $vals[0];
	else return array_combine($keys,$vals);
}
//==============================================================================
// Get Record Field(s) by Primary value without JOIN fields.
// multi fields is separate by SPC(DOT), ARRAY (COMMA)
// Result:   field-data
public function getRecordByField($primary,$field) {
	if(empty($primary)) $primary = 0;
	return $this->getRecordField($this->Primary,$primary,$field);
}
//==============================================================================
// 条件に一致するレコード数を検索する
public function getCount($cond) {
    return $this->dbDriver->getRecordCount($cond);
}
//==============================================================================
// Normalized Field-Filter
	private function normalize_filter($filter) {
		if(empty($filter)) $filter = $this->virtual_columns;
//		if(empty($filter)) $filter = array_keys($this->dbDriver->columns);
		else if(is_scalar($filter)) {
			$filter = (strpos($filter,'.')!==FALSE) ? explode('.',$filter): [$filter];
		}
		return $filter;
	}
//==============================================================================
// Get Selection pair
// Result:   Select array
public function SelectFinder($chain, $filter, $cond) {
	$filter = $this->normalize_filter($filter);
	list($id,$fn,$pid) = array_alternative($filter,3);
	$data = [];
    $this->dbDriver->findRecord($cond,TRUE);
    while (($fields = $this->dbDriver->fetchDB())) {
		if($chain) {
			if(empty($fn)) {
				$fn = $id;
				$id = $this->Primary;
			}
			$pval = (empty($pid)) ? 0 : $fields[$pid];
			$data[] = [ $fields[$id], $fields[$fn], $pval];
		} else {
			$key = (empty($fn)) ? $fields[$id] : $fields[$fn];
			$pval = $fields[$id];
			$data[$key] = $pval;
		}
	}
	if(!$chain) ksort($data,SORT_FLAG_CASE|SORT_STRING);
    xdebug_log(DBMSG_NONE, [
        "Filter" => $filter,
        "COND" => $cond,
        "RECORDS" => $data,
    ]);
	return $data;
}
//==============================================================================
// Get Record List by FIND-CONDITION with JOIN Table.
// Result:   $this->Records  Find-Result List
public function RecordFinder($cond,$filter=NULL,$sort=NULL) {
	$filter = $this->normalize_filter($filter);
    $fields_list = array_filter($this->FieldSchema, function($vv) use (&$filter) {
        return in_array($vv,$filter,true) || ($vv === NULL);
    });
    $fields_list[$this->Primary] = $this->Primary;  // must be include Primary-Key
    $data = array();
    if(empty($sort)) $sort = [ $this->Primary => $this->SortDefault ];
    else if(is_scalar($sort)) {
        $sort = [ $sort => $this->SortDefault ];
    }
    $this->dbDriver->findRecord($cond,TRUE,$sort);
	$this->record_max = $this->dbDriver->recordMax;
    while (($fields = $this->dbDriver->fetchDB())) {
        unset($record);
        foreach($fields_list as $key => $val) {
            $record[$key] = $fields[$key];
            if($val !== NULL && $key !== $val) $record[$val] = $fields[$val];
        }
        $data[] = $record;
    }
    $this->Records = $data;
}
//==============================================================================
// Get Raw Record List by FIND-CONDITION from RAW-TABLE.
// Result:   $this->Records  Find-Result List
public function RawRecordFinder($cond,$filter=NULL,$sort=NULL) {
    if(empty($filter)) $fields_list = $this->dbDriver->raw_columns;
    else {
        $fields_list = array_filter($this->dbDriver->raw_columns,function($v) { return in_array($v,$filter);},ARRAY_FILTER_USE_KEY);
        $fields_list[$this->Primary] = $this->Primary;  // must be include Primary-Key
    }
    $data = array();
    if(empty($sort)) $sort = [ $this->Primary => $this->SortDefault ];
    else if(is_scalar($sort)) {
        $sort = [ $sort => $this->SortDefault ];
    }
    $this->dbDriver->findRecord($cond,FALSE,$sort,true);
	$this->record_max = $this->dbDriver->recordMax;
    while(($fields = $this->dbDriver->fetch_array())) {
        unset($record);
        foreach($fields_list as $key => $val) {
            $record[$key] = $fields[$key];
        }
        $data[] = $record;
    }
    $this->Records = $data;
}
//==============================================================================
// Get First Record by condition w/o JOIN!
// Result:   return field array or FALSE
public function firstRecord($cond=[],$filter=NULL,$sort=NULL) {
	$filter = $this->normalize_filter($filter);
    $fields_list = array_combine($filter,$filter);
    $fields_list[$this->Primary] = $this->Primary;  // must be include Primary-Key
    $fields = $this->dbDriver->firstRecord($cond,FALSE,$sort);
    return ($fields === FALSE) ? FALSE : array_filter($fields, function($val,$key) use (&$filter) {return in_array($key,$filter,true);},ARRAY_FILTER_USE_BOTH);
}
//==============================================================================
// Get Record with Prev/Next Record List by FIND-CONDITION without JOIN!.
// Result:   $this->NearData  Find-Result List by $filter field only
//           $this->RecData   $primary Record Data by all fields
public function NearRecordFinder($primary,$cond,$filter=NULL,$sort=NULL) {
	$filter = $this->normalize_filter($filter);
    $fields_list = [$this->Primary => $this->Primary];  // must be include Primary-Key
    foreach($filter as $key) {
        if(isset($this->FieldSchema[$key])) $fields_list[$key] = $this->FieldSchema[$key];
    }
    if(empty($sort)) $sort = [ $this->Primary => $this->SortDefault ];
    else if(is_scalar($sort)) $sort = [ $sort => $this->SortDefault ];
    $this->dbDriver->findRecord($cond,TRUE,$sort);
    $this->record_max = $this->dbDriver->recordMax;
    $r_prev = $r_next = NULL;
    $prev = true;
    $row_num = 0;
    while (($fields = $this->dbDriver->fetchDB())) {
        $data = [];
        foreach($fields_list as $key => $val) $data[$key] = $fields[$key];
        $row_id = $fields[$this->Primary];
        if( $row_id === $primary) {
            $prev = false;
            $this->RecData = $fields;   // all-fields in 
            ++$row_num;
        } else if($prev) {
            $r_prev = $data;
            ++$row_num;
        } else {
            $r_next = $data;
            break;
        }
    }
    $this->row_number = $row_num;
    $this->NearData = [$r_prev, $r_next ];
}
//==============================================================================
// for Access Log Aggregate method
//	pickup FIELD set by GROUP BY grouping columns.
//	and except NOT NULL COLUMN and RECORD count Limited
protected function tableAggregate($cond,$groups,$calc = NULL,$filter=NULL,$sortby = [],$limit=0) {
    $data = array();
	$sql = $this->dbDriver->getGroupCalcList($cond,$groups,$calc,$sortby,$limit);
    while (($fields = $this->dbDriver->fetchDB())) {
		$data[] = $fields;
    }
    $this->Records = $data;
	$this->Headers = $this->dbDriver->active_column;
	if(!empty($filter)) {
		$this->Headers = array_filter($filter,function($vv) { return in_array($vv,$this->Headers,true);});
	}
}
//==============================================================================
// Delete Record(Primary-key)
public function DeleteRecord($num) {
    $this->dbDriver->deleteRecord([$this->Primary => $num]);
}
//==============================================================================
// DELETE Records by FIND-CONDITION
public function MultiDeleteRecord($cond) {
    $this->dbDriver->deleteRecord($cond);
}
//==============================================================================
// TRUNCATE TABLE
public function doTruncate() {
    $this->dbDriver->doTruncate();
}
//==============================================================================
// VALIDATION of write data
public function is_valid(&$row) {
    return TRUE;
}
//==============================================================================
// field-alias, field-bind processing.
    private function field_lang_alias($row) {
        $this->fields = array();
        foreach($row as $key => $val) {
            $alias = ($this->AliasMode) ? $this->dbDriver->fieldAlias->get_lang_alias($key) :$key;
            $this->fields[$alias] = $val;
        }
    }
//==============================================================================
// POST-name convert to Real field name by PostRenames[]
public function get_post_field($key) {
    return (array_key_exists($key,$this->PostRenames))
            ? $this->PostRenames[$key]
            : $key;
}
//==============================================================================
// pickup on exist edit table database field
    private function field_pickup($row) {
        $data = array();
		// last-update field update, if exists
        if(!array_key_exists('last_update',$row)) {
			$row['last_update'] = date($this->DateTimeFormat);
		}
        foreach($row as $key => $val) {
            $xkey = $this->get_post_field($key);
            if(array_key_exists($xkey,$this->dbDriver->raw_columns)) {
                $data[$xkey] = $val;
            }
        }
        unset($data[$this->Primary]);
		if($this->is_valid($data)) {
			return $data;
		} else {
			debug_log(DBMSG_MODEL,['VALID-ERROR'=>$data]);
	        return FALSE;
		}
    }
//==============================================================================
// Copy record
public function CopyRecord($id,$replaces=[]) {
    $row = $this->getRecordBy($this->Primary,$id);
	foreach($replaces as $fn => $val) {
		if(array_key_exists($fn,$row)) $row[$fn] = $val;
	}
	if($data = $this->field_pickup($row)) {
	    $row = $this->dbDriver->insertRecord($data);
		$this->RecData = ($row) ? $row : [];
	}
	return empty($this->RecData) ? false : $this->RecData[$this->Primary];
}
//==============================================================================
// Add NEW record
public function AddRecord($row) {
	if($data = $this->field_pickup($row)) {
        $this->field_lang_alias($data);
	    $row = $this->dbDriver->insertRecord($this->fields);
		$this->RecData = ($row) ? $row : [];
    }
	return empty($this->RecData) ? false : $this->RecData[$this->Primary];
}
//==============================================================================
// UPDATE Record
public function UpdateRecord($num,$row) {
	if($data = $this->field_pickup($row)) {
        $this->field_lang_alias($data);
        $row = $this->dbDriver->updateRecord([$this->Primary => $num],$this->fields);
		$this->RecData = ($row) ? $row : [];
    }
	return empty($this->RecData) ? false : $this->RecData[$this->Primary];
}
//==============================================================================
// CSV file Load (must be UTF-8)
public function UploadCSV($path) {
    if(file_exists($path)) {
		if (($handle = fcsvopen($path, "r")) !== FALSE) {
			$raw_columns = array_keys($this->dbDriver->raw_columns);
			while (($data = fcsvget($handle))) {	// for Windows/UTF-8 trouble avoidance
				if(count($data) !== count($raw_columns)) {
					fclose($handle);
					return false;
				} else {
					$diff_arr = array_diff($data,$raw_columns);
					if(empty($diff_arr)) continue;	// maybe CSV Header line
				}
				$row = array_combine($raw_columns,$data);
				list($primary,$id) = array_first_item($row);
				$this->dbDriver->updateRecord([$primary=>$id],$row);
			}
			fclose($handle);
			return true;
		}
    }
	return false;
}
//==============================================================================
// CSV file download data
public function RecordsCSV($map=true,$limit=0) {
	$records = $this->Records;
	if(empty($records)) return false;
	$col = reset($records);
	// create header by Model Schema
	$keys = array_keys($col);
	if($map) {
		$keys = array_map(function($v) {
			if(isset($this->Model->Schema[$v])) {
				list($disp_name,$disp_flag) = $this->Model->Schema[$v];
                if(empty($disp_name)) return $v;
                else if($disp_name[0] === '.') return $this->_(".Schema{$disp_name}");
				return $disp_name;
			} else return $v;
		},$keys);
	}
	$csv = [ implode(',',$keys) ];	// column header
	foreach($records as $columns) {
		$row = array_map(function($v) use(&$limit) {
			if($limit !== 0 && mb_strlen($v) > $limit) $v = mb_substr($v,0,$limit)."...";
			if(mb_strpos($v,'"') !== false) $v = str_replace('"','""',$v);
			foreach([' ',',',"\f","\t","\r","\n"] as $esc) {
				if(strpos($v,$esc)!==false) return "\"{$v}\"";
			}
			return $v;
		},$columns);
		$csv[] = implode(',',$row);
	}
	return $csv;
}

}
