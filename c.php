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
    // key                  [ dispp_name, disp_flag, width, lang, relations_binds ]
    'id' =>                   [032, ['.IP' => ,50] ],
    'active' =>               ['.Status',22],
    'name_list_id' =>         ['.Host',2,0,'en',['Names.id' => ['.Host' => ['name',50],'.Host' =>['title',80]] ],['name','name2']],
    'product_name' =>         ['.product',02,0,'ja;en',0,['aaaa','bbb']],
    'operating_system_id' =>  ['.os', 2,0,0,'Os.id.name',0],
    'license_id' =>           ['.license',2,0,0,'Licenses.id.license'],
    'location'=>              ['.location',2],  // '所在',2],
    'entity'=>                ['.entity',2,0,0,['location','note'] ],
    'service'=>               ['.service',0],
    'subnet' =>               ['.subnet',0],
    'note'=>                  ['.note',0],
    'provider_id'         => ['',0,0,'en','Providers.id.name'],
    'desktop_id'          => ['',0,0,'ja','Desktops.id.name'],
];

list($header,$relation,$locale,$bind,$field) = SchemaAnalyzer($Schema,$langs);

debug_log(-99,[
    'Schema'    => $Schema,
    'header'    => $header,
    'Relation'  => $relation,
    'Bind'      => $bind,
    'Locale'    => $locale,
    'Field'    => $field,
]);

function SchemaAnalyzer($Schema,$langs) {
    $header = $relation = $locale = $bind = $field = [];
    foreach($Schema as $key => $defs) {
        array_push($defs,0,NULL,NULL,NULL,NULL);
        $ref_key = $key;
        list($disp_name,$disp_flag,$width,$accept_lang,$relations,$binds) = $defs;
        list($disp_align,$disp_head) = [intdiv($disp_flag%100,10), $disp_flag%10];
        if(!empty($relations)) {
            if(substr($key,-3)==='_id') $ref_key = substr($key,0,strlen($key)-3);
            $relation[$ref_key] = [$relations,$accept_lang];
        } else if(!empty($binds)) {
            $bind[$ref_key] = $binds;
            $key = NULL;
        }
        $field[$ref_key] = $key;
        if($disp_head !== 0) {
            $header[$ref_key] = [$disp_name,$disp_align,$disp_head,$width];
        }
        // リレーションしているものはリレーション先の言語を後で調べる
        if(!empty($accept_lang)) {
            $ref_name = "{$ref_key}_{$langs}";
            if(strpos($accept_lang,$langs) !== FALSE) { // && array_key_exists($ref_name,$this->dbDriver->columns)) {}
                $locale[$ref_key] = $ref_name;
            }
        }
    }
    return [$header,$relation,$locale,$bind,$field];
}
