//  メインページのセクション・レイアウト定義
@Header => [
	 PageTitle => ${#TITLE}
	 AdditionHeader => [
		 ./css/common.css
		 ./js/common.js
	]
]
-body =>  [ bgcolor => "whitesmoke" ]
@BlogHeader
.appWindow => [
	.blogBody.fitWindow => [
		.leftBlock => [
			@Calendar
		]
		.blogMain => [
			h1. => [ ${#.ARTICLE} ]
			.blog_list => [
				&TitleList
			]
		]
		.rightBlock => [
			@SideMenu
		]
	]
]

@Footer
