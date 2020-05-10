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
		$('.tab li').removeClass('selected');
		//クリックされたタブのみにクラスselectをつけます。
		$(this).addClass('selected');
		$(".contents-view").scrollTop(0);
	});
});
</script>

<div class='tabmenu fixedsticky'>
<ul class="tab">
	<li class="selected"><?=  $Helper->_('.tabmenu.basic') ?></li>
	<li><?=  $Helper->_('.tabmenu.detail') ?></li>
	<li><?=  $Helper->_('.tabmenu.install') ?></li>
</ul>
</div>
<div class='content-panel'>
<ul class="content">
<li>
<pre>
<?= $Helper->_('.Dialog.name') ?><input type="text" id="Name" size="16" value="<?= $RecData['name'] ?>">
<?= $Helper->_('.Dialog.source') ?><input type="text" id="Source" size="16" value="<?= $RecData['source'] ?>">
</pre>
</li>
<li class="hide">
<pre>
<?= $Helper->_('.Dialog.desc') ?>
<textarea id="description" rows="5" cols="80"><?= $RecData['description'] ?></textarea>
<?= $Helper->_('.Dialog.note') ?>
<textarea name="note" rows="5" cols="80"><?= $RecData['note'] ?></textarea>
</pre>
</li>
<li class="hide">
<?= $Helper->_('.Dialog.ip') ?><?php echo $RecData['IP'] ?>
<pre>
チュートリアル
CSS 学習エリア は基礎から CSS を教える複数のモジュールにスポットを当てています
 — 事前の知識は必要ありません。

CSS の第一歩

CSS (Cascading Style Sheets) はウェブページをスタイリングしたりレイアウトしたり
するのに使われます — 例えば、文字、色、大きさを変えたり、コンテンツに余白を設け
たり、複数列に分けたり、 あるいはアニメーションを加えたりなど様々な装飾機能があ
ります。このモジュールでは CSS を習得するために、どう働くかの基本とともに、構文
のありかたと HTML にスタイリングを加えることを簡単な始め方を提供します。

CSS の構成要素
このモジュールは CSS の第一歩が終わったところを引き継いでいます。言語とその構文
に慣れてきて、基本的な使用経験を積んだところで、もう少し深く掘り下げてみましょう。
このモジュールでは、カスケードと継承、利用可能なすべてのセレクターのタイプ、単位、
寸法の調整、背景や境界のスタイル付け、デバッグなど多くのことを見ていきます。

ここでの目的は、テキストの装飾やCSS レイアウトなどのより具体的な分野に進む前に、
適切な CSS を書くための手法を知り、すべての重要な理論を理解できるようになること
です。

テキストの装飾

CSS 言語の基本を習得したら、次に取り組むべき CSS のトピックはテキストの装飾です
 — これは、CSS で行う最も一般的なことの一つです。 ここでは、フォント、太字、イ
 タリック体、ラインと文字の間隔、ドロップシャドウやその他のテキスト機能の設定を
 含む、テキストの装飾の基本を見ていきます。あなたのページにカスタムフォントを適
 用し、リストとリンクを装飾するところを見ることによって、このモジュールを締めく
 くります。

CSS レイアウト

現段階で、すでに CSS の基本、テキストの装飾方法、コンテンツを格納するボックスの
装飾方法と操作方法を見てきました。今度は、ビューポートを基準にしてボックスを適切
な場所に配置する方法、および互いの配置方法を検討します。必要な前提知識をカバーし
ているので、さまざまな表示の設定、フレックスボックス・CSS グリッド・位置指定など
の最新のレイアウトツール、そしてまだ知っておきたいと思うかもしれない過去のテク
ニックのいくつかを見ながら、CSS レイアウトについて深く掘り下げることができます。

CSS を使ってよくある問題を解決する

ウェブページを作成する際のとても一般的な問題を解決するための CSS の使用方法を説明
するコンテンツの節へのリンクを提供しています。

最終行
</pre>
</li>
</ul>

</div>
