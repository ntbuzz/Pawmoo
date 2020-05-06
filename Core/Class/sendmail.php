<?php
/* -------------------------------------------------------------
 * Biscuitフレームワーク
 *   SendMail: メール送信
 */

class SendMail {

	public static $Header;		// メールヘッダ
	public static $From;		// 差出人
	public static $To;		// 宛先
	public static $CC;		// 写し
	public static $BCC;		// ブラインドコピー
	public static $Message;		// メール本文
	public static $Subject;		// 件名

//===============================================================================
// static クラスにおける初期化処理
static function __Init() {
	self::$Header	= '';		// メールヘッダ
	self::$From		= '';		// 差出人
	self::$To		= '';		// 宛先
	self::$CC		= '';		// 写し
	self::$BCC		= '';		// ブラインドコピー
	self::$Message	= '';		// メール本文
	self::$Subject	= '';		// 件名
}
//===============================================================================
// 本文に追加
static function AppendMessage($msg) {
	self::$Message .= $msg;
}
//===============================================================================
// 差出人を設定
static function SetFrom($username,$email) {
	self::$From = mb_encocde_mimeheader($username) . "<{$email}>";
}
//===============================================================================
// 写しの宛先を追加
static function AppendCC($email) {
	self::$CC .= "{$email},";
}
//===============================================================================
// メール送信
static function Send() {
	mb_language('uni');
	// 静的変数を出力するためのクロージャ
	$x = function($c) { return $c; };
	$from = "From: {$x(self::$From)}";
	$cc   = "Cc: {$x(self::$CC)}";
	$header= "{$from}\n{$cc}\n";
	$body = mb_convert_kana(self::$Message, "KV");		// 半角カナを全角に濁点付き文字は1文字に変換
	mb_send_mail($to,self::$Subject,self::$Message,$header);
}

}
