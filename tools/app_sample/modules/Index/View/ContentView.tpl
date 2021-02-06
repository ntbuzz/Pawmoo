//  セクション・レイアウト定義
@Header => [
	 PageTitle => ${#TITLE}
	 AdditionHeader => [
		 ./css/common.css
		 ./js/common.js
	]
]
-body =>  [ bgcolor => "white" ]       // HTMLタグ出力
@BlogHeader
.appWindow => [    // タグ名省略は DIVタグセクション
	.blogBody.fitWindow => [
		.leftBlock.stickyBar => [
			@Calendar
		]
		.blogMain => [
			+echo => "${:row_number}/${:record_max}"
			.blog_item => [ id => ${@@id}
				&BlogHeading
				.blog_body => [
					&BlogBody
				]
			]
			<hr>
    		span.rightitem => [ %▲TOP => './' ]
		]
		.rightBlock => [
			@SideMenu
		]
	]
	p#move-top => ▲
]

@Footer
