<?php
// デバッグ用のクラス
require_once('Core/AppDebug.php');
require_once('Core/Config/appConfig.php');
require_once('Core/Common/coreLibs.php');
require_once('Core/Common/appLibs.php');

$arr = [1,2,3,4,5,6,7];
$arr = [1,2,'b'=>3,['a' => 7,'b' => 8,9]];
$sub = [4,5,$arr];

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
