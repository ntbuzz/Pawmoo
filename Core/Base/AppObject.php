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
    protected $autoload = TRUE;

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
        foreach($database as $key => $val) {
            $this->$key = $val;
        }
    }
//==============================================================================
// クラスの動的クラスプロパティを生成(オードロード)
public function __get($PropName) {
    if($this->autoload === FALSE) return NULL;
    if(isset($this->$PropName)) return $this->$PropName;
    $class_list = array(
        'Class'     => 0,
        'Model'     => 1,
        'Helper'    => 2,
        'Controller'=> 2,
    );
    if(array_key_exists($PropName,$class_list)) {
        $mod_name = $this->ModuleName;
        $cls_name = $PropName;
    } else  {
        // クラス名を取り出す
        preg_match('/[A-Z][a-z]+?$/', $PropName, $matches);
        $cls_name = $matches[0];
        if(empty($cls_name)) {
            throw new Exception("Bad name request for SubClass '{$PropName}'");
        }
        if(array_key_exists($cls_name,$class_list)) {
            $mod_name = str_replace($cls_name,'',$PropName);
        } else {
            $mod_name = $PropName;
            $cls_name = 'Model';
        }
    }
    $prop_name = "{$mod_name}{$cls_name}";
    // ロード済か確認
    if(class_exists($prop_name)) {
        $this->$PropName = new $prop_name($this);
        return $this->$PropName;
    }
    // 格納パスを探索する
    $fldr = array(
        ["Class"],
        ["Models","modules/{$mod_name}"],
        ["modules/{$mod_name}"],
    );
    $path_list = $fldr[$class_list[$cls_name]];
    foreach($path_list as $path) {
        $modfile = App::Get_AppPath("{$path}/{$prop_name}.php");
        if(file_exists($modfile)) {
            if($cls_name === 'Controller') {
                App::LoadModuleFiles($mod_name);    // Controllerの場合はモジュールセットでロードする
                if(class_exists($prop_name)) {      // ロードできたか確かめる
                    $this->$PropName = new $prop_name($this);
                    return $this->$PropName;
                }
            } else {
                require_once($modfile);
                $this->$PropName = new $prop_name($this);
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
