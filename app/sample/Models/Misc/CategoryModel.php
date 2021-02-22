<?php
/*
    データアクセスのみを提供する場合は、モジュールではなく Modelsに登録するのが便利です。
*/
class CategoryModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DataTable' => 'category',
        'Primary' => 'id',
        'Schema' => [
            'id'        => ['',0],
            'title'     => ['',100],    // 多言語対応
            'note'      => ['',0],
        ],
        'PostRenames' => [],
    ];
//==============================================================================


}
