<?php

class IndexController extends AppController {
	public $defaultAction = 'List';		//  デフォルトのアクション
	public $disableAction = [ 'Page', 'Find' ];	// 無視するアクション

//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================
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
