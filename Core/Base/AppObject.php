<?php
/* -------------------------------------------------------------
 * Biscuitフレームワーク
 *  AppObject:    全てのクラスのベースになるオブジェクトクラス
 *                継承情報とクラス情報を保持する
 */
//==================================================================================================
class AppObject {
    protected $AOwner;      // 所有オブジェクト
    protected $ClassType;   // オブジェクトの所属クラス(Controller, Model, View. Helper)
    protected $ModuleName;  // オブジェクトの名前
    protected $ClassName;   // クラス名
    protected $LocalePrefix;    // 言語プレフィクス

//==================================================================================================
//	コンストラクタ：　テーブル名
	function __construct($owner) {
        $this->AOwner = $owner;
        $this->ClassName = get_class($this);
        $this->ModuleName = preg_replace("/[A-Z][a-z]+?$/",'',$this->ClassName);     // モジュール名
        $this->ClassType = substr($this->ClassName,strlen($this->ModuleName));      // クラスタイプ
        // 基底クラスでクリエイトされてきたら親のモジュール名を流用する
        if($this->ClassName == "App{$this->ClassType}") $this->ModuleName = $owner->ModuleName;
        $this->LocalePrefix = ($owner===NULL) ? $this->ModuleName : $owner->LocalePrefix;	// オーナーの言語プレフィクスを引継ぐ
//        print_r($this);
	}
//==================================================================================================
//	デストラクタ
	function __destruct() {
    }
//==================================================================================================
// 初期化
	protected function __InitClass() {
        $this->ClassInit();                       // クラス固有の追加初期化メソッド
	}
//==================================================================================================
//	クラス初期化処理
//  必要ならサブクラスでオーバーライドする
    protected function ClassInit() {
    }
//==================================================================================================
//	イベント関数をセット
//==================================================================================================
    protected function SetEvent($event,$Instance,$method) {
        list($class,$ev) = explode('.',$event);         // モジュール指定があればモジュールインスタンスにセットする
//        echo "CLASS:{$class}\nEV:{$ev}\n";
        if(empty($ev)) {
            $this->$event = array($Instance,$method);
        } else {
            $this->$class->SetEvent($ev,$Instance,$method);
        }
	}
//===============================================================================
// イベント関数が登録されていれば発火させる
//==================================================================================================
    public function doEvent($event, $args) {
        if(is_array($this->$event)) {      // コールバックイベント
            list($Instance,$method) = $this->$event;
            if(method_exists($Instance,$method)) {
                echo "Event Fire!!!!!\n";
                $Instance->$method($args);
            }
        }
    }
//==================================================================================================
//	プロパティ初期化
    protected function setProperty($database) {
        APPDEBUG::MSG(10,$database);
        foreach($database as $key => $val) {
            $this->$key = $val;
        }
    }
//==================================================================================================
// 動的クラスプロパティを生成
    protected function addSubclass($PropName) {
        APPDEBUG::MSG(10, $PropName . " を動的生成します。");
        if(isset($this->$PropName)) return $this->$PropName;
        // Model or View or Helper or Controller を付加する
        $props = $PropName . $this->ClassType;
        if(class_exists($props)) {
            $this->$PropName = new $props($this);
    	    return $this->$PropName;
        }
		throw new Exception("SubClass Create Error for '{$props}'");
    }
//===============================================================================
// 言語リソース値を取り出す
// allow_array が TRUE なら値が配列になるものを許可する
    public function _($defs, $allow_array = FALSE) {
        return LangUI::get_value($this->LocalePrefix, $defs, $allow_array);
    }
    protected function __($defs, $allow_array = FALSE) {
        return LangUI::get_value('core', $defs, $allow_array);
    }
//===============================================================================
// 言語リソース値から連想配列の要素を取り出す
    public function _in($arr,$defs) {
        return LangUI::get_array($arr, $this->LocalePrefix, $defs);          // 言語識別子から排列要素を取得する
    }
    protected function __in($arr,$defs) {
        return LangUI::get_array($arr, 'core', $defs);          // 言語識別子から排列要素を取得する
    }
//==================================================================================================
// 実行時間
    public static function RunTime($func,$arg) {
    	$start = getUnixTimeMillSecond();
//        $this->$func($arg);
		$finish = getUnixTimeMillSecond();
		$this->RunTime = array($start,$finish);
    }
//==================================================================================================

}
