<?php

class IndexController extends AppController {
	public $defaultAction = 'List';		//  デフォルトのアクション
	public $disableAction = [ 'Page','Makepdf' ];	// 無視するアクション
    protected $needLogin = FALSE;
    protected $noLogging = [];//継承元も含めログ除外メソッド
	protected $LoggingMethod = 'Access.Logged';	// ログ処理モデルとメソッドを定義
//==============================================================================
//	クラス初期化処理
	protected function ClassInit() {
		MySession::set_paramIDs('admin',1);  // SET DEBUGGER
	}
//==============================================================================
// デフォルトの動作
public function ListAction() {
	$cond = [];		// 公開しているものだけのときは条件を指定	['published'=>'t'];
	$this->Model->BlogMonth($cond);
	$this->View->PutLayout();
}
//==============================================================================
// 記事コンテンツの表示
public function ViewAction() {
	$num = App::$Params[0];
	$this->Model->BlogMonth(NULL);		// 公開しているだけにするなら条件を指定
	$this->Model->ReadContents($num);
	$this->View->PutLayout('ContentView');
}

}
