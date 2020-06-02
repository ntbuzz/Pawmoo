<?php

class IndexController extends AppController {
	public $defaultAction = 'List';		//  デフォルトのアクション
	public $disableAction = [ 'Page', 'Find' ];	// 無視するアクション

//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================
//===============================================================================
// ログイン要求が必要ならTRUEを返す
public function Login() {
// ログイン情報がセッションに記録されているか確認
	if(empty($_SESSION['Login'])) {
		// ログイン情報がなくてログインPOSTされてきたなら
		if(!empty(MySession::$PostEnv['Login'])) {
			$LoginUser = array();
			foreach(['user','password','email'] as $val) {
				$LoginUser[$val] = MySession::$PostEnv[$val];
				unset(MySession::$PostEnv[$val]);
			}
			unset(MySession::$PostEnv['Login']);
			// isValidUser($Login)
			if(!empty($LoginUser['user'])) {
				// パスワードは暗号化して保存
				$LoginUser['password'] = openssl_encrypt($LoginUser['password'], 'AES-128-CBC', '_minimvc_biscuit');
				// 復号化は
				//$LoginUser['password'] = openssl_decrypt($LoginUser['password'], 'AES-128-CBC', '_minimvc_biscuit');
				$_SESSION['Login'] = $LoginUser;
				debug_dump(0, ["セットセッション" => $_SESSION]);
				return FALSE;
			}
		}
	} else {
		$LoginUser = $_SESSION['Login'];
		if(empty($LoginUser['user'])) {
			unset($_SESSION['Login']);
		} else return FALSE;
	}
	debug_dump(0, [
		"セッション2" => $_SESSION,
		"POST2" => MySession::$PostEnv,
	]);
	// ログインフォームを表示して終了
	$this->View->ViewTemplate('Login');
	return TRUE;
}
//===============================================================================
// BIND DATA
public function BindlistAction() {
	$this->View->SetLayout("ListLayout");
	$this->Model->RecordFinder([]);
	$this->View->PutLayout();
}

//===============================================================================
// デフォルトの動作
	public function TestAction() {
	echo "TEST-LAYOUT\n";
		$this->View->SetLayout("TestLayout");
		$this->View->PutLayout();
	}


}
