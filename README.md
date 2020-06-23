# Biscuits(MAP)フレームワーク

オブジェクト指向型で作る、MVCもどきのPHPフレームワークです。  
PHPプログラミングとデータベース操作(SQL)のスキルアップの為に作成しています。  
慣れ親しんでいる言語がObjectPascalなので、何とな〜くPascal風の匂いがするコードが紛れ込んでます。

## 特徴

* 各クラスのオブジェクト継承により、必要なメソッドのみ実装するだけでアプリが動作する
* コアシステムひとつで複数のアプリケーションを管理できるMulti Application Platform(MAP)システム
* コントローラーリソースをひとつのフォルダー構造に格納するモジュールフォルダー構成を採用
* ビューテンプレートはHTML構造をPHPの連想配列に適用したセクション形式でレイアウト作成が簡単
* 使えるかもしれないビューライブラリをいくつか用意してある

## ディレクトリ・ファイル構造

一人あるいは少人数で開発することを想定し、controller/model/view クラスファイルを
module(=contoroller) フォルダにまとめて格納する方式をとっています。  
多くのPHPフレームワークのように、クラス別にフォルダをわけると、少人数の体制では
開発が非効率になるだけでなく、修正漏れが発生しやすくなるという経験を元に決めています。
```
app/ … 複数のアプリケーションを格納するフォルダ
    /myapp     アプリケーション１のフォルダ
        /Config    設定ファイルの格納フォルダ
            config.php  各種パラメータの初期設定
        /common    共通ライブラリの格納フォルダ
        /Models    コントローラーを持たないモデルクラス
        /extends   コアクラスの拡張クラスを格納するフォルダ
        /modules   モジュールファイルの格納フォルダ
            /m1    モジュール名フォルダ（コントローラー名に相当)
                m1Controller.php    モジュールコントローラー
                m1Helper.php        HTML出力ヘルパー
                m1Model.php         モジュールモデル
                /View   モジュール個別のビューテンプレート格納フォルダ
                /res    モジュールリソースフォルダ
                    template.mss    リソース参照定義
                    /css    モジュール用スタイルシート
                    /js     モジュール用javascript
        /View       モジュール共通ビューテンプレート
            /lang   言語定義
            /res    モジュール共通リソースフォルダ
                template.mss    リソース参照定義
                /css    スタイルシート
                /js     javascript
        webwoot/     画像/css/jsファイルの格納フォルダ

Core/   フレームワークコア
    Main.php        アプリケーションルーティング
    resource.psp    リソースルーティング
    Base/           各クラスの基底クラスを定義
    Class/          コアシステムで使用する各種クラス
    Handler/        データベース入出力ハンドラー
    Tamplate/       ビューテンプレートのライブラリ

vendor/             外部ライブラリの格納フォルダ    
    vendor/         composer等でインストールするライブラリ
    webwoot/        css/jsファイルの外部ライブラリ
```

### 使用方法

#### コントローラークラスの定義

コントローラクラスは必ず定義します。リクエストURIの対応するアクションメソッドを実行します。  
メソッドが未定義のときは $defaultAction に定義されたアクションを実行します。  
コントローラーのメソッド名だけは、ルーティング処理からの命名規則に縛りがあります。
関数名はキャメルケースのみで、呼び出されるメソッド名は「Action」を付加します。  

```
class IndexController extends AppController {
	public $defaultAction = 'List';		//  Default Action
	public $disableAction = [ 'Page', 'Find' ];	// Ignore Action on AppController class.

	protected function ClassInit() {
        // Initialized for this Controller
	}
    public ListAction() {
        ......
    }
}
```

#### モデルクラスの定義

モデルクラスはデータベースを参照します。  
$DatabaseSchema 変数によりモデルクラスごとに別々のデータベースを参照することが可能です。  
フレームワークコアにハンドラーを追加すれば、様々なデータベースを参照できるようになります。  

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

ヘルパークラスはビューテンプレートだけでは処理しきれない、データ操作や固有の整形出力をします。  
PHPテンプレートを使えば同様の処理ができますが、テンプレート内では可能な限り
簡単なレイアウト処理に限定し、複雑な処理が必要な場合にはヘルパーに実装します。  
そうすることでレイアウトのデザイン変更があってもテンプレートの修正が最小限ですみます。
ヘルパーに残ったメソッドコードは使用しないゴミになる可能性はありますが…まぁそれも人生です(笑）

```
class IndexHelper extends AppHelper {
    // Generating HTML for View Template
}
```


#### リソースの定義

スタイルシートとJavascriptを小さな「パーツ」に分離して管理できるようにして  
それらを結合したものをブラウザに返すことができます。  
結合の際にコメントだけを削除したり、改行まで含めて削除してサイズをコンパクトにすることができます。  

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

多言語リソースはモジュールごとにファイルを分割します。  
HTTP_ACCEPT_LANGUAGE にもとづき対応するセクションが読み込まれます。  

```
// Language Definition
@Schema         // Import Common schema language
.ja => [
    TITLE => "Biscuitsヘルプドキュメント"
]
.en => [
    TITLE => "Biscuits Help Documents"
]
...
```
