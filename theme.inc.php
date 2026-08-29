<?php
/*
PukiWiki - Yet another WikiWikiWeb clone.
theme.inc.php, v1.04 2020 M.Taniguchi
License: GPL v3 or (at your option) any later version

スタイルシートを切り替えるプラグイン。

PukiWiki側で使用するスキンPHPを変更するものではなく、ブラウザーにCSSファイルを読み込み直させるだけの仕組みです。
選んだCSSファイル名はブラウザーに記憶され、次回から自動的に適用されます。
ページ表示のたびにデフォルトCSSを剥がして選択CSSを適用し直す非効率さはあるものの、システムに手を入れず簡単に導入できるのが特長です。
PHPのバージョンはほぼ問わないと思いますが、古いブラウザーでは動作しない場合があります。

【使い方】
&theme([CSSファイル名1>表示名1][,CSSファイル名2>表示名2][,...]);

CSSファイル名と表示名との組をカンマで区切って必要なだけ羅列する。
CSSファイル名はスキンディレクトリ（ベースURL/skin/）からの相対パスで指定する。絶対パスは不可。
引数を省略するとスキンディレクトリ直下の全CSSファイルが選択肢となる。

【使用例】
&theme(pukiwiki.css>標準,hoge.css>暖色,fuga/piyo.css>寒色);

本プラグインは、MenuBar など全画面共通で表示されるページに挿入してください。
もしくは、次のようなコードをスキンファイル（pukiwiki.skin.php等）HTML内の適当な場所に挿入してください。
  <?php if (exist_plugin_convert('theme')) echo do_plugin_convert('theme','pukiwiki.css>標準,hoge.css>暖色'); ?>
なお、本プラグインを挿入できるのは1ページにつき1箇所のみです。
*/

function plugin_theme_convert() {
	return plugin_theme_output(func_get_args());
}

function plugin_theme_inline() {
	return plugin_theme_output(func_get_args());
}

// 実処理
function plugin_theme_output($args = null) {
	if (!PKWK_ALLOW_JAVASCRIPT) return '';

	// 二重起動禁止
	static	$included = false;
	if ($included) return '';
	$included = true;

	$options = '';

	if (!$args) {
		// 引数がなければスキンディレクトリ内のCSSファイルを検索して選択肢とする
		$files = glob(SKIN_DIR . '*.css');
		foreach ($files as $file) {
			$file = htmlspecialchars(str_replace(SKIN_DIR, '', $file));
			$options .= '<option value="' . $file . '">' . str_replace('.css', '', $file) . '</option>';
		}
	} else {
		// 引数群を選択肢とする
		foreach ($args as $v) {
			if (!$v) continue;
			$v = explode('>', $v);
			if (!isset($v[1])) $v[1] = str_replace('.css', '', $v[0]);	// 「>名称」がなけれがCSSファイル名を名称にする
			$options .= '<option value="' . htmlspecialchars(trim($v[0])) . '">' . htmlspecialchars(trim($v[1])) . '</option>';
		}
	}

	// 選択ボックス要素＆JavaScript出力
	//$url = get_base_uri(PKWK_URI_ABSOLUTE);	// ベースURL
    $url = "./";
	$dir = SKIN_DIR;	// スキンディレクトリ
	$body = <<<EOT
<select id="PluginThemeUI">${options}</select>
<script>
// 初期化
__PluginTheme__ = function() {
	const	self = this;
	const	ui = document.getElementById('PluginThemeUI');	// 選択ボックス要素
	var		sheet = localStorage.getItem('pluginTheme.sheet') || this.getCurrentCss() || '';	// 初期スタイルシート名

	// 選択ボックス内の初期スタイルシートを選択状態に
	for (var i = ui.options.length - 1; i >= 0; --i) {
		if (ui.options[i].value == sheet) {
			ui.options[i].selected = true;
			break;
		}
	}

	// スタイルシート切り替え
	this.change(sheet);

	// 以後、選択イベントによりスタイルシート切り替え
	ui.addEventListener('change', function(e) { self.change(e.srcElement.value); });
};

// スタイルシート切り替え
__PluginTheme__.prototype.change = function(sheet) {
	// ローカルストレージに保存
	localStorage.setItem('pluginTheme.sheet', sheet);

	// 既存スタイルシート消去
	sheet = '${dir}' + sheet;
	if (this.clearCss(sheet)) {
		// 消去が実行されたら、選択したスタイルシートを新たに適用する
		const	doc = document.getElementsByTagName('head')[0];
		doc.insertAdjacentHTML('afterbegin', '<link rel="stylesheet" type="text/css" href="${url}' + sheet + '" />');
	}
};

// 現在スタイルシート取得
__PluginTheme__.prototype.getCurrentCss = function() {
	var	result = false;

	// link要素を走査
	var	links = document.getElementsByTagName('link');
	for (var i = links.length; i >= 0; --i) {
		// スキンディレクトリ以下のスタイルシートか？
		if (links[i] && links[i].getAttribute('rel') == 'stylesheet' && links[i].getAttribute('href').indexOf('${dir}') >= 0) {
			// ファイル名を返す
			result = links[i].getAttribute('href').replace('${dir}', '');
			break;
		}
	}

	return result;
};

// 既存スタイルシート消去
__PluginTheme__.prototype.clearCss = function(css) {
	var	result = false;

	// link要素を走査
	var	v = document.getElementsByTagName('link');
	for (var i = v.length; i >= 0; --i) {
		// スキンディレクトリ以下のスタイルシートか？
		if (v[i] && v[i].getAttribute('rel') == 'stylesheet' && v[i].getAttribute('href').indexOf('${dir}') >= 0) {
			if (css && css == v[i].getAttribute('href')) continue;	// 引数と一致するスタイルシートは除外
			// link要素を削除
			if (v[i].remove) v[i].remove();
			else v[i].parentNode.removeChild(v[i]);	// IE対策
			result = true;
		}
	}

	return result;
};

new __PluginTheme__();	// 実行
</script>
EOT;

	return $body;
}