<?php
class JapanHolidays {
	const JapanHoliday = [
		// 休日名			月,日,種,振, 開始,終了,無効, 例外配列
		['元日',			 1, 1, 0, 1,  1948,    0,    0, 0],   // 
		['成人の日',		 1,15, 0, 1,  1949, 1999,    0, 0],   // 
		['成人の日',		 1, 2, 3, 0,  2000,    0,    0, 0],   // 
		['建国記念の日',	 2,11, 0, 1,  1967,    0,    0, 0],   // 
		['天皇誕生日',		 2,23, 0, 1,  2020,    0,    0, 0],   // 
		['春分の日',		 3, 1, 1, 1,  1948,    0,    0, 0],   // 
		['天皇誕生日',   	 4,29, 0, 1,  1949, 1989,    0, 0],   // 
		['みどりの日',		 4,29, 0, 1,  1990, 2006,    0, 0],   // 
		['昭和の日',		 4,29, 0, 1,  2007,    0,    0, 0],   // 
		['憲法記念日',		 5, 3, 0, 1,  1948,    0,    0, 0],   // 
		['国民の休日',		 5, 4, 0, 0,  1988, 2006,    0, 0],   // 
		['みどりの日',		 5, 4, 0, 0,  2007,    0,    0, 0],   // 
		['こどもの日',		 5, 5, 0, 1,  1948,    0,    0, 0],   // 
		['海の日',			 7,20, 0, 1,  1996, 2002,    0, 0],   // 
		['海の日',			 7, 3, 3, 0,  2003,    0,    0,[ 2020 => '7/23', ] ],   // 東京オリンピック特別
		['山の日',			 8,11, 0, 1,  2016,    0,    0,[ 2020 => '8/10', ] ],   // 東京オリンピック特別
		['敬老の日',		 9,15, 0, 1,  1966, 2002,    0, 0],   // 
		['敬老の日',		 9, 3, 3, 0,  2003,    0,    0, 0],   // 
		['秋分の日',		 9, 2, 2, 1,  1948,    0,    0, 0],   // 
		['体育の日',		10,10, 0, 1,  1966, 1999,    0, 0],   // 
		['体育の日',		10, 2, 3, 0,  2000, 2019,    0, 0],   // 
        ['スポーツの日',	10, 2, 3, 0,  2020,    0,    0,[ 2020 => '7/24', ] ],   // 東京オリンピック特別 
		['文化の日',		11, 3, 0, 1,  1948,    0,    0, 0],   // 
		['勤労感謝の日',	11,23, 0, 1,  1948,    0,    0, 0],   // 
		['天皇誕生日',		12,23, 0, 1,  1989, 2018,    0, 0],   // 
		// 年末年始(年またぎになるのでこちらで定義)
		['年末休業',		12,30, 0, 0,  1989,    0,    0, 0],   // 
		['年末休業',		12,31, 0, 0,  1989,    0,    0, 0],   // 
		['年始休業',		 1, 2, 0, 0,  1989,    0,    0, 0],   // 
		['年始休業',		 1, 3, 0, 0,  1989,    0,    0, 0],   // 
        //以下、1年だけの祝日
        ['皇太子明仁親王の結婚の儀',	 4, 10, 0, 1, 1959, 1959,  0, 0],   // 
        ['昭和天皇の大喪の礼',			 2, 24, 0, 1, 1989, 1989,  0, 0],   // 
        ['即位礼正殿の儀',				11, 12, 0, 1, 1990, 1990,  0, 0],   // 
        ['皇太子徳仁親王の結婚の儀',	 6,  9, 0, 1, 1993, 1993,  0, 0],   // 
        ['天皇の即位の日', 				 5,  1, 0, 1, 2019, 2019,  0, 0],   // 
		['即位礼正殿の儀',				10, 22, 0, 1, 2019, 2019,  0, 0],   // 
		];
	// 一般休日
	const Restday = [
		// 休日名		月,日,日数,該当年,無効
		['夏期休業',	 7,28, 5,  2014,   0],
		['夏期休業',	 7,27, 5,  2015,   0],
		['夏期休業',	 8, 1, 5,  2016,   0],
		['夏期休業',	 7,31, 5,  2017,   0],
		['夏期休業',	 7,30, 5,  2018,   0],
		['夏期休業',	 7,29, 5,  2019,   0],
	];
	private $HolidayTable;			// 休日表
//===============================================================================
// コンストラクタ
	function __construct(){
		$this->HolidayTable = array();
		foreach(range(2014,2020) as $year) {
			$this->HolidayTable[$year] = $this->calcYearholiday($year);
		}
	}
//===============================================================================
// デバッグ用ダンプ
	function dumpHoliday() {
		dumparray($this->HolidayTable,"祝日一覧");
	}
//===============================================================================
// 指定年の休日一覧を作成
	private function calcYearholiday($yyyy) {
		$holidayarray = array();
		$begday = mktime(0,0,0, 1, 1,$yyyy);			// １月１日を起点
		foreach(self::JapanHoliday as $val) {
			// 休日名			月,日,種類,振替,曜日,開始,終了,無効,重要,色
			list($title,$mm,$dd,$tt,$alt,$beg,$end,$enable,$ex) = $val;
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
					$holidayarray[$key] = ["振替({$title})", date('Y/m/d',$hol)];
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
				$holidayarray[$chk] = ['国民の休日', date('Y/m/d',$hol)];
			}
		}
		// 一般休日を加える
		foreach(self::Restday as $val) {
			// 休日名,月,日,日数,該当年,無効
			list($title,$mm,$dd,$nn,$yy,$enable) = $val;
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
//===============================================================================
// 休日判定
	private function isholiday($dt) {
		$yyyy = intval(date('Y',$dt));
		if(array_key_exists($yyyy,$this->HolidayTable)) {
			$begday = mktime(0,0,0,1,1,$yyyy);		// 1月1日を起点にする
			$dm = (strtotime(date("Y/m/d",$dt)) - $begday)/86400;
			return array_key_exists($dm,$this->HolidayTable[$yyyy]) or in_array(date('w',$dt),[0,6]);
		}
	}
//===============================================================================
// 稼働日
	public function nextWorkingDay($workin) {
		while($this->isholiday($workin) ) $workin += (24*60*60);	// 24時間後＝土日をスキップ
		return $workin;
	}
//===============================================================================
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
