<?php
/* -------------------------------------------------------------
 * PHP�t���[�����[�N
 *  SectionParser:  �Z�N�V�������C�A�E�g�t�@�C���̐V�P��p�[�T�[
 *		�������x�͒x���Ȃ邪�A�g�[�N�����󔒂ŋ�؂�K�v���Ȃ��Ȃ�
 *      �󔒂��܂߂�Ƃ��̓N�I�[�g�ň͂�
 *      �g�[�N��:
 *          "�C�ӂ̕�����" �܂��� '�C�ӂ̕�����'
 *           <�C�ӂ̕�����>
 *          (�C�ӂ̕�����)
 *          //�R�����g������\r
 */
//==============================================================================
class SectionParser {
	private $lines;
	private $line_count;
	private $lno = 0;
	private $current_line;
	const token_separator = [
		'=>' => 2,'/*' => 2,'*/' => 2,	// '//' => 2,	http:// �̉���̂���
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
        $contents = file_get_contents($template);          // �t�@�C������S�ēǂݍ���
		// ���s������ \n �ɓ���A������萔�̂��ߋ�s�t�B���^�͂��Ȃ�
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
		$trim = ($ch === '^');
		if($trim) $ch = $this->current_ch();
		if($ch === false || $ch === $qend) return '';
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
        // �O��̋󔒂��폜
        if($trim) $token = implode("\n", text_line_array("\n",$token,FALSE));
        // �G�X�P�[�v����[\n,\t]�𕶎��R�[�h�u������
        if($q === '"') $token = str_replace(['\n','\t'],["\n","\t"], $token);
        else if($q === '<') $token = "<{$token}>";	// �^�O�N�I�[�g
		return $token;
	}
//==============================================================================
	public function get_token() {
retry:	do {
			$ch = $this->current_ch();
			if($ch === false) return false;
		} while(isset(self::skip_separator[$ch])) ;
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
					// ] �̐�ǂ�
					if(mb_substr($this->current_line,0,1) === ']') {
						if($nest-- === 0) break;	// name�����̊O
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
//  �P�ꃊ�X�g����Z�N�V�����z����쐬���A�z��v�f��Ԃ�
    function getSectionDef($is_TAG) {
        $arr = [];
		while(($wd=$this->get_token())!==false) {
next_wd:    if($wd == ']') return $arr;            // �Z�N�V�����I���Ȃ琶�������z���Ԃ�
            if($wd == '[') {                       // �Z�N�V�����J�n
                do {
                    $arr[] = $this->getSectionDef($is_TAG);    // �Z�N�V�����z����Ƃ肾���A�Z�N�V�����z��ɒǉ�����
                    $wd=$this->get_token();          // ���̃g�[�N��
                } while( $wd == '[');                  // ����ɃZ�N�V�����v�f�������ԌJ��Ԃ�
                if($wd == ']') return $arr;            // �Z�N�V�����I���Ȃ�z���Ԃ�
            }
            $nwd = $this->get_token();          // ���̃g�[�N��
            if($nwd === '=>') {                          // �A�z�z��v�f�Ȃ�
                $wkey = array_key_unique($wd,$arr);
	            $wd = $this->get_token();          // ���̃g�[�N��
                if($wd == '[') {                // �Z�N�V�����J�n�Ȃ�
                    $arr[$wkey] = $this->getSectionDef($is_TAG);   // �ċA�Ăяo���ŃZ�N�V�����v�f��A�z�z��ɑ��
                } else {
                    $arr[$wkey] = $wd;                  // �g�[�N����A�z�z��ɑ��
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
