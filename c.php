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
$cond = ['active=' => 't','name_list_id@' => ['source' => ['神話','元素','惑星']] ];
function re_build_array2($cond) {
    $reduce_array = function($cond) use(&$reduce_array) {
        $wd = [];
        foreach($cond as $key => $val) {
            if(is_array($val)) $val = $reduce_array($val);
            if(is_numeric($key) && is_array($val)) {
                foreach($val as $kk => $vv) $wd[$kk] =$vv;
            } else $wd[$key] =$val;
        }
        return $wd;
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
    return $sort_array($reduce_array($cond));
}

debug_log(-99,["INPUT" => $cond]);
$new_cond = re_build_array2($cond);
debug_log(-99,["INPUT" => $cond, "REBUILD" => $new_cond]);

