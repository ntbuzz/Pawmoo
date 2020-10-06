<?php

$aaa = [ 'arg' => 'param','index' => 0];

$aaa = [ 'AND' => $aaa ];
var_dump($aaa);
exit;

$param = [ 100,21,2,3,4,5,6,7,8,9 ];

list($nm,$pg) = $param;

var_dump($param);
echo "N:{$nm}\n";
echo "P:{$pg}\n";
