# Biscuits(MAP)フレームワーク

Biscuits(MAP)はオブジェクト指向型のMVCもどきミニ・フレームワークです。

## 特徴

* 一般的なフレムワークに比べ軽量でコンパクトな実装
* フレームワーク内に複数のWEBアプリケーションが独立して同居できる（MAP::Multi Application Platform）
* コントローラーのリソースをひとつのフォルダー構造に格納するモジュールフォルダー方式を採用
* ビューテンプレートが直感的なセクション構造で作成＆修正が容易
* スタイルシートやjavascriptファイルの結合とコンパクト出力機能
* 多言語クラスによる複数の言語切替えが可能

### インストール

```
$ git clone XXXXX
or
dounload zip, and extract files.
```

### 使用方法

#### コントローラークラスの定義

コントローラクラスは必ず定義します。リクエストURIの対応するアクションメソッドを実行します。<br>
メソッドが未定義のときは $defaultAction に定義されたアクションを実行します。<br>

```
class IndexController extends AppController {
	public $defaultAction = 'List';		//  Default Action
	public $disableAction = [ 'Page', 'Find' ];	// Ignore Action on AppController class.

	protected function ClassInit() {
        // Initialized for this Controller
	}
}
```

#### モデルクラスの定義

モデルクラスは必須ではありませんが、データベースを参照するには必ず必要です。<br>
$DatabaseSchema 変数によりモデルクラスごとに別々のデータベースを参照することが可能です。<br>
フレームワークコアにハンドラーを追加すれば、様々なデータベースを参照できるようになります。<br>

```
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
```

#### ビューヘルパーの定義

ヘルパークラスは必須ではありませんが、固有の整形出力をするには必須です。<br>
ビューテンプレートから呼び出せます。<br>

```
class IndexHelper extends AppHelper {
    // Generating HTML for View Template
}
```


#### リソースの定義

スタイルシートとJavascriptを小さな「パーツ」に分離して管理できるようにして<br>
それらを結合したものをブラウザに返すことができます。<br>
結合の際にコメントだけを削除したり、改行まで含めて削除してサイズをコンパクトにすることができます。<br>

```
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
```

多言語リソースはモジュールごとにファイルを分割します。<br>
HTTP_ACCEPT_LANGUAGE にもとづき対応するセクションが読み込まれます。<br>

```
// Language Definition
@Schema         // Import Common schema language
.ja => [
    TITLE => "Biscuitヘルプドキュメント"
]
.en => [
    TITLE => "Biscuit Help Documents"
]
...
```
