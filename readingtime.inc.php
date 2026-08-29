<?php
/*
PukiWiki - Yet another WikiWikiWeb clone.
readingtime.inc.php, v1.02 2019 M.Taniguchi
License: GPL v3 or (at your option) any later version

ユーザーがページを読むのに要するおおよその時間を表示するプラグイン。

1分未満は15秒単位、1分以上60分未満は分単位、60分以上は時間と分単位で表します。
時間は、ページ内の文字数を500（変更可）で割った値を1分とします。
高速化のため文章以外の文字も区別せずに数えてしまうので、あくまで目安と考えてください。

【使い方】
&readingtime;
&readingtime([文字数][,表示文言]);
#readingtime
#readingtime([文字数][,表示文言])

文字数   … 1分間あたりにユーザーが読める文字数。日本語の場合、一般に400～600程度とされる
表示文言 … 出力する文字列。%TIME% が読了時間に置換される

【使用例】
この記事は約&readingtime;で読めます。

【スタイル】
スキンCSSにおいて、次のセレクターで表示時間および文言のスタイルを指定することができます。
読了時間 … .plugin-readingtime
文言全体 … .plugin-readingtime-message
*/

// デフォルト文字数/分
define('PLUGIN_READINGTIME_PERMINUTE', 500);

// デフォルト表示文言
define('PLUGIN_READINGTIME_STRING', 'このページは約&thinsp;%TIME%&thinsp;で読めます。');
// デフォルト表示文言（インライン版）
define('PLUGIN_READINGTIME_STRING_INLINE', '%TIME%');


function plugin_readingtime_convert() {
	list($perMin, $str) = func_get_args();
	$perMin = (float)$perMin;

	$time = plugin_readingtime_gettime($perMin);
	$time = '<span class="plugin-readingtime">' . $time . '</span>';

	if (!$str) $str = PLUGIN_READINGTIME_STRING;
	$str = strip_htmltag($str);

	return '<p class="plugin-readingtime-message">' . str_replace('%TIME%', $time, $str) . '</p>';
}

function plugin_readingtime_inline() {
	list($perMin, $str) = func_get_args();
	$perMin = (float)$perMin;

	$time = plugin_readingtime_gettime($perMin);

	if (!$str) $str = PLUGIN_READINGTIME_STRING_INLINE;
	$str = strip_htmltag($str);

	return '<span class="plugin-readingtime">' . str_replace('%TIME%', $time, $str) . '</span>';
}

function plugin_readingtime_gettime($perMin = PLUGIN_READINGTIME_PERMINUTE, $space = '') {
	global $vars;

	$time = null;

	if (isset($vars['page'])) {
		$page = get_source($vars['page']);

		unset($page[0]);
		$text = '';
		foreach ($page as $row) if (strpos($row, '#') !== 0) $text .= $row;
		$page = preg_replace('(&.+;|\s|\n|\r|\t)', '', $text);

		if ($perMin <= 0) $perMin = PLUGIN_READINGTIME_PERMINUTE;
		$time = mb_strlen($page) / $perMin;

		if ($time <= 0.75) {
			$time = (ceil($time / 0.25) * 15) . $space . '秒';
		} else {
			if ($time < 60) {
				$time = ceil($time) . $space . '分';
			} else {
				$hour = floor($time / 60);
				$min = floor($time % 60);
				$time = number_format($hour) . $space . '時間' . $space . $min . $space . '分';
			}
		}
	}

	return $space . $time . $space;
}