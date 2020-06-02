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
			$LoginUser = array(
				'UserName' => MySession::$PostEnv['user'],
				'Password' => MySession::$PostEnv['password'],
				'Mail' => MySession::$PostEnv['email'],
			);
			// isValidUser($Login)
			if(!empty($LoginUser['UserName'])) {
				$_SESSION['Login'] = $LoginUser;
				debug_dump(0, ["セットセッション" => $_SESSION]);
				return FALSE;
			}
		}
	} else {
		$LoginUser = $_SESSION['Login'];
		if(empty($LoginUser['UserName'])) {
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
