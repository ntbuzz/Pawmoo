.blogHeader => [
+jquery => ~
	$('#admin_login').click(function() {
        var flip_url = { Admin:"index",Index:"admin"};
		var url = '/blog/'+flip_url["${$controller$}"];
		location.href = url;
	});
~

h1. => [ %${#.BLOGTITLE} => /sample/${$controller$}/ ]

// 言語スイッチ
.lang-switch => [
	style => [
		position:fixed;
		top:10px;
		right:50px;
		width:5em;
		background-color:yellow;
		text-align:center;
	]
	?${'Login.LANG'} => [
	'en*' => [ %link => [ 日本語 => "?lang=ja" ] ]
	'ja*' => [ %link => [ Englisth => "?lang=en" ] ]
	]
]

]
