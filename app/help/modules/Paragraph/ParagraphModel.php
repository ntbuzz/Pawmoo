<?php

class ParagraphModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => 'mvcman',
        'DataTable' => 'Paragraph',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id'            => ['',2],          // モジュールSchemaの言語ID
            'section_id'    => ['',0],
            'category_id'   => ['',0],
            'disp_id'       => ['',0],
            'title'         => ['',2],
            'contents'      => ['',2],    // 共通Schemaの言語ID
        ],
        'Relations' => [
            'section_id' => 'Section.id.title',
        ],
        'PostRenames' => [
/*
            "disp_id"   => 'disp_id',
            "title"     => 'title',
            "contents"  => 'contents',
*/
        ]
    ];
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================


}
