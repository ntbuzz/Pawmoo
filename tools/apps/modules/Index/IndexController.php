<?php

class IndexController extends AppController {
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
	$this->View->PutLayout();
}

}
