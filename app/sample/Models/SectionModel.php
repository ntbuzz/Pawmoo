<?php
/*
    データアクセスのみを提供する場合は、モジュールではなく Modelsに登録するのが便利です。
*/
class SectionModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DataTable' => 'blogSection',
        'Primary' => 'id',
        'Schema' => [
            'id'        => ['',0],
            'blog_id'   => ['',0,'Index.id.title'],
            'seq_no'    => ['',0],
            'published' => ['',0],
            'title'     => ['',100],
            'contents'  => ['',100],
        ],
    ];
//==============================================================================
// DELETE Records by FIND-CONDITION
public function DeleteRecord($num) {
	    // パラブラフ以降のリレーションテーブルは無いので基本メソッドで消せる
		$this->Paragraph->MultiDeleteRecord(['section_id' => $num]);
		// セクションを削除する
		parent::DeleteRecord($num);
}

}
