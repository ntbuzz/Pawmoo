<?php
/*
    データアクセスのみを提供する場合は、モジュールではなく Modelsに登録するのが便利です。
*/
class ParagraphModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DataTable' => 'blogParagraph',
        'Primary' => 'id',
        'Schema' => [
            'id'        => ['',0],
            'section_id'   => ['',0,'Section.id.title'],
            'published' => ['',0],
            'seq_no'    => ['',0],
            'title'     => ['',100],
            'contents'  => ['',100],
        ],
        'PostRenames' => [],
    ];
//==============================================================================


}
