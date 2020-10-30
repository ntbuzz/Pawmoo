<?php
// デバッグ用のクラス
require_once('Core/AppDebug.php');
require_once('Core/Config/appConfig.php');
require_once('Core/Common/coreLibs.php');
require_once('Core/Common/appLibs.php');
echo str_repeat("=", 150)."\n";

function tables($col) {
    list($tag,$vars) = ['TD',''];
    // maybe additional calss and colspan/rowspan
    preg_match('/^([@\^]+)*(\.(\w+))*?\s/',$col,$m);
    debug_log(-99,["MATCH" => $m]);
    $bind = $cls = '';
    switch(count($m)) {
    case 4: $cls =" class='{$m[3]}'";
    case 2: $len = strlen($m[1]);
            if($len === 0) $bind = '';
            else {
                $binds = [];
                foreach(['@'=>'colspan','^'=>'rowspan'] as $bkey => $bval) {
                    $clen = substr_count($m[1],$bkey);
                    if($clen !== 0) $binds[] = " {$bval}='{$clen}'";
                }
                $bind = implode($binds);
            }
            $col = mb_substr($col,strlen($m[0]));
            break;
    }
    return "<{$tag}{$cls}{$bind}{$vars}>{$col}</{$tag}>";
}


$template = [
    'class ヘッドクラス',
    '.class ヘッドクラス',
    '@@@ ヘッドクラス',
    '^^^ ヘッドクラス',
    '@^^@@^^ ヘッドクラス',
    '@@.class ヘッドクラス',
    '^^.class ヘッドクラス',
    '^^^@@.class ヘッドクラス',
    '@@@^^.class ヘッドクラス',
    '^@@^@^^.class ヘッドクラス',
];
foreach($template as $vv) {
        debug_log(-99,["INPUT" => $vv, "TABLE"=>tables($vv)]);
}
exit;

