<pre>
    
***  Biscuits(MAP) 使い方説明書

1. はじめに
 Biscuits(MAP)は Controller-View-Model のようなWEBアプリケーションを作成するための
 オブジェクト指向型のミニ・フレームワークです。
 (MAP)というのは「(M)マルチ(A)アプリケーション(P)プラットフォーム」という
 意味で命名しました。これはフレームワーク内に、複数のWEBアプリケーションを
 同居させることができる点を表現しています。

 M-V-Cに相当する各ファイルを「モジュール」というフォルダ単位で管理できるので
 ファイル間の見通しが良く、機能追加や修正の際にも修正漏れやバグ発生を抑えられます。

1.1 特徴
 ビューテンプレートは一般的なフレームワークで採用されているSmartyなどの
 テンプレートエンジンを使わず、独自の「セクションテンプレート」と呼ぶ形式を
 採用しています。
 PHPの連想配列を利用して、HTMLで書く時の構造に近い記述ができるようになっています。
 これによりレイアウト全体が見渡しやすくなり、デザイン時や保守が簡単にできます。
 またスタイルシートやjavascript、言語リソースにもセクション形式を採用しています。

 フォルダ構造も他のフレームワークと異なり「モジュール」という考え方に基づいて、
 アプリケーションの機能実装をモジュール単位で行えるようにファルダ分けしました。
 これによりリクエストURIのパスとフォルダ構造が一致するので、デバッグやテストが
 容易になります。

 リクエストURIとモジュールの関係(ルーティング)
   http://localhost/bisucuit/help/index/view/100/20
   ~~~~~~~~~~~~~~~~~~~~~~~~~ ~~~~ ~~~~~ ~~~~ ~~~~~~
     フレームワークフォルダ   ↑ 　↑  　↑　　↑
     　　　　　　　　　　　　 │ 　│  　│   メソッドへのパラメータ
　　　　　　　　　　　　　　　 │ 　│   メソッド名
　　　　　　　　　　　　　　　 │  モジュール名(コントローラー)
　　　　　　　　　　　　　　アプリ名
　　　　　　　　　　　　　　　　　　　　　　
1.2 フォルダ構成

(Biscuits)         フレームワーク本体
 ├ app
 │  ├─ (アプリ1)
 │  │   ├── Config            アプリケーション設定ファイルフォルダ
 │  │   ├── common            共有ライブラリの格納フォルダ
 │  │   ├── Models            コントローラを持たないモデルクラスのフォルダ
 │  │   ├── extends           モジュール共通の拡張クラス定義
 │  │   ├─┬ modules           アプリケーションモジュールフォルダ
 │  │   │  └─┬ Index         モジュール名フォルダ
 │  │   │      ├── View      モジュール固有ビューのテンプレート
 │  │   │      └─┬ res       モジュール有リソースフォルダ
 │  │   │          ├── css   スタイルシート
 │  │   │          └── js    javascript
 │  │   ├─┬ View              アプリケーション共通ビューのテンプレートフォルダ
 │  │   │  ├── lang          言語リソースフォルダ
 │  │   │  └─┬ res           リソースフォルダ
 │  │   │      ├── css       スタイルシートテンプレート
 │  │   │      └── js        Javascriptテンプレート
 │  │   └─┬ webroot           アプリケーション共通の静的リソース
 │  │       ├── cssimg          CSS内で使用する画像
 │  │       ├── css             スタイルシート
 │  │       ├── images          イメージファイル
 │  │       └── js              Javascript
 │  ├─ (アプリ2)
 │  │     .....
 │   ........
 ├ Core                    フレームワークフォルダ
 │  ├── Base              フレームワーク基底クラス
 │  ├── Class             フレームワーク汎用クラス
 │  ├── Common            フレームワーク共有関数ファイル
 │  ├── Config            フレームワーク設定フォルダ
 │  ├── handler           データベースドライバークラス
 │  └─┬ Template          フレームワークテンプレート
 │      ├── cssimg          CSSで使用する画像フォルダ
 │      ├── images          画像ファイル
 │      ├── lang            フレームワーク言語リソース
 │      ├── View            ビューテンプレート
 │      └─┬ res             リソースフォルダ
 │          ├── css         スタイルシート
 │          └── js          javascript
 │
 └ vendor                  ベンダーライブラリフォルダ
     ├─ vendor               PHPライブラリフォルダ
     │   ├ PHPExcel            PHPExcelのライブラリ
     │   ├ mpdf70              PDF作成ライブラリ
     │   ├ Twig                TWIGライブラリ
     │   └ FileMaker           FileMaker PHP-API
     └ webroot              外部リソース
         ├─ css                スタイルシート
         ├─ images             イメージファイル
         └─ js                 Javascript

 モジュールフォルダの中に「アプリ名」と同じフォルダを作成すると、そのモジュールは
 特別な意味をもち、コントローラーが省略されたときのトップフォルダを表示しりときの
 処理モジュールとして動作します。

</pre>