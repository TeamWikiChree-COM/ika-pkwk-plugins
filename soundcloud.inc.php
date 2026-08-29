<?php
/*
PukiWiki - Yet another WikiWikiWeb clone.
soundcloud.inc.php, v1.01 2020 M.Taniguchi
License: GPL v3 or (at your option) any later version

SoundCloud埋め込みウィジェットを表示するプラグイン。

【使い方】
#soundcloud(トラックID[,コメント表示[,カラーコード]])

トラックID … SoundCloud内対象楽曲の埋め込みコード内URL「//api.soundcloud.com/tracks/000000000&color=...」における「000000000」部分の数字
コメント表示 … 1なら表示。0または省略すると非表示
カラーコード … 再生ボタンの色を表す #RRGGBB 形式の文字列。省略ならデフォルト色

【使用例】
#soundcloud(123456789,1,#ff0000)
*/

define('PLUGIN_SOUNDCLOUD_WIDGET_HEIGHT', 166);			// ウィジェットiframe要素の高さ（公式ウィジェット仕様に合わせておくこと）
define('PLUGIN_SOUNDCLOUD_DEFAULT_COLOR', 'ff5500');	// デフォルトカラーコード（ここに「#」はつけないこと）

function plugin_soundcloud_convert() {
	list($code, $comments, $color) = func_get_args();

	if (!isset($code)) return '#soundcloud(code[,showComments[,color]])';
	$comments = ($comments)? 'true' : 'false';
	$color = ($color && preg_match('([\da-fA-F]{6}|[\da-fA-F]{3})', $color, $matches) > 0)? $matches[0] : PLUGIN_SOUNDCLOUD_DEFAULT_COLOR;

	$widgetTag = '<iframe width="100%" height="' . PLUGIN_SOUNDCLOUD_WIDGET_HEIGHT . '" scrolling="no" frameborder="no" allow="autoplay" src="https:'.'//w.soundcloud.com/player/?url=https%3A'.'//api.soundcloud.com/tracks/' . $code . '&color=%23' . $color . '&auto_play=false&hide_related=true&show_comments=' . $comments . '&show_user=true&show_reposts=false&show_teaser=false"></iframe>';

	return '<div class="_p_soundcloud">' . $widgetTag . '</div>';
}