<?php
//=====================================================================
// 記事カテゴリテーブル
class CategorySetup extends AppDatabase {
static $Database = [
	'Handler' => HANDLER,
	'DataTable' => 'category',
	'DataView' => [],
	'Primary' => 'id',
	'Schema' => [
		'id'		=> [ 'integer', true ],
		'title'		=> [ 'TEXT', False ],
		'title_en'	=> [ 'TEXT', False ],
		'note'		=> [ 'TEXT', False ],
	],
	'InitCSV' => [
		'100,パソコン,Personal Computer,パソコン一般',
		'200,グルメ,Gourmet,食べ歩き',
		'300,Pawmoo,Pawmoo,フレームワークについて',
		'999,一般,General,日常',
	],
];
}
//=====================================================================
// 記事テーブル
class BlogSetup extends AppDatabase {
static $Dependent = [
	'Category',
];
static $Database = [
	'Handler' => HANDLER,
	'DataTable' => 'blogContents',
	'DataView' => [],
	'Primary' => 'id',
	'Schema' => [
		'id'						=> [ 'integer', true ],
		'post_date'				=> [ 'DATE', False ],
		'edit_date'				=> [ 'DATE', False ],
		'category_id'			=> [ 'INTEGER', False, 'Category.id' ],
		'published'				=> [ 'BOOLEAN', False ],
		'toc_gen'				=> [ 'BOOLEAN', False ],
		'title'					=> [ 'TEXT', False ],
		'title_en'				=> [ 'TEXT', False ],
		'summary'				=> [ 'TEXT', False ],
		'summary_en'				=> [ 'TEXT', False ],
		'preface'				=> [ 'TEXT', False ],
		'preface_en'				=> [ 'TEXT', False ],
	],
];
}
//=====================================================================
// 記事セクションテーブル
class SectionSetup extends AppDatabase {
static $Dependent = [
	'Blog',
];
static $Database = [
	'Handler' => HANDLER,
	'DataTable' => 'blogSection',
	'DataView' => [],
	'Primary' => 'id',
	'Schema' => [
		'id'						=> [ 'integer', true ],
		'blog_id'				=> [ 'INTEGER', False, 'Blog.id' ],
		'seq_no'					=> [ 'INTEGER', False ],
		'published'				=> [ 'BOOLEAN', False ],
		'title'					=> [ 'TEXT', False ],
		'title'					=> [ 'TEXT', False ],
		'contents'				=> [ 'TEXT', False ],
		'contents'				=> [ 'TEXT', False ],
	],
];
}
//=====================================================================
// 記事本文テーブル
class ParagraphSetup extends AppDatabase {
static $Dependent = [
	'Section',
];
static $Database = [
	'Handler' => HANDLER,
	'DataTable' => 'blogParagraph',
	'DataView' => [],
	'Primary' => 'id',
	'Schema' => [
		'id'						=> [ 'integer', true ],
		'section_id'				=> [ 'INTEGER', False, 'Section.id' ],
		'published'				=> [ 'BOOLEAN', False ],
		'seq_no'					=> [ 'INTEGER', False ],
		'title'					=> [ 'TEXT', False ],
		'title_en'				=> [ 'TEXT', False ],
		'contents'				=> [ 'TEXT', False ],
		'contents_en'			=> [ 'TEXT', False ],
	],
];
}
//==============================================================================
// アクセスログ
class AccessSetup extends AppDatabase {
static $Database = [
	'Handler' => HANDLER,
	'DataTable' => 'accesslog',
	'DataView' => [],
	'Primary' => 'id',
	'Schema' => [
		'id'			=> [ 'integer', true ],
		'logdate'		=> [ 'date', false ],
		'access'		=> [ 'time', false ],
		'last_access'	=> [ 'time', false ],
		'userid'		=> [ 'TEXT', false ],
		'logid'			=> [ 'TEXT', false ],
		'page'			=> [ 'TEXT', false ],
		'method'		=> [ 'TEXT', false ],
		'contents'		=> [ 'integer', false ],
		'repeat'		=> [ 'integer', false ],
		'query'			=> [ 'TEXT', false ],
		'post'			=> [ 'TEXT', false ],
	],
];
}
