#!/usr/bin/sh
# setup cmd app
#		cmd:
#			create		フォルダツリーを作成
#			schema		スキーマファイルを作成
#			gen			Model/Lang ファイルを作成
#			table		テーブルとビューを作成
#			view		ビューのみ作成

php Tools/cmd/Setup.php $@

