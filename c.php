<?php
// デバッグ用のクラス
require_once('Core/AppDebug.php');
require_once('Core/Config/appConfig.php');
require_once('Core/Common/coreLibs.php');
require_once('Core/Common/appLibs.php');
echo str_repeat("=", 150)."\n";

$cond = [
    [
        [   'flag_a' => 't',
            [ 'flag_g' => 'g' ],
            'flag_b' => 'f',
        ],
        'AND' => [
            [ 'mode' => 'test',
              'OR'=>  [ 'name' => 'ntak','pass' => 'root'],
              [
              "one" => 100,
              "two" => 200,
              "three" => 300,
               ],
            ],
        ],
        [
        'NOT' => [ 'scan' => 'OK', 'las'=>999 ],
        ]
    ],
];

function re_build_array($cond) {
    $reduce_array = function($opr,$arr) use(&$reduce_array) {
        $wd = [];
        foreach($arr as $key => $val) {
            if(is_array($val)) $val = $reduce_array($key,$val);
            if((is_numeric($key)||$key===$opr) && is_array($val)) {
                foreach($val as $kk => $vv) {
                    if(is_array($vv) && count($vv)===1) list($kk,$vv) = array_first_item($vv);
                    if(isset($wd[$kk]) && is_array($vv)) {
                        foreach($vv as $k2 => $v2) $wd[$kk][$k2] = $v2;
                    } else $wd[$kk] = $vv;
                }
            } else {
                $wd[$key] = $val;
            }
        }
        return is_numeric($opr) ? $wd : [$opr => $wd];
    };
    $sort_array = function($arr) use(&$sort_array) {
        $wd = [];
        foreach($arr as $key => $val) {
            if(is_scalar($val)) $wd[$key] = $val;
        }
        foreach($arr as $key => $val) {
            if(is_array($val)) $wd[$key] = $sort_array($val);
        }
        return $wd;
    };
    $new_cond = $reduce_array('AND',$cond);
    $sort_cond = $sort_array($new_cond);
    return $sort_cond;
};

$new_cond = re_build_array($cond);
debug_log(-99,["INPUT" => $cond,"REBUILD" => $new_cond]);

