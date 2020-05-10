<?php

class SampleModel extends AppModel {
// データベースの構造を定義して基底クラスに引渡す
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => 'mvcman',
        'DataTable' => 'Paragraph',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id'            => ['.id',2],          // モジュールSchemaの言語ID
            'section_id'    => ['',0],
            'category_id'   => ['',0],
            'title'         => ['.title',2],
            'contents'      => ['.contents',2],    // 共通Schemaの言語ID
        ],
        'Relations' => [
            'section_id' => 'Section.id.title',
        ],
        'PostRenames' => [
        ]
    ];
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//===============================================================================
//	サブクラスの初期化処理オーバーライド
    protected function ClassInit() {
    }
}
