<?php
/*
PukiWiki - Yet another WikiWikiWeb clone.
fahstats.inc.php, v1.1 2020 M.Taniguchi
License: GPL v3 or (at your option) any later version

Folding@home の統計情報を表示するプラグイン。

ユーザー（ドナー）個人統計とチーム統計の2種類を表示できます。

統計情報は公式APIから取得します。
負荷軽減のためと、もとよりAPI側の情報更新頻度が低いうえ応答を返さないこともあるため、結果はキャッシュして一定時間使い回します。
リアルタイム性は低いものとご理解ください。

【使い方】
#fahstats(ユーザー名)
#fahstats(チームID)
&fahstats(ユーザー名){テキスト};
&fahstats(チームID){テキスト};

【引数】
ユーザー名 … ユーザー名を表す文字列
チームID … チームIDを表す数字
テキスト … 表示する文字列。文字列内の「%キー名%」が対応する値に置換される。キー／値について詳しくは、本プラグイン実行後に生成されるキャッシュファイル cache/fahstats.*.dat 内を参照

【使用例】
#fahstats(FahstatsSample)
#fahstats(999999)
&fahstats(FahstatsSample){%name%さんのポイントは%credit%、処理したワークユニット数は%wus%です。};
&fahstats(999999){%name%チームのポイントは%credit%、処理したワークユニット数は%wus%です。};
*/

define('PLUGIN_FAHSTATS_URL', 'https:/'.'/stats.foldingathome.org/');		// Folding@home統計情報ドメイン
define('PLUGIN_FAHSTATS_URL_DONOR', PLUGIN_FAHSTATS_URL . 'api/donor/');	// Folding@homeドナー情報APIのURL
define('PLUGIN_FAHSTATS_URL_TEAM',  PLUGIN_FAHSTATS_URL . 'api/team/');		// Folding@homeチーム情報APIのURL

define('PLUGIN_FAHSTATS_API_INTERVAL', (4 * 60 * 60));	// APIアクセス間隔（秒）※短くしすぎてサーバーに負荷をかけないよう注意。もとよりAPI側の情報更新頻度は低く（公式には1時間毎）、あまり短くしても無駄
define('PLUGIN_FAHSTATS_API_TIMEOUT', 30);	// APIタイムアウト時間（秒）

define('PLUGIN_FAHSTATS_RECURSIVE', false);	// trueなら入れ子の情報を再帰的に走査する。テキスト引数でより詳細な情報を表示したいときに使う


function plugin_fahstats_convert() {
	list($id) = func_get_args();
	return plugin_fahstats_exec($id);
}

function plugin_fahstats_inline() {
	$args = func_get_args();
	$id = $args[0];
	$text = array_pop($args);
	return plugin_fahstats_exec($id, $text);
}

// 実行
function plugin_fahstats_exec($id, $text = null) {
	$id = trim((string)$id);
	if ($id === '') return;
	$team = is_numeric($id);	// 第1引数が数字ならチームIDとみなす（数字だけの名前のユーザーは勘弁…）

	$cacheFile = CACHE_DIR . 'fahstats' . (($id != '')? '.' . encode($id) : '') . '.dat';	// キャッシュファイルパス
	$data = null;

	// キャッシュファイルがない、またはキャッシュが古くなっていたらAPIから最新情報を取得
	if (!file_exists($cacheFile) || (filemtime($cacheFile) < (time() - max(PLUGIN_FAHSTATS_API_INTERVAL, 600)))) {
		// APIアクセス
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, (($team)? PLUGIN_FAHSTATS_URL_TEAM : PLUGIN_FAHSTATS_URL_DONOR) . rawurlencode($id));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, PLUGIN_FAHSTATS_API_TIMEOUT);
		$data = curl_exec($ch);
		curl_close($ch);

		// 成功？
		if ($data) {
			// JSONデコード
			$data = json_decode($data);
			if (!$data || isset($data->error)) return;
			$data = plugin_fahstats_readdata($data);	// 情報を走査

			// キャッシュファイル書き込み
			$fp = fopen($cacheFile, 'w');
			flock($fp, LOCK_EX);
			rewind($fp);
			fwrite($fp, json_encode($data));
			flock($fp, LOCK_UN);
			fclose($fp);
		}
	}

	// 情報がなければキャッシュファイル読み込み
	if (!$data) {
		$fp = fopen($cacheFile, 'r');
		$data = json_decode(fread($fp, filesize($cacheFile)));
		fclose($fp);
	}
	if (!$data) return;

	// デフォルト表示：表形式
	$url = PLUGIN_FAHSTATS_URL;
	$thStyle = ' style="text-align:left;width:21em"';
	$tdStyle = ' style="text-align:left;width:14em"';
	if (!$text) {
		if (!$team) {
			// ドナー情報
			$text = <<<EOT
<table class="style_table" cellspacing="1" border="0">
	<tbody>
		<tr><th class="style_th"${thStyle}">Donor</th><td class="style_td"${tdStyle}><a href="${url}%path%" rel="noopener nofollow external">%name%</a></td></tr>
		<tr><th class="style_th"${thStyle}>Date of last Work Unit</th><td class="style_td"${tdStyle}>%last%</td></tr>
		<tr><th class="style_th"${thStyle}>Total score</th><td class="style_td"${tdStyle}>%credit%</td></tr>
		<tr><th class="style_th"${thStyle}>Total WUs</th><td class="style_td"${tdStyle}>%wus%</td></tr>
		<tr><th class="style_th"${thStyle}>Overall rank (if points are combined)</th><td class="style_td"${tdStyle}>%rank% of %total_users%</td></tr>
		<tr><th class="style_th"${thStyle}>Active clients (within 50 days)</th><td class="style_td"${tdStyle}>%active_50%</td></tr>
		<tr><th class="style_th"${thStyle}>Active clients (within 7 days)</th><td class="style_td"${tdStyle}>%active_7%</td></tr>
	</tbody>
</table>
EOT;
		} else {
			// チーム情報
			$text = <<<EOT
<table class="style_table" cellspacing="1" border="0">
	<tbody>
		<tr><th class="style_th"${thStyle}>Team</th><td class="style_td"${tdStyle}><a href="${url}%path%" rel="noopener nofollow external">%name%</a></td></tr>
		<tr><th class="style_th"${thStyle}>Date of last Work Unit</th><td class="style_td"${tdStyle}>%last%</td></tr>
		<tr><th class="style_th"${thStyle}>Active CPUs within 50 days</th><td class="style_td"${tdStyle}>%active_50%</td></tr>
		<tr><th class="style_th"${thStyle}>Team ID</th><td class="style_td"${tdStyle}>%team%</td></tr>
		<tr><th class="style_th"${thStyle}>Grand score</th><td class="style_td"${tdStyle}>%credit%</td></tr>
		<tr><th class="style_th"${thStyle}>Work Unit count</th><td class="style_td"${tdStyle}>%wus%</td></tr>
		<tr><th class="style_th"${thStyle}>Team ranking</th><td class="style_td"${tdStyle}>%rank% of %total_teams%</td></tr>
	</tbody>
</table>
EOT;
		}
	}

	// テキスト置換
	$search = array();
	$replace = array();
	foreach ($data as $key => $val) {
		$search[] = $key;
		$replace[] = $val;
	}
	$text = str_replace($search, $replace, $text);

	return $text;
}

// 情報を走査
function plugin_fahstats_readdata($data, $prefix = null) {
	if ($prefix) $prefix .= ':';

	$result = array();
	foreach ($data as $key => $val) {
		if (is_array($val) || is_Object($val)) {
			// 入れ子を再帰的に走査
			if (PLUGIN_FAHSTATS_RECURSIVE) {
				$result = array_merge($result, plugin_fahstats_readdata($val, $prefix . $key));
			}
		} else {
			if (is_numeric($val) && strpos($key, 'id') === false && $key != 'team' && $key != 'year') $val = number_format($val);
			$result['%' . $prefix . $key . '%'] = $val;
		}
	}

	return $result;
}