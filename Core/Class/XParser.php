<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  SectionParser:  セクションレイアウトファイルの新単語パーサー
 *		処理速度は遅くなるが、トークンを空白で区切る必要がなくなる
 *      空白を含めるときはクオートで囲む
 *      トークン:
 *          "任意の文字列" または '任意の文字列'
 *           <任意の文字列>
 *          (任意の文字列)
 *          //コメント文字列\r
 */
//==============================================================================
class SectionParser {
	private $lines;
	private $line_count;
	private $lno = 0;
	private $current_line;
	const token_separator = [
		'=>' => 2,'/*' => 2,'*/' => 2,	// '//' => 2,
		' ' => 1,"\t" => 1,"\n" => 1,
	];
	const skip_separator = [
		' ' => 1,"\t" => 1,"\n" => 1,
	];
	const token_quote = [
		'"' => '"',
		"'" => "'",
		'~' => '~',
		'(' => ')',
		'{' => '}',
		'<' => '>',
	];
//==============================================================================
    function __construct($template) {
        $contents = file_get_contents($template);          // ファイルから全て読み込む
		// 改行文字を \n に統一、文字列定数のため空行フィルタはしない
        $this->lines = explode("\n",str_replace(["\r\n","\r"],"\n",$contents));
		$this->line_count = count($this->lines);
		$this->reset_line();
	}
//==============================================================================
	private function clear() {
		$this->lno = 0;
        $this->lines = '';
		$this->line_count = 0;
	}
//==============================================================================
	private function reset_line() {
		$this->lno = 0;
		$this->next_line();
	}
//==============================================================================
	private function next_line() {
		if($this->lno >= $this->line_count) return false;
		$this->current_line = $this->lines[$this->lno++];
		return true;
	}
//==============================================================================
	private function current_ch() {
		if($this->current_line === true) {
			if($this->next_line() === false) return false;
		}
		if($this->current_line === '') {
			$this->current_line = true;
			return "\n";
		}
		$ch = mb_substr($this->current_line,0,1);
		$this->current_line = mb_substr($this->current_line,1);
		return $ch;
	}
//==============================================================================
	private function is_token($token) {
		return $token === '//' || isset(self::token_separator[$token]);
	}
//==============================================================================
	private function is_separator() {
		if($this->current_line === '') return true;//$this->next_line();
		foreach(self::token_separator as $wd => $len) {
			if(strncmp($this->current_line,$wd,$len) === 0) {
				return true;
			}
		}
		return false;
	}
//==============================================================================
	private function quote($q) {
		$qend = self::token_quote[$q];
		$ch = $this->current_ch();
		if($ch === $qend) return '';
		$trim = ($ch === '^');
		if($trim) $ch = $this->current_ch();
		if($ch === false) return '';
		if($this->current_line === true) {
			if($this->next_line()===false) return "\n";
		}
		$token = $ch;
retry:	while(($n=mb_strpos($this->current_line,$qend))===false) {
			$token = "{$token}{$this->current_line}\n";
			if($this->next_line()===false) break;
		}
		if($n !== false) {
			$ln = mb_substr($this->current_line,0,$n);
			$token = "{$token}{$ln}";
			$this->current_line = mb_substr($this->current_line,$n+1);
			if(substr($token,-1) === '\\') {		// escape-char
				$token[strlen($token)-1] = $qend;
				goto retry;
			}
		}
        // 前後の空白を削除
        if($trim) $token = implode("\n", text_line_array("\n",$token,FALSE));
        // エスケープ文字[\n,\t]を文字コード置換する
        if($q === '"') $token = str_replace(['\n','\t'],["\n","\t"], $token);
        else if($q === '<') $token = "<{$token}>";	// タグクオート
		return $token;
	}
//==============================================================================
	public function get_token() {
retry:	do {
			$ch = $this->current_ch();
			if($ch === false) return false;
		} while(isset(self::skip_separator[$ch])) ;
		if($ch === false) return false;
		$token = false;
		do {
			if(isset(self::token_quote[$ch])) {
				$token = $this->quote($ch);
			} else {
				$token = $ch;
				if($token === '[' || $token === ']') break;
				$nest = 0;
				while(!$this->is_token($token)) {
					if($this->is_separator()) break;
					// ] の先読み
					if(mb_substr($this->current_line,0,1) === ']') {
						if($nest-- === 0) break;	// name属性の外
					}
					if(($ch = $this->current_ch())===false) break;
					if($ch === '[') ++$nest;
					$token = "{$token}{$ch}";
				}
				if($token === '//') {
					$this->next_line();
					goto retry;
				}
				if($token === '/*') {
					do {
						$token = $this->get_token();
					} while($token !== false && $token !== '*/');
					goto retry;
				}
			}
		} while($token === false);
		return $token;
	}
//==============================================================================
//  単語リストからセクション配列を作成し、配列要素を返す
    function getSectionDef($is_TAG) {
        $arr = [];
		while(($wd=$this->get_token())!==false) {
next_wd:    if($wd == ']') return $arr;            // セクション終了なら生成した配列を返す
            if($wd == '[') {                       // セクション開始
                do {
                    $arr[] = $this->getSectionDef($is_TAG);    // セクション配列をとりだし、セクション配列に追加する
                    $wd=$this->get_token();          // 次のトークン
                } while( $wd == '[');                  // さらにセクション要素が続く間繰り返す
                if($wd == ']') return $arr;            // セクション終了なら配列を返す
            }
            $nwd = $this->get_token();          // 次のトークン
            if($nwd === '=>') {                          // 連想配列要素なら
                $wkey = array_key_unique($wd,$arr);
	            $wd = $this->get_token();          // 次のトークン
                if($wd == '[') {                // セクション開始なら
                    $arr[$wkey] = $this->getSectionDef($is_TAG);   // 再帰呼び出しでセクション要素を連想配列に代入
                } else {
                    $arr[$wkey] = $wd;                  // トークンを連想配列に代入
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
				if($nwd === false) break;
				$wd = $nwd;
				goto next_wd;
            }
        }
		$this->clear();
       return $arr;
    }
}
