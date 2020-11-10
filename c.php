<?php
// デバッグ用のクラス
require_once('Core/AppDebug.php');
require_once('Core/Config/appConfig.php');
require_once('Core/Common/coreLibs.php');
require_once('Core/Common/appLibs.php');
echo str_repeat("=", 150)."\n";

$cond = [
    [
   'OR'=> [   'flag_a' => 't',
            [ 'flag_g' => 'g' ],
            'flag_b' => 'f',
        'OR' => [
            [ 'mode' => 'test',
              'AND'=>  [ 'name' => 'ntak','pass' => 'root'],
              'AND:0'=>  [ 'name' => 'root','pass' => 'super'],
              [
              "one" => 100,
              "two" => 200,
              "three" => 300,
               ],
            ],
        ],
        'NOT' => [ 'scan' => 'OK', 'las'=>999 ],
    ],
    ],
];
$cond = ['active=' => 't','name_list_id@' => ['source' => ['神話','元素','惑星']] ];
        function re_build_array2($cond) {
            $reduce_array = function($opr,$cond) use(&$reduce_array) {
                $wd = [];
                foreach($cond as $key => $val) {
                    if(is_array($val)) {
                        $val_s = $val;
                        $val = $reduce_array(is_numeric($key)?$opr:$key,$val);
                    }
                    if(is_array($val) && (is_numeric($key) || $opr === $key)) {
                        foreach($val as $kk => $vv) $wd[$kk] =$vv;
                    } else $wd[$key] =$val;
                }
                return $wd;
            };
            $sort_array = function($arr) use(&$sort_array) {
                $wd = array_filter($arr, function($vv) {return is_scalar($vv);});
                foreach($arr as $key => $val) {
                    if(is_array($val)) $wd[$key] = $sort_array($val);
                }
                return $wd;
            };
            return $sort_array($reduce_array('AND',$cond));
        //    return $reduce_array('AND',$cond);
        }

debug_log(-99,["INPUT" => $cond]);
$new_cond = re_build_array2($cond);
debug_log(-99,["INPUT" => $cond, "REBUILD" => $new_cond]);

