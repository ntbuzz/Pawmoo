<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 * 	AppController: Controller Processing
 */
class AppController extends AppObject {
	public $defaultAction = 'List';		// Omitted URI, Default Action 
	public $disableAction = [];			// Ban Action
	private $my_method;					// active method list on Instance
	protected $needLogin = FALSE;		// Login NEED flag
	protected $aliasAction = [];		// Action Method Alias [ Alias => Real,... ]
	protected $discardParams = [];		// Params Discard method
    protected $noLogging = NULL;		// execute log save exception method list ex. [ 'List',... ]
	protected $LoggingMethod = NULL;	// execute log save Model "class.method". ex. 'Access.Logging'
	protected $BypassMethod = '';		// Login bypass method,if NEDD LOGIN(ex.Logout)
	protected $AutoLogin = '';		    // DefaultUser AutoLogin method,if NEDD LOGIN(ex. MailsendAction)
	private $orgModel;					// save original Model Class for Spoofing
//==============================================================================
// constructor: create MODEL, VIEW(with HELPER)
	function __construct($owner = NULL){
		parent::__construct($owner);
		$model = "{$this->ModuleName}Model";
		$model_class = (class_exists($model)) ? $model : 'AppModel';	// file not exists, Use basic Class
		$this->orgModel = $this->Model = ClassManager::Create($model,$model_class,$this);
		$view = "{$this->ModuleName}View";
		$view_class = (class_exists($view)) ? $view : 'AppView';		// file not exists, Use basic Class
		$this->View = ClassManager::Create($view,$view_class,$this);
		$this->Helper = $this->View->Helper;			// Helper class short-cut
		if(empty(App::$Filter) && isset($this->defaultFilter)) {
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
// VIEW CREATE for command-line
public function CreateAction() {
	$this->Model->CreateMyView();
}
//==============================================================================
// check active METHOD
public function is_enable_action($action) {
	if(empty($action)) return false;
	if(	array_key_exists($action,$this->aliasAction) ||		// exists Alias Action
		in_array($action,$this->my_method,true)) return TRUE;	// exist ENABLED List
	return FALSE;	// diable ActionMethod
}
//==============================================================================
// authorised login mode, if need view LOGIN form, return FALSE
public function is_authorised($method) {
	if(defined('LOGIN_CLASS')) {			// enable Login Class
		if($this->needLogin) {
			$model = LOGIN_CLASS . 'Model';
			$Login = $this->$model;
			$login_key = isset($Login->LoginID) ? $Login->LoginID:'login-user';
			$autologin = (is_array($this->AutoLogin)) ? $this->AutoLogin : [$this->AutoLogin];
			$bypass_method = (is_array($this->BypassMethod)) ? $this->BypassMethod : [$this->BypassMethod];
			$bypass_method[] = 'Logout';	// must be append LogoutAction
			$pass_check = false;	// NO password check.
			if(in_array($method,$autologin) || CLI_DEBUG) {
				$data = $Login->defaultUser();		// auto-login user
				if(isset(App::$Post['login'])) $data = array_override($data,App::$Post);
			} else if(isset(App::$Post['login'])) {	// Distinction Normal Login POST
				$data = App::$Post;
				$pass_check = true;
			} else {	// check already LOGIN
				$login = MySession::get_LoginValue([$login_key,'LANG','REGION']);
				$data = array_combine([$login_key,'language','region'],$login);
			}
			$udata = $Login->is_validLoginUser($data,$pass_check);
			if($udata === false) {	// fail valid user => not login, or unknown user
				if(in_array($method,$bypass_method)) {
					// not login, and this method is BYPASS
					$udata = [NULL,DEFAULT_LANG,DEFAULT_REGION];
				} else {
					$userid = (isset($data[$login_key])) ? $data[$login_key] : '';
					$login_page = (defined('LOGIN_PAGE')) ? LOGIN_PAGE : 'app-login.php';
					page_response($login_page,$Login->retryMessages($userid));
					// NEVER RETURN HERE!
					return FALSE;
				}
			}
		}
		// if(CLI_DEBUG || in_array($method,$autologin)) {
		// 	$data = $Login->defaultUser();		// auto-login user
		// 	if(isset(App::$Post['login'])) $data = array_override($data,App::$Post);
		// } else if(isset(App::$Post['login'])) {	// Distinction Normal POST
		// 	$data = App::$Post;
		// 	$pass_check = true;
		// }
		// if($udata === false) {	// fail valid user => not login, or unknown user
		// 	// NEED-LOGIN and not BYPASS method
		// 	if($this->needLogin && !in_array($method,$bypass_method)) {
		// 		$userid = (isset($data[$login_key])) ? $data[$login_key] : '';
		// 		$login_page = (defined('LOGIN_PAGE')) ? LOGIN_PAGE : 'app-login.php';
		// 		page_response($login_page,$Login->retryMessages($userid));
		// 		// LOGIN PAGE Response, NO returned HERE!
		// 		return FALSE;
		// 	}
		// 	// no need login and not login
		// 	$udata = [NULL,DEFAULT_LANG,DEFAULT_REGION];
		// }
		// setup login user language and region
		list($userid,$lang,$region) = $udata;
		$login_data = [
			'LANG' => $lang,
			'REGION' => $region,
		];
		if(!empty($userid)) $login_data[$login_key] = $userid;
		MySession::set_LoginValue($login_data);
		LockDB::SetOwner($userid);
	}
	return TRUE;
}
//==============================================================================
// Logout Processing
public function LogoutAction() {
//	MySession::setup_Login(NULL);
	MySession::ClearSession();
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
// Spoofing the Model class in self/View/Helper.
protected function SpoofingViewModel($model) {
	$this->View->Model = $this->Helper->Model = $this->Model = $model;
}
//==============================================================================
// Restore Original Model Class
protected function SpoofingRestore() {
	$this->View->Model = $this->Helper->Model = $this->Model = $this->orgModel;
}
//==============================================================================
// execute log output Model call, after $action invoked.
private function exec_Logging($action) {
	$no_log = (in_array($this->noLogging,['*',$action],true)) ||
			(is_array($this->noLogging) && in_array($action,$this->noLogging,true));
	list($model,$method) = fix_explode('.',$this->LoggingMethod,2);
	if(!empty($model) && !$no_log) {
		if(empty($method)) $method = 'Logged';
		$class_name = "{$model}Model";
		if(class_exists($class_name)) $this->$model->$method($this,$action);
	}
}
//==============================================================================
// Method Dispatcher before Pre-Process, after Post-Processing
public function ActionDispatch($action) {
	$discard = (is_scalar($this->discardParams)) ? [$this->discardParams] : $this->discardParams;
	if(in_array($action,$discard)) App::CleareParams();
	if($this->ActionPreProcess($action)) {
		if(array_key_exists($action,$this->aliasAction)) {
			$action = $this->aliasAction[$action];
		}
		$method = "{$action}Action";
		if(method_exists($this,$method)) {
			$this->$method();
		} else {
            stderr("Controller Method:'{$method}' not found. Please Create this method.\n");
		}
		$this->ActionPostProcess($action);
		$this->exec_Logging($action);
	}
}
//==============================================================================
// Auto Paging.
// Pager Break: URI != prev-URI
//				Saved-COND === NULL
//				COND not NULL AND Saved-COND != COND
//				COND Record-Count < page-size
// Page-Size param POST, it willbe setup App::$Params[1]
//
public function AutoPaging($cond, $max_count = 100) {
	list($num,$size) = array_intval(App::$Params);		// from URL parameter .../page#/size#
	$cond = re_build_array($cond);
	$uri = App::Get_PagingPath();
	// check SAVED Paging-Param
	$sSize = MySession::getPagingIDs('Size');
	$Page = MySession::getPagingIDs('Setup');
	list($sCond,$sURI,$sEnv) = array_keys_value($Page,['Cond','URI','ENV']);
	list($sQuery,$sPost) = $sEnv;
	if($num === 0) $num = 1;
	$array_same_check = function($pair_arrs) {
		foreach($pair_arrs as $pair) {
			list($base,$save) = $pair;
			$pcomp = (is_array($base) && is_array($save));
			if($pcomp) $pcomp = (array_intersect($save,$base) === $base);
			if(!$pcomp) return false;
		}
		return true;
	};
	$same_check = $array_same_check([
		[ $cond,		$sCond],
		[ App::$Post,	$sPost],
		[ App::$Query,	$sQuery],
	]);
	if($uri === $sURI  && $same_check) {
		$cond = $sCond;				// repeat by saved condition
		App::$Query = $sQuery;
		App::$Post = $sPost;
		if($size === 0) $size = intval($sSize);
	} else {
		$Page['Cond'] = $cond;
		$Page['URI'] = $uri;
		$Page['ENV'] = [App::$Query,App::$Post];
	}
	if($size === 0) $size = $max_count;
	if($size !== intval($sSize)) $num = 1;	// different size must be jump to Page-1
	$cnt = $this->Model->getCount($cond);
	if($cnt < $size )  $Page = NULL;	// no-NEED Paging
	else {
		$last = ($num - 1) * $size;		// check LAST-Page#
		if($last  > $cnt) $num = 1;
		App::ChangeParams([$num,0]);
		$this->Model->SetPage($size,$num);
	}
	MySession::setPagingIDs('Size',$size);
	MySession::setPagingIDs('Setup',$Page);
	return $cond;
}
//==============================================================================
// Auto Paging, and Model Finder
public function PagingFinder($cond, $max_count=100,$filter=[],$sort=[]) {
	$cond = $this->AutoPaging($cond, $max_count);
	$this->Model->RecordFinder($cond,$filter,$sort);
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
	$cond = $this->AutoPaging([], 50);
	$this->Model->RecordFinder($cond,NULL);
	$this->View->PutLayout();
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
// Default View Action (Full Page)
public function ViewAction() {
	$num = App::$Params[0];
	$this->Model->GetRecord($num,TRUE,TRUE);
	$this->View->PutLayout('ContentView');
}
//==============================================================================
// Default Item View Action (Parts of Page)
public function ItemAction() {
	$num = App::$Params[0];
	$this->Model->GetRecord($num,TRUE,TRUE);
	$this->View->ViewTemplate('ItemView');
}
//==============================================================================
// Contents Template Action in AJAX access for like a SPA
// app/(cont)/contents/(templatename)/(rec-number)
public function ContentsAction() {
	$num = App::$Params[0];
	$template = ucfirst(strtolower(App::$Filter));
	if($num > 0) $this->Model->GetRecord($num,TRUE,TRUE);
	$this->View->ViewTemplate("{$template}Parts");
}
//==============================================================================
// Delete Record
public function DeleteAction() {
	$num = App::$Params[0];
	$this->Model->DeleteRecord($num);
	$this->set_location(App::$Referer);
}
//==============================================================================
// location echoback
protected function set_location($url) {
	if(empty($url)) $url = App::Get_AppRoot($this->ModuleName,TRUE) . '/list/'.App::$Filter;
	header('Location:' . $url);
}
//==============================================================================
// Default Add Record Action
public function AddAction() {
	$url = App::$Referer;
	$num = $this->Model->AddRecord(App::$Post);
	MySession::setEnvVariables(['RecordNo' => $num]);
	$this->set_location(App::$Referer);
}
//==============================================================================
// Default Update Action
public function UpdateAction() {
	$num = App::$Params[0];
	MySession::setEnvVariables(['RecordNo' => $num]);
	$this->Model->UpdateRecord($num,App::$Post);
	$this->set_location(App::$Referer);
}
//==============================================================================
// Default Copy Record Action
public function CopyAction() {
	$num = App::$Params[0];
	$num = $this->Model->CopyRecord($num,App::$Post);
	MySession::setEnvVariables(['RecordNo' => $num]);
	$this->set_location(App::$Referer);
}
//==============================================================================
// Default PDF Convert Action
public function MakepdfAction() {
	$num = App::$Params[0];
	$this->Model->GetRecord($num,TRUE);
	$this->View->ViewTemplate('MakePDF');
}
//==============================================================================
// Language Switch Action
public function LanguageAction() {
	list($lang,$region,$refer) = array_keys_value(App::$Post,['lang','region','referer'],[DEFAULT_LANG,DEFAULT_REGION,App::$Referer]);
	$login_data = [
		'LANG' => $lang,
//		'REGION' => $region,
	];
	MySession::set_LoginValue($login_data);
	$this->set_location($refer);
}

}
