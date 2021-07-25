<?php
// データベース定義をインポート
require_once(__DIR__ . '/../database.php');

//==============================================================================
// クラス名とファイルの対応表
const AliasMap  = [
	'Blog' => [
		'CategorySetup',
    	'BlogSetup',
    	'SectionSetup',
		'ParagraphSetup',
		'AccessSetup',
	],
];
