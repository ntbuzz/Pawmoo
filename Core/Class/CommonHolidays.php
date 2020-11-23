<?php
class CommonHolidays {
	private $HolidayTable;			// 休日表
	private $Prefix;				// LangUI Prefix
//==============================================================================
// コンストラクタ
	function __construct($HolidayList, $range) {
		$this->Prefix = get_class($this);
		// 休日用配列をプロパティ変数に登録
        foreach($HolidayList as $key => $val) $this->$key = $val;
		$this->HolidayTable = array();
		foreach($range as $year) {
			$this->HolidayTable[$year] = $this->calcYearholiday($year);
		}
	}
//==============================================================================
// デバッグ用ダンプ
	public function dumpHoliday() {
		foreach($this->HolidayTable as $key => $val) {
			echo "年: {$key}<br>\n";
			echo "プレフィクス:{$this->Prefix}<br>\n";
			echo "<table>\n";
			foreach($val as $date) {
				echo "<tr><td>{$date[1]}</td><td>{$date[0]}</td></tr>\n";
			}
			echo "</table>\n";
		}
	}
//==============================================================================
// 指定年の休日一覧を作成
	private function calcYearholiday($yyyy) {
		$holidayarray = array();
		$begday = mktime(0,0,0, 1, 1,$yyyy);			// １月１日を起点
		foreach($this->Holidays as $val) {
			// 休日名			月,日,種類,振替,曜日,開始,終了,無効,重要,色
			list($title,$mm,$dd,$tt,$alt,$beg,$end,$enable,$ex) = $val;
			if($title[0] === '.') $title = LangUI::get_value($this->Prefix,$title);
			if($end == 0) $end = 99999;
			if($yyyy >= $beg && $yyyy <= $end) {
				switch($tt) {			// 種別 0=固定日, 1=春分日, 2= 秋分日, 3=HappyManday
				case 0:		//	固定日
						$hol = mktime(0,0,0,$mm,$dd,$yyyy);
						break;
				case 1:		//	春分の日 = int(20.8431+0.242194*(年-1980)-int((年-1980)/4))
						$dd = intval(20.8431+0.242194*($yyyy-1980)-intval(($yyyy-1980)/4));
						$hol = mktime(0,0,0, 3, $dd,$yyyy);
						break;
				case 2:		//	秋分の日 = int(23.2488+0.242194*(年-1980)-int((年-1980)/4))
						$dd = intval(23.2488+0.242194*($yyyy-1980)-intval(($yyyy-1980)/4));
						$hol = mktime(0,0,0,9, $dd,$yyyy);
						break;
				case 3:		//	Happer Manday
						$t = mktime(0,0,0,$mm,1,$yyyy);		// 1日の日付
						$w = 8 - (int)date('w',$t);		// 0=日曜 ... 6=土曜、シフト量を計算
						if($w >= 7) $w = $w - 7;		// 日曜と月曜の補正
						$hol = $t + ($w + (($dd-1)*7))*86400;
						break;
				}
				// 例外指定の該当年なら日付を置換
				if(is_array($ex) && array_key_exists($yyyy, $ex)) {
					$md = explode('/',$ex[$yyyy]);
					$hol = mktime(0,0,0,$md[0],$md[1],$yyyy);
				}
				$key = ($hol - $begday)/86400;		// １月１日からの経過日数
				$holidayarray[$key] = [$title,date('Y/m/d',$hol)];
				$w = (int)date('w',$hol);
				if($w == 0 && $alt !== 0) {	// 振替休日あり
					$hol = $hol + (1*86400);
					$key = ($hol - $begday)/86400;		// １月１日からの経過日数
					$alt = LangUI::get_value($this->Prefix,".振替");
					$holidayarray[$key] = ["{$alt}({$title})", date('Y/m/d',$hol)];
				}
			}
		}
		// 国民の休日になるか判定
		$tmp = array_keys($holidayarray);
		foreach($tmp as $dt) {
			// 翌日が平日で翌々日が祝日
			$chk = $dt+1;
			if( !array_key_exists( $chk , $holidayarray) && array_key_exists( $chk+1 , $holidayarray) ) {
				$hol = $begday + ($chk*86400);
				$title = LangUI::get_value($this->Prefix,".国民の休日");
				$holidayarray[$chk] = [$title, date('Y/m/d',$hol)];
			}
		}
		// 一般休日を加える
		foreach($this->Vacations as $val) {
			// 休日名,月,日,日数,該当年,無効
			list($title,$mm,$dd,$nn,$yy,$enable) = $val;
			if($title[0] === '.') $title = LangUI::get_value($this->Prefix,$title);
			if($yy == 0) $yy = $yyyy;		// ０なら毎年
			if($yyyy == $yy) {
				$hol = mktime(0,0,0,$mm,$dd,$yyyy);
				$key = ($hol - $begday)/86400;		// １月１日からの経過日数
				do {
					if( !array_key_exists( $key, $holidayarray)) {		// 途中に祝日があったらスキップ
						$holidayarray[$key] = [$title,date('Y/m/d',$hol)];
						$nn--;
					}
					$key++;
					$hol += 86400;
				} while ($nn > 0);
			}
		}
		ksort($holidayarray);
		return $holidayarray;
	}
//==============================================================================
// 一般休日を加える
	protected function AddVacations($yyyy,$vacations,$holidayarray) {
		foreach($this->Vacations as $val) {
			// 休日名,月,日,日数,該当年,無効
			list($title,$mm,$dd,$nn,$yy,$enable) = $val;
			if($title[0] === '.') $title = LangUI::get_value($this->Prefix,$title);
			if($yy == 0) $yy = $yyyy;		// ０なら毎年
			if($yyyy == $yy) {
				$hol = mktime(0,0,0,$mm,$dd,$yyyy);
				$key = ($hol - $begday)/86400;		// １月１日からの経過日数
				do {
					if( !array_key_exists( $key, $holidayarray)) {		// 途中に祝日があったらスキップ
						$holidayarray[$key] = [$title,date('Y/m/d',$hol)];
						$nn--;
					}
					$key++;
					$hol += 86400;
				} while ($nn > 0);
			}
		}
		ksort($holidayarray);
		return $holidayarray;
	}
//==============================================================================
// 休日判定
	private function isholiday($dt) {
		$yyyy = intval(date('Y',$dt));
		if(array_key_exists($yyyy,$this->HolidayTable)) {
			$begday = mktime(0,0,0,1,1,$yyyy);		// 1月1日を起点にする
			$dm = (strtotime(date("Y/m/d",$dt)) - $begday)/86400;
			return array_key_exists($dm,$this->HolidayTable[$yyyy]) or in_array(date('w',$dt),[0,6]);
		}
	}
//==============================================================================
// 稼働日
	public function nextWorkingDay($workin) {
		while($this->isholiday($workin) ) $workin += (24*60*60);	// 24時間後＝土日をスキップ
		return $workin;
	}
//==============================================================================
// 稼働日数
	public function networkDays($beg,$fin) {
		$beg = strtotime(date('Y/m/d',$beg));
		$fin = strtotime(date('Y/m/d',$fin));
		$cnt = 0;
		while($beg <= $fin) {
			if(! $this->isholiday($beg)) $cnt++;
			$beg += (24*60*60);		// 次の日
		}
		return $cnt;
	}
}
