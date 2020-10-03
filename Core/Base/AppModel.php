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
        'Relations' => [],
        'PostRenames' => [],
        'BindColumns' => [],
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
    private $LocaleSchema= [];       // ロケール置換用のリスト [ field_name => locale_name,,... ]
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
        $this->SchemaHeader($this->Schema);
        debug_log(3, ["HeaderSchema" => $this->HeaderSchema]);
        parent::__InitClass();                    // 継承元クラスのメソッドを呼ぶ
    }
//==============================================================================
// スキーマを分解してヘッダー情報を生成
protected function SchemaHeader($schema) {
    // ヘッダ表示用とフィールド取得用のスキーマとロケール用のスキーマ
    foreach($schema as $key => $val) {
        if(count($val)>=3)  list($alias,$mflag,$wd) = $val;
        else {
            list($alias,$mflag) = $val;
            $wd = 0;
        }
        list($lang,$align,$flag) = array_slice(str_split("000{$mflag}"),-3);
        // リレーション設定があれば _id を抜いたフィールド名を参照
        if((substr($key,-3)==='_id') && array_key_exists($key,$this->Relations)) {
            $key_name = substr($key,0,strlen($key)-3);    // _id を抜いた名称
            $ref_name = $key_name;
        } else {
            $key_name = $key;
            $ref_name = "{$key}_" . LangUI::$LocaleName;
            if($lang === '1' && array_key_exists($ref_name,$this->dbDriver->columns)) {
                $this->LocaleSchema[$key] = $ref_name;
            } else $ref_name = $key;
        }
        if(empty($alias)) $alias = $key_name;
        $this->FieldSchema[$key_name] = [$ref_name,  $key, NULL];         // Schema 配列に定義されたフィールドを取得する
        if($alias[0] === '.') $alias = $this->_(".Schema{$alias}");   //  Schema 構造体を参照する
        if($flag !== '0') {
            $this->HeaderSchema[$key_name] = [$alias,(int)$align,(int)$flag,(int)$wd];
        }
    }
    if(isset($this->BindColumns)) {
        foreach($this->BindColumns as $key => $columns) {
            $this->FieldSchema[$key] = [NULL,  NULL, $columns];
        }
    }
}
//==============================================================================
// リレーション先のフィールド情報はインスタンスが生成された後でしか確認できない
//
public function RelationSetup() {
    if(!empty($this->Relations)) {
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
                    if(array_key_exists($refer,$this->$model->LocaleSchema)) {
                        $lang_ref = "{$refer}_" . LangUI::$LocaleName;
                        if(array_key_exists($lang_ref,$this->$model->dbDriver->columns)) $refer = $lang_ref;
                    }
                    $sub_rel[$refer] = $link;
                    $ref_name = "{$kk}_{$refer}";
                    $this->FieldSchema[$key_name] = [$ref_name,$key,NULL];
                }
                $this->Relations[$key] =  $sub_rel;
            } else {
                list($model,$field,$refer) = explode('.', "{$rel}...");
                if(array_key_exists($refer,$this->$model->LocaleSchema)) {
                    $lang_ref = "{$refer}_" . LangUI::$LocaleName;
                    if(array_key_exists($lang_ref,$this->$model->dbDriver->columns)) $refer = $lang_ref;
                }
                $arr = [$this->$model->DataTable,$field,$refer]; // モデル名→テーブル名に置換
                $this->Relations[$key] =  implode('.',$arr);            // Relations変数に書き戻す
            }
        }
    }
    debug_log(3,["FieldSchema" => $this->FieldSchema, "Relations" => $this->Relations]);
}
//==============================================================================
// ページング設定
public function SetPage($pagesize,$pagenum) {
    $this->pagesize = $pagesize;            // 1ページ当たりのレコード取得件数、0 = 制限なし
    $this->page_num = ($pagenum <= 0) ? 1 : $pagenum;            // 現在のページ番号 1から始まる
    $this->dbDriver->SetPaging($this->pagesize,$this->page_num);
}
//==============================================================================
// ロケールフィールドとカラム連結の処理
// 結果：   レコードデータ = field
    private function read_local_bind_field() {
        foreach($this->LocaleSchema as $key => $lang_nm) {
            if(!empty($this->fields[$lang_nm]))
                $this->fields[$key] = $this->fields[$lang_nm];           // ロケールフィールドに置換
        }
        if(isset($this->BindColumns)) {
            foreach($this->BindColumns as $key => $columns) {
                $ss = array_concat_keys($this->fields,$columns);
                $this->fields[$key] = $ss;
            }
        }
    }
//==============================================================================
// ロケールフィールドによる置換処理
// 結果：   レコードデータ = field
    private function writeLocaleField() {
        foreach($this->LocaleSchema as $key => $lang_nm) {
            if( array_key_exists($lang_nm,$this->dbDriver->columns)) {
                $this->fields[$lang_nm] = $this->fields[$key];          // ロケールフィールドへ書込み
                unset($this->fields[$key]);                             // もとのフィールドは消しておく
            }
        }
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
        $this->read_local_bind_field();
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
        list($table,$fn, $ref) = explode('.', $val);
        // $key カラムの一覧を取得する
        $valueLists[$key] = $this->dbDriver->getValueLists($table,$ref,$fn);
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
    $this->read_local_bind_field();
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
    $fiels_list = array_filter($this->FieldSchema, function($vv) use (&$filter) {
        list($ref_name,$org_name,$bind) = $vv;
        return in_array($org_name,$filter) || ($org_name === NULL); // バインド名は必ず含める
    });
    $data = array();
    if(empty($sort)) $sort = [ $this->Primary => SORTBY_ASCEND ];
    else if(is_scalar($sort)) {
        $sort = [ $sort => SORTBY_ASCEND ];
    }
    // 複数条件の検索
    $this->dbDriver->findRecord($cond,$this->Relations,$sort);
    while (($fields = $this->dbDriver->fetchDB())) {
        debug_log(FALSE, ["Fech:" => $fields,"Filter:" => $fiels_list]);
//        if(!isset($fields[$this->Unique])) continue;
        unset($record);
        foreach($fiels_list as $key => $val) {
            list($ref_name,$org_name,$bind) = $val;
            if($bind === NULL) {
                $ref_key = (empty($fields[$ref_name])) ? $org_name : $ref_name;
                $record[$key] = $fields[$ref_key];
                if($key !== $org_name) $record[$org_name] = $fields[$org_name];
            } else {
                $record[$key] = array_concat_keys($fields,$bind); // バインド処理
            }
        }
        // プライマリキーは必ず含める
        $record[$this->Primary] = $fields[$this->Primary];
        // 抽出したレコード内に指定の値が含まれるか
/*      この方法はSQLカウント数と食い違いが起きるのでNG!
        if(!empty($vfilter)) {
            $vf = array_filter($vfilter, function($v,$k) use(&$record) {
                return ($v === '*') ? isset($record[$k]) : ($record[$k] === $v);
            },ARRAY_FILTER_USE_BOTH);
            if(empty($vf)) continue;
        }
*/
        if(! empty($record) ) {
            $data[] = $record;
            $this->record_max = $this->dbDriver->recordMax;
            $this->doEvent('OnGetRecord', $record);     // イベントコールバック
        } else {
            debug_log(3, ["fields" => $fields]);
        }
    }
    $this->Records = $data;
//    $this->record_max = count($data);
    debug_log(3, [ "record_max" => $this->record_max, "Header" => $this->HeaderSchema,"RECORDS" => $this->Records]);
}
//==============================================================================
// レコードの追加
public function AddRecord($row) {
    $this->fields = array();
    foreach($row as $key => $val) {
//        $xkey = (isset($this->PostRenames[$key])) ? $xkey = $this->PostRenames[$key] : $key;
//        if(array_key_exists($xkey,$this->dbDriver->columns)) $this->fields[$xkey] = $val;
        if(array_key_exists($key,$this->dbDriver->columns)) $this->fields[$key] = $val;
    }
    $this->writeLocaleField();      // ロケールフィールドに移動する
    debug_log(3, ["INSERT" => $this->fields]);
    $this->dbDriver->insertRecord($this->fields);
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
// レコードの更新
public function UpdateRecord($num,$row) {
    // 更新しないカラムを保護する
//        $this->getRecordByKey($num);
    // 更新内容で書き換える
    $this->fields = array();
    foreach($row as $key => $val) {
//        $xkey = (isset($this->PostRenames[$key])) ? $xkey = $this->PostRenames[$key] : $key;
//        if(array_key_exists($xkey,$this->dbDriver->columns)) $this->fields[$xkey] = $val;
        if(array_key_exists($key,$this->dbDriver->columns)) $this->fields[$key] = $val;
    }
    $this->fields[$this->Primary] = $num;
    $this->writeLocaleField();          // ロケールフィールドに移動する
    debug_log(3, ["UPDATE" => $this->fields]);
    $this->dbDriver->updateRecord([$this->Primary => $num],$this->fields);
}

}
