<h2> Biscuit(MAP)フレームワーク </h2>
<p>Biscuit(MAP)はCakePHPにインスパイアされて作成したMVCもどきのミニフレームワークです。
</p>
<pre>
 @copyright  Copyright (c) 2018 - 2020 by nTak
 @license    MIT
 @version 0.8.1, 2020-05-05
 System Require: PHP 5.6 or Higher
</pre>
<h3>特徴</h3> 
<p>
<ul>
<li>軽量でコンパクト（一般的なフレムワークに比べて）</li>
<li>コントローラーのリソースをひとつのフォルダー構造に格納するモジュールフォルダー方式を採用</li>
<li>コントローラーごとに参照するデータベースを定義可能</li>
<li>複数のWEBアプリケーションが独立して同居できる（MAP::Multi Application Platform）/li>
<li>ビューテンプレートが直感的なセクション構造で作成＆修正が容易</li>
<li>スタイルシートやjavascriptファイルの結合とコンパクト出力機能</li>
<li>多言語クラスによる複数の言語切替えが可能</li>
</ul>
</p>
<h3>インストール</h3> 
<p>
<pre>
# git clone XXXXX
or
dounload zip, and extract files.
</pre>
</p>
<h3>使用方法</h3> 
<h4>コントローラークラスの定義</h4>
<p></p>
<blockquote>
<pre>
class IndexController extends AppController {
	public $defaultAction = 'List';		//  Default Action
	public $disableAction = [ 'Page', 'Find' ];	// Ignore Action on AppController class.

	protected function ClassInit() {
        // Initialized for this Controller
	}
}
</pre>
</blockquote>
<h4>モデルクラスの定義</h4>
<p>
</p>
<blockquote>
<pre>
class IndexModel extends AppModel {
    static $DatabaseSchema = [
        // Database Reference, Relation, PostForm Tag Schema Definition
        ...
    ];

    protected function ClassInit() {
        // Initialized for this Controller
    }
}
</pre>
</blockquote>
<h4>ビューヘルパーの定義</h4>
<p>
</p>
<blockquote>
<pre>
class IndexHelper extends AppHelper {
    // Generating HTML for View Template
}
</pre>
</blockquote>
<h4>リソースーの定義</h4>
<p>
</p>
<blockquote>
<pre>
// Resource define by import files
@comment => off
Stylesheet => [
    // Style template for module
    common => [
        import => [
            mystyle.css
            ...
        ]
        section => ^common       // invoke parent section resource.
    ]
]
Javascript => [
    // Javascript template for module
    common => [
        // import type is JQuery code.
        jquery => [
            myevent.js
            ...
        ]
        // normal import
        import => menu.js
        section => ^common       // invoke parent section resource.
    ]
]
</pre>
</blockquote>
