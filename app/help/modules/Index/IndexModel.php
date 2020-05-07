<?php
/*
    Database Table Relations
        section
            items
                pages
        category
*/
class IndexModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => 'mvcman',
        'DataTable' => 'helpdoc',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id' =>         ['.id',2],          // モジュールSchemaの言語ID
            'category' =>   ['.category',2],
            'section' =>    ['.section',2],    // 共通Schemaの言語ID
            'node' =>       ['.node',2],
            'item' =>       ['.item', 2],
            'contents' =>   ['.contents',2],
        ],
        'Relations' => [
        ],
        'PostRenames' => [
        ]
    ];
    public $outlone;            // アウトライン配列 $outlien[SECTION-ID][ITEM-ID][PAGE-ID] = CATEGORY-ID
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//===============================================================================
//	クラス初期化処理
//  必要ならサブクラスでオーバーライドする
    protected function ClassInit() {
    }
//===============================================================================
// レコードのリレーションからアウトライン配列を作成する
//===============================================================================
    function MakeOutline() {
        $this->outline = array();

    }
}
