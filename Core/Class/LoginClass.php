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
			list($uid,$pwid) = explode(':',"{$this->LoginID}:");
			$this->LoginID = $uid;
			$this->PasswdID = empty($pid) ? 'password' : $pid;
		}
	}
//==============================================================================
//　Default User Info for CLI Debug
public function defaultUser() {
	static::$LoginUser = [
		'userid'	=>	'guest',
		'roll'		=>	'Guest',
		'language'	=>	'ja',
		'full_name'	=>	'Guest User',
		'email'		=>	'no-mail@localhost',
	];
}
//==============================================================================
//　ユーザーIDの妥当性を検証する
public function is_validUser($userid,$passwd = NULL) {
    $this->error_type = $this->__('Login.NeedLogin');
    if(empty($userid)) return NULL;
    $data = $this->getRecordBy($this->LoginID,$userid);
    if($userid === $data[$this->LoginID]) {
        // $passwd != NULL ならここでパスワードチェックをする
        if($passwd !== NULL) {
		    $this->error_type = $this->__('Login.PassError');
            $user_pass = $data['password'];
            if($passwd !== $user_pass) return NULL;
        }
        $this->error_type = '';
		$user_lang = array_filter_values($data,['language','region'],[DEFAULT_LANG,DEFAULT_REGION]);
		list($lang,$region) = array_filter_values(App::$Query,['lang','region'],$user_lang);
		if($lang !== LangUI::$LocaleName) {
			// Reload UserDataa when User Locale not match current Locale
			LangUI::SwitchLangs($lang);
			$this->ResetSchema();
		    $data = $this->getRecordBy($this->LoginID,$userid);
			debug_log(DBMSG_SYSTEM,['Language Switch'=>$lang]);
		}
        static::$LoginUser = $data;
        return [$userid,$lang,$region];
    }
    $this->error_type = $this->__('Login.UnknownUser').": '{$userid}'";
    return NULL;
}
//==============================================================================
// Recieved LOGIN POST FORM, do accept USER LOGIN correct
public function is_validLogin($values) {
    $Login = [];
    foreach($values as $key => $val) {
        // FORM POST name, renamed to Database column name
        $xkey = $this->get_post_field($key);
        if(array_key_exists($xkey,$this->Schema)) {     // pickup exists field name
            list($disp,$flag) = $this->Schema[$xkey];   // need encrypt password
            $dval = ($flag === -1) ? passwd_encrypt($val) : $val;
            $Login[$xkey] = $dval;    // accepta NULL value
        }
    }
    $this->error_type = NULL;
    if(!array_key_exists($this->LoginID,$Login)) return NULL;
	$passwd = $Login[$this->PasswdID];
	if(empty($passwd)) $passwd = '*';
    return $this->is_validUser($Login[$this->LoginID],$passwd);
}
//==============================================================================
// Recieved LOGIN POST FORM, do accept USER LOGIN correct
public function reset_password($userid,$maxlen = 8) {
	$passwd = '';
    if(array_key_exists($this->PasswdID,$this->Schema)) {     	// exist password field
    	$data = $this->getRecordBy($this->LoginID,$userid);		// check userid
		if(!empty($data)) {
			list($disp,$flag) = $this->Schema[$this->PasswdID];   // need encrypt password
			$passwd = passwd_random($maxlen);
			$dval = ($flag === -1) ? passwd_encrypt($passwd) : $passwd;
			$id = $data[$this->Primary];
			$row[$this->PasswdID] = $dval;
			$this->UpdateRecord($id,$row);
		}
    }
	return $passwd;
}

}
