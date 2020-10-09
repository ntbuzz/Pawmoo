<?php
// デバッグ用のクラス
require_once('Core/AppDebug.php');
require_once('Core/Config/appConfig.php');
require_once('Core/Common/coreLibs.php');
require_once('Core/Common/appLibs.php');
// 言語ファイルの対応
$lang = $argv[1];
$arr = array_unique(             // 重複行を取り除く
    array_filter(           // strlen を使って空行を取り除く
        array_map(          // 各要素に有効識別子の取り出し関数を適用
            function($a) {
                if(($n=strpos($a,'-')) !== FALSE)       return substr($a,0,$n);     // en-US => en
                else if(($n=strpos($a,';')) !== FALSE)  return substr($a,0,$n);     // en;q=0.9 => en
                else return $a;
            },
            explode(',', $lang)  // 言語受け入れリスト
        ),
        'strlen'));
$langs = array_shift($arr);             // strict回避
$Schema = [
    // key                  [ dispp_name, disp_flag, width, lang, relations, binds ]
    'id' =>                   ['.IP',32],
    'active' =>               ['.Status',22,],
    'name_list_id' =>         ['.Host',2,0,0,'Names.id.name',0],
    'product_name' =>         ['.product',102],
    'operating_system_id' =>  ['.os', 2,0,0,'Os.id.name.osid',0],
    'license_id' =>           ['.license',2,0,0,'Licenses.id.license',0],
    'location'=>              ['.location',2],  // '所在',2],
    'entity'=>                ['.entity',2],
    'service'=>               ['.service',0],
    'subnet' =>               ['.subnet',0],
    'note'=>                  ['.note',0],
    'provider_id'         => ['',0,0,0,'Providers.id.name',0],
    'desktop_id'          => ['',0,0,0,'Desktops.id.name',0],
];

$header = [];
$relation = [];
$locale = [];
$bind = [];
foreach($Schema as $key => $defs) {
    array_push($defs,0,NULL,NULL,NULL,NULL);
    list($disp_name,$disp_flag,$width,$accept_lang,$relations, $binds) = $defs;
    list($disp_align,$disp_head) = [intdiv($disp_flag,10), $disp_flag%10];
    if($disp_head !== 0) {
        $header[$key] = [$disp_name,$disp_align,$width];
    }
    if(!empty($binds) && is_array($binds)) $bind[$key] = $binds;
    // リレーションしているものはリレーション先の言語を後で調べる
    if(!empty($relations)) {
        if(substr($key,-3)==='_id') $key = substr($key,0,strlen($key)-3);
        $relation[$key] = [$accept_lang,$relations];
    } else if(!empty($accept_lang)) {
        $ref_name = "{$key}_{$langs}";
        if(strpos($accept_lang,$langs) !== FALSE) { // && array_key_exists($ref_name,$this->dbDriver->columns)) {}
            $locale[$key] = $ref_name;
        }
    }
}

debug_log(-99,[
    'Schema'    => $Schema,
    'header'    => $header,
    'Relation'  => $relation,
    'Bind'      => $bind,
    'Locale'    => $locale,
]);
