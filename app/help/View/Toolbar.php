
<div class="font20" id="toolbar">
<div class="leftitem">
<span class="font24" id="popup-left">■</span>
</div>
<div class="rightitem">
<input type="text" class="font16" id="c00" size="20" name="cc">
<span class="font24" id="popup-dialog">≡</span>
</div>
<ul id="menu">
	<li><?= $Helper->ALink("/index",'#Toolbar.home') ?></li>
	<li><?= $Helper->ALink('#','#Toolbar.ファイル') ?>
		<ul class="sub">
			<li><?= $Helper->ALink('#','#Toolbar.ファイル.新規') ?></li>
			<li><?= $Helper->ALink('#','#Toolbar.ファイル.開く') ?></li>
			<li><?= $Helper->ALink('#','#Toolbar.ファイル.終了') ?></li>
		</ul>
	</li>
	<li><?= $Helper->ALink('#','#Toolbar.編集') ?>
		<ul class="sub">
			<li><?= $Helper->ALink('#','#Toolbar.編集.コピー') ?></li>
			<li><?= $Helper->ALink('#','#Toolbar.編集.貼付け') ?></li>
			<li><?= $Helper->ALink('#','#Toolbar.編集.削除') ?></li>
			<li><?= $Helper->ALink('#','#Toolbar.編集.取消') ?></li>
			<li><?= $Helper->ALink('#','#Toolbar.編集.検索') ?></li>
		</ul>
	</li>
	<li><?= $Helper->ALink('#','#Toolbar.表示') ?>
		<ul class="sub">
			<li><?= $Helper->ALink('/index/page/','#Toolbar.home') ?></li>
			<li><?= $Helper->ALink('/hosts/page/','#Toolbar.表示.ホスト') ?></li>
			<li><?= $Helper->ALink('/names/page/','#Toolbar.表示.名前') ?></li>
			<li><?= $Helper->ALink('/os/page/','#Toolbar.表示.OSリスト') ?></li>
			<li><?= $Helper->ALink('/licenses/page/','#Toolbar.表示.ライセンス') ?></li>
			<li><?= $Helper->ALink('/apps/page/','#Toolbar.表示.アプリ') ?></li>
			<li><?= $Helper->ALink('/index/bindlist/100/50','#Toolbar.表示.BIND') ?></li>
		</ul>
	</li>
	<li><?= $Helper->ALink('#','#Toolbar.管理ページ') ?>
		<ul class="sub global">
		<li><?= $Helper->ALink(':','#Toolbar.管理ページ.トップ') ?></li>
		<li><?= $Helper->ALink(':phpinfo.php','#Toolbar.管理ページ.PHP情報') ?></li>
		<li><?= $Helper->ALink(':SQLiteManager','#Toolbar.管理ページ.SQLite') ?></li>
		<li><?= $Helper->ALink(':phpPgAdmin','#Toolbar.管理ページ.PostgreSQL') ?></li>
        </ul>
    </li>
	<li><?= $Helper->ALink('#','#Toolbar.ヘルプ') ?>
		<ul class="sub">
		<li><?= $Helper->ALink('#','#Toolbar.ヘルプ.よくある質問') ?></li>
		<li><?= $Helper->ALink('#','#Toolbar.ヘルプ.問合わせ') ?></li>
        </ul>
    </li>
</ul>
</div>


