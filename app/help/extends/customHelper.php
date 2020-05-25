<?php
/* -------------------------------------------------------------
 *  customHelper: コアクラスにベンダーライブラリを加える
 * 
 */
require_once('vendor/vendor/PHPExcel/PHPExcel.php');
require_once("vendor/vendor/PHPExcel/PHPExcel/IOFactory.php");
require_once("vendor/vendor/mpdf50/mpdf.php");

//===============================================================================
class customHelper  extends AppHelper {
	// プロパティリスト
    const MyPropList = [
		'Excel' => 'PHPExcel',
		'PDF' => 'mPDF'
	];
//==================================================================================================
// Excel/PDFの動的クラスプロパティを生成
// 固有クラスのため AppObject のメソッドは使用しない
	public function __get($PropName) {
		APPDEBUG::MSG(10, $PropName . " を動的生成します。");
		if(isset($this->$PropName)) return $this->$PropName;
		// Model or View or Helper or Controller を付加する
		$props = self::MyPropList[$PropName];
		if(class_exists($props)) {
			$this->$PropName = new $props();
			return $this->$PropName;
		}
		parent::__get($PropName);
    }
}
