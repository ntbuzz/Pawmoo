<?php
/* -------------------------------------------------------------
 * Biscuitフレームワーク
 *  AppModel:    データベース操作用の基底クラス
 * 
 *    クラス・プロパティ変数(static $DatabaseSchema で連想配列として定義する)
 *    static $DatabaseSchema = [
 *        'Handler' => データベース
 *        'DatabaseName' => FMDB以外は未使用,
 *        'DataTable' => テーブル名
 *        'Primary' => プライマリキー名
 *        'Unique' => ユニークキー名
 *        'Schema' => リスト表示するときのヘッダスキーマ
 *        'Relations' => リレーション定義
 *        'PostRenames' => POST変数の読み替え
 *    ];
 */

// データベースの接続情報クラス
require_once('Core/Handler/DatabaseHandler.php');

//==================================================================================================
class AppModel extends AppObject {
    static $DatabaseSchema = [];
    protected $dbDriver;        // データベースドライバー
    protected $TableHead;      // テーブルヘッダ
    protected $fields;            // レコードフィールドの値
//    protected $OnGetRecord;   // レコード取得時のコールバック関数
    public $pagesize;           // 1ページ当たりのレコード取得件数
    public $page_num;           // 取得ページ番号
    public $record_max;         // 総レコード数

//==================================================================================================
//	コンストラクタ：　テーブル名
//==================================================================================================
	function __construct($owner) {
	    parent::__construct($owner);                    // 継承元クラスのコンストラクターを呼ぶ
        APPDEBUG::MSG(11,$DatabaseSchema);
        $this->setProperty(static::$DatabaseSchema);    // クラスプロパティを設定
        $this->__InitClass();                             // クラス固有の初期化メソッド
	}
//==================================================================================================
//	デバッグ用に空のレコードを生成
//==================================================================================================
    function DebugRecord() {
        dump_debug(DEBUG_DUMP_NONE, "DEBUG-MODEL", [
            "Type:"     => $this->ClassType,   // オブジェクトの所属クラス(Controller, Model, View. Helper)
            "Module:"   => $this->ModuleName,  // オブジェクトの名前
            "Class:"    => $this->ClassName,   // クラス名
            "Locale:"   => $this->LocalePrefix,    // 言語プレフィクス
        ]);
        $this->Records = array();          // レコード検索したレコードリスト(JOIN済)
        $this->LogData = array();          // レコードデータ(JOINなし)
        $this->Header = array();           // レコード検索したレコードの列名リスト
    }
//==================================================================================================
// クラス変数の初期化
    protected function __InitClass() {
        $driver = $this->Handler . 'Handler';
    //echo "DRIVER: {$driver}\n";
        $this->dbDriver = new $driver($this->DatabaseName,$this->DataTable);        // データベースドライバー
        // ヘッダ表示用のスキーマ
        $this->TableHead = array();
        APPDEBUG::MSG(11,$this->Schema);
        foreach($this->Schema as $key => $val) {
            list($nm,$mflag) = $val;
            if($nm == '') $nm = $key;
            if($nm[0] == '.') {            // 言語ファイルの参照
                $nm = $this->_(".Schema{$nm}");   //  Schema 構造体を参照する
            }
            $flag = $mflag % 10;
            $align= ($mflag - $flag) / 10;
            $this->TableHead[$key] = array($nm,$flag,$align);
        }
        APPDEBUG::MSG(11, $this->TableHead, "TableHead");
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
//==================================================================================================
// 参照先のモデルクラスをダイナミック生成するマジックメソッド
    public function __get($SubModelName){
        return parent::loadModels($SubModelName);
//        return parent::addSubclass($SubModelName);
    }
//==================================================================================================
// PrimaryKey でレコードを取得
// 結果：   レコードデータ = RecData
//          リレーションフィールドは取得しない
    public function getRecordByKey($id) {
        APPDEBUG::MSG(11,$id);
        if(empty($id)) {
            $this->field = array();
            return;
        }
        $this->fields = $this->dbDriver->doQueryBy($this->Primary,$id);
        APPDEBUG::MSG(19,$this->fields);
        return $this;
    }
//==================================================================================================
// アイテムの読み込み (JOIN無し)
//   リレーション先のラベルと値の連想配列リスト作成
// 結果：   レコードデータ = RecData
//          リレーション先の選択リスト = Select (Relations)
	public function GetRecord($num) {
		APPDEBUG::MSG(11, $num);
        $this->getRecordByKey($num);                    // レコードデータを読み込む
        $valueLists = array();
        foreach($this->Relations as $key => $val) {     // リレーション先の値リストを取得する
        list($table,$fn, $ref,$grp) = explode('.', $val);
            if(!isset($grp)) $grp = 0;
           // $key カラムの一覧を取得する
            $valueLists[$key] = $this->dbDriver->getValueLists($table,$ref,$fn);
        }
        APPDEBUG::MSG(11, $valueLists);
        $this->RecData= $this->fields;          // レコードの生データ
        $this->Select= $valueLists;             // JOIN先の値リスト
    }
//==================================================================================================
// フィールドの読み込み (JOIN無し)
// 結果：   フィールドデータ
    public function getRecordField($key,$field) {
        APPDEBUG::MSG(11, $key);
        $this->getRecordByKey($key);                // レコードデータを読み込む
        return $this->fields[$field];               // フィールド値を返す
    }
//==================================================================================================
// ページング設定
public function SetPage($pagesize,$pagenum) {
    $this->pagesize = $pagesize;            // 1ページ当たりのレコード取得件数、0 = 制限なし
    $this->page_num = ($pagenum <= 0) ? 1 : $pagenum;            // 現在のページ番号 1から始まる
    $this->dbDriver->SetPaging($this->pagesize,$this->page_num);
}
//==================================================================================================
// レコードリストの読み込み(JOIN済レコード)
// 結果：   レコードデータのリスト = Records
//          読み込んだ列名 = Header (Schema)
//          $filter[] で指定したオリジナル列名のみを抽出
    public function RecordFinder($cond,$filter=[]) {
        APPDEBUG::MSG(2, $cond, "cond");
        $data = array();
        // 複数条件の検索
        $this->dbDriver->findRecord($cond,$this->Relations,$this->Primary);
        while ($this->fetchRecord()) {
            APPDEBUG::MSG(11, $this->fields, "fields:" . (count($data)+1));
            if(!isset($this->fields[$this->Unique])) continue;
            $record = array();
            foreach($this->TableHead as $key => $val) {
                list($nm,$flag,$align) = $val;
                // フィルタが無指定、またはフィルタにヒット
                if($filter === [] || in_array($key,$filter)) {
                    // Alias がかかっていたらオリジナルキーも登録しておく
                    if($key !== $nm) $record[$key] = trim($this->fields[$key]);
                    $record[$nm] = trim($this->fields[$key]);
                }
            }
            // プライマリキーは必ず含める
            $record[$this->Primary] = $this->fields[$this->Primary];
            if(! empty($record) ) {
                $data[] = $record;
                $this->record_max = $this->dbDriver->recordMax;
                $this->doEvent('OnGetRecord', $record);     // イベントコールバック
            } else {
                APPDEBUG::MSG(2, $this->fields, "fields");
            }
        }
        $this->Records = $data;
        $this->Header = $this->TableHead;       // 作成済みのヘッダリストを使う
        APPDEBUG::MSG(2, $this->record_max,"record_max");
    }
//==================================================================================================
// レコードの取得
    public function fetchRecord() {
        return ($this->fields = $this->dbDriver->fetchDB());
    }
//==================================================================================================
// レコードの更新
	public function UpdateRecord($num,$row) {
		APPDEBUG::MSG(11, $row);
        // 更新しないカラムを保護する
//        $this->getRecordByKey($num);
        // 更新内容で書き換える
        $this->fields = array();
        foreach($row as $key => $val) {
            $xkey = (isset($this->PostRenames[$key])) ? $xkey = $this->PostRenames[$key] : $key;
            // フィールドキーが存在するものだけ書き換える
            if(array_key_exists($xkey,$this->dbDriver->columns)) $this->fields[$xkey] = $val;
        }
		APPDEBUG::MSG(6, $this->fields);
		$this->dbDriver->replaceRecord([$this->Primary => $num],$this->fields);
	}

}
