<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  arrayLibs: Common Array Functions for any Class
 */
//==============================================================================
// ARRAY first key , before PHP 7.3
if (!function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}
//==============================================================================
// multi delimitter explode
function str_explode($delm,$string,$trim_empty = true) {
    $str_arr = (is_array($delm)) ? explode($delm[0],str_replace($delm, $delm[0], $string)) : explode($delm,$string);
	if($trim_empty) $str_arr = array_map(function($v) { return trim($v);} , array_filter($str_arr, "strlen"));
    return $str_arr;
}
//==============================================================================
// text line split by NL char, and reverse element with trim
function explode_reverse($delm,$text) {
	$array = array_reverse(array_filter(explode($delm,trim($text)),'strlen'));
    return $array;
}
//==============================================================================
// first key-value pair for associative arrays
function array_first_item($arr) {
	if(is_scalar($arr)) return [0,$arr];
    if(!empty($arr)) {
        foreach($arr as $key => $val) {
            return [$key,$val];
        }
    }
    return ['',''];
}
//==============================================================================
// exists item in array of KEY
function array_item_value(&$arr,$key,$default=NULL) {
    return (isset($arr[$key])) ? $arr[$key] : $default;
}
//==============================================================================
//  To compensate array, fixed count
function array_alternative($a,$max = 0, $b = []) {
    $n = count($b);
    if($max === 0) $max = $n;
    else if($n < $max) $b += array_fill($n,$max - $n,NULL);
    else $b = array_slice($b,0,$max);
    foreach($b as $key => $val) {
        if(empty($a[$key])) $a[$key] = $val;
    }
    return $a;
}
//==============================================================================
// strpos for array version
function strpos_of_array($str,$hayz) {
    foreach($hayz as $val) {
        if(strpos($str,$val) !== false) return TRUE;
    }
    return FALSE;
}
//==============================================================================
// Array depth calculation, short priority 1-row version
function array_depth($a, $c = 0) {
    return (is_array($a) && count($a))
          ? max(array_map("array_depth", $a, array_fill(0, count($a), ++$c)))
          : $c;
  }
//==============================================================================
// alternative array_merge(), Overwrite existing index elements
function array_override($a, $b) {
    if(empty($b)) return $a;
    foreach($b as $key => $val) $a[$key] = $val;
    return $a;
}
//==============================================================================
// alternative array_merge_recursive(), Overwrite existing index elements
function array_override_recursive($a,$b) {
    if(empty($b)) return $a;
    if(is_scalar($a) || is_scalar($b)) return $b;
    foreach($b as $key => $val) {
        $a[$key] = (isset($a[$key])) ? array_override_recursive($a[$key],$val) : $val;
    }
    return $a;
}
//==============================================================================
// text line split by NL char, and trim-space each line
function text_line_array($del,$txt,$trim = FALSE) {
    $array = array_values(
            array_filter(
                array_map(function($a) {return trim(preg_replace('/\s+/', ' ', str_replace('　',' ',$a)));},
                    explode($del, $txt)
            ), ($trim) ? 'strlen' : function($a) { return TRUE;}
        ));
    return $array;
}
//==============================================================================
// array value concatinate to TEXT
function array_reduce_recursive($array,$callback, $init='') {
    foreach($array as $key => $val) {
        if(is_array($val)) $init = array_reduce_recursive($val,$callback, $init);
        else $init .= $callback($key,$val);
    }
    return $init;
}
//==============================================================================
// array value concatinate to TEXT
function array_to_text($array,$sep = "\n", $in_key = TRUE) {
    $dump_text = function ($indent, $items)  use (&$dump_text,&$sep,&$in_key)  {
        $txt = ''; $spc = str_repeat(' ', $indent);
        foreach($items as $key => $val) {
            if(is_array($val)) {
            	if(!is_numeric($key))  $txt .= "{$spc}===== {$key} =====\n";
                $txt .= $dump_text($indent+2, $val);
            } else if(is_numeric($key) || $in_key === FALSE) {
                $txt .= "{$spc}{$val}{$sep}";
            } else {
                $txt .= "{$spc}[{$key}] = {$val}{$sep}" ;
            }
        }
        return trim($txt,$sep);
    };
    return (is_array($array)) ? $dump_text(0,$array) : $array;
}
//==============================================================================
function array_key_value($arr,$sep=',',$quote='') {
    array_walk($arr,function(&$item,$key) use(&$quote) { $item = "{$key}={$quote}{$item}{$quote}"; });
    return implode($sep,$arr);
}
//==============================================================================
function array_filter_values($arr,$filter,$alt=[]) {
	$val = [];
	if(is_array($arr)) {
		$alt_filter = array_combine($filter,array_alternative($alt,count($filter)));
		foreach($alt_filter as $key => $alt_val)
			$val[] = (array_key_exists($key,$arr)) ? $arr[$key] : $alt_val;
	} else {
		$val = array_fill(0,count($filter),NULL);
	}
	return $val;
}
//==============================================================================
function array_filter_import($ignore,$filter,...$items) {
	$new_filter = [];
	foreach($filter as $key) {
		if($ignore) $new_filter[strtolower($key)] = strtoupper($key);
		else $new_filter[$key] = NULL;
	}
	$n = count($new_filter);
	$vals = array_combine(array_keys($new_filter),array_fill(0,$n,NULL));
	foreach($items as $arr)
		if(is_array($arr) && !empty($arr)) {
			foreach($new_filter as $key => $val) {
				// uppercase check if ignore is TRUE
				if($val !== NULL && array_key_exists($val,$arr)) $vals[$key] = $arr[$val];
				// origin or lower case check
				if(array_key_exists($key,$arr)) $vals[$key] = $arr[$key];
			}
		}
	return array_values($vals);
}
//==============================================================================
// Recursive call to array_key_exists
function array_key_exists_recursive($key,$arr) {
    if(empty($arr)) return FALSE;
    foreach($arr as $kk => $vv) {
        if($kk === $key) return TRUE;
        if(is_array($vv)) {
            if(array_key_exists_recursive($key,$vv)) return TRUE;
        }
    }
    return FALSE;
}
//==============================================================================
// array-key duplicate avoidance
function array_key_unique($key,&$arr) {
    $wkey = $key;
    for($n=1;array_key_exists($key,$arr); $n++) $key = "{$wkey}::#{$n}";
    return $key;
}
//==============================================================================
// set value by array-key duplicate avoidance
function set_array_key_unique(&$arr,$key,$val) {
    $key = array_key_unique($key,$arr);
    $arr[$key] = $val;
}
//==============================================================================
// convert array element STRING to INTEGER
function array_intval($arr) {
    return array_map(function($v) { return intval($v);} , $arr);
}
//==============================================================================
// convert to num STRING to INTEGER
function array_intval_recursive($arr) {
	if($arr === NULL) return NULL;
    if(is_scalar($arr)) return (is_numeric($arr))?intval($arr):$arr;
    return array_map(function($v) {
        if(is_array($v)) return array_intval_recursive($v);
        return (is_numeric($v))?intval($v):$v;
    },$arr);
}
//==============================================================================
// convert nexting array to flat array
function array_flat_reduce($arr) {
    $wx = [];
    $reduce_array = function ($arr) use(&$reduce_array,&$wx) {
        if(is_array($arr)) {
            foreach($arr as $key => $val) {
                if(is_array($val)) {
                    $reduce_array($val);
                } else if(isset($wx[$key])) {
                    $wx[] = $val;
                // } else if(is_numeric($key)) {
                //     $wx[] = $val;
                } else {
                    $wx[$key] = $val;
                }
            }
        } else $wx[] = $arr;
    };
    $reduce_array($arr);
    return $wx;
}
//==============================================================================
// Generate URI from array, even when there is an array in the element
function array_to_URI($arr,$query=NULL) {
    $array_builder = function ($lst) {
        $ret = [];
        foreach($lst as $val) {
            $uri = (is_array($val)) ? array_to_URI($val) : strtolower($val);
            if(!empty($uri)) $ret[] = trim($uri,'/');
        }
        return $ret;
    };
    $ret = $array_builder($arr);
    return implode('/',$ret) . ((!empty($query)) ? "?{$query}":'');
}
//==============================================================================
// Concatenate array values by key value
function array_concat_keys(&$arr,$keys) {
    if(is_scalar($keys)) return $keys;
    $ss = '';
    foreach($keys as $kk => $val) {
        $sep = (is_numeric($kk)) ? ' ' : $kk;
        $item = (isset($arr[$val])) ? $arr[$val] : '';
        if(!empty($item)) $ss = "{$ss}{$sep}{$item}";
    }
    return trim($ss);
}
//==============================================================================
// set array element
function array_set_element(&$arr,$name,$val) {
	if(!empty($val)) $arr[$name] = $val;
	else if(!array_key_exists($name,$arr)) $arr[$name] = '""';
}
//==============================================================================
// set array element
function array_key_rename(&$arr,$from,$to) {
	if(isset($arr[$from])) {
		$arr[$to] = $arr[$from];
		unset($arr[$from]);
	}
}
//==============================================================================
// set array element
function array_set_key_value(&$arr,$keys,$vals) {
	foreach(array_combine($keys,$vals) as $key => $val) $arr[$key] = $val;
}
//==============================================================================
// get array element by structured-name
function array_member_value($nVal,$names) {
    if(empty($names)) return $nVal;
    $vset = (mb_strpos($names,'.') !== FALSE) ? array_filter(explode('.',$names),'strlen'):[$names];
    foreach($vset as $nm) {
        if(is_array($nVal) && array_key_exists($nm,$nVal)) {
            $nVal = $nVal[$nm];
        } else return NULL;
    }
    return $nVal;
}
//==============================================================================
// get AND condition array by separate SPC word from find keyword
function condition_array($keyset,$keystr) {
	$cond = [];
	$and = explode(' ',str_replace(['　','  '],' ',$keystr));
    foreach($and as $val) {
		if(mb_substr($val,0,1)==='-') {
			$val = mb_substr($val,1);
			$cond[] = ['NOT' => [$keyset => $val]];
		} else  $cond[] = [$keyset => $val];
    }
    return $cond;
}
//==============================================================================
// get AND condition array by separate SPC word from find keyword
function condition_array_multi($keyset,$keystr) {
	$and_str = explode(' ',str_replace(['　','  '],' ',$keystr));
	$and_cond = [];
    foreach($and_str as $and_val) {
		$cond = [];
		foreach(explode('|',$and_val) as $val) {
			if(mb_substr($val,0,1)==='-') {
				$val = mb_substr($val,1);
				$cond[] = ['NOT' => [$keyset => $val]];
			} else  $cond[] = [$keyset => $val];
		}
		$and_cond[] = ['OR'=>$cond];
	}
    return $and_cond;
}
