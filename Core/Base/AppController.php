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

//==============================================================================
// コンストラクタでビューを生成、モデルはビュークラス内で生成する
	function __construct($owner = NULL){
		parent::__construct($owner);
		if(empty(App::$Filter)) App::$Filter = $this->defaultFilter;	// フィルタが無ければデフォルトをセット
		$model = "{$this->ModuleName}Model";
		if(!class_exists($model)) $model = 'AppModel';	// クラスがなければ基底クラスで代用
		$this->Model = new $model($this);			// データアクセスモデルクラス
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
		debug_dump(0, [ 'MY METHOD' => $this->my_method ]);
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
public function PageSetup() {
	$num = App::$Params[0];
	$size= App::$Params[1];
	if($size == 0) {
		$size = (isset(MySession::$PostEnv['PageSize'])) ? MySession::$PostEnv['PageSize'] : 15;
	} else MySession::$EnvData['PageSize'] = $size;		// 新しいページサイズに置換える
	if($num == 0) $num = 1;
	// 自分とヘルパーのパラメータを書き換える
	App::$Params[0] =  $num;
	App::$Params[1] =  $size;
	$this->Model->SetPage($size,$num);
	APPDEBUG::DebugDump(2, [
		'ページャーパラメータ' => [
			"App" 		=> App::$Params,
		],
	]);
}
//==============================================================================
// デフォルトの動作
public function ListAction() {
	APPDEBUG::MSG(12,":List");
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
	APPDEBUG::MSG(12,":Find");
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
	APPDEBUG::MSG(12,":View");
	try {
		$num = App::$Params[0];
		$this->Model->GetRecord($num);
		$this->View->ViewTemplate('ContentView');
	} catch (Exception $e) {

	}
}
//==============================================================================
// PDFを作成する
public function MakepdfAction() {
	try {
		$num = App::$Params[0];
		$this->Model->GetRecord($num);
		$this->View->ViewTemplate('MakePDF');
	} catch (Exception $e) {
	}
}
//==============================================================================
// 更新
public function UpdateAction() {
	try {
		$num = App::$Params[0];
//		MySession::Dump();
		$this->Model->UpdateRecord($num,MySession::$PostEnv);
//		echo ('Location:' . App::Get_AppRoot(strtolower($this->ModuleName)) . '/list/' . $num );exit;
		header('Location:' . App::Get_AppRoot(strtolower($this->ModuleName)) . '/list/' . $num );
	} catch (Exception $e) {

	}
}

//==============================================================================
// デバッグダンプ
public function DumpAction() {
	APPDEBUG::MSG(12,MySession::$PostEnv);
}

}
