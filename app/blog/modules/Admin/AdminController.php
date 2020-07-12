<?php

class AdminController extends customController {
	public $defaultAction = 'List';		//  デフォルトのアクション
	public $disableAction = [ 'Page','View','Makepdf','Update','Dump' ];	// 無視するアクション

//==============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
	protected function ClassInit() {
	}
//==============================================================================
// デフォルトの動作
public function ListAction() {
	APPDEBUG::MSG(15,":Test");
	$this->View->PutLayout();
}

}
