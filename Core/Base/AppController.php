<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 * 	AppController: Controller Processing
 */
class AppController extends AppObject {
	public $defaultAction = 'List';		// Omitted URI, Default Action 
	public $defaultFilter = 'all';		// Default Filter
	public $disableAction = [];			// Ban Action
	private $my_method;					// active method list on Instance
	protected $needLogin = FALSE;		// Login NEED flag
	protected $aliasAction = [			// Action Method Alias
//			'List' => 'View',			// ListAction called ViewAction
//			'View' => 'List',			// ViewAction called ListAction
	];
//==============================================================================
// constructor: create MODEL, VIEW(with HELPER)
	function __construct($owner = NULL){
		parent::__construct($owner);
		$model = "{$this->ModuleName}Model";
		$model_class = (class_exists($model)) ? $model : 'AppModel';	// file not exists, Use basic Class
		$this->Model = ClassManager::Create($model,$model_class,$this);
		$view = "{$this->ModuleName}View";
		$view_class = (class_exists($view)) ? $view : 'AppView';		// file not exists, Use basic Class
		$this->View = ClassManager::Create($view,$view_class,$this);
		$this->Helper = $this->View->Helper;			// Helper class short-cut
		if(empty(App::$Filter)) {
			App::$Filters[0] = App::$Filter = $this->defaultFilter;
		}
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
									return !in_array($v,$except,true);
								});
	}
//==============================================================================
// Initialized Class Property
	protected function class_initialize() {
		// Deadlock occurs when a AppModel construtor, Controller runs on behalf of AppModel.
		$this->LocalePrefix = $this->Model->LocalePrefix;
		parent::class_initialize();                       // Call Initialize method chain.
	}
//==============================================================================
// Terminated Contorller
public function __TerminateApp() {
	$this->View->__TerminateView();
}
//==============================================================================
// check active METHOD
public function is_enable_action($action) {
	if(	array_key_exists($action,$this->aliasAction) ||		// exists Alias Action
		in_array($action,$this->my_method,true)) return TRUE;	// exist ENABLED List
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
			}
			if($data === NULL) {
				$msg = $this->__('.Login');
				$err_msg = $this->Login->error_type;
				page_response('app-999.php',$msg,$msg,$err_msg);     // LOGIN PAGE Response
			}
		} else {
			list($userid,$lang) = $data;
			if(!empty($lang)) {
				MySession::set_LoginValue([$login_key => $userid,'LANG'=>$lang]);
				LangUI::SwitchLangs($lang);
				$this->Model->ResetSchema();
				debug_log(DBMSG_SYSTEM,['Language SWITCH'=>$lang]);
			}
		}
		LockDB::SetOwner($userid);
	};
	return TRUE;
}
//==============================================================================
// Logout Processing
public function LogoutAction() {
	MySession::setup_Login(NULL);
	$url = App::Get_SysRoot('index.html');
	if(CLI_DEBUG) echo "Location:{$url}\n";
	else header("Location:{$url}");
}
//==============================================================================
// Set Property value on Helper Class
public function SetHelperProps($arr) {
	$this->Helper->setProperty($arr);
}
//==============================================================================
// Pre-Processing before Action method invoke
protected function ActionPreProcess($action) {
	return TRUE;
}
//==============================================================================
// Post-Processing after Action method complete
protected function ActionPostProcess($action) {
	return TRUE;
}
//==============================================================================
// Method Dispatcher before Pre-Process, after Post-Processing
public function ActionDispatch($action) {
	if($this->ActionPreProcess($action)) {
		if(array_key_exists($action,$this->aliasAction)) {
			$action = $this->aliasAction[$action];
		}
		$method = "{$action}Action";
		$this->$method();
		$this->ActionPostProcess($action);
	}
}
//==============================================================================
// Auto Paging.
public function AutoPaging($cond, $max_count = 100) {
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
		MySession::$EnvData['PageSize'] = $size;		// remember in SESSION
		$this->Model->SetPage($size,$num);
		debug_log(DBMSG_SYSTEM, ["Param"  => App::$Params]);
	}
}
//==============================================================================
// Auto Paging, and Model Finder
public function PagingFinder($cond, $max_count=100,$filter=[],$sort=[]) {
	$this->AutoPaging($cond, $max_count);
	$this->Model->RecordFinder(NULL,$filter,$sort);
}
//==============================================================================
// Default List Action
public function ListAction() {
	$this->Model->RecordFinder([]);
	$this->View->PutLayout();
}
//==============================================================================
// Default Page Action
public function PageAction() {
	$this->AutoPaging([],50);
	$this->ListAction();
}
//==============================================================================
// Default Find Action
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
// Default View Action
public function ViewAction() {
	$num = App::$Params[0];
	$this->Model->GetRecord($num,TRUE,TRUE);
	$this->View->PutLayout('ContentView');
}
//==============================================================================
// Default Item View Action
public function ItemAction() {
	$num = App::$Params[0];
	$this->Model->GetRecord($num,TRUE,TRUE);
	$this->View->ViewTemplate('ContentView');
}
//==============================================================================
// Contents Template Action in AJAX access for like a SPA
// app/(cont)/contents/(templatename)/(rec-number)
public function ContentsAction() {
	$num = App::$Params[0];
	$template = ucfirst(strtolower(App::$Filter));
	if($nm > 0) $this->Model->GetRecord($num,TRUE,TRUE);
	$this->View->ViewTemplate("{$template}Parts");
}
//==============================================================================
// Default Add Record Action
public function AddAction() {
	$url = App::$Referer;
	$this->Model->AddRecord(MySession::$ReqData);
	if(empty($url)) $url = App::Get_AppRoot($this->ModuleName,TRUE) . '/list/'.App::$Filter;
	header('Location:' . $url);
//	echo App::$Referer;
}
//==============================================================================
// Default Update Action
public function UpdateAction() {
	$num = App::$Params[0];
	$url = App::$Referer;
	MySession::setVariables(TRUE,['RecordNo' => $num]);
	$this->Model->UpdateRecord($num,MySession::$ReqData);
	if(empty($url)) $url = App::Get_AppRoot($this->ModuleName,TRUE) . '/list/'.App::$Filter;
	header('Location:' . $url);
//	echo App::$Referer;
}
//==============================================================================
// Default PDF Convert Action
public function MakepdfAction() {
	$num = App::$Params[0];
	$this->Model->GetRecord($num,TRUE);
	$this->View->ViewTemplate('MakePDF');
}

}
