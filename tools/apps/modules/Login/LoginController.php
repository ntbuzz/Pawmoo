<?php

class LoginController extends AppController {
	public $defaultAction = 'Logout';		//  デフォルトのアクション

//==============================================================================
// ログイン要求を処理する
public function LoginAction() {
	// Login POST が発生しているか
	$data = $this->Model->is_validLogin(MySession::$ReqData);
	if($data === NULL) {
		// ログインフォームを表示して終了
		$this->View->ViewTemplate('Login');
		return FALSE;
	}
	echo MySession::get_envIDs('sysVAR.REFERER');
	return FALSE;
}
//==============================================================================
// ログアウト処理
public function LogoutAction() {
	MySession::setup_Login(NULL);		// ログイン情報を接セッションから消去
	$url = App::Get_AppRoot();
	header("Location:{$url}index.html");
}

}