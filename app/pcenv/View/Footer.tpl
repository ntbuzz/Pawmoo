// ツールバーのポップアップボックス
//
.popup-box#setting-dialog{popup-dialog} => [ size => "420,450"
  .contents => [ +echo => ~
<form method="post" action="/pcmanager/hosts/list" id="setfilter">
	<table width="100%">
	<tr>
		<th nowrap>QA対象:</th><td nowrap colspan="3"></td>
	</tr>
	<tr>
		<td colspan="4"><hr></td>
	</tr>
	<tr>
		<th nowrap>期間: </th><td><SELECT name="begdate"></SELECT></td><td>～</td><td><SELECT name="enddate"></SELECT></td>
	</tr>
	<tr>
		<td colspan="4"><hr></td>
	</tr>
	<tr>
		<th colspan="2">ステータス</th><th colspan="2">分類</th>
	</tr>
	<tr>
		<td valign="top" class="solidbox" colspan="2" nowrap>
			<ul id="ListStatus">
			</ul>
			<br />
		</td>
		<td valign="top" class="solidbox" colspan="2">
			<ul id="ListKind">
			</ul>
			<br />
		</td>
	</tr>
	<tr>
		<td colspan="4"><hr></td>
	</tr>
	<tr>
		<td class="solidbox" colspan="2">
			<div align="center" style="font-size:9pt;">
				<span class="pseudo" onclick="CheckElements('ff',0);">全選択</span> /
				<span class="pseudo" onclick="CheckElements('ff',1);">全解除</span> /
				<span class="pseudo" onclick="CheckElements('ff',2);">反転</span>
			</div>
		</td>
		<td class="solidbox" colspan="2">
			<div align="center">
				<span class="pseudo" onclick="CheckElements('gg',0);">全選択</span> /
				<span class="pseudo" onclick="CheckElements('gg',1);">全解除</span> /
				<span class="pseudo" onclick="CheckElements('gg',2);">反転</span>
			</div>
		</td>
	</tr>
	<tr>
		<td colspan="4"><hr><input type="checkbox" id="c01" name="cc">担当外</td>
	</tr>
</table>
<hr class="border">
<div align="center" style="padding-top:10px;">
	<span class="button" onclick="SetParams();">SET</span>
</div>
<input type="submit">
</form>
  ~ ]
]

.popup-box#property-dialog{popup-left} => [ size => "400,700,200,300"
  .contents => [ 
//	  @ContentView
	]
]
// #popup-left のクリックバルーンヘルプ
.popup-baloon{popup-left} => [
	${#BALOON_HELP}
]
// #popup-dialog のマウスオーバーバルーンヘルプ
.popup-baloon{@popup-dialog} => [
	${#SETTING_HELP}
]
// ページャーナビゲータのバルーンヘルプ
.popup-baloon{pager_help} => [
	${#HELP2}
]
.popup-baloon{@size_selector} => [
	${#HELP3}
]

// コンテキストメニュー
+ul.context-menu#popup_menu{myTable} => [
	#ctxEdit => [ 編集(ent) ]
	#ctxUndo => [ 取消(esc) ]
	[ <hr> ]
	#ctxDel => [ 削除(del) ]
]
