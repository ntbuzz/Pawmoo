// リソースインポート
Stylesheet => [
	common => [
		+import => [
			mystyle.css
		]
		+section => ^common
	]
]
Javascript => [
	common => [
		+jquery => [
			myscript.js
		]
		+import => [
		]
		+section => ^common
	]
]
