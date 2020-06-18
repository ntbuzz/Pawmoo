<h2> Biscuits(MAP)フレームワーク </h2>
<p>Biscuits(MAP)はCakePHPにインスパイアされて作成したMVCもどきのミニフレームワークです。
</p>
<pre>
 @copyright  Copyright (c) 2018 - 2020 by nTak
 @license    MIT
 @version 0.16.0, 2020-06-18
 System Require: PHP 5.6 or Higher
</pre>
<h3>特徴</h3> 
<ul>
<li>軽量でコンパクト（一般的なフレムワークに比べて）</li>
<li>コントローラーのリソースをひとつのフォルダー構造に格納するモジュールフォルダー方式を採用</li>
<li>コントローラーごとに参照するデータベースを定義可能</li>
<li>複数のWEBアプリケーションが独立して同居できる（MAP::Multi Application Platform）/li>
<li>ビューテンプレートが直感的なセクション構造で作成＆修正が容易</li>
<li>スタイルシートやjavascriptファイルの結合とコンパクト出力機能</li>
<li>多言語クラスによる複数の言語切替えが可能</li>
</ul>
<h3>インストール</h3> 
<p>
</p>
<pre>
git clone XXXXX
or
dounload zip, and extract files.
</pre>
<h3>使用方法</h3> 
<h4>コントローラークラスの定義</h4>
<p>
コントローラクラスは必ず定義します。リクエストURIの対応するアクションメソッドを実行します。<br>
メソッドが未定義のときは $defaultAction に定義されたアクションを実行します。<br>
</p>
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
モデルクラスは必須ではありませんが、データベースを参照するには必ず必要です。<br>
$DatabaseSchema 変数によりモデルクラスごとに別々のデータベースを参照することが可能です。<br>
フレームワークコアにハンドラーを追加すれば、様々なデータベースを参照できるようになります。<br>
</p>
<blockquote>
<pre>
class IndexModel extends AppModel {
    static $DatabaseSchema = [
        // Database Reference, Relation, PostForm Tag Schema Definition
        'Handler' => 'SQLite',  // SQLite3, PostgreSQL, ...
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
ヘルパークラスは必須ではありませんが、固有の整形出力をするには必須です。<br>
ビューテンプレートから呼び出せます。<br>
</p>
<blockquote>
<pre>
class IndexHelper extends AppHelper {
    // Generating HTML for View Template
}
</pre>
</blockquote>
<h4>リソースの定義</h4>
<p>
スタイルシートとJavascriptを小さな「パーツ」に分離して管理できるようにして<br>
それらを結合したものをブラウザに返すことができます。<br>
結合の際にコメントだけを削除したり、改行まで含めて削除してサイズをコンパクトにすることができます。<br>
</p>
<blockquote>
<pre>
// Resource define by import files
@comment => off
@compact => off
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
<p>
多言語リソースはモジュールごとにファイルを分割します。<br>
HTTP_ACCEPT_LANGUAGE にもとづき対応するセクションが読み込まれます。<br>
</p>
<blockquote>
<pre>
// Language Definition
@Schema         // Import Common schema language
.ja => [
    TITLE => "Biscuitヘルプドキュメント"
]
.en => [
    TITLE => "Biscuit Help Documents"
]
...
</pre>
</blockquote>
