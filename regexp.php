<?php
require_once('Core/Common/coreLibs.php');
require_once('Core/Common/appLibs.php');

$text = <<<'EOS'

```
ハイパーリンク
  [テキスト]\(URL)

画像
  ![代替テキスト]\(/res/images/biscuits.png)
  ![代替テキスト](/res/images/biscuits.png)


水平線 \<hr> <hr>
  --- | ___ | ***
---
___
***

見出し

 # 見出し                  | 見出し1 \<h1>|
 ## 見出し                 | 見出し2 \<h2>|
 ### 見出し                | 見出し3 \<h3>|
 #### 見出し               | 見出し4 \<h4>|
 ##### 見出し              | 見出し5 \<h5>|
 ###### 見出し             | 見出し6 \<h6>|

文字修飾
<blockquote>

|: 記述      |: タグ          |
| \**強調**  | **強調** \<strong> |
| \__強調__  | __強調__ \<em> |
| \*斜体*    | *斜体* \<i> |
| \--取消線--| --取消線-- \<del> |
| \_下線_    | _下線_  "text-decoration:underline" |

</blockquote>


リスト
  - アイテム1               // リスト(UL-LI)
  - アイテム2

  1. アイテム1               // リスト(OL-LI)
  1. アイテム2

テーブル
 |: ヘッダ標準 |:< ヘッダ左寄せ |:> ヘッダ右寄せ |:= ヘッダ中央 |   // テーブル(ヘッダ行:TH)
 | セル標準    |>  右寄せ       |<左寄せ         |=    中央     |   // セル(データ行:TD)


引用
 \> 引用                     // 引用 (blockquote)
 \>> 引用ネスト
 \>>>
 \>> \>>
 \<
 \<<
 \<<<
 \<<<<
 \<<<>

 <hr>

コード
  \```                       // pre class="code"
  source-code
  \```

  \```クラス名               // pre class="クラス名"
  source-code
  \```

その他
  行末の空白2個              // 改行 \<br>


  -> | =>                    // > タグの回避


```
マークダウン変換したタグは \<div class="easy_markdown">...\</div>ブロック要素になります。

EOS;

$text = <<<'EOS'
フレームワーク内のユーティリティではJavascriptの外部ライブラリとして以下を使用します。  
各ライブラリの提供場所からダウンロードし**vendor/webroot/js** フォルダ下に格納してください。  

- [jquery-3.2.1](https://jquery.com/download/)
- [jquery-ui-1.12.1](https://github.com/jquery/jquery-ui)

本Helpアプリでは次の外部ライブラリも使用していますが
作成するアプリケーションによっては不要です。

1. jquery.treeview
1. table-sorter-2.28.8
1. [split-pane](http://www.dreamchain.com/split-pane/)


EOS;
$text = <<<'EOS'

- リスト1
 - ネスト リスト1_1
  - ネスト リスト1_1_1
  - ネスト リスト1_1_2
 - ネスト リスト1_2
- リスト2
- リスト3

ブレークフェーズ

EOS;
$xtext = <<<'EOS'
> リスト1
>> ネスト リスト1_1
>>> ネスト リスト1_1_1
>>> ネスト リスト1_1_2
>> ネスト リスト1_2
> リスト2
> リスト3
ブレークフェーズ

EOS;

$ans = [
"リスト1",
[
	"ネスト リスト1_1",
	[
		"ネスト リスト1_1_1",
		 "ネスト リスト1_1_2",
	],
	"ネスト リスト1_2",
],
"リスト2",
"リスト3",
];

$text = str_replace("\r",'',$text);
echo pseudo_markdown($text);
exit;

$user_func = function($text) {
	$tags = array(
		'- ' => ['ul','ul_list',true],
		'1.' => ['ol','ol_list',true],
		'> ' => ['blockquote','',false]);
	
	$call_func = function($arr) use(&$tags) {
		$key_str = mb_substr($arr[0],0,2);
		$islist = $tags[$key_str][2];
		$app = 0;
		$make_array = function($array,$lvl) use(&$make_array,&$app,&$key_str,&$islist) {
			$result = [];
			while(isset($array[$app])) {
				$str = $array[$app];
				for($n = 0; ($islist)?ctype_space($str[$n]):($str[$n+1]==='>'); ++$n) ;
				if(mb_substr($str,$n,2) !== $key_str) break;
				$ll = ltrim(mb_substr($str,$n+2));
				if($n === $lvl) {
					$result[] = $ll;
					$app++;
				} else if($n > $lvl) {
					$result[] = $make_array($array,$lvl+1);
				} else break;
			}
			return $result;
		};
		return [ $key_str => $make_array($arr,0)];
	};
	$arr = $call_func(explode("\n", $text));			// Make Level array
	$key = array_key_first($arr);
	list($ptag,$ptagcls,$islist) = $tags[$key]; 
	$ptag_start = (empty($ptagcls)) ? "<{$ptag}>" : "<{$ptag} class='{$ptagcls}'>";
	$ptag_close = "</{$ptag}>";

	$ul_text = function($array,$n) use(&$ul_text,&$ptag_start,&$ptag_close,$islist) {
		$spc = str_repeat('  ',$n);
		$res = "";
		foreach($array as $n => $val) {
			if(is_array($val)) {
				$low = $ul_text($val,$n+2);
				$res .= "{$spc}{$ptag_start}\n{$low}{$spc}{$ptag_close}";
				if($n > 0)	$res .= "</li>\n";
			} else if($islist) {
				$res .= (is_array(next($array)))?"{$spc}<li>{$val}\n":"{$spc}<li>{$val}</li>\n";
			} else {
				$res .= "{$spc}{$val}\n";
			}
		}
		return $res;
	};
	debug_dump(0,[ "ARRAY" => $arr]);
	return $ul_text($arr,0);
};
$arr = $user_func($text);

debug_dump(5,[ "LIST DEBUG" => ["INPUT" => $text,"RESULT" => $arr, "ANSWER" => $ans]]);
exit;

