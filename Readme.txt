 * -------------------------------------------------------------
 * Pawmoo - Object Oriented Web Application Platform with Section View Template
 * 
 * @copyright  Copyright (c) 2017 - 2022 by nTak
 * @license    MIT
 * @version 2.0.8, 2022-05-31
 * System Require: PHP 5.6 or Higher
 *

0. 概要
  pawmoo は Controller-View-Model のようなPHP-Webアプリケーションを作成するための
  オブジェクト指向型のプラットフォームです。
  MVCに相当する機能のファイルをまとめて「モジュール」というフォルダ単位で管理するので
  MVCモデル別にファイルを分散させるより機能の追加やデバッグ、修正漏れの防止が容易になります。

  このフレームワークは如何にシンプルかつ簡単にアプリケーションを構築できるか、ということを
  最優先にしているので、そのしわ寄せがフレームワークコアのコードに重くのしかかっています。
  そのため些細なバグが随所に残ってしまっているので、そのあたりはご容赦ください。

  最新バージョンはGitHubより入手できます
    https://github.com/ntbuzz/Pawmoo

1. インストール
  詳しい使い方はdocsフォルダにあるドキュメントを参照してください。

  a) アーカイブをWEBサーバーのルートフォルダ下に展開する
  b) 展開したフォルダ内にある vendor/webroot に以下のJavascriptライブラリを格納する
        vendor/webroot/js/jquery-3.2.1.min.js
        vendor/webroot/js/jquery-ui-1.12.1/jquery-ui.min.js
        vendor/webroot/js/split-pane/split-pane.js
        vendor/webroot/js/treeview/jquery.treeview.js
        vendor/webroot/js/tablesorter/js/jquery.tablesorter.js

      ライブラリのバージョンやフォルダ名を変えたときは以下のファイルを編集し
      +include セクションに記述しているパスを合わせてください。
      /js より前は mod_rewrite で書換えるので記述不要です
        app/help/View/Header.tpl
  c) .htaccess の "RewriteBase" を展開したフォルダ名に合わせます
      IIS には該当する設定がないので修正不要です
  d) IISの場合は、WEBサーバーを再起動します
  e) ブラウザからWEBサーバーにアクセスします
      例: http://localhost/pawmoo/help/

2. 使用条件、免責事項など
  本ソフトウェアはMITライセンスに従います。
  本ソフトを使用したことで発生したいかなる障害に対しても作者および転載者は
  一切の責任を負いません。
  それに伴うバージョンアップの義務も負いませんのでご了承下さい。

3. 変更履歴
	(Git log参照)
