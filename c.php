<?php
// デバッグ用のクラス
require_once('Core/AppDebug.php');
require_once('Core/Config/appConfig.php');
require_once('Core/Common/coreLibs.php');
require_once('Core/Common/appLibs.php');

$arr = [1,2,3,4,5,6,7];
$arr = [1,2,3];
$sub = [4,5];
$arr = $arr + $sub;

list($a,$b,$c,$d) = $arr;

var_dump($arr,$a,$b,$c);
