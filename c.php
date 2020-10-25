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
debug_log(-99,["ORIGIN" => $template]);
echo "WALK\n";
$arr = array_walk_replace($template, function($v,$k) {
    debug_log(-99,["Check:" => $k,'VAL:'=>$v]);
    if($k[0]==='?') {
        foreach($v as $check => $value) {
            if($check === '') {
                debug_log(-99,["HIT:" => $value]);
                return $value;
            }
        }
    };
    return [$k => $v];
});
debug_log(-99,["REPLACE" => $arr]);

exit;
function array_walk_replace($arr, $callback, $var = NULL) {
    $wd = [];
    foreach($arr as $key => $val) {
        $ret = $callback($val,$key);
        foreach($ret as $kk => $vv) {
            if(is_numeric($kk)) $wd[] = $vv;
            else $wd[$kk] = $vv;
        }
    }
    return $wd;
}

list($a,$b,$c,$d) = $arr;

function array_flat_nexted($arr) {
    $wx = [];
    $reduce_array = function ($arr) use(&$reduce_array,&$wx) {
        if(is_array($arr)) {
            foreach($arr as $key => $val) {
                if(is_array($val)) {
                    $reduce_array($val);
                } else if(is_numeric($key)) {
                    $wx[] = $val;
                } else {
                    $wx[$key] = $val;
                }
            }
        } else $wx[] = $arr;
    };
    $reduce_array($arr);
    return $wx;
}
$wx = array_flat_nexted($sub);
var_dump($sub,$wx);
