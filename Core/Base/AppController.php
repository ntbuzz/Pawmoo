<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	AppController: コントローラー処理のコアクラス
 */

class AppController extends AppObject {
	public $defaultAction = 'List';		// デフォルトのアクション
	public $defaultFilter = 'all';		// デフォルトのフィルタ
	public $disableAction = [];			// 禁止する継承元のアクション
	private $my_method;					// active method list on Instance
	protected $needLogin = FALSE;
//==============================================================================
// コンストラクタでビューを生成、モデルはビュークラス内で生成する
	function __construct($owner = NULL){
		parent::__construct($owner);
		if(empty(App::$Filter)) App::$Filter = $this->defaultFilter;	// フィルタが無ければデフォルトをセット
		$model = "{$this->ModuleName}Model";
		if(!class_exists($model)) $model = 'AppModel';	// クラスがなければ基底クラスで代用
		$this->Model = new $model($this);			// データアクセスモデルクラス
		// Model側の construct 中にはデッドロックが発生し、呼び出せないのでコントローラ側で処理してやる
		$this->Model->RelationSetup();				// リレーション情報を実テーブル名とロケール名に置換
		$this->LocalePrefix = $this->Model->LocalePrefix;	// 言語プレフィクスをオーバーライド
		$view = "{$this->ModuleName}View";		// ローカルビューが存在するなら使う
		if(!class_exists($view)) $view = 'AppView';	// クラスがなければ基底クラスで代用
		$this->View = new $view($this);			// ビュークラス
		$this->__InitClass();                       // クラス固有の初期化メソッド
		// filter of '*Action' method
		$map_conv = function($nm) { return (substr_compare($nm,'Action',-6) === 0) ? substr($nm,0,-6):''; };
		// mekae active method list
		$en = $this->defaultAction;		// default Action must be ENABLED
		if(is_scalar($this->disableAction)) {
			$except = array_filter( array_map( $map_conv,get_class_methods('AppController')),
						function($v) use ($en) { return !empty($v) && ($en !== $v);});
		} else $except = $this->disableAction;
		// Instance method
		$this->my_method = array_filter( 		// $except array filter
							array_filter(		// *Action method pickup filter
								array_map( $map_conv,get_class_methods($this)),'strlen'),
								function($v) use ($except) {
									return !in_array($v,$except);
								});
		debug_log(FALSE, [ 'MY METHOD' => $this->my_method ]);
	}
//==============================================================================
// 後始末の処理
	function __TerminateApp() {
		$this->View->__TerminateView();
	}
//==============================================================================
// check active METHOD
function is_enable_action($action) {
	if(in_array($action,$this->my_method)) return TRUE;	// exist ENABLED List
	return FALSE;	// diable ActionMethod
}
//==============================================================================
// authorised login mode, if need view LOGIN form, return FALSE
function is_authorised() {
	return TRUE;
}
//==============================================================================
// View Helperクラスへの値セット
public function ViewSet($arr) {
	$this->View->Helper->SetData($arr);
}
//==============================================================================
// View HelperクラスへのPOST変数セット
public function ImportSession() {
	$this->View->Helper->SetData(MySession::$PostEnv);
}
//==============================================================================
// ページネーションのセットアップ
public function PageSetup($pgsz = 0) {
	// 数字パラメータのみを抽出して数値変換する
	$Params = array_map(function($v) {return (empty($v)) ? 0 : intval($v);}, 
			array_values(array_filter(App::$Params, function($vv) { return empty($vv) || is_numeric($vv);})));
	list($num,$size) = $Params;
	if($size === 0) {
		if($pgsz > 0) $size = $pgsz;
		else {
			$size = (isset(MySession::$PostEnv['PageSize'])) ? MySession::$PostEnv['PageSize'] : 0;
		}
	} else MySession::$EnvData['PageSize'] = $size;		// 新しいページサイズに置換える
	if($num === 0) $num = 1;
	$this->Model->SetPage($size,$num);
	debug_log(1, ["Param"  => $Params]);
}
//==============================================================================
// 自動ページネーション
public function AutoPaging($cond, $max_count = 100) {
	// 数字パラメータのみを抽出して数値変換する
	$Params = array_map(function($v) {return (empty($v)) ? 0 : intval($v);}, 
			array_values(array_filter(App::$Params, function($vv) { return empty($vv) || is_numeric($vv);})));
	list($num,$size) = $Params;
	if($num > 0) {
		if($size === 0) {
			$size = MySession::$PostEnv['PageSize'];
			if($size === 0) $size = $max_count;
		}
	} else {
		$cnt = $this->Model->getCount($cond);
		if($cnt > $max_count) {
			$num = 1;
			if($size === 0) $size = $max_count;
		}
	}
	if($size > 0) {
		MySession::$EnvData['PageSize'] = $size;		// 新しいページサイズに置換える
		$this->Model->SetPage($size,$num);
		debug_log(1, ["Param"  => $Params]);
	}
}
//==============================================================================
// デフォルトの動作
public function ListAction() {
	$this->Model->RecordFinder([]);
	$this->View->PutLayout();
}
//==============================================================================
// ページング処理
public function PageAction() {
	$this->PageSetup();
	$this->ListAction();
}
//==============================================================================
// 検索
// find/カラム名/検索値
public function FindAction() {
	if(App::$ParamCount > 1 ) {
		$row = array(App::$Filter => "={App::$Params[0]}");
	} else {
		$row = array();
	}
	$this->Model->RecordFinder($row);
	$this->View->PutLayout();
}
//==============================================================================
// ビュー
public function ViewAction() {
	$num = App::$Params[0];
	$this->Model->getRecordValue($num);
	$this->Model->GetValueList();
	$this->View->ViewTemplate('ContentView');
}
//==============================================================================
// PDFを作成する
public function MakepdfAction() {
	$num = App::$Params[0];
	$this->Model->GetRecord($num);
	$this->View->ViewTemplate('MakePDF');
}
//==============================================================================
// 更新
public function UpdateAction() {
	$num = App::$Params[0];
	$this->Model->UpdateRecord($num,MySession::$PostEnv);
	header('Location:' . App::Get_AppRoot(strtolower($this->ModuleName)) . '/list/' . $num );
}

//==============================================================================
// デバッグダンプ
public function DumpAction() {
	debug_log(110,MySession::$PostEnv);
}

}
