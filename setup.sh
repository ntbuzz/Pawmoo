#!/usr/bin/sh
# setup cmd app [module]
#  cmd:
#	create app module	アプリケーションフォルダと仕様フォルダを作成する
#						moduleまで指定するとmoduleコマンドも実行する
#	module app module	モジュールフォルダを作成する
#	spec app 			仕様フォルダ(appSpec)にアプリ用フォルダを作成する
#	schema app module	テーブル仕様(仕様CSV)からモデルスキーマと言語リソースを生成する
#						moduleを省略すると仕様フォルダ全ての仕様CSVを変換する
#	model app module	モデルスキーマからモデルクラスを生成する
#	setup app module	schemaコマンドとmodelコマンドを連続実行する
#	table app module	モデルスキーマからテーブルとビューを生成する
#						InitCSVのあるモデルはCSVの読込みも実行する
#	view app module		モデルスキーマからビューを生成する
#	csv app module		モデルスキーマにInitCSVがあればCSV読込みを実行する
#

php Tools/cmd/Setup.php $@

