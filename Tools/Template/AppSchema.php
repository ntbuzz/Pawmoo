<?php
//==============================================================================
// Databas Table Create Class
class %model%Schema extends AppSchema {
  static $DatabaseSchema = [
	'Handler' => '%handler%',
	'DataTable' => %table%,
	%view%
	'Primary' => '%primary%',
	'Lang_Alternate' => TRUE,
	%schema%
  ];
}
