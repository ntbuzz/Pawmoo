
<div class='title'><span class="boldtxt"><?= $Helper->_('Toolbar.トピックス') ?></span></div>
<ul id="sitemap" class="filetree">
<li><span class="file"><?= $Helper->ALink('/hosts','#Toolbar.表示.ホスト') ?></span></li>
<li><span class="file"><?= $Helper->ALink('/names','#Toolbar.表示.名前') ?></span></li>
<li><span class="file"><?= $Helper->ALink('/os','#Toolbar.表示.OSリスト') ?></span></li>
<li><span class="file"><?= $Helper->ALink('/licenses','#Toolbar.表示.ライセンス') ?></span></li>
<li><span class="file"><?= $Helper->ALink('/apps','#Toolbar.表示.アプリ') ?></span></li>

<li class="closed"><span class="folder"><?= $Helper->_('Toolbar.管理ページ') ?></span>
  <ul class='global'>
    <li><span class="file"><?= $Helper->ALink(':','#Toolbar.管理ページ.トップ') ?></span></li>
		<li><span class="file"><?= $Helper->ALink(':phpinfo.php','#Toolbar.管理ページ.PHP情報') ?></span></li>
		<li><span class="file"><?= $Helper->ALink(':SQLiteManager','#Toolbar.管理ページ.SQLite',true) ?></span></li>
		<li><span class="file"><?= $Helper->ALink(':phpPgAdmin','#Toolbar.管理ページ.PostgreSQL') ?></span></li>
    </ul>
</li>
<li><span class="file global"><?= $Helper->ALink(':redmine/','#Toolbar.Redmine',true) ?></span></li>
</ul>
