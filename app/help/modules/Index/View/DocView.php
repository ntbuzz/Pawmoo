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
	$('.new-section').click(function() {
		$("#add_section_dialog").floatWindow();
	});
});

</script>
<div class='tabmenu fixedsticky' data-element=".contents-view">
<?php $Helper->SectionTab(); ?>
</div>
<div class='content-panel'>
<?php $Helper->SectionContents(); ?>

</div>
