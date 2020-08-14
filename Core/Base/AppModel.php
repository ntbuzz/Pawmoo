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
        'PostRenames' => []
    ];
    protected $dbDriver;        // データベースドライバー
    protected $TableHead;      // テーブルヘッダ
    protected $fields;            // レコードフィールドの値
//    protected $OnGetRecord;   // レコード取得時のコールバック関数
    public $pagesize;           // 1ページ当たりのレコード取得件数
    public $page_num;           // 取得ページ番号
    public $record_max;         // 総レコード数

    private $LocaleField;       // ロケール置換フィールドセット
//==============================================================================
//	コンストラクタ：　テーブル名
//==============================================================================
	function __construct($owner) {
	    parent::__construct($owner);                    // 継承元クラスのコンストラクターを呼ぶ
        APPDEBUG::MSG(13,static::$DatabaseSchema);
        $this->setProperty(static::$DatabaseSchema);    // クラスプロパティを設定
        $this->__InitClass();                             // クラス固有の初期化メソッド
        $this->fields = [];
	}
//==============================================================================
//	デバッグ用に空のレコードを生成
//==============================================================================
    function DebugRecord() {
        debug_dump(DEBUG_DUMP_NONE, [
            "Type:"     => $this->ClassType,   // オブジェクトの所属クラス(Controller, Model, View. Helper)
            "Module:"   => $this->ModuleName,  // オブジェクトの名前
            "Class:"    => $this->ClassName,   // クラス名
            "Locale:"   => $this->LocalePrefix,    // 言語プレフィクス
        ]);
        $this->Records = array();          // レコード検索したレコードリスト(JOIN済)
        $this->LogData = array();          // レコードデータ(JOINなし)
        $this->Header = array();           // レコード検索したレコードの列名リスト
    }
//==============================================================================
// クラス変数の初期化
    protected function __InitClass() {
        $driver = $this->Handler . 'Handler';
        $this->dbDriver = new $driver($this->DataTable);        // データベースドライバー
        // ヘッダ表示用のスキーマ
        APPDEBUG::MSG(13,$this->Schema);
        $this->SchemaHeader($this->Schema);
        APPDEBUG::MSG(13, $this->TableHead, "TableHead");
        // 各種データ初期化
        $this->RecData = NULL;          // レコードデータ(JOINなし)
        $this->Select = NULL;           // リレーション先のラベルと値の連想配列リスト
        $this->Records = NULL;          // レコード検索したレコードリスト(JOIN済)
        $this->Header = NULL;           // レコード検索したレコードの列名リスト
        $this->OnGetRecord = NULL;      // レコード取得時のコールバック関数
        $this->pagesize = 0;            // 1ページ当たりのレコード取得件数、0 = 制限なし
        $this->page_num = 0;            // 現在のページ番号
        $this->record_max = 0;          // 総レコード数
        parent::__InitClass();                    // 継承元クラスのメソッドを呼ぶ
    }
//==============================================================================
// スキーマを分解してヘッダー情報を生成
protected function SchemaHeader($schema) {
    // ヘッダ表示用のスキーマ
    $this->TableHead = array();
    $this->LocaleField = [];
    foreach($schema as $key => $val) {
        list($nm,$mflag) = $val;
        // 参照フィールド設定：*_id でRelations設定されていれば_idなしを参照フィールドにする
        if((substr($key,-3)==='_id') && array_key_exists($key,$this->Relations)) {
            $ref = substr($key,0,strlen($key)-3);    // _id を抜いた名称を表示名とする
        } else $ref = $key;
        if(empty($nm)) $nm = $ref;  // alias名が未定義なら参照名と同じにする
        if($nm[0] === '.') {            // 言語ファイルの参照
            $nm = $this->_(".Schema{$nm}");   //  Schema 構造体を参照する
        }
        list($lang,$align,$flag) = array_slice(str_split("000{$mflag}"),-3);
        $this->TableHead[$key] = array($nm,(int)$flag,(int)$align,$ref);
        if($lang === '1') $this->LocaleField[$key] = array($nm, "{$key}_" . LangUI::$LocaleName);
    }
debug_dump(0,["LocaleField" => $this->LocaleField]);
}
//==============================================================================
// ページング設定
public function SetPage($pagesize,$pagenum) {
    $this->pagesize = $pagesize;            // 1ページ当たりのレコード取得件数、0 = 制限なし
    $this->page_num = ($pagenum <= 0) ? 1 : $pagenum;            // 現在のページ番号 1から始まる
    $this->dbDriver->SetPaging($this->pagesize,$this->page_num);
}
//==============================================================================
// ロケールフィールドによる置換処理
// 結果：   レコードデータ = field
    private function readLocaleField() {
        foreach($this->LocaleField as $key => $val) {
            list($nm,$lang_nm) = $val;
            if( array_key_exists($lang_nm,$this->dbDriver->columns)
                && !empty($this->fields[$lang_nm]) )
                    $this->fields[$nm] = $this->fields[$lang_nm];           // ロケールフィールドを取得
        }
    }
//==============================================================================
// ロケールフィールドによる置換処理
// 結果：   レコードデータ = field
    private function writeLocaleField() {
        foreach($this->LocaleField as $key => $val) {
            list($nm,$lang_nm) = $val;
            if( array_key_exists($lang_nm,$this->dbDriver->columns)) {
                $this->fields[$lang_nm] = $this->fields[$nm];           // ロケールフィールドへ書込み
                unset($this->fields[$nm]);                             // もとのフィールドは消しておく
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
public function getRecordBy($field,$value) {
    if(!empty($value)) {
        $this->fields = $this->dbDriver->doQueryBy($field,$value);
        $this->readLocaleField();
    } else $this->fields = array();
    return $this->fields;
}
//==============================================================================
// アイテムの読み込み (JOIN無し)
//   リレーション先のラベルと値の連想配列リスト作成
// 結果：   レコードデータ = RecData
//          リレーション先の選択リスト = Select (Relations)
public function GetRecord($num) {
    APPDEBUG::MSG(13, $num);
    $this->getRecordBy($this->Primary,$num);
    $valueLists = array();
    foreach($this->Relations as $key => $val) {     // リレーション先の値リストを取得する
        list($table,$fn, $ref,$grp) = explode('.', $val);
        if(!isset($grp)) $grp = 0;
        // $key カラムの一覧を取得する
        $valueLists[$key] = $this->dbDriver->getValueLists($table,$ref,$fn);
    }
    APPDEBUG::MSG(13, $valueLists);
    $this->RecData= $this->fields;          // レコードの生データ
    $this->Select= $valueLists;             // JOIN先の値リスト
}
//==============================================================================
// フィールドの読み込み (JOIN無し)
// 結果：   フィールドデータ
public function getRecordField($key,$field) {
    APPDEBUG::MSG(13, $key);
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
    APPDEBUG::MSG(3, $cond, "cond");
    $data = array();
    $this->Header = $this->TableHead;       // 作成済みのヘッダリストを使う
    if(empty($sort)) $sort = [ $this->Primary => SORTBY_ASCEND ];
    else if(is_scalar($sort)) {
        $sort = [ $sort => SORTBY_ASCEND ];
    }
    // 複数条件の検索
    $this->dbDriver->findRecord($cond,$this->Relations,$sort);
    while ($this->fetchRecord()) {
        APPDEBUG::DebugDump(13, [
            "fields:".(count($data)+1) => $this->fields,
            "Head:" => $this->Header,
        ]);
        if(!isset($this->fields[$this->Unique])) continue;
        // ロケールフィールドで置換しておく
        $this->readLocaleField();
        $record = array();
        foreach($this->Header as $key => $val) {
            list($nm,$flag,$align,$ref) = $val;
            // フィルタが無指定、またはフィルタにヒット
            if($filter === [] || in_array($key,$filter)) {
                // 参照フィールド名がキー名と違っていればオリジナルを登録する
                if($nm !== $ref || $key !== $ref)  $record[$key] = $this->fields[$key];
                $record[$nm] = $this->fields[$ref];
            }
        }
        APPDEBUG::MSG(3, $record, "RECORD:");
        // プライマリキーは必ず含める
        $record[$this->Primary] = $this->fields[$this->Primary];
        if(! empty($record) ) {
            $data[] = $record;
            $this->record_max = $this->dbDriver->recordMax;
            $this->doEvent('OnGetRecord', $record);     // イベントコールバック
        } else {
            APPDEBUG::MSG(3, $this->fields, "fields");
        }
    }
    $this->Records = $data;
    APPDEBUG::MSG(3, $this->record_max,"record_max");
}
//==============================================================================
// レコードの取得
public function fetchRecord() {
    return ($this->fields = $this->dbDriver->fetchDB());
}
//==============================================================================
// レコードの追加
public function AddRecord($row) {
    APPDEBUG::MSG(13, $row);
    $this->fields = array();
    foreach($row as $key => $val) {
        $xkey = (isset($this->PostRenames[$key])) ? $xkey = $this->PostRenames[$key] : $key;
        // フィールドキーが存在するものだけ書き換える
        if(array_key_exists($xkey,$this->dbDriver->columns)) $this->fields[$xkey] = $val;
    }
    unset($this->fields[$this->Primary]);
    // ロケールフィールドに移動する
    $this->writeLocaleField();
    APPDEBUG::MSG(13, $this->fields);
    $this->dbDriver->insertRecord($this->fields);
}
//==============================================================================
// レコードの削除
public function DeleteRecord($num) {
    APPDEBUG::MSG(13, $row);
    $this->dbDriver->deleteRecord([$this->Primary => $num]);
}
//==============================================================================
// レコードの削除
// 検索条件がインプット
public function MultiDeleteRecord($cond) {
    APPDEBUG::MSG(13, $cond);
    $this->dbDriver->deleteRecord($cond);
}
//==============================================================================
// レコードの更新
public function UpdateRecord($num,$row) {
    APPDEBUG::MSG(13, $row);
    // 更新しないカラムを保護する
//        $this->getRecordByKey($num);
    // 更新内容で書き換える
    $this->fields = array();
    foreach($row as $key => $val) {
        $xkey = (isset($this->PostRenames[$key])) ? $xkey = $this->PostRenames[$key] : $key;
        // フィールドキーが存在するものだけ書き換える
        if(array_key_exists($xkey,$this->dbDriver->columns)) $this->fields[$xkey] = $val;
    }
    $this->fields[$this->Primary] = $num;
    // ロケールフィールドに移動する
    $this->writeLocaleField();
    APPDEBUG::MSG(3, $this->fields);
    $this->dbDriver->updateRecord([$this->Primary => $num],$this->fields);
}

}
