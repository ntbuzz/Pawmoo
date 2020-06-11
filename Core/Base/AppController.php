<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	AppController: コントローラー処理のコアクラス
 */

class AppController extends AppObject {
	public $defaultAction = 'List';		// デフォルトのアクション
	public $defaultFilter = 'all';		// デフォルトのフィルタ
	public $disableAction = [];			// 禁止する継承元のアクション

//==============================================================================
// コンストラクタでビューを生成、モデルはビュークラス内で生成する
	function __construct($owner = NULL){
		parent::__construct($owner);
		if(App::$Filter == '') App::$Filter = $this->defaultFilter;	// フィルタが無ければデフォルトをセット
		$model = "{$this->ModuleName}Model";
		if(!class_exists($model)) $model = 'AppModel';	// クラスがなければ基底クラスで代用
		$this->Model = new $model($this);			// データアクセスモデルクラス
		$this->LocalePrefix = $this->Model->LocalePrefix;	// 言語プレフィクスをオーバーライド
		$view = "{$this->ModuleName}View";		// ローカルビューが存在するなら使う
		if(!class_exists($view)) $view = 'AppView';	// クラスがなければ基底クラスで代用
		$this->View = new $view($this);			// ビュークラス
		$this->__InitClass();                       // クラス固有の初期化メソッド
	}
//==============================================================================
// 後始末の処理
	function __TerminateApp() {
		$this->View->__TerminateView();
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
		MySession::Dump();
		$this->Model->UpdateRecord($num,MySession::$PostEnv);
		header('Location:' . App::geApptRoot(strtolower($this->ModuleName)) . '/list/' . $num );
	} catch (Exception $e) {

	}
}

//==============================================================================
// デバッグダンプ
public function DumpAction() {
	APPDEBUG::MSG(12,MySession::$PostEnv);
}

}
