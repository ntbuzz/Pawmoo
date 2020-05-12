<?php

class IndexModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => 'mvcman',
        'DataTable' => 'Part',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id'            => ['.id',0],          // モジュールSchemaの言語ID
            'category_id'   => ['.category',0],
            'disp_id'      => ['',0],
            'title'         => ['',0],
            'contents'      => ['',0],
        ],
        'Relations' => [
            'category_id' => 'Category.id.title',
        ],
        'PostRenames' => [
        ]
    ];
    public $outlone;            // アウトライン配列 $outlien[SECTION-ID][ITEM-ID][PAGE-ID] = CATEGORY-ID
    public $PartSelect;
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//===============================================================================
//	クラス初期化処理
//  必要ならサブクラスでオーバーライドする
    protected function ClassInit() {
    }
//===============================================================================
// レコードのリレーションからアウトライン配列を作成する
//   outline
//===============================================================================
    function MakeOutline() {
        $outline = array();
        $this->PartSelect = [];
        // Part レコードを取得
        $this->RecordFinder([],[],'disp_id');
        foreach($this->Records as $columns) {
            $outline[$columns['id']] = $columns['title'];
            $this->PartSelect[] = array($columns['id'],$columns['title']);
        };
        // Chapter リストを取得する
        $this->outline = array();
        foreach($outline as $key => $columns) {
            $this->Chapter->RecordFinder(['part_id' => $key],['id','title'],'disp_id');
            $this->outline[$key] = ['title' => $columns, 'child' => $this->Chapter->Records];
        }
    }

}
