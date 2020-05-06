<script>
$(function() {
//タブクリックしたときのファンクションをまとめて指定
$('.tab li').click(function() {
	//.index()を使いクリックされたタブが何番目かを調べ、
	//indexという変数に代入します。
	var index = $('.tab li').index(this);
	//コンテンツを一度すべて非表示にし、
	$('.content li').css('display','none');
	//クリックされたタブと同じ順番のコンテンツを表示します。
	$('.content li').eq(index).css('display','block');
	//一度タブについているクラスselectを消し、
	$('.tab li').removeClass('select');
	//クリックされたタブのみにクラスselectをつけます。
    $(this).addClass('select');
    $(".datalist").scrollTop(0);
});
});
</script>

<div class='tabmenu fixedsticky' data-class="datalist">
<ul class="tab">
	<li class="select">OS情報</li>
	<li>拡張情報</li>
	<li>ライセンス情報</li>
	<li>インストール情報</li>
</ul>
</div>
<div class='content-panel'>
<ul class="content">
<li>
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<th>ホスト名：</th>
<td>
<input type="text" name="hostname" id="hostname" size="16" value="<?php echo $RecData['hostname'] ?>">
</td>
<th>OS</th><td><?php echo $RecData['os']; ?></td>
</tr>
    <tr>
        <th>Localtion:</th>
        <td>
        <input type="text" name="location" id="location" size="50" value="<?php echo $RecData['location'] ?>">
        </td>
        <th>Licence:</th>
        <td><?php echo $RecData['prodkey'] ?></td>
    </tr>
<tr>
    <th valign="top">
Service:
    </th>
    <td>
<textarea id="service" rows="5" cols="40"><?php echo $RecData['service'] ?></textarea>
    </td>
    <th valign="top">
Note:
    </th>
    <td>
<textarea name="note" rows="5" cols="40"><?php echo $RecData['note'] ?></textarea>
    </td>
</tr>
</table>
<pre>
ホスト名：<input type="text" name="hostname" id="hostname" size="16" value="<?php echo $RecData['hostname'] ?>">
OS: <?php echo $RecData['os']; ?>

Localtion:<input type="text" name="location" id="location" size="60" value="<?php echo $RecData['location'] ?>">
IP：<?php echo $RecData['subnet'] ?>.<input type="text" name="ipaddr" id="ipaddr" size="3" value="<?php echo $RecData['ipaddr'] ?>">
有効：<input type="radio" name="active" value="1"><?php echo $RecData['active']; ?>
<hr>
Service:
<textarea id="service" rows="5" cols="80"><?php echo $RecData['service'] ?></textarea>
Note:
<textarea name="note" rows="5" cols="80"><?php echo $RecData['note'] ?></textarea>
</pre>
</li>
<li class="hide">
<pre>
ProductKey：<input type="text" name="prodkey" id="prodkey" size="80" value="<?php echo $RecData['prodkey'] ?>">
</pre>
</li>
</ul>

</div>
