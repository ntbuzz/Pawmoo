<?php
// デバッグ用のクラス
require_once('Core/AppDebug.php');
require_once('Core/Config/appConfig.php');
require_once('Core/Common/coreLibs.php');
require_once('Core/Common/appLibs.php');
echo str_repeat("=", 150)."\n";
$arr = [1,2,3,4,5,6,7];
$arr = [1,2,'b'=>3,['a' => 7,'b' => 8,9]];
$sub = [4,5,$arr];
$template = [
    'span.flapMenu rightitem' => [
        'link' => 'edit_form',
        '?${@@title}' => [
            ''	=> [ 'style' => "color:black;" ],
            '*' => [ 'style' => "color:white;" ],
            't' => [ 'style' => "color:blue;" ],
        ],
        '【編集】'
    ],
    '?${@@title}' => [
        ''	=> [],
        '*' => [ 'h3' => [ '${@@title}' ], 'span' => [ 'タイトル' ] ]
    ],
    '+markdown' => '${@@contents}',
    '.edit_form' => [
        '+input[seq_no]' => [	'表示順：', 'size'=> 3, 'value'=> '${@@seq_no}' ],
        '+checkbox[published]' => [ 
            't' => '公開',
            [ '${@published}' => 't' ],
        ],
        '+input[title]' => [	'タイトル：', 'size'=> 76, 'value'=> '${@@title}' ]
    ]
];
foreach([
    ' aaaa=>bbbb ',
    'aaaa=>',
    '=>',
    'bbbb',
] as $vv) {
    debug_log(-99,["SPLIT" => explode('=>',$vv)]);
}
exit;

