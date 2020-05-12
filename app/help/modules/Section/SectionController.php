<?php

class SectionController extends AppController {
	public $defaultAction = 'List';		//  デフォルトのアクション
	public $disableAction = [ 'Page', 'Find' ];	// 無視するアクション

//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================
//	クラス初期化処理
	protected function ClassInit() {
	}
//===============================================================================
// 必要なメソッドアクションをオーバーライド定義する
//  AppControllerにはごく基本的な
//  List/Page/Find/View/Update アクションのみが定義されている
//===============================================================================
// データを更新してREFERERに戻す
public function AddAction() {
	$this->Model->AddRecord(MySession::$PostEnv);
	echo App::$Referer;
}
//===============================================================================
// データを更新してREFERERに戻す
public function UpdateAction() {
	$num = App::$Params[0];
	dump_debug(DEBUG_DUMP_NONE, "Update", [
		'番号' => $num,
		'POST' => $_REQUEST,
		'データ' => MySession::$PostEnv,
		'タブセット' => MySession::$PostEnv['TabSelect'],
	]);
	$this->Model->UpdateRecord($num,MySession::$PostEnv);
	echo App::$Referer;
}
//===============================================================================
// レコードを削除して、REFERERに戻す
public function DeleteAction() {
	$num = App::$Params[0];
	$this->Model->deleteRecordset($num);
	echo App::$Referer;
}

}
