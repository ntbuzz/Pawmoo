<?php
/*
    Loginが必要なアプリケーションで使用する
    データベースの定義は LoginModel に実装する
    データベーステーブルには以下の列が必須
        ユーザーID(任意)    // LoginID プロパティで指定可
        'password'          // パスワード openssl_encrypt() で暗号化したもの
        'language'          // ユーザー言語(ja;en)
*/
abstract class LoginClass extends AppModel {
    static public $LoginUser;
    public $error_type;
//==============================================================================
//	Class-Initialize after __construct()
	public function class_startup() {
        parent::class_initialize();
		if(isset($this->LoginID)) {
			list($uid,$pwid) = fix_explode(':',$this->LoginID,2);
			$this->LoginID = $uid;
			$this->PasswdID = empty($pid) ? 'password' : $pid;
		}
	}
//==============================================================================
// Default User Info for CLI Debug
public function defaultUser() {
	$udata = [
		'userid'	=>	'guest',
		'language'	=>	'ja',
		'region'	=>	'jp',
	];
	static::$LoginUser = $udata;
	return $udata;
}
//==============================================================================
// ログインエラー時のメッセージ配列(app-login.phpで参照)
//  メッセージをカスタマイズする時はこのメソッドをoverrideする
public function retryMessages($userid) {
	return [
		'page_title'	=> $this->__('Login.LoginPage'),
		'msg_title'		=> $this->__('Login.LoginTitle'),
		'user_title'	=> $this->__('Login.UserName'),
		'pass_title'	=> $this->__('Login.Password'),
		'send_button'	=> $this->__('Login.Submit'),
		'reset_button'	=> $this->__('Login.Reset'),
		'referer'		=> App::$SysVAR['REQURI'],
		'msg_body'		=> $this->error_type,
		'login_user'	=> $userid,
	];
}
//==============================================================================
// password alive period limitation
public function is_passwd_limitation($login) {
	return false;				// default permanent period
}
//==============================================================================
// password alive period limitation
public function set_passwd_limitation(&$login,$limit) {
	return false;				// no limit
}
//==============================================================================
// last login time-stamp
public function set_last_login($userid) {
	return false;				// no logging
}
//==============================================================================
// Recieved LOGIN POST FORM, do accept USER LOGIN correct
//	Success: [ID,LANG,REGION]
//	NO-POST: NULL
//	VALID FAIL: FALSE
public function is_validLoginUser($values,$pass_check) {
    $Login = [];
	// FORM POST name, renamed to Database column name
    foreach($values as $key => $val) {
        $xkey = $this->get_post_field($key);
        if(array_key_exists($xkey,$this->Schema)) {     // pickup exists field name
            list($alt,$disp,$flag) = $this->Schema[$xkey];   // need encrypt password
            $dval = ($flag === -1) ? passwd_encrypt($val) : $val;
            if(!empty($dval)) $Login[$xkey] = $dval;    // accepta NULL value
	}
}
    $this->error_type = $this->__('Login.NeedLogin');
	list($userid,$passwd) = array_keys_value($Login,[$this->LoginID,'password']);
	// exist Login-ID
    if(empty($userid)) return FALSE;
    $this->error_type = $this->__('Login.UnknownUser').": '{$userid}'";
    $login_data = $this->getRecordBy($this->LoginID,$userid);
    if($login_data === false) return false;	// not-exist user
	if($pass_check) {
		    $this->error_type = $this->__('Login.PassError');
		if($passwd !== $login_data['password']) return false;
			// limitation check
		if($this->is_passwd_limitation($login_data)) {
			    $this->error_type = $this->__('Login.PassLimit');
				return false;
			}
        }
	// user-data value override from request value
	$data = array_override($login_data,$Login);
	list($lang,$region) = array_keys_value($data,['language','region'],[DEFAULT_LANG,DEFAULT_REGION]);
	// RELOAD user-data when user-locale not match current language
		if($lang !== LangUI::$LocaleName) {
			// Reload UserDataa when User Locale not match current Locale
		LangUI::SwitchLangs($lang,$region);
		$data = $this->getRecordBy($this->LoginID,$userid);
		$data = array_override($data,$Login);
		}
	$udata = [$userid,$lang,$region];
	unset($login_data['password']);	// security keep
	static::$LoginUser = $login_data;
		$this->set_last_login($userid);
        return $udata;
}
//==============================================================================
// Recieved LOGIN POST FORM, do accept USER LOGIN correct
private function update_password($userid,$passwd,$limit = true) {
    if(array_key_exists($this->PasswdID,$this->Schema)) {     	// exist password field
    	$data = $this->getRecordBy($this->LoginID,$userid);		// check userid
		if(!empty($data)) {
			list($alt,$disp,$flag) = $this->Schema[$this->PasswdID];   // need encrypt password
			$dval = ($flag === -1) ? passwd_encrypt($passwd) : $passwd;
			$id = $data[$this->Primary];
			$row[$this->PasswdID] = $dval;
			$this->set_passwd_limitation($row,$limit);
			$this->UpdateRecord($id,$row);
			return !empty($this->RecData);		// update success check
		}
    }
	return false;
}
//==============================================================================
// RESET Password with limitation
public function reset_password($userid,$maxlen = 8) {
	$passwd = passwd_random($maxlen);
	if($this->update_password($userid,$passwd)) return $passwd;
	return '';
}
//==============================================================================
// Permanent Password Setup
public function password_update($userid,$passwd) {
	return ($this->update_password($userid,$passwd,false));
}

}
