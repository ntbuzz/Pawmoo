//  セクション・レイアウト定義
@Header => [
    PageTitle => ${#TITLE}
    AdditionHeader => [
        res/css/common.min.css      // 結合出力
        res/js/common.min.js        // 結合出力
    ]
	Test => ${varname}
]
<body>
.appWindow => [    // タグ名省略は DIVタグセクション
	@Toolbar
~
<div id="app">
  {{ message }}
</div>
~
    .pretty-split-pane-frame => [
	    ".split-pane fixed-left" => [
    	    .split-pane-component => [
        	    .pretty-split-pane-component-inner#left-component => [
            	    @SideMenu
              	]
	        ]
    	    .split-pane-divider#vertical-divider => []
        	.split-pane-component#right-component => [
            	".split-pane horizontal-percent" => [
                	".split-pane-component list-view#list-component" => [
						&makeListTable => [ category => 'content2' ]
	                ],
    	            .split-pane-divider#bottom-divider" => []
	        	]
    		]
		]
	]
]
.modal-popup#ContentBody => [
	title => ダイアログタイトル
]

@Footer
