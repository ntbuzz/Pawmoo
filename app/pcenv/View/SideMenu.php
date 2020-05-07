<div class='title'><span class="boldtxt"><?= $Helper->_('Toolbar.フィルタ表示') ?></span></div>
<ul id="sitemenu" class="filetree">
<li><span class="file" id="host_help"><?= $Helper->ALink('/hosts/page/1','#Toolbar.表示.ホスト') ?></span></li>
<li><span class="file"><?= $Helper->ALink('/names/page/1','#Toolbar.表示.名前') ?></span></li>
<li><span class="file"><?= $Helper->ALink('/os/page/1','#Toolbar.表示.OSリスト') ?></span></li>
<li><span class="file"><?= $Helper->ALink('/licenses/page/1','#Toolbar.表示.ライセンス') ?></span></li>
<li><span class="file"><?= $Helper->ALink('/apps/page/1','#Toolbar.表示.アプリ') ?></span></li>

<li class="closed"><span class="folder"><?= $Helper->_('Toolbar.管理ページ') ?></span>
  <ul class='global'>
    <li><span class="file"><?= $Helper->ALink(':','#Toolbar.管理ページ.トップ') ?></span></li>
		<li><span class="file"><?= $Helper->ALink(':phpinfo.php','#Toolbar.管理ページ.PHP情報') ?></span></li>
		<li><span class="file"><?= $Helper->ALink(':SQLiteManager','#Toolbar.管理ページ.SQLite',true) ?></span></li>
		<li><span class="file"><?= $Helper->ALink(':phppgadmin','#Toolbar.管理ページ.PostgreSQL') ?></span></li>
    </ul>
</li>
<li><span class="file global"><?= $Helper->ALink(':redmine/','#Toolbar.Redmine',true) ?></span></li>
</ul>
