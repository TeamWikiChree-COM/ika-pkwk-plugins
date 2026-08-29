<?php
/*
PukiWiki - Yet another WikiWikiWeb clone.
steam.inc.php, v1.01 2020 M.Taniguchi
License: GPL v3 or (at your option) any later version

Steam埋め込みウィジェットを表示するプラグイン。

【使い方】
#readingtime(製品ID[,紹介文])

製品ID … Steam内対象ソフトのストアページURL「//store.steampowered.com/app/123456/Hoge/」における「123456」部分の数字
紹介文 … 任意の文字列。省略すると各ソフト既定の紹介文が表示される

【使用例】
#readingtime(123456,最高のFPS！)
*/

define('PLUGIN_STEAM_WIDGET_ATTRIBUTES', 'frameborder="0" width="646" height="190"');	// ウィジェットiframe要素の属性（公式ウィジェット仕様に合わせておくこと）
define('PLUGIN_STEAM_INTRO_MAXLEN', 375);	// 紹介文の最大文字数（公式ウィジェット仕様に合わせておくこと）

function plugin_steam_convert() {
	list($code, $intro) = func_get_args();

	if (!isset($code)) return '#steam(code[,introduction])';

	if (isset($intro)) $intro = '?t=' . urlencode(mb_strimwidth($intro, 0, PLUGIN_STEAM_INTRO_MAXLEN));

	$widgetTag = '<iframe src="https:'.'//store.steampowered.com/widget/' . htmlspecialchars($code) . '/' . $intro . '" ' . PLUGIN_STEAM_WIDGET_ATTRIBUTES . ' style="max-width:100%;max-height:auto"></iframe>';

	return '<div class="_p_steam">' . $widgetTag . '</div>';
}