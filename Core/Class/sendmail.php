<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *   SendMail: メール送信
 */
class SendMail {

	public static $Header;	// メールヘッダ
	public static $From;	// 差出人
	public static $To;		// 宛先		[address => name] array
	public static $CC;		// 写し		[address => name]  array
	public static $BCC;		// BCC		[address => name]  array
	public static $Message;		// メール本文
	public static $Subject;		// 件名

//==============================================================================
// static クラスにおける初期化処理
static function Init() {
	static::$Header	= '';		// メールヘッダ
	static::$From	= [];		// 差出人
	static::$To		= [];		// 宛先
	static::$CC		= [];		// 写し
	static::$BCC	= [];		// ブラインドコピー
	static::$Message= '';		// メール本文
	static::$Subject= '';		// 件名
}
//==============================================================================
// メール本体の作成
//	From:	[ email => name ]
//	To:		[ email => name, ... emailes(address;address,address,... ) ]
//	Cc: Bcc: same as To:
static function Reset($obj) {
	static::Init();
	foreach($obj as $key => $val) {
		switch($key) {
		case 'From':	static::$From = (is_array($val)) ? $val : [ $val => '']; break;
		case 'To':		static::$To = self::email_array($val); break;
		case 'Cc':		static::$CC = self::email_array($val); break;
		case 'Bcc':		static::$BCC= self::email_array($val); break;
		case 'Subject':	static::$Subject = $val; break;
		case 'Message':	static::$Message = $val; break;
		}
	}
}
//==============================================================================
// 本文に追加
static function AppendMessage($msg) {
	static::$Message .= $msg;
}
//==============================================================================
// アドレス配列に変換
private static function email_array($arr) {
	if(is_scalar($arr)) $arr = [ $arr => '' ];
	$addres = [];
	foreach($arr as $addr => $name) {
		if(is_numeric($addr)) {
			$emails = str_explode([',',';'],$name);
			foreach($emails as $key) $addres[$key] = '';
		} else $addres[$addr] = $name;
	}
	return 	$addres;
}
//==============================================================================
// 差出人を設定
static function SetFrom($username,$email) {
	static::$From = mb_encode_mimeheader($username) . " <{$email}>";
}
//==============================================================================
// 写しの宛先を追加
// mail-address => username
static function AppendCC($email) {
	$emails = email_array($email);
	foreach($emails as $adr => $name) static::$CC[$adr] = $name;
}
//==============================================================================
// メールアドレスとユーザー名
private static function convMailAddress($arr) {
	$addres = [];
	foreach($arr as $addr => $name) {
		$addres[] = (empty($name)) ?  "<{$addr}>" : mb_encode_mimeheader($name) . " <{$addr}>";
	}
	return 	implode(', ',$addres);
}
//==============================================================================
// 差出人を設定
private static function ConvHeader($header,$attr,$addres) {
	$cc   = self::convMailAddress($addres);
	if(!empty($cc)) $header = "{$header}{$attr}: {$cc}\n";
	return $header;
}
//==============================================================================
// メールデバッグ用
private static function MailAddress($addres) {
	if(is_scalar($addres)) return "<{$addres}>";
	$addr = [];
	foreach($addres as $mm => $nm) $addr[] = "{$nm} <{$mm}>";
	return implode(',',$addr);
}
//==============================================================================
// メール送信
static function Send() {
	$header = self::ConvHeader('','From',static::$From);
	$header = self::ConvHeader($header,'Cc',static::$CC);
	$header = self::ConvHeader($header,'Bcc',static::$BCC);
	$to   = self::convMailAddress(static::$To);
	$body = mb_convert_kana(static::$Message, "KV");		// 半角カナを全角に濁点付き文字は1文字に変換
	$subject = mb_convert_kana(static::$Subject, "KV");
	mb_language('uni');
	if(defined('SENDMAIL_DEBUG')) {		// デバッグメールの宛先が定義されている
		$body = array_to_text([
			'HEADER'=> $header,
			'MAIL-BODY' => [
				'From'	=> self::MailAddress(static::$From),
				'To'	=> self::MailAddress(static::$To),
				'Cc'	=> self::MailAddress(static::$CC),
				'Bcc'	=> self::MailAddress(static::$BCC),
				'SUBJECT'=>static::$Subject,
				'BODY'	=> $body,
			]]);
		$to = SENDMAIL_DEBUG;
		$subject = "DEBUG: {$subject}";
	}
	$header = self::ConvHeader('','From',static::$From);
	if(mb_send_mail($to,$subject,$body,$header) === false) {
		sysLog::die(['MAIL-CHECK' => [
			'HEAD'=> [ 'FROM' => static::$From, 'CC' => static::$CC, 'BCC' => static::$BCC],
			'HEADER'=> htmlspecialchars($header),
			'TO'=>static::$To,
			'SUBJECT'=>static::$Subject,
			'BODY'=>$body]]);
	}
}

}
