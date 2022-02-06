<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  SectionParser:  セクションレイアウトファイルの単語パーサー
 *      レイアウト要素を連想配列に変換する
 *      トークンは必ず空白またはカンマで区切られていること
 *      空白を含めるときはクオートで囲む
 *      トークン:
 *          "任意の文字列" または '任意の文字列'
 *           <任意の文字列>
 *          (任意の文字列)
 *          //コメント文字列\r
 */
class SectionParser {
    const WORDSTRING = array( '()', '{}', '~~','""',"''");
    private $wpos;          // 処理中の単語インデックス
    private $wend;          // 単語リストの数
    private $wordlist;       // 単語配列
//==============================================================================
// コンストラクタ
// ファイルからトークンを切り出して単語リストを作成しておく
    function __construct($template) {
        $lines = file_get_contents($template);          // ファイルから全て読み込む (正規表現でトークン分離するため)
        $this->wordlist = array();
        $p = <<<'EOS'
/(?:^|[,\s]+)
((?:
"(?:[^"]|(?:\\\\)*\\")+"|
'(?:[^']|(?:\\\\)*\\')+'|
~(?:[^~]|(?:\\\\)*\\~)+~|
<(?:[^>]|(?:\\\\)*\\>)+>|
\{(?:[^\}]|(?:\\\\)*\\\})+\}|
(?:\/\/.*)|
[^,\s]+
)*)/x
EOS;
        preg_match_all($p,$lines,$m);               // 全ての要素をトークン分離する
        $wd = $m[1];
        $incomm = FALSE;                            // コメントの処理
        foreach($wd as $token) {
            if(mb_substr($token,0,2) == '//') continue;    // 先頭が // 文字ならコメント・トークンなので単語帳には入れない
            if($incomm && $token == '*/') $incomm = FALSE;     // ブロック・コメントの終了
            else if($token == '/*') $incomm = TRUE;        // ブロック・コメントの開始
            else if( !$incomm && strlen($token)) {          // ブロック・コメント内でなく、トークンが空でなければ処理
                $wrapstr = $token[0] . mb_substr($token,-1);     // 先頭文字と最終文字を取り出す
                if ( in_array($wrapstr, self::WORDSTRING,true)) {
                    $token = trim($token, $wrapstr);
                    if(mb_substr($token,0,1)==='^') {
                        $token = implode("\n", text_line_array("\n",mb_substr($token,1),FALSE));
                    }
                } else if($token !== '=>') {
                    $md = explode('=>',trim($token));
                    if(count($md)===2) {
                        foreach(array_map(function($a) { $aa = $a[0] . mb_substr($a,-1);     // 先頭文字と最終文字を取り出す
                                    if(in_array($aa, self::WORDSTRING,true)) $a = trim($a, $aa );
                                    return $a;
                                },array_filter([$md[0],'=>',$md[1]],'strlen')) as $vv) {
                            $this->wordlist[] = $vv;
                        }
                        continue;
                    }
                }
                // 改行文字\nを置換する
                $this->wordlist[] = ($wrapstr == '""') ? str_replace('\n', "\n", $token) : $token;
            }
        }
        $this->wpos = 0;                        // インデクスを先頭にする
        $this->wend = count($this->wordlist);   // 要素数を数える
    }
//==============================================================================
	private function clear() {
        $this->wpos = $this->wend = 0;   // 要素数を数える
		$this->wordlist = [];
    }
//==============================================================================
//  トークン取り出し、インデクスを進める
    private function nextToken($pp = -1) {
        $token = $this->getToken($this->wpos++);    // 現在のトークンを取り出し、インデクスを進める
        return $token;
    }
//==============================================================================
//  現在のインデクス位置のトークンを取り出す
    private function getToken($i) {
        $token = ($i < $this->wend) ? $this->wordlist[$i] : '';     // インデクスが範囲内なら単語を取出す
        return $token;
    }
//==============================================================================
//  単語リストからセクション配列を作成し、配列要素を返す
    function getSectionDef($is_TAG) {
        $arr = array();
        while($this->wpos < $this->wend) {
            $wd = $this->nextToken(0);              // トークンの取り出し
            if($wd == ']') return $arr;            // セクション終了なら生成した配列を返す
            if($wd == '[') {                       // セクション開始
                do {
                    $arr[] = $this->getSectionDef($is_TAG);    // セクション配列をとりだし、セクション配列に追加する
                    $wd = $this->nextToken(1);          // 次のトークン
                } while( $wd == '[');                  // さらにセクション要素が続く間繰り返す
                if($wd == ']') return $arr;            // セクション終了なら配列を返す
            }
            $nw = $this->getToken($this->wpos);         // トークンの先読み
            if($nw == '=>') {                          // 連想配列要素なら
                $wkey = array_key_unique($wd,$arr);
                $this->wpos++;
                $nw = $this->nextToken(2);              // 次のトークン
                if($nw == '[]') {                      // 空要素なら特別処理
                    $arr[$wkey] = [];                   // 再帰呼出しを省略
                } else if($nw == '[') {                // セクション開始なら
                    $arr[$wkey] = $this->getSectionDef($is_TAG);   // 再帰呼び出しでセクション要素を連想配列に代入
                    $wd = $this->getToken($this->wpos);     // トークンの先読み
                    if($wd == ']') {                       // 終了トークンなら
                        $this->wpos++;                      // 次の単語に移動し、配列を返す
                        return $arr;
                    }
                } else {
                    $arr[$wkey] = $nw;                  // トークンを連想配列に代入
                }
            } else {
                switch(is_tag_identifier($wd)) {
                case 1: if($is_TAG===FALSE) {
                            $arr[] = $wd;
                            break;
                        }
                case 2:
                case 3:
                        $arr[$wd] = '';
                        break;
                case 0: 
					if(mb_substr($wd,0,1) === '\\') $wd = mb_substr($wd,1);
                    $arr[] = $wd; break;
                }
            }
        }
		$this->clear():
       return $arr;
    }
//==============================================================================

}