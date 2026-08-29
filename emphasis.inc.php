<?php
/*
PukiWiki - Yet another WikiWikiWeb clone.
emphasis.inc.php, v1.01 2019 M.Taniguchi
License: GPL v3 or (at your option) any later version

文字列に圏点（傍点・脇点）を付加するPukiWiki用プラグイン。
CSS3の text-emphasis-style を利用するため、対応ブラウザーでのみ有効。

使い方：
&emphasis(){文字列}
&emphasis(圏点種別){文字列}

引数：
文字列 … 圏点を付加したい文字列
圏点種別 … [filled|open] [dot|circle|double-circle|triangle|sesame]（CSSの text-emphasis-style 構文に準じる。省略時のデフォルトは filled circle）
*/

define('PLUGIN_EMPHASIS_USAGE', '&emphasis([emphasis type]){words};');
define('PLUGIN_EMPHASIS_CLASS', false);	// style属性の代わりにclass属性を付加

function plugin_emphasis_inline() {
	$argNum = func_num_args();
	if ($argNum <= 0) return PLUGIN_EMPHASIS_USAGE;

	$emphasis = 'filled';
	if ($argNum == 1) {
		list($body) = func_get_args();
	} else {
		list($emphasis, $body) = func_get_args();
	}

	$body = strip_htmltag($body);
	if ($body == '') return PLUGIN_EMPHASIS_USAGE;

	$emphasis = strip_htmltag($emphasis);

	if (PLUGIN_EMPHASIS_CLASS) {
		$emphasis = str_replace(' ', '-', $emphasis);
		$html = '<span class="emphasis-' . $emphasis . '">' . $body . '</span>';
	} else {
		$html = '<span style="-webkit-text-emphasis-style:' . $emphasis . ';text-emphasis-style:' . $emphasis . ';">' . $body . '</span>';
	}

	return $html;
}
?>