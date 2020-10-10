<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  AppModel:    データベース操作用の基底クラス
 */

// データベースの接続情報クラス
require_once('Core/Handler/DatabaseHandler.php');

//==============================================================================
class AppModel extends AppObject {
    static $DatabaseSchema = [
        'Handler' => 'Null',
        'DataTable' => '',
        'Primary' => '',
        'Unique' => '',
        'Schema' => [],
        'PostRenames' => [],
    ];
    protected $dbDriver;        // データベースドライバー
    protected $TableHead;      // テーブルヘッダ
    protected $fields;            // レコードフィールドの値
//    protected $OnGetRecord;   // レコード取得時のコールバック関数
    public $pagesize = 0;           // 1ページ当たりのレコード取得件数
    public $page_num = 0;           // 取得ページ番号
    public $record_max = 0;         // 総レコード数

    public $RecData = NULL;          // レコードデータ(JOINなし)
    public $Select = NULL;           // リレーション先のラベルと値の連想配列リスト
    public $Records = NULL;          // レコード検索したレコードリスト(JOIN済)
    public $Header = NULL;           // レコード検索したレコードの列名リスト
    public $OnGetRecord = NULL;      // レコード取得時のコールバック1関数
    // Schema を分解してヘッダ表示用エイリアス・属性＋参照フィールド名を記憶する
    public $HeaderSchema = [];       // ヘッダー表示用のリスト [ field_name => [disp_name, align, sort_flag ]
    private $FieldSchema = [];       // 取得フィールドのリスト [ref_name, org_name]
    public $DateFormat;             // 日付表示形式
//==============================================================================
//	コンストラクタ：　テーブル名
//==============================================================================
	function __construct($owner) {
	    parent::__construct($owner);                    // 継承元クラスのコンストラクターを呼ぶ
        debug_log(13,static::$DatabaseSchema);
        $this->setProperty(static::$DatabaseSchema);    // クラスプロパティを設定
        $this->__InitClass();                             // クラス固有の初期化メソッド
        $this->fields = [];
	}
//==============================================================================
// クラス変数の初期化
    protected function __InitClass() {
        $driver = $this->Handler . 'Handler';
        $this->dbDriver = new $driver($this->DataTable);        // データベースドライバー
        $this->DateFormat = $this->dbDriver->DateStyle;         // データベースの日付書式
        // ヘッダ表示用のスキーマ
        $this->NewSchemaAnalyzer($this->Schema);
        parent::__InitClass();                    // 継承元クラスのメソッドを呼ぶ
    }
//==============================================================================
// リレーション先のフィールド情報はインスタンスが生成された後でしか確認できない
//
public function RelationSetup() {
    // リレーション先の情報をモデル名からテーブル名とロケール先のフィールド名に置換する
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
                $this->FieldSchema[$key_name] = NULL;//$key;
            }
            $this->Relations[$key] =  $sub_rel;
        } else {
            list($model,$field,$refer) = explode('.', "{$rel}...");
            if($this->$model->dbDriver->fieldAlias->exists_locale($key)) {
                $lang_ref = "{$refer}_" . LangUI::$LocaleName;
                if(array_key_exists($lang_ref,$this->$model->dbDriver->columns)) $refer = $lang_ref;
            }
            $arr = [$this->$model->DataTable,$field,$refer]; // モデル名→テーブル名に置換
            $this->Relations[$key] =  implode('.',$arr);            // Relations変数に書き戻す
        }
    }
    debug_log(3,["Relations" => $this->Relations]);
}
//==============================================================================
// スキーマを分解してヘッダー情報を生成
protected function NewSchemaAnalyzer($Schema) {
    $header = $relation = $locale = $bind = $field = [];
    foreach($Schema as $key => $defs) {
        array_push($defs,0,NULL,NULL,NULL,NULL);
        $ref_key = $key;
        list($disp_name,$disp_flag,$width,$relations,$binds) = $defs;
        list($accept_lang,$disp_align,$disp_head) = [intdiv($disp_flag,100),intdiv($disp_flag%100,10), $disp_flag%10];
        if(!empty($relations)) {
            if(substr($key,-3)==='_id' && is_scalar($relations)) $ref_key = substr($key,0,strlen($key)-3);
            $relation[$key] = $relations;//[$relations,$accept_lang];
            if($disp_head !== 0) $field[$ref_key] = $key;
        } else {
            if(!empty($binds)) {
                $bind[$ref_key] = $binds;
                $key = NULL;
            }
            $field[$ref_key] = $key;
        }
        if($disp_head !== 0) {
            if($disp_name[0] === '.') $disp_name = $this->_(".Schema{$disp_name}");   //  Schema 構造体を参照する
            $header[$ref_key] = [$disp_name,$disp_align,$disp_head,$width];
        }
        // リレーションしているものはリレーション先の言語を後で調べる
        if($accept_lang) {
            $ref_name = "{$ref_key}_" . LangUI::$LocaleName;
            if(array_key_exists($ref_name,$this->dbDriver->columns)) {
                $locale[$ref_key] = $ref_name;
            }
        }
    }
    debug_log(9,[
        "Header" => $header, 
        "Field" => $field, 
        "Relation" => $relation, 
        "locale" => $locale,
        "bind" => $bind,
    ]);

    $this->HeaderSchema = $header;
    $this->FieldSchema = $field;
    $this->Relations = $relation;
    $this->dbDriver->fieldAlias->SetupAlias($locale,$bind);
}
//==============================================================================
// ページング設定
public function SetPage($pagesize,$pagenum) {
    $this->pagesize = $pagesize;            // 1ページ当たりのレコード取得件数、0 = 制限なし
    $this->page_num = ($pagenum <= 0) ? 1 : $pagenum;            // 現在のページ番号 1から始まる
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
    $this->RecData= $this->fields;          // レコードの生データ
}
//==============================================================================
//   リレーション先のラベルと値の連想配列リスト作成
// 結果：  リレーション先の選択リスト = Select (Relations)
public function GetValueList() {
    $valueLists = array();
    foreach($this->Relations as $key => $val) {     // リレーション先の値リストを取得する
        if(is_array($val)) {
            $base = (substr($key,-3)==='_id') ? substr($key,0,strlen($key)-3) : $key;
            foreach($val as $kk => $ref) {
                list($table,$id,$fn) = explode('.', $ref);
                $key_name = "{$base}_{$kk}";
                $valueLists[$key_name] = $this->dbDriver->getValueLists($table,$kk,$fn);
            }
        } else {
            list($table,$fn, $ref) = explode('.', $val);
            // $key カラムの一覧を取得する
            $valueLists[$key] = $this->dbDriver->getValueLists($table,$ref,$fn);
        }
    }
    $this->Select= $valueLists;             // JOIN先の値リスト
    debug_log(3, [ "RELATIONS" => $this->Relations, "VALUE_LIST" => $valueLists]);
}
//==============================================================================
// フィールドの読み込み (JOIN無し)
// 結果：   フィールドデータ
public function getRecordField($key,$field) {
    $this->getRecordByKey($key);                // レコードデータを読み込む
    return $this->fields[$field];               // フィールド値を返す
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
//          $vfilter[] で $filter した列名に指定の値が含むレコードのみ抽出
public function RecordFinder($cond,$filter=[],$sort=[],$vfilter=[]) {
    debug_log(3, [ "cond" => $cond, "filter" => $filter]);
    if(empty($filter)) $filter = $this->dbDriver->columns;
    // 取得フィールドリストを生成する
    $fields_list = array_filter($this->FieldSchema, function($vv) use (&$filter) {
        return in_array($vv,$filter) || ($vv === NULL); // orgがNULLならバインド名を必ず含める
    });
debug_log(3,["ARG" => $filter,"FIELD" => $this->FieldSchema," => FILTER" => $fields_list]);
    $data = array();
    if(empty($sort)) $sort = [ $this->Primary => SORTBY_ASCEND ];
    else if(is_scalar($sort)) {
        $sort = [ $sort => SORTBY_ASCEND ];
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
            $this->doEvent('OnGetRecord', $record);     // イベントコールバック
        } else {
            debug_log(3, ["fields" => $fields]);
        }
        debug_log(3, ["Fech:" => $fields,"Filter:" => $fields_list,"record" => $record]);
    }
    $this->Records = $data;
    debug_log(3, [ "record_max" => $this->record_max, "Header" => $this->HeaderSchema,"RECORDS" => $this->Records]);
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
                $alias = $this->$model->fieldAlias->get_lang_alias($key);
                $this->fields[$alias] = $val;
            }
        }
        debug_log(3,['ALIAS' => $this->field]);
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
