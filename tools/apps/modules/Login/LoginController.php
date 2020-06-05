<?php

class LoginController extends AppController {
	public $defaultAction = 'Login';		//  デフォルトのアクション

//==============================================================================
// ログイン要求を処理する
public function LoginAction() {
	$login = MySession::getLoginInfo();
	$newlogin = $this->Model->SetLoginInfo(MySession::$PostEnv);
	if(!empty($newlogin)) {
		MySession::SetLogin($newlogin);
		$url = App::$SysVAR['URI'];
		header("Location:{$url}");
	}
    if(!empty($login)) {     // ログイン状態ではない
		// 新しいログインでなければセッションに記憶された情報を書き込む
		$newlogin = $this->Model->SetLoginInfo($login);
	}
	// ログインフォームを表示して終了
	$this->View->ViewTemplate('Login');
}
//==============================================================================
// ログアウト処理
public function LogoutAction() {
	MySession::ClearLogin();		// ログイン情報を接セッションから消去
	$url = App::getAppRoot();
	header("Location:{$url}");
}

}