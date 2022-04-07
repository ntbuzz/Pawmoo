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
	public $HeaderSchema = [];		// Table Viewing Header Columns
	public $TableFields = [];		// Table Columns List with attributes
	public $ModelFields;			// Model class field within alias/bind/virtual
    public $pagesize = 0;           // get record count per PAGE
    public $page_num = 0;           // get Page Number
    public $record_max = 0;         // Total records count
    public $row_number = 0;         // near record in target record row
    public $AliasMode = TRUE;       // Language Alias Enable

    public $RecData = NULL;          // ROW record data (no JOIN)
    public $Select = NULL;           // Select List for relation table field
    public $Records = NULL;          // get records lists (with JOIN field)
    public $DateFormat;              // Date format for Database
    public $TimeFormat;              // Time format for Database
    public $DateTimeFormat;          // TimeStamp format for Database
    public $SortDefault = SORTBY_ASCEND;    // findRecord Default Sort Sequence
    protected $SelectionDef = [];      // Selection JOIN list
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
		if(isset(static::$OptionSchema)) $this->setProperty(static::$OptionSchema);    // Set Option Schema, if exists
		if(empty($this->Primary)) $this->Primary = 'id';	// default primary name
        if(isset($this->ModelTables)) {                 // Multi-Language Tabele exists
            $db_key = (array_key_exists(LangUI::$LocaleName,$this->ModelTables)) ? LangUI::$LocaleName : '*';
            $this->DataTable = $this->ModelTables[$db_key]; // DataTable SWITCH
        }
        $this->fields = [];
        $driver = $this->Handler . 'Handler';
        $this->dbDriver = new $driver($this->DataTable,$this->Primary); // connect Database Driver
		list($dFormat,$tFormat,$dtFormat) = array_keys_value($this->dbDriver->DateFormat,['Date','Time','TimeStamp']);
        $this->DateFormat = $dFormat;         	// Date format from DB-Driver
        $this->TimeFormat = $tFormat;         	// Time format from DB-Driver
        $this->DateTimeFormat = $dtFormat;		// DateTime
		$this->dbDriver->fieldAlias->lang_alternate = $this->Lang_Alternate;
		if(method_exists($this,'virtual_field')) {
			$this->dbDriver->register_method($this,'virtual_field');
		}
	}
//==============================================================================
// Initializ Class Property
    protected function class_initialize() {
	    $this->SchemaAnalyzer2();
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
	$this->ResetLocation();
    $this->SelectionSetup();
    debug_log(DBMSG_CLI|DBMSG_MODEL,[             // DEBUG LOG information
        $this->ModuleName => [
            "Header"    => $this->HeaderSchema,
			// "Table"		=> $this->TableFields,
			// "Model"		=> $this->ModelFields,
            "Locale-Bind"   => $this->dbDriver->fieldAlias->GetAlias(),
        ]
    ]);
}
//==============================================================================
// Schema Define Analyzer
private function SchemaAnalyzer2() {
	$this->TableFields = $this->HeaderSchema = [];
	foreach($this->Schema as $key => $defs) {
		list($dtype,$flag,$width) = array_extract($defs,3);
		$dtype = strtolower($dtype);
		// リスト表示対象か
		list($sort,$align,$csv,$lang) = oct_extract($flag,4);
		if($sort) {
			$this->HeaderSchema[$key] = [$dtype, $align, $sort, $width];
		}
		switch($dtype) {
		case 'alias':
		case 'virtual':
		case 'bind':	break;
		default:
			$this->TableFields[$key] = [$dtype,$flag,$width];
		}
		$this->ModelFields[$key] = [$dtype,$flag,$width];
	}
}
//==============================================================================
//	移行用ダミーメソッド
public function JoinDefinition($table,$field,$rel_key) {
	return $field;
}
//==============================================================================
//	言語リソースの再設定
protected function ResetLocation() {
	$this->locale_columns=[];
	foreach($this->Schema as $key => $defs) {
		list($type,$flag,$wd) = array_extract($defs,3);
		if($flag & 01000) {
			$locale_name = "{$key}_" . LangUI::$LocaleName;
			if(array_key_exists($locale_name,$this->dbDriver->columns)) {
				$this->locale_columns[$key] = $locale_name;
			}
		}
	}
	$this->dbDriver->fieldAlias->SetupAlias($this->locale_columns);
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
// Selection Table Relation setup
protected function SelectionSetup() {
        $new_Selection = [];
		$extract_array = function($arr,$n) {
			if(is_array($arr)) {
				$slice = [];
				foreach($arr as $key => $val) {
					if(is_int($key)) $slice[] = $val;
					else $slice[] = [$key => $val];
					--$n;
				}
			} else $slice = [$arr];
			while($n-- > 0)$slice[]=NULL;
			return $slice;
		};
        foreach($this->Selection as $key_name => $seldef) {
            $lnk = [];
            if(is_scalar($seldef)) {
                $lnk[0] = $seldef;
                $cond = [];
            } else {
                list($target,$cond,$sort) = $extract_array($seldef,3);
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
            $new_Selection[$key_name] =  [$lnk,$cond,$sort];
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
	if(empty($value)) return $default;		// not-allow NULL value
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
		list($target,$cond,$sort) = $this->SelectionDef[$key_name];
		$cond = array_override($cond,$opt_cond);	// additional cond
		list($model,$ref_list) = array_first_item($target);
		if(is_int($model)) {        // self list
			// case b., e.
			$this->Select[$key_name] = $this->SelectFinder(is_array($target),$target,$cond,$sort);
		} else if(is_array($ref_list)) {
			// case a., f.
			list($method,$args) = array_first_item($ref_list);
			if(is_numeric($method)) {
				// case a.
				$this->Select[$key_name] = $this->$model->SelectFinder(true,$ref_list,$cond,$sort);
			} else if(method_exists($this->$model,$method)) {	// case f.
				$method_val = $this->$model->$method($args,$cond);
				$this->Select[$key_name] = $method_val;
			}
		} else {
			// case c., d.
			$ref_list = array_filter(explode('.', $ref_list), "strlen" );
			$this->Select[$key_name] = $this->$model->SelectFinder(false,$ref_list,$cond,$sort);
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
	debug_log(false,['Select'=>$this->Select]);
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
		if(is_scalar($filter)) $filter = explode('.',$filter);
		if(empty($filter)) $filter = array_keys($this->ModelFields);
		else {
			$filter = array_filter($filter,function($v) {
					return array_key_exists($v,$this->ModelFields);
			});
		}
		return array_combine($filter,$filter);
	}
//==============================================================================
// Get Selection pair
// Result:   Select array
public function SelectFinder($chain, $filter, $cond, $sort=[]) {
	$filter = $this->normalize_filter($filter);
	list($id,$fn,$pid) = array_alternative($filter,3);
	$data = [];
    $this->dbDriver->findRecord($cond,false,$sort);
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
	if(!$chain && $sort === NULL) ksort($data,SORT_FLAG_CASE|SORT_STRING);
    debug_log(false, [
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
	$fields_list = $this->normalize_filter($filter);
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
        $fields_list = array_filter($this->dbDriver->raw_columns,function($v) use(&$filter) { return in_array($v,$filter);},ARRAY_FILTER_USE_KEY);
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
	$fields_list = $this->normalize_filter($filter);
    $fields_list[$this->Primary] = $this->Primary;  // must be include Primary-Key
    $fields = $this->dbDriver->firstRecord($cond,FALSE,$sort);
    return ($fields === FALSE) ? FALSE : array_filter($fields, function($val,$key) use (&$fields_list) {
							return in_array($key,$fields_list,true);
						},ARRAY_FILTER_USE_BOTH);
}
//==============================================================================
// Get Record with Prev/Next Record List by FIND-CONDITION without JOIN!.
// Result:   $this->NearData  Find-Result List by $filter field only
//           $this->RecData   $primary Record Data by all fields
public function NearRecordFinder($primary,$cond,$filter=NULL,$sort=NULL) {
	$fields_list = $this->normalize_filter($filter);
    $fields_list = [$this->Primary => $this->Primary];  // must be include Primary-Key
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
// CSV download field flags
public function get_csv_columns() {
	$cols = array_filter($this->ModelFields,function($v) {
			list($type,$flag,$wd) = $v;
			return $flag & 0100;
		});
	return array_keys($cols);
}
//==============================================================================
// CSV file download data
public function RecordsCSV($map=true,$limit=0) {
	$records = $this->Records;
	if(empty($records)) return false;
	$col = reset($records);
	// create header by Model Schema
	$keys = array_keys($col);
	if($map) $keys = array_map(function($v) { return $this->_(".Schema.{$v}");},$keys);
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
