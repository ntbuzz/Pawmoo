<?php

class IndexModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => 'mvcman',
        'DataTable' => 'Part',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id'            => ['',0],          // モジュールSchemaの言語ID
            'category_id'   => ['',0],
            'disp_id'       => ['',0],
            'title'         => ['',0],
            'contents'      => ['',0],
        ],
        'Relations' => [
            'category_id' => 'Category.id.title',
        ],
        'PostRenames' => [
        ]
    ];
    public $outline;            // アウトライン配列 $outlien[SECTION-ID][ITEM-ID][PAGE-ID] = CATEGORY-ID
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
        $outline[$columns['id']] = [ $columns['title'],$columns['contents']];
        $this->PartSelect[] = array($columns['id'],$columns['title']);
    };
    // Chapter リストを取得する
    $this->outline = array();
    foreach($outline as $key => $columns) {
        $this->Chapter->RecordFinder(['part_id=' => $key],['id','title','contents'],'disp_id');
        $this->outline[$key] = ['title' => $columns[0], 'contents' => $columns[1],'child' => $this->Chapter->Records];
    }
}
//===============================================================================
// レコードのリレーションからアウトライン配列を作成し、各々のレコードを取得
//===============================================================================
function ReadOutline() {
    $filters = array('id','title','contents');
    $outline = array();
    // Part レコードを取得
    $this->RecordFinder([],$filters,'disp_id');
    foreach($this->Records as $key => $columns) {
        $part_id = $columns['id'];
        $outline[$key] = $columns;
        $outline[$key]['child'] = $this->Chapter->ReadOutline($part_id,$filters);
    };
    return $outline;
}

}
