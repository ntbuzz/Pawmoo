#!/usr/bin/sh
# setup cmd app [module]
#  cmd:
#	create app module	アプリケーションフォルダと仕様フォルダを作成する
#						moduleまで指定するとmoduleコマンドも実行する
#	module app module	モジュールフォルダを作成する
#	spec app 			仕様フォルダ(appSpec)にアプリ用フォルダを作成する
#	schema app module	テーブル仕様(仕様CSV)からモデルスキーマを生成する
#						moduleを省略すると仕様フォルダ全ての仕様CSVを変換する
#	model app module	モデルスキーマからモデルクラスと言語リソースを生成する
#	setup app module	schemaコマンドとmodelコマンドを連続実行する
#	table app module false	モデルスキーマからテーブルとビューを生成する
#						InitCSVのあるモデルはCSVの読込みも実行する
#	view app module false	モデルスキーマからビューを生成する
#	csv app module		モデルスキーマにInitCSVがあればCSV読込みを実行する
#
#			module = '-' | all 全てのモジュール
#			table / view コマンドはfalseを指定するとSQL実行を抑制
#	
# 一括作成例:
#	create pcenv Index	// pcenv アプリフォルダを作成し Index もジュールフォルダを作成
#	setup pcanv			// pcenv モデルスキーマを全て生成し、モデルクラスと言語リソースを生成
#	table pcenv			// pcenv テーブル作成とcsvインポート、ビュー生成を実行する
#	これ以降、生成された各モデルクラスファイルをモジュールフォルダにコピーし編集する
#
php Tools/cmd/Setup.php $@

