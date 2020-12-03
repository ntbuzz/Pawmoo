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
		$model = "{$this->ModuleName}Model";
		if(!class_exists($model)) $model = 'AppModel';	// クラスがなければ基底クラスで代用
		$this->Model = new $model($this);			// データアクセスモデルクラス
		$view = "{$this->ModuleName}View";		// ローカルビューが存在するなら使う
		if(!class_exists($view)) $view = 'AppView';	// クラスがなければ基底クラスで代用
		$this->View = new $view($this);			// ビュークラス
		$this->Helper = $this->View->Helper;		// ヘルパークラスへのショートカット
		if(empty(App::$Filter)) App::$Filter = $this->defaultFilter;	// フィルタが無ければデフォルトをセット
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
		$this->__InitClass();                       // クラス固有の初期化メソッド
	}
//==============================================================================
// クラス変数の初期化
	protected function __InitClass() {
		// Model側の construct 中にはデッドロックが発生し、呼び出せないのでコントローラ側で処理してやる
		$this->Model->RelationSetup();				// リレーション情報を実テーブル名とロケール名に置換
		$this->LocalePrefix = $this->Model->LocalePrefix;	// 言語プレフィクスをオーバーライド
		parent::__InitClass();                       // 継承元の初期化メソッド
	}
//==============================================================================
// 後始末の処理
public function __TerminateApp() {
	$this->View->__TerminateView();
}
//==============================================================================
// check active METHOD
public function is_enable_action($action) {
	if(in_array($action,$this->my_method)) return TRUE;	// exist ENABLED List
	return FALSE;	// diable ActionMethod
}
//==============================================================================
// authorised login mode, if need view LOGIN form, return FALSE
public function is_authorised() {
	if($this->needLogin) {
		if(CLI_DEBUG) {
			$this->Login->defaultUser();
			return TRUE;
		}
		$login_key = isset($this->Login->LoginID)?$this->Login->LoginID:'login-user';
		// new login request POST check
		$data = $this->Login->is_validLogin(MySession::$ReqData);
		if($data === NULL) {		// non-request NEW LOGIN POST
			// check ALREADY LOGIN information if EXIST
			if($this->Login->error_type === NULL) {		// NO-POST LOGIN
				$userid = MySession::get_LoginValue($login_key);    // already Login check, IN SESSION
				$data = $this->Login->is_validUser($userid);		// is_enabled account
				if($data !== NULL) return TRUE;		// login OK
			}
			$msg = $this->__('.Login');
			$err_msg = $this->Login->error_type;
			page_response('app-999.php',$msg,$msg,$err_msg);     // LOGIN PAGE Response
		} else {
			list($userid,$lang) = $data;
			if(!empty($lang)) {
				MySession::set_LoginValue([$login_key => $userid,'LANG'=>$lang]);
				LangUI::SwitchLangs($lang);
				$this->Model->ResetSchema();
				debug_log(DBMSG_SYSTEM,['Language SWITCH'=>$lang]);
			}
		}
		debug_log(FALSE, [
			"SESSION" => $_SESSION,
			"ENVDATA" => MySession::$EnvData,
			"POSTENV" => MySession::$ReqData,
		]);
	};
	return TRUE;
}
//==============================================================================
// ログアウト処理
public function LogoutAction() {
	MySession::setup_Login(NULL);
	$url = App::Get_SysRoot('index.html');
	if(CLI_DEBUG) echo "Location:{$url}\n";
	else header("Location:{$url}");
}
//==============================================================================
// View Helperクラスへの値セット
public function SetHelperProps($arr) {
	$this->Helper->setProperty($arr);
}
//==============================================================================
// View HelperクラスへのPOST変数セット
public function ImportHelpProperty(...$keys) {
	foreach($keys as $key) {
		$this->Helper->$key = MySession::$ReqData[$key];
	}
}
//==============================================================================
// 自動ページネーション
public function AutoPaging($cond, $max_count = 100) {
	// 数字パラメータのみを抽出して数値変換する
//	$Params = array_map(function($v) {return (empty($v)) ? 0 : intval($v);}, 
//			array_values(array_filter(App::$Params, function($vv) { return empty($vv) || is_numeric($vv);})));
	list($num,$size) = App::$Params;
	$cnt = $this->Model->getCount($cond);
	if($num > 0) {
		if($size === 0) {
			$size = (array_key_exists('PageSize',MySession::$EnvData)) ? MySession::$EnvData['PageSize']:0;
			if($size === 0) $size = $max_count;
			if($cnt < $max_count) $size = 0;
		}
	} else {
		if($cnt > $max_count) {
			$num = 1;
			if($size === 0) $size = $max_count;
		}
	}
	if($size > 0) {
		MySession::$EnvData['PageSize'] = $size;		// 新しいページサイズに置換える
		$this->Model->SetPage($size,$num);
		debug_log(DBMSG_SYSTEM, ["Param"  => App::$Params]);
	}
}
//==============================================================================
// 自動ページネーションと検索実行
public function PagingFinder($cond, $max_count=100,$filter=[],$sort=[]) {
//	debug_log(-11, ["cond"  => $cond, "count" => $max_count]);
	$this->AutoPaging($cond, $max_count);
	$this->Model->RecordFinder(NULL,$filter,$sort);
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
	$this->AutoPaging([],50);
	$this->ListAction();
}
//==============================================================================
// 検索
// find/カラム名/検索値
public function FindAction() {
	if(!empty(App::$Filter) ) {
		$row = array(App::$Filter => "=".App::$Params[0]);
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
	$this->Model->UpdateRecord($num,MySession::$ReqData);
	header('Location:' . App::Get_AppRoot(strtolower($this->ModuleName)) . '/list/' . $num );
}

//==============================================================================
// デバッグダンプ
public function DumpAction() {
	debug_log(110,MySession::$ReqData);
}

}
