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
    ];
    protected $dbDriver;            // Database Driver
    protected $fields;              // Record-Data all fields value
    public $pagesize = 0;           // get record count per PAGE
    public $page_num = 0;           // get Page Number
    public $record_max = 0;         // Total records count
    public $AliasMode = TRUE;       // Language Alias Enable

    public $RecData = NULL;          // ROW record data (no JOIN)
    public $Select = NULL;           // Select List for relation table field
    public $Records = NULL;          // get records lists (with JOIN field)
    public $OnGetRecord = NULL;      // for feature FUNCTION
    public $HeaderSchema = [];       // Display Header List [ field_name => [disp_name, align, sort_flag ]
    private $FieldSchema = [];       // Pickup Record fields columns [ref_name, org_name]
    private $Relations = [];         // Table Relation
    public $DateFormat;              // Date format for Database
    public $SortDefault = SORTBY_ASCEND;    // findRecord Default Sort Sequence
//==============================================================================
//	Constructor: Owner
//==============================================================================
	function __construct($owner) {
	    parent::__construct($owner);                    // call parent constructor
        $this->setProperty(static::$DatabaseSchema);    // Set Propert from Database Schema Array
        if(isset($this->ModelTables)) {                 // Multi-Language Tabele exists
            $db_key = (array_key_exists(LangUI::$LocaleName,$this->ModelTables)) ? LangUI::$LocaleName : '*';
            $this->DataTable = $this->ModelTables[$db_key]; // DataTable SWITCH
        }
        $this->__InitClass();
        $this->fields = [];
	}
//==============================================================================
// Initializ Class Property
    protected function __InitClass() {
        $driver = $this->Handler . 'Handler';
        $this->dbDriver = new $driver($this->DataTable);        // connect Database Driver
        $this->DateFormat = $this->dbDriver->DateStyle;         // Date format from DB-Driver
        $this->SchemaAnalyzer($this->Schema);                   // Schema Initialize
        parent::__InitClass();
    }
//==============================================================================
// Switch Schema Language
public function ResetSchema() {
    $this->SchemaAnalyzer();
    $this->RelationSetup();
}
//==============================================================================
// Table Relation setup
public function RelationSetup() {
    foreach($this->Relations as $key => $rel) {
        $kk = (substr($key,-3)==='_id') ? substr($key,0,strlen($key)-3) : $key;
        if(is_array($rel)) {
            $sub_rel = [];
            list($db,$ref_list) = array_first_item($rel);
            if(is_numeric($db)) continue;
            list($model,$field) = explode('.', "{$db}.");
            $link = $this->$model->DataTable.".{$field}";
            foreach($ref_list as $refer) {
                $key_name = "{$kk}_{$refer}";
                $ref_name = $refer;
                if($this->$model->dbDriver->fieldAlias->exists_locale($ref_name)) {
                    $lang_ref = "{$ref_name}_" . LangUI::$LocaleName;
                    if(array_key_exists($lang_ref,$this->$model->dbDriver->columns)) $ref_name = $lang_ref;
                }
                $sub_rel[$refer] = "{$link}.{$ref_name}";
                $this->FieldSchema[$key_name] = NULL;   // $key; import MUST!
            }
            $this->Relations[$key] =  $sub_rel;
        } else {
            list($model,$field,$refer) = explode('.', "{$rel}...");
            if($this->$model->dbDriver->fieldAlias->exists_locale($key)) {
                $lang_ref = "{$refer}_" . LangUI::$LocaleName;
                if(array_key_exists($lang_ref,$this->$model->dbDriver->columns)) $refer = $lang_ref;
            }
            $arr = [$this->$model->DataTable,$field,$refer];        // ModelName in schema convert to Table name
            $this->Relations[$key] =  implode('.',$arr);
        }
    }
    $this->dbDriver->setupRelations($this->Relations);
    debug_log(DBMSG_MODEL,[             // DEBUG LOG information
        "Header" => $this->HeaderSchema,
        "Field" => $this->FieldSchema, 
        "Relation" => $this->Relations, 
        "Locale-Bind" => $this->dbDriver->fieldAlias->GetAlias(),
    ]);
}
//==============================================================================
// Schema Define Analyzer
    protected function SchemaAnalyzer() {
        $header = $relation = $locale = $bind = $field = [];
        foreach($this->Schema as $key => $defs) {
            array_push($defs,0,NULL,NULL,NULL,NULL);
            $ref_key = $key;
            list($disp_name,$disp_flag,$width,$relations,$binds) = $defs;
            list($accept_lang,$disp_align,$disp_head) = [intdiv($disp_flag,100),intdiv($disp_flag%100,10), $disp_flag%10];
            if(!empty($relations)) {
                if(substr($key,-3)==='_id' && is_scalar($relations)) $ref_key = substr($key,0,strlen($key)-3);
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
// Paging Parameter Setup
public function SetPage($pagesize,$pagenum) {
    $this->pagesize = $pagesize;
    $this->page_num = ($pagenum <= 0) ? 1 : $pagenum;
    $this->dbDriver->SetPaging($this->pagesize,$this->page_num);
}
//==============================================================================
// PrimaryKey で生レコードを取得
// 結果：   レコードデータ = fields
public function getRecordByKey($id) {
    return $this->getRecordBy($this->Primary,$id);
}
//==============================================================================
// 指定フィールドで生レコードを取得
// 結果：   レコードデータ = field
public function getRecordBy($key,$value) {
    if(!empty($value)) {
        $this->fields = $this->dbDriver->doQueryBy($key,$value);
    } else $this->fields = array();
    return $this->fields;
}
//==============================================================================
// アイテムの読み込み (JOIN無し)
//   リレーション先のラベルと値の連想配列リスト作成
// 結果：   レコードデータ = RecData
//          リレーション先の選択リスト = Select (Relations)
public function GetRecord($num) {
    $this->getRecordBy($this->Primary,$num);
    $this->RecData= $this->fields;
}
//==============================================================================
//   リレーション先のラベルと値の連想配列リスト作成
// 結果：  リレーション先の選択リスト = Select (Relations)
public function GetValueList() {
    $valueLists = array();
    foreach($this->Relations as $key => $val) {
        if(is_array($val)) {
            $base = (substr($key,-3)==='_id') ? substr($key,0,strlen($key)-3) : $key;
            foreach($val as $kk => $ref) {
                list($table,$id,$fn) = explode('.', $ref);
                $key_name = "{$base}_{$kk}";
                $valueLists[$key_name] = $this->dbDriver->getValueLists($table,$fn,$id);
            }
        } else {
            list($table,$fn, $ref) = explode('.', $val);
            // $key カラムの一覧を取得する
            $valueLists[$key] = $this->dbDriver->getValueLists($table,$ref,$fn);
        }
    }
    $this->Select= $valueLists;
    debug_log(DBMSG_MODEL, [ "VALUE_LIST" => $valueLists]);
}
//==============================================================================
//   指定フィールドの値一覧を取得する
// 結果：  リレーション先の選択リスト = Select
public function GetFieldValues($field) {
    $this->Select[$field] = $this->dbDriver->getValueLists(NULL,$field,$field);
    debug_log(DBMSG_MODEL, [ "VALUE_LIST" => $this->Select]);
}
//==============================================================================
// フィールドの読み込み (JOIN無し)
// 結果：   フィールドデータ
public function getRecordField($key,$value,$field) {
    $this->getRecordBy($key,$value);
    return $this->fields[$field];
}
//==============================================================================
// レコードデータの読み込み(JOIN済レコード)
public function getRecordValue($num) {
    if(empty($num)) {
        $this->field = array();
        return;
    }
    $this->fields = $this->dbDriver->getRecordValue([$this->Primary => $num],$this->Relations);
    $this->RecData = $this->fields;
}
//==============================================================================
// 条件に一致するレコード数を検索する
public function getCount($cond) {
    return $this->dbDriver->getRecordCount($cond);
}
//==============================================================================
// レコードリストの読み込み(JOIN済レコード)
// 結果：   レコードデータのリスト = Records
//          読み込んだ列名 = Header (Schema)
//          $filter[] で指定したオリジナル列名のみを抽出
public function RecordFinder($cond,$filter=[],$sort=[]) {
    if(empty($filter)) $filter = $this->dbDriver->columns;
    // 取得フィールドリストを生成する
    $fields_list = array_filter($this->FieldSchema, function($vv) use (&$filter) {
        return in_array($vv,$filter,true) || ($vv === NULL); // orgがNULLならバインド名を必ず含める
    });
    $data = array();
    if(empty($sort)) $sort = [ $this->Primary => $SortDefault ];
    else if(is_scalar($sort)) {
        $sort = [ $sort => $SortDefault ];
    }
    // 複数条件の検索
    $this->dbDriver->findRecord($cond,$this->Relations,$sort);
    while (($fields = $this->dbDriver->fetchDB())) {
        unset($record);
        foreach($fields_list as $key => $val) {
            $record[$key] = $fields[$key];
            if($val !== NULL && $key !== $val) $record[$val] = $fields[$val];
        }
        // プライマリキーは必ず含める
        $record[$this->Primary] = $fields[$this->Primary];
        if(! empty($record) ) {
            $data[] = $record;
            $this->record_max = $this->dbDriver->recordMax;
            $this->doEvent('OnGetRecord', $record);     // for FEATURE!!!!
        } else {
            debug_log(DBMSG_MODEL, ["fields" => $fields]);
        }
    }
    $this->Records = $data;
    debug_log(FALSE, [
        "record_max" => $this->record_max,
        "Filter" => $filter,
        "FieldSchema" => $this->FieldSchema,
        "FILTER" => $fields_list,
        "RECORDS" => $this->Records,
    ]);
}
//==============================================================================
// レコードリストの読み込みJOIN/ALIASなし
public function realFinder($cond,$filter=[],$sort=[]) {
    if(empty($filter)) $filter = $this->dbDriver->columns;
    $fields_list =[];
    foreach($filter as $kk) $fields_list[$kk] = $kk;
    // プライマリキーは必ず含める
    $fields_list[$this->Primary] = $this->Primary;
    $data = array();
    if(empty($sort)) $sort = [ $this->Primary => $SortDefault ];
    else if(is_scalar($sort)) {
        $sort = [ $sort => $SortDefault ];
    }
    // 複数条件の検索
    $this->dbDriver->findRecord($cond,NULL,$sort);
    while (($fields = $this->dbDriver->fetch_array())) {
        unset($record);
        foreach($fields_list as $key => $val) {
            $record[$key] = $fields[$key];
        }
        if(! empty($record) ) {
            $data[] = $record;
            $this->record_max = $this->dbDriver->recordMax;
            $this->doEvent('OnGetRecord', $record);     // イベントコールバック
        } else {
            debug_log(DBMSG_MODEL, ["fields" => $fields]);
        }
    }
    $this->Records = $data;
    debug_log(FALSE, [
        "record_max" => $this->record_max,
        "Filter" => $filter,
        "FieldSchema" => $this->FieldSchema,
        "FILTER" => $fields_list,
        "RECORDS" => $this->Records,
    ]);
}
//==============================================================================
// レコードリストの読み込みJOINなし
public function nearRecord($primary,$cond=[],$filter=[]) {
    if(empty($filter)) $filter = $this->dbDriver->columns;
    $data = array();
    foreach(['>' => SORTBY_ASCEND,'<' => SORTBY_DESCEND] as $cmp => $seq) {
        $mcond = [ $cond, $this->Primary.$cmp => $primary];
        $fields = $this->dbDriver->firstRecord($mcond,NULL,[ $this->Primary => $seq ]);
        $data[] = array_filter($fields, function($val,$key) use (&$filter) {return in_array($key,$filter,true);},ARRAY_FILTER_USE_BOTH);
    }
    $this->nearRecords = $data;
    debug_log(FALSE, [
        "Filter" => $filter,
        "RECORDS" => $this->nearRecords,
    ]);
}
//==============================================================================
// レコードの削除
public function DeleteRecord($num) {
    $this->dbDriver->deleteRecord([$this->Primary => $num]);
}
//==============================================================================
// レコードの削除
// 検索条件がインプット
public function MultiDeleteRecord($cond) {
    $this->dbDriver->deleteRecord($cond);
}
//==============================================================================
// ロケールフィールドによる置換処理
    private function fieldSetup($row) {
        unset($row[$this->Primary]);        // プライマリーキーは削除
        $this->fields = array();
        foreach($row as $key => $val) {
            if(array_key_exists($key,$this->dbDriver->columns)) {
                $alias = ($this->AliasMode) ? $this->dbDriver->fieldAlias->get_lang_alias($key) :$key;
                $this->fields[$alias] = $val;
            }
        }
        debug_log(DBMSG_MODEL,['ALIAS' => $this->fields]);
    }
//==============================================================================
// レコードの追加
public function AddRecord($row) {
    $this->fieldSetup($row);
    $this->dbDriver->insertRecord($this->fields);
}
//==============================================================================
// レコードの更新
public function UpdateRecord($num,$row) {
    $this->fieldSetup($row);
    $this->dbDriver->updateRecord([$this->Primary => $num],$this->fields);
}

}
