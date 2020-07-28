<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  AppObject:    全てのクラスのベースになるオブジェクトクラス
 *                継承情報とクラス情報を保持する
 */
//==============================================================================
class AppObject {
    protected $AOwner;      // 所有オブジェクト
    protected $ClassType;   // オブジェクトの所属クラス(Controller, Model, View. Helper)
    protected $ModuleName;  // オブジェクトの名前
    protected $ClassName;   // クラス名
    protected $LocalePrefix;    // 言語プレフィクス
    protected $autoload = FALSE;

//==============================================================================
//	コンストラクタ：　テーブル名
	function __construct($owner) {
        $this->AOwner = $owner;
        $this->ClassName = get_class($this);
        $this->ModuleName = preg_replace("/[A-Z][a-z]+?$/",'',$this->ClassName);     // モジュール名
        $this->ClassType = substr($this->ClassName,strlen($this->ModuleName));      // クラスタイプ
        // 基底クラスでクリエイトされてきたら親のモジュール名を流用する
        if($this->ClassName == "App{$this->ClassType}") $this->ModuleName = $owner->ModuleName;
        $this->LocalePrefix = ($owner===NULL) ? $this->ModuleName : $owner->LocalePrefix;	// オーナーの言語プレフィクスを引継ぐ
	}
//==============================================================================
//	デストラクタ
	function __destruct() {
    }
//==============================================================================
// 初期化
	protected function __InitClass() {
        $this->ClassInit();                       // クラス固有の追加初期化メソッド
	}
//==============================================================================
//	クラス初期化処理
//  必要ならサブクラスでオーバーライドする
    protected function ClassInit() {
    }
//==============================================================================
//	イベント関数をセット
//==============================================================================
    protected function setEvent($event,$Instance,$method) {
        list($class,$ev) = explode('.',$event);         // モジュール指定があればモジュールインスタンスにセットする
        if(empty($ev)) {
            $this->$event = array($Instance,$method);
        } else {
            $this->$class->setEvent($ev,$Instance,$method);
        }
	}
//==============================================================================
// イベント関数が登録されていれば発火させる
//==============================================================================
    public function doEvent($event, $args) {
        if(is_array($this->$event)) {      // コールバックイベント
            list($Instance,$method) = $this->$event;
            if(method_exists($Instance,$method)) {
                $Instance->$method($args);
            }
        }
    }
//==============================================================================
//	プロパティ初期化
    protected function setProperty($database) {
        APPDEBUG::MSG(10,$database);
        foreach($database as $key => $val) {
            $this->$key = $val;
        }
    }
//==============================================================================
// クラスの動的クラスプロパティを生成
public function __get($PropName) {
    if($this->autoload === FALSE) return NULL;
    $fldr = array(
        'Controller'=> [],
        'Helper'    => ["modules/{$PropName}"],
        'Model'     => ["Models","modules/{$PropName}"],
    );
    APPDEBUG::MSG(10, $PropName . " を動的生成します。");
    if(isset($this->$PropName)) return $this->$PropName;
    // Model or View or Helper or Controller を付加する
    preg_match('/[A-Z][a-z]+?$/', $PropName, $matches);
    $class = $matches[0];
    if(!array_key_exists($class,$fldr)) $class = 'Model';
    $props = "{$PropName}{$class}";
    // ロード済か確認
    if(class_exists($props)) {
        $this->$PropName = new $props($this);
        return $this->$PropName;
    }
    if($class === 'Controller') {
        // Controllerの場合はセットでロードする
        App::LoadModuleFiles($PropName);
        // ロードできたか確かめる
        if(class_exists($props)) {
            $this->$PropName = new $props($this);
            return $this->$PropName;
        }
    } else {
        // Models, modules フォルダにファイルがあればロードする
        foreach($fldr[$class] as $model) {
            $modfile = App::Get_AppPath("{$model}/{$props}.php");
            if(file_exists($modfile)) {
                require_once($modfile);
                $this->$PropName = new $props($this);
                return $this->$PropName;
            }
        }
    }
    // 見つからなかった
    throw new Exception("SubClass Create Error for '{$props}'");
}
//==============================================================================
// 言語リソース値を取り出す
// allow_array が TRUE なら値が配列になるものを許可する
public function _($defs, $allow_array = FALSE) {
    return LangUI::get_value($this->LocalePrefix, $defs, $allow_array);
}
protected function __($defs, $allow_array = FALSE) {
    return LangUI::get_value('core', $defs, $allow_array);
}
//==============================================================================
// 言語リソース値から連想配列の要素を取り出す
public function _in($arr,$defs) {
    return LangUI::get_array($arr, $this->LocalePrefix, $defs);          // 言語識別子から排列要素を取得する
}
protected function __in($arr,$defs) {
    return LangUI::get_array($arr, 'core', $defs);          // 言語識別子から排列要素を取得する
}

}
