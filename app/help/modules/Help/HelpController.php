<?php
/*
	アプリケーショントップフォルダのリクエストを処理するモジュール
	モジュール名はアプリ名と同じにする
*/
class HelpController extends AppController {
	public $defaultAction = 'List';		//  デフォルトのアクション
	public $disableAction = [ 'Page','View','Makepdf','Update','Dump' ];	// 無視するアクション
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================
//	クラス初期化処理
	protected function ClassInit() {
//		$this->SetEvent('Model.OnGetRecord',$this->View->Helper,"echo_dump");
	}
//===============================================================================
// デフォルトの動作
public function ListAction() {
	$this->Model->MakeOutline();
	APPDEBUG::arraydump(5, [
		'レコード' => $this->Model->Records,
		'アウトライン' => $this->Model->outline,
	]);
	$this->ViewSet(['PartData' => [],'ChapterData' => []]);
	$this->ViewSet(['Part' => 0,'Chapter' => 0,'Section' => [], 'Tabmenu' => 0]);
	$this->View->PutLayout();
}
//===============================================================================
// キーワード検索
// PostEnv
//	q=クエリ文字列
public function FindAction() {
	$qstr = MySession::$PostEnv['q'];
	if(empty($qstr)) {
		echo $this->_('.NoKeyword');
		return;
	}
	$this->ViewSet(['QUERY' => $qstr]);
	$this->Model->SearchOutline($qstr);
	$this->View->ViewTemplate('FindResult');
}


}
