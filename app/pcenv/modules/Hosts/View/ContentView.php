<script>
$(function() {
//タブクリックしたときのファンクションをまとめて指定
$('.tabmenu li').click(function() {
	//.index()を使いクリックされたタブが何番目かを調べ、
	//indexという変数に代入します。
	var index = $('.tabmenu li').index(this);
	//コンテンツを一度すべて非表示にし、
	$('.content li').css('display','none');
	//クリックされたタブと同じ順番のコンテンツを表示します。
	$('.content li').eq(index).css('display','block');
	//一度タブについているクラスselectを消し、
	$('.tabmenu li').removeClass('selected');
	//クリックされたタブのみにクラスselectをつけます。
	$(this).addClass('selected');
	$(".datalist").scrollTop(0);
});
});
var oss = [
<?= $Helper->SelectList('license_id'); ?>
];
</script>

<div class='tabmenu fixedsticky' data-element=".datalist">
	<ul class="tab">
		<li class="selected"><?=  $Helper->_('.tabmenu.基本情報') ?></li>
		<li><?=  $Helper->_('.tabmenu.拡張情報') ?></li>
		<li><?=  $Helper->_('.tabmenu.アプリケーション') ?></li>
		<li><?=  $Helper->_('.tabmenu.ライセンス') ?></li>
		<li><?=  $Helper->_('.tabmenu.インストール') ?></li>
	</ul>
</div>

<div class='content-panel'>

	<ul class="content">
		<?= $Helper->TabContents('base','base'); ?>

			<?php $Helper->Form('host/update/' . $RecData['id'],array('id'=>"updatedata")); ?>
				<table border="0" cellspacing="0" cellpadding="0">
				<tr>
					<th><?=  $Helper->_('.Dialog.ホスト名：') ?></th>
					<td>
						<?php  $Helper->Select('name_list_id','hostname'); ?>
					</td>
					<td>
						IP：<?= $RecData['subnet'] ?>.<input type="text" name="ipaddr" size="3" value="<?= $RecData['id'] ?>">
						<input type="checkbox" name="active" value=1<?= ($RecData['active'])?' checked':''; ?>>:<?=  $Helper->_('.Dialog.有効') ?>
						<hr>
						<input type="radio" name="subnet" value="DHCP"<?= ($RecData['subnet']==='DHCP')?' checked':''; ?>>:DHCP
						<input type="radio" name="subnet" value="192.168.111"<?= ($RecData['subnet']!=='DHCP')?' checked':''; ?>>:IP
					</td>
					<th>OS</th>
					<td>
						<?php  $Helper->Select('operating_system_id','operating_system'); ?>
					</td>
				</tr>
				<tr>
			    	<th>Localtion:</th>
			    	<td colspan="2">
			        	<input type="text" name="location" size="50" value="<?= $RecData['location'] ?>">
    				</td>
			    	<th>Licence:</th>
			    	<td>
						<?php  $Helper->Select('license_id','license'); ?>
					</td>
				</tr>
				<tr>
			    	<th valign="top">
						Service:
			    	</th>
    				<td colspan="2">
						<textarea name="service" rows="5" cols="40"><?= $RecData['service'] ?></textarea>
    				</td>
			    	<th valign="top">
						Note:
			    	</th>
    				<td>
						<textarea name="note" rows="5" cols="40"><?= $RecData['note'] ?></textarea>
    				</td>
				</tr>
				</table>
				<input type="submit" value="更新">
			</form>
		</li>

		<?= $Helper->TabContents('ext'); ?>
		{%&TabContents('ext')%}
			拡張情報
			<pre>
				LicenseKey：	<?php  $Helper->Select('license_id','license'); ?>
				LicenseKey：	{%&Select('license_id','license')%}
			</pre>
		</li>

		<?= $Helper->TabContents('app'); ?>
			<pre>
				アプリケーション情報
			</pre>
		</li>

		<?= $Helper->TabContents('licence'); ?>
			<pre>
				ライセンス情報
			</pre>
		</li>

		<?= $Helper->TabContents('install'); ?>
			<?php $Helper->Form('host/filter/',array('id'=>"filterdata")); ?>
 				<pre>
					インストール情報
 					このパソコンの構築手順を示す。
 					<?= $Helper->Input('text','begDate',["id" => "datepicker1","value" => $Helper->begDate]); ?>
～ <input type="text" id="datepicker2" name="endDate">
				</pre>
				<input type="submit" value="更新">
			</form>
		</li>
	</ul>

</div>