//  セクション・レイアウト定義

@Header => [	          // @ ViewTemplate() 呼び出し
	 PageTitle => $#TITLE		// 引数は変数にセット
	 AdditionHeader => [
		css/style.css      // 
		index/css/common.min.css      // 結合出力
       	index/js/common.min.js        // 結合出力
	]
]

-body.class#id =>  [ bgcolor => "white" ]       // HTMLタグ出力
.appWindow => [    // タグ名省略は DIVタグセクション
    .pretty-split-pane-frame => [
	    ".split-pane fixed-left" => [
    	    .split-pane-component#left-component => [
				+echo => '<img src="/biscuit/res/images/biscuit.png" />'
            	@SideMenu
	        ]
    	    .split-pane-divider#vertical-divider => []
        	.split-pane-component#right-component => [
				@Toolbar
            	".split-pane horizontal-percent" => [
                	".split-pane-component list-view#list-component" => [
						&makeListTable =>  [ category => 'content2' ]
	                ],
    	            .split-pane-divider#bottom-divider" => []
	        	]
    		]
		]
	]
]

@Footer
