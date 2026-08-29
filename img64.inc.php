<?php
/*
PukiWiki - Yet another WikiWikiWeb clone.
img64.inc.php, v1.0 2020 M.Taniguchi
License: GPL v3 or (at your option) any later version

Base64エンコードされた画像を埋め込み表示するプラグイン。

ファイルをアップロードすることなく、画像をページに直接埋め込むことができます。
権限のない一般ユーザーでも任意の画像を掲載でき、添付ファイル管理の煩わしさからも解放されます。
画像のBase64エンコーダーは「画像 Base64 エンコード」等でインターネット検索して見つけてください。

【使い方】
#img64(画像のBase64エンコード文字列)

【使用例】
#img64(data:image/jpeg;base64,xxxxxxxxx...)
*/

define('PLUGIN_IMG64_MAXSIZE', 0);	// Base64文字列最大長（キロバイト）。0なら無制限

function plugin_img64_convert() {
	list($type, $code) = func_get_args();
	$code = $type . ',' . $code;

	if (PLUGIN_IMG64_MAXSIZE > 0) {
		$len = ceil(strlen($code) / 1024);
		if ($len > PLUGIN_IMG64_MAXSIZE) return '#img64 : Base64 string too long (' . number_format($len) . '&thinsp;KB / ' . number_format(PLUGIN_IMG64_MAXSIZE) . '&thinsp;KB)';
	}

	return '<div class="img_margin"><img src="' . htmlspecialchars($code) . '"/></div>';
}