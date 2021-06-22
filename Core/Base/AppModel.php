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
    public $SortDefault = SORTBY_ASCEND;    // findRecord Default Sort Sequence
    private $FieldSchema = [];       // Pickup Record fields columns [ref_name, org_name]
    private $Relations = [];         // Table Relation
    private $SelectionDef = [];      // Selection JOIN list
//==============================================================================
//	Constructor: Owner
//==============================================================================
	function __construct($owner) {
	    parent::__construct($owner);                    // call parent constructor
        $this->setProperty(self::$DatabaseSchema);      // Set Default Database Schema Property
        $this->setProperty(static::$DatabaseSchema);    // Set Instance Property from Database Schema Array
		if(empty($this->Schema)) {
			debug_log(DBMSG_DIE,["BAD Schema"=>static::$DatabaseSchema,"CLASS"=>$this->ClassName]);
		}
		if(empty($this->Primary)) $this->Primary = 'id';	// default primary name
        if(isset($this->ModelTables)) {                 // Multi-Language Tabele exists
            $db_key = (array_key_exists(LangUI::$LocaleName,$this->ModelTables)) ? LangUI::$LocaleName : '*';
            $this->DataTable = $this->ModelTables[$db_key]; // DataTable SWITCH
        }
        $this->fields = [];
        $driver = $this->Handler . 'Handler';
        $this->dbDriver = new $driver($this->DataTable);        // connect Database Driver
        $this->DateFormat = $this->dbDriver->DateStyle;         // Date format from DB-Driver
		$this->dbDriver->fieldAlias->lang_alternate = $this->Lang_Alternate;
	}
//==============================================================================
// Initializ Class Property
    protected function class_initialize() {
        $this->ResetSchema();                   // Schema Initialize
        parent::class_initialize();
    }
//==============================================================================
// Initializ Class Property
/*
public function CreateMyView() {
	if(isset($this->ViewSchema)) {
		$viewset = (isset($this->DataView)) ? ((is_array($this->DataView)) ? $this->DataView : [$this->DataView]) : [];
		if(is_array($this->DataTable)) {
			list($table,$view) = $this->DataTable;
			if($table !== $view) array_unshift($viewset, $view);
		} else $table = $this->DataTable;
		foreach($viewset as $view) {
			echo $this->dbDriver->createView($table,$view,$this->ViewSchema)."\n\n";
		} 
	}
}
*/
//==============================================================================
// column exist check
public function is_exist_column($name) {
	return array_key_exists($name,$this->dbDriver->columns);
}
//==============================================================================
// Switch Schema Language
public function ResetSchema() {
    $this->SchemaAnalyzer();
    $this->RelationSetup();
    $this->SelectionSetup();
    debug_log(DBMSG_CLI|DBMSG_MODEL,[             // DEBUG LOG information
        $this->ModuleName => [
//            "Header"    => $this->HeaderSchema,
            "Field"     => $this->FieldSchema, 
            "Join-Defs"     => $this->dbDriver->relations,
            "Locale-Bind"   => $this->dbDriver->fieldAlias->GetAlias(),
            "Select-Defs"   => $this->SelectionDef,
        ]
    ]);
}
//==============================================================================
// Schema Define Analyzer
    private function SchemaAnalyzer() {
        $header = $relation = $locale = $bind = $field = [];
        foreach($this->Schema as $key => $defs) {
            array_push($defs,0,NULL,NULL);
            $ref_key = $key;
            list($disp_name,$disp_flag,$width,$relations,$binds) = $defs;
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
        list($model,$field,$refer) = explode('.', "{$db}...");
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
    private function RelationSetup() {
        $new_Relations = [];
        foreach($this->Relations as $key => $rel) {
            $base_name = id_relation_name($key);
            $rel_array = is_array($rel);
            if($rel_array) {            // multi-column refer
                list($db,$ref_list) = array_first_item($rel);
                if(is_numeric($db)) {       // maybe 'Model.id.Field'
                    if(!is_scalar($ref_list)) continue;
                    list($d,$f,$r) = explode('.', "{$ref_list}...");
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
			list($rel_tbl,$primary) = explode('.',$rel_def);
            foreach($ref_list as $refer) {
                $sub_ref = $this->$model->JoinDefinition($table,$refer,$primary);
                $alias_name = "{$base_name}_".id_relation_name($refer);
                $this->FieldSchema[$alias_name] = NULL;   // $key; import MUST!
                // locale if exist in relation-db
                if($this->$model->dbDriver->fieldAlias->exists_locale($refer)) {
                    $ref_name = "{$refer}_" . LangUI::$LocaleName;
                } else $ref_name = $refer;
                $sub_rel[$alias_name] = (is_array($sub_ref)) ? $sub_ref : "{$link}.{$ref_name}";
            }
            $new_Relations[$key] =  $sub_rel;
        }
        $this->dbDriver->setupRelations($new_Relations);
    }
//==============================================================================
// Selection Table Relation setup
    private function SelectionSetup() {
        $new_Selection = [];
        $separate_rel_cond = function($defs) {
            $rel = $cond = [];
            foreach($defs as $key => $val) {
                if(is_int($key)) {
                    if(is_array($val)) {
		                list($kk,$vv) = array_first_item($val);
						if(is_int($kk)) $rel = $val;
						else $cond = $val;
					} else $rel[] = $val;
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
//debug_log(DBMSG_NOLOG,['KEY'=>$key_name,'DEF'=>$seldef,'TARGET'=>[$target,$cond],'REF'=>[$model,$ref_list]]);
                list($model,$ref_list) = array_first_item($target);
                if($model === 0) {
                    $lnk = $target;
                } else {
                    list($model,$table,$field) = $this->model_view($model);
                    if(is_scalar($ref_list)) {
                        $lnk = [ $model => "{$field}.{$ref_list}"] ;
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
	$str_value = strval($value);
	foreach($this->Select[$sel_name] as $key => $val) {
		if($str_value === $val) return $key;
	}
	return "";
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
    if(!empty($value)) {
        $row = $this->dbDriver->doQueryBy($key,$value);
        $this->fields = ($row === FALSE) ? [] : $row;
    } else $this->fields = array();
    return $this->fields;
}
//==============================================================================
// Get Record Data by primary-key,and JOIN data by $join is TRUE.
// Result:   $this->RecData in Column Data
public function GetRecord($num,$join=FALSE,$values=FALSE) {
    if($join) {
        if(empty($num)) $this->fields = array();
        else $this->fields = $this->dbDriver->getRecordValue([$this->Primary => $num],TRUE);
    } else $this->getRecordBy($this->Primary,$num);
    $this->RecData= $this->fields;
    if($values) $this->GetValueList();
}
//==============================================================================
//   Get Relation Table fields data list.
// Result:   $this->Select (Relations)
public function LoadSelection($key_names) {
	$selections = (is_array($key_names)) ? $key_names : [$key_names];
    foreach($selections as $key_name) {
		list($target,$cond) = $this->SelectionDef[$key_name];
		list($model,$ref_list) = array_first_item($target);
		$sort_flag = SORT_REGULAR;
		if(is_int($model)) {        // self list
			$sel_item = [];
			// Make [Value => Value ] Selection-List
			$this->RecordFinder($cond,$ref_list,NULL,function($record,$filter) use(&$sel_item) {
				foreach($filter as $key) {
					$kk = $record[$key];
					$sel_item[$kk] = $kk;
				}
				return [];
			});
			$sort_flag = SORT_FLAG_CASE | SORT_STRING;
			$this->Select[$key_name] = $sel_item;
		} else if(is_array($ref_list)) {
			list($method,$args) = array_first_item($ref_list);
			if(is_numeric($method)) {
				if(is_array($args)) {
					list($id,$field) = $args;
					$this->Select[$key_name] = $this->$model->getFieldValues($id,$field,$cond);
				} else {
					die("MODEL-FINDER\n");
					$this->$model->RawRecordFinder($cond,$ref_list,NULL,function($record,$filter) {
        				$new = [];
        				foreach($filter as $key) $new[$key] = $record[$key];
				        return $new;
    				});
					$this->Select[$key_name] = $this->$model->Records;
				}
			} else if(method_exists($this->$model,$method)) {	// selection by method call 
				$method_val = $this->$model->$method($args,$cond);
				$this->Select[$key_name] = $method_val;
			}
		} else {
			$sort_flag = SORT_NUMERIC;
			$ref_list = explode('.', $ref_list);
			$postfix = (count($ref_list) > 2);      // append to keyname_field
			$ref_list = array_filter( $ref_list, "strlen" ) ;
			$new_rec = [];
			$this->$model->RecordFinder($cond,$ref_list,NULL,function($record,$filter) use(&$new_rec) {
				$id = array_shift($filter);
				foreach($filter as $key) {
					$rec_key = $record[$key];
					$new_rec[$key][$rec_key] = $record[$id];
				}
				return [];
			});
			array_shift($ref_list);     // remove relation-id
			foreach($ref_list as $key) {
				$key_set = ($postfix) ? "{$key_name}_{$key}" : $key_name;
				$this->Select[$key_set] = $new_rec[$key];
			}
		}
		ksort($this->Select[$key_name],$sort_flag);       // sort VALUE-LIST ignore-case
	}
}
//==============================================================================
//   Get Relation Table fields data list.
// Result:   $this->Select (Relations)
public function GetValueList() {
    $this->Select= [];
	$keyset = array_keys($this->SelectionDef);
	$this->LoadSelection($keyset);
    debug_log(DBMSG_MODEL, [ "SELECT_LIST" => $this->Select]);
}
//==============================================================================
//   Get Field Value List
// Result:  Select array
public function getFieldValues($id,$field, $cond = NULL) {
    $select = $this->dbDriver->getValueLists(NULL,$field,$id,$cond);
    debug_log(DBMSG_MODEL, [ "VALUE_LIST" => $select]);
	return $select;
}
//==============================================================================
// Get Record Field by field-key without JOIN fields.
// Result:   field-data
public function getRecordField($key,$value,$field) {
    $this->getRecordBy($key,$value);
    return (array_key_exists($field,$this->fields)) ? $this->fields[$field] : NULL;
}
//==============================================================================
// 条件に一致するレコード数を検索する
public function getCount($cond) {
    return $this->dbDriver->getRecordCount($cond);
}
//==============================================================================
// Normalized Field-Filter
	private function normalize_filter($filter) {
		if(empty($filter)) $filter = $this->dbDriver->columns;
		else if(is_scalar($filter)) $filter = [$filter];
		return $filter;
	}
//==============================================================================
// Get Record List by FIND-CONDITION with JOIN Table.
// Result:   $this->Records  Find-Result List
public function RecordFinder($cond,$filter=NULL,$sort=NULL,$callback=NULL) {
	$filter = $this->normalize_filter($filter);
    $fields_list = array_filter($this->FieldSchema, function($vv) use (&$filter) {
        return in_array($vv,$filter,true) || ($vv === NULL);
    });
    $data = array();
    if(empty($sort)) $sort = [ $this->Primary => $this->SortDefault ];
    else if(is_scalar($sort)) {
        $sort = [ $sort => $this->SortDefault ];
    }
    $this->dbDriver->findRecord($cond,TRUE,$sort);
    while (($fields = $this->dbDriver->fetchDB())) {
        unset($record);
        foreach($fields_list as $key => $val) {
            $record[$key] = $fields[$key];
            if($val !== NULL && $key !== $val) $record[$val] = $fields[$val];
        }
        // Must be PRIMARY-KEY
        $record[$this->Primary] = $fields[$this->Primary];
        if($callback !== NULL) $record = $callback($record,$filter);
        if(! empty($record) ) {
            $data[] = $record;
            $this->record_max = $this->dbDriver->recordMax;
        } else {
            debug_log(FALSE, ["fields" => $fields]);
        }
    }
    $this->Records = $data;
//    debug_log(DBMSG_CLI, ['DATA'=>$data]);
    debug_log(FALSE, [
        "record_max" => $this->record_max,
        "Filter" => $filter,
//        "FieldSchema" => $this->FieldSchema,
        "COND" => $cond,
        "RECORDS" => $this->Records,
    ]);
}
//==============================================================================
// Get Raw Record List by FIND-CONDITION without JOIN!.
// Result:   $this->Records  Find-Result List
public function RawRecordFinder($cond,$filter=NULL,$sort=NULL,$callback=NULL) {
	$filter = $this->normalize_filter($filter);
    $fields_list = array_combine($filter,$filter);
    $fields_list[$this->Primary] = $this->Primary;  // must be include Primary-Key
    $data = array();
    if(empty($sort)) $sort = [ $this->Primary => $this->SortDefault ];
    else if(is_scalar($sort)) {
        $sort = [ $sort => $this->SortDefault ];
    }
    $this->dbDriver->findRecord($cond,FALSE,$sort);
    while (($fields = $this->dbDriver->fetch_locale())) {
        unset($record);
        foreach($fields_list as $key => $val) {
            $record[$key] = $fields[$key];
        }
        if(! empty($record) ) {
            if($callback !== NULL) $record = $callback($record,$filter);
            $data[] = $record;
            $this->record_max = $this->dbDriver->recordMax;
        }
    }
    $this->Records = $data;
}
//==============================================================================
// Get First Record by condition w/o JOIN!
// Result:   $this->RecData
public function firstRecord($cond=[],$filter=NULL,$sort=NULL) {
	$filter = $this->normalize_filter($filter);
    $fields_list = array_combine($filter,$filter);
    $fields_list[$this->Primary] = $this->Primary;  // must be include Primary-Key
    $fields = $this->dbDriver->firstRecord($cond,FALSE,$sort);
    $this->RecData = array_filter($fields, function($val,$key) use (&$filter) {return in_array($key,$filter,true);},ARRAY_FILTER_USE_BOTH);
}
//==============================================================================
// Get Record with Prev/Next Record List by FIND-CONDITION without JOIN!.
// Result:   $this->NearData  Find-Result List by $filter field only
//           $this->RecData   $primary Record Data by all fields
public function NearRecordFinder($primary,$cond,$filter=NULL,$sort=NULL) {
	$filter = $this->normalize_filter($filter);
    $fields_list = [$this->Primary => $this->Primary];  // must be include Primary-Key
    foreach($filter as $key) {
        if(array_key_exists($key,$this->FieldSchema)) $fields_list[$key] = $this->FieldSchema[$key];
    }
    if(empty($sort)) $sort = [ $this->Primary => $this->SortDefault ];
    else if(is_scalar($sort)) $sort = [ $sort => $this->SortDefault ];
    $this->dbDriver->findRecord($cond,TRUE,$sort);
    $r_prev = $r_next = NULL;
    $prev = true;
    $row_num = 0;
    $primary = intval($primary);
    while (($fields = $this->dbDriver->fetchDB())) {
        $data = [];
        foreach($fields_list as $key => $val) $data[$key] = $fields[$key];
        $row_id = $fields[$this->Primary];
        if( intval($row_id) === $primary) {
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
    $this->record_max = $this->dbDriver->recordMax;
    $this->NearData = [$r_prev, $r_next ];
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
// VALIDATION of write data
public function is_valid(&$row) {
    return TRUE;
}
//==============================================================================
// field-alias, field-bind processing.
    private function field_alias_bind($row) {
        $this->fields = array();
        foreach($row as $key => $val) {
            $alias = ($this->AliasMode) ? $this->dbDriver->fieldAlias->get_lang_alias($key) :$key;
            $this->fields[$alias] = $val;
        }
        debug_log(DBMSG_MODEL,['ALIAS' => $this->fields]);
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
        foreach($row as $key => $val) {
            $xkey = $this->get_post_field($key);
            if(array_key_exists($xkey,$this->dbDriver->raw_columns)) {
                $data[$xkey] = $val;
            }
        }
        unset($data[$this->Primary]);
        return $data;
    }
//==============================================================================
// Add NEW record
public function AddRecord($row) {
    $data = $this->field_pickup($row);
    if($this->is_valid($data)) {
        $this->field_alias_bind($data);
        $this->dbDriver->insertRecord($this->fields);
    } else {
        debug_log(DBMSG_MODEL,['VALID-ERROR'=>$data]);
    }
}
//==============================================================================
// UPDATE Record
public function UpdateRecord($num,$row) {
    $data = $this->field_pickup($row);
    if($this->is_valid($data)) {
        $this->field_alias_bind($data);
//		debug_log(DBMSG_DUMP,[$row,$this->fields]);     // for DEBUG
        $this->dbDriver->updateRecord([$this->Primary => $num],$this->fields);
    } else {
        debug_log(DBMSG_MODEL,['VALID-ERROR'=>$data]);
    }
}

}
