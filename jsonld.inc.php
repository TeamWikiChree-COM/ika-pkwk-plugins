<?php
/*
PukiWiki - Yet another WikiWikiWeb clone.
jsonld.inc.php, v1.04 2020 M.Taniguchi
License: GPL v3 or (at your option) any later version

JSON-LDを出力するプラグイン。

ページの情報に基づくJSON-LD構造化データを生成し出力します。
具体的には、記事情報 Article とパンくずリスト情報 BreadcrumbList を生成します。
ウィキの構造を検索エンジンにより良く伝えるため（SEO）に役立ちます。

【使い方】
#jsonld

本プラグインは、MenuBar など全画面共通で表示されるページに挿入してください。
もしくは、次のコードをスキンファイル（skin/pukiwiki.skin.php等）HTML内の</body>閉じタグ直前に挿入してください。
 <?php if (exist_plugin_convert('jsonld')) echo do_plugin_convert('jsonld'); ?>
なお、本プラグインを挿入できるのは1ページにつき1箇所のみです。
*/

if (!defined('PLUGIN_JSONLD_ARTICLE'))                    define('PLUGIN_JSONLD_ARTICLE',        1); // 1：Article （記事情報）を出力, 0：無効
if (!defined('PLUGIN_JSONLD_BREADCRUMBLIST'))             define('PLUGIN_JSONLD_BREADCRUMBLIST', 1); // 1：BreadcrumbList （パンくずリスト情報）を出力, 0：無効
if (!defined('PLUGIN_JSONLD_BREADCRUMBLIST_NOTEXISTPOS')) define('PLUGIN_JSONLD_BREADCRUMBLIST_NOTEXISTPOS', 0); // パンくずリストにおいて、ページとして存在しない階層の扱い。0：上位階層のURLを記載, 1：存在しない階層のURLをそのまま記載, 2：その階層を無視
if (!defined('PLUGIN_JSONLD_ENCODEFLAGS'))                define('PLUGIN_JSONLD_ENCODEFLAGS',    (JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)); // json_encode関数のJSONエンコードフラグ指定

function plugin_jsonld_convert() {
	if (!PLUGIN_JSONLD_ARTICLE && !PLUGIN_JSONLD_BREADCRUMBLIST) return '';
	// if (!PKWK_ALLOW_JAVASCRIPT) return '';	// JavaScriptではなくJSONなので無視

	global	$modifier, $defaultpage, $page_title, $title;

	// 二重起動禁止
	static	$included = false;
	if ($included) return '';
	$included = true;

	$script = get_script_uri();
	$isHome = ($title == $defaultpage);

	// Article（記事情報）生成
	if (PLUGIN_JSONLD_ARTICLE) {
		$long_title = (!$isHome ? $title . ' | ' : '') . $page_title;
		$thisPageUri = ($isHome)? $script : get_page_uri($title, PKWK_URI_ABSOLUTE);
		$modifiedDate = date('Y-m-d\TH:i:sP', get_filetime($title));

		$article = array(
			'@context' => 'http:'.'//schema.org',
			'@type' => 'Article',
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id' => $thisPageUri
			),
			'datePublished' => $modifiedDate,
			'dateModified' => $modifiedDate,
			'author' => array(
				'@type' => 'Person',
				'name' => $modifier
			),
			'publisher' => array(
				'@type' => 'Organization',
				'name' => $page_title
			),
			'headline' => $long_title
		);
		$article = '<script type="application/ld+json">' . json_encode($article, PLUGIN_JSONLD_ENCODEFLAGS) . '</script>';
	} else $article = '';

	// BreadcrumbList（パンくずリスト）生成
	if (PLUGIN_JSONLD_BREADCRUMBLIST && !$isHome ) {
		$names = explode('/', $title);
		$path = '';
		$item = $script;
		$i = 0;

		$bread = array();
		$bread[] = array(
			'@type' => 'ListItem',
			'position' => ++$i,
			'name' => $defaultpage,
			'item' => $item
		);

		foreach ($names as $name) {
			$path .= (($path != '')? '/' : '') . $name;
			if (PLUGIN_JSONLD_BREADCRUMBLIST_NOTEXISTPOS != 1 && !is_page($path)) {
				if (PLUGIN_JSONLD_BREADCRUMBLIST_NOTEXISTPOS != 0) continue;
			} else {
				$item = get_page_uri($path, PKWK_URI_ABSOLUTE);
			}
			$bread[] = array(
				'@type' => 'ListItem',
				'position' => ++$i,
				'name' => $name,
				'item' => $item
			);
		}

		$bread = array(
			'@context' => 'http:'.'//schema.org',
			'@type' => 'BreadcrumbList',
			'itemListElement' => $bread
		);
		$bread = '<script type="application/ld+json">' . json_encode($bread, PLUGIN_JSONLD_ENCODEFLAGS) . '</script>';
	} else $bread = '';

	return $article . $bread;
}