<?php
/*
PukiWiki - Yet another WikiWikiWeb clone.
tinykanban.inc.php, v1.2.4 2022 M. Taniguchi
License: GPL v2 or (at your option) any later version

簡易かんばんボードプラグイン

【使い方】
#tinykanban(["columnName1[:color1][|columnName2[:color2][|...]]"])

・columnName … 列名
・color      … 列の色（HTMLカラーコード）

coumnName:Colorの組を「|」で区切って必要なだけ羅列する。必ず全体を「"」で囲むこと。すべて省略すると「To Do」「Doing」「Done」の3列になる。

【使用例】
#tinykanban()
#tinykanban("予定|進行中|完了")
#tinykanban("提案:orange|着手:#e00000|完了:#0c0|却下:rgb(128,128,128)")

【かんばんボードの操作法】
ヘッダーの「＋」ボタンをクリックするとかんばんが追加されます。
かんばんをクリックすると名前を入力できます。
かんばんの端をドラッグ＆ドロップすることで列の間を移動させることができます。
かんばんの名前を消去すると横に「×」ボタンが現れ、クリックするとそのかんばんを削除できます。

【ご注意】
●追加・編集したかんばん情報は、当プラグインを埋め込んだページに直接書き込まれます（標準commentプラグインと似た仕組み）。
　ページへの書き込みはバックグラウンドで行われ、衝突を無視して常に上書きします。
　そのため、プライベートなウィキや編集制限されたページでのご利用をお勧めします。

●お勧めしませんが複数ユーザーで同時に編集したい場合は、定数 PLUGIN_TINYKANBAN_SYNC_INTERVAL に適当な同期間隔を設定してください。
　他ユーザーの更新内容がほぼリアルタイムに（設定した秒数の遅れで）自分の画面に反映されるため、衝突が起こりにくくなります。
　サーバーへの問い合わせがバックグラウンドで定期実行されるため、負荷や通信量の増加にご注意ください。
*/

/////////////////////////////////////////////////
// 簡易かんばんボードプラグイン（tinykanban.inc.php）
if (!defined('PLUGIN_TINYKANBAN_JQUERY_URL'))    define('PLUGIN_TINYKANBAN_JQUERY_URL',    'https://code.jquery.com/jquery-3.7.1.min.js');        // jQuery のURL（すでに読み込まれており不要な場合は空にする）
if (!defined('PLUGIN_TINYKANBAN_JQUERYUI_URL'))  define('PLUGIN_TINYKANBAN_JQUERYUI_URL',  'https://code.jquery.com/ui/1.13.2/jquery-ui.min.js'); // jQuery UI のURL（すでに読み込まれており不要な場合は空にする）
if (!defined('PLUGIN_TINYKANBAN_ADDJS_URL'))     define('PLUGIN_TINYKANBAN_ADDJS_URL',     '');                                                   // 追加JavaScriptのURL（jQuery UIをタッチ操作に対応させるハック jquery.ui.touch-punch.js 等必要に応じて）
if (!defined('PLUGIN_TINYKANBAN_THEME'))         define('PLUGIN_TINYKANBAN_THEME',         0);                                                    // 0：ライトテーマ, 1：ダークテーマ, 2：自動
if (!defined('PLUGIN_TINYKANBAN_DEFAULTCOLOR'))  define('PLUGIN_TINYKANBAN_DEFAULTCOLOR',  '#667788');                                            // 列のデフォルト色
if (!defined('PLUGIN_TINYKANBAN_SYNC_INTERVAL')) define('PLUGIN_TINYKANBAN_SYNC_INTERVAL', 0);                                                    // 更新同期間隔（秒）。0なら同期しない
if (!defined('PLUGIN_TINYKANBAN_MAXLENGTH'))     define('PLUGIN_TINYKANBAN_MAXLENGTH',     80);                                                   // かんばん名の最大文字数
if (!defined('PLUGIN_TINYKANBAN_PROTECT'))       define('PLUGIN_TINYKANBAN_PROTECT',       1);                                                    // 1：名前が空のかんばんのみ削除できる, 0：名前付きのかんばんも削除できる
if (!defined('PLUGIN_TINYKANBAN_ACROSS'))        define('PLUGIN_TINYKANBAN_ACROSS',        0);                                                    // 1：ページ内に複数のかんばんボードがあるとき、かんばんがボードを跨いで移動できる, 0：かんばんがボードを跨げない
if (!defined('PLUGIN_TINYKANBAN_PUBLIC'))        define('PLUGIN_TINYKANBAN_PUBLIC',        0);                                                    // 1：編集権限のないユーザーにもかんばんの変更を許可, 0：かんばんの変更には編集権限が必須
if (!defined('PLUGIN_TINYKANBAN_NOTIMESTAMP'))   define('PLUGIN_TINYKANBAN_NOTIMESTAMP',   0);                                                    // 1：看板変更時にページのタイムスタンプを更新しない, 0：タイムスタンプを更新する

function plugin_tinykanban_convert() {
	global	$vars, $script;
	static	$id = 0;
	$id++;

	// 定数・引数他を取得して変数を設定
	$page = $vars['page'];
	$readOnly = (PKWK_READONLY || (!PLUGIN_TINYKANBAN_PUBLIC && (!is_editable($page) || !is_page_writable($page))))? 'disabled' : null;
	$jqueryUrl = (PLUGIN_TINYKANBAN_JQUERY_URL)? '<script src="' . PLUGIN_TINYKANBAN_JQUERY_URL . '" defer></script>' : '';
	$jqueryUrl .= (PLUGIN_TINYKANBAN_JQUERYUI_URL)? '<script src="' . PLUGIN_TINYKANBAN_JQUERYUI_URL . '" defer></script>' : '';
	$jqueryUrl .= (PLUGIN_TINYKANBAN_ADDJS_URL)? '<script src="' . PLUGIN_TINYKANBAN_ADDJS_URL . '" defer></script>' : '';
	$maxLength = (int)PLUGIN_TINYKANBAN_MAXLENGTH;
	$interval = (float)PLUGIN_TINYKANBAN_SYNC_INTERVAL * 1000;
	$filetime = get_filetime($page);
	list($columns, $json) = func_get_args();
	$columns = htmlsc(trim($columns));
	$page = htmlsc($page);
	$json = (isset($json) && $json) ? str_replace('</script>', '<\/script>', htmlspecialchars_decode($json)) : '[[""]]';
	$across = PLUGIN_TINYKANBAN_ACROSS ? '' : '[data-tinykanban-id="\' + self.id + \'"]';

	// ボード要素を作成（かんばん要素は後からJavaScriptで追加）
	$columnEles = '';
	$lists = '';
	$colorStyle = '';
	$args = explode('|', $columns);
	if (count($args) <= 1 && !$args[0]) $args = ['To Do','Doing','Done'];
	foreach ($args as $i => $name) {
		$name = explode(':', $name);
		$color = (isset($name[1]))? htmlsc(trim($name[1])) : PLUGIN_TINYKANBAN_DEFAULTCOLOR;
		$name = trim($name[0]);
		$name = ($name)? htmlsc($name) : ($i + 1);
		$lists .= ($lists ? ',' : '') . '#__TinyKanban_' . $id . '__ ul.__TinyKanban_List__';
		$columnEles .= '<div class="__TinyKanban_Column__" data-tinykanban-column="' . $i . '"><div class="__TinyKanban_Header__">' . $name . '<button title="Add" onclick="__pluginTinyKanban_' . $id . '__.add(' . $i . ')">&plus;</button></div><ul class="__TinyKanban_List__" data-tinykanban-id="' . $id . '" data-tinykanban-list="' . $i . '" style="margin:0;padding:0"></ul></div>';
		$colorStyle .= '#__TinyKanban_' . $id . '__ .__TinyKanban_Column__[data-tinykanban-column="' . $i . '"] .__TinyKanban_Header__{background-color:' . $color . "}\n";
		$colorStyle .= '#__TinyKanban_' . $id . '__ .__TinyKanban_Column__[data-tinykanban-column="' . $i . '"] li{background:linear-gradient(to right,' . $color . ',' . $color . " .75em,var(--TinyKanban-kanban-bg-color) .75em,var(--TinyKanban-kanban-bg-color) 100%)}\n";
	}

	$body = '<div class="__TinyKanban__" id="__TinyKanban_' . $id . '__">' . "\n";

	// 初回のみ挿入
	if ($id == 1) {
		// スタイルを定義
		$body .= <<<EOT
<style>
:root {
	--TinyKanban-board-bg-color: rgba(128,128,128,.1);	/* ボード背景色 */
	--TinyKanban-board-margin: .125rem;	/* ボード間隔 */
	--TinyKanban-header-color: #fff; /*  ヘッダー文字色 */
	--TinyKanban-header-font: sans-serif; /* ヘッダーフォント */
	--TinyKanban-header-font-size: 1rem; /* ヘッダー文字サイズ */
	--TinyKanban-kanban-bg-color: #fff; /* かんばん背景色 */
	--TinyKanban-kanban-color: rgba(0,0,0,.9); /* かんばん文字色 */
	--TinyKanban-kanban-font: sans-serif; /* かんばんフォント */
	--TinyKanban-kanban-font-size: .9rem; /* かんばん文字サイズ */
	--TinyKanban-kanban-margin: .25rem; /* かんばん間隔 */
	--TinyKanban-corner-radius: .3125rem; /* 角丸半径 */
	--TinyKanban-shadow: 0 0 .0625rrem rgba(0,0,0,.13), 0 .0625rrem .1875rrem rgba(0,0,0,.2); /* 影 */
	--TinyKanban-transition-fadein: 17ms; /* UIフェードイン時間 */
	--TinyKanban-transition-fadeout: 125ms; /* UIフェードアウト時間 */
}

/* for high contrast theme */
@media screen and (forced-colors: active) {
	:root {
		--TinyKanban-board-bg-color: Canvas;
		--TinyKanban-header-color: HighlightText;
		--TinyKanban-kanban-bg-color: Canvas;
		--TinyKanban-kanban-color: CanvasText;
	}
	.__TinyKanban_Column__ {
		border: 1px solid GrayText !important;
	}
	.__TinyKanban_Header__ {
		color: CanvasText !important;
	}
	ul.__TinyKanban_List__ > li {
		border: 1px solid GrayText !important;
	}
}

.__TinyKanban__ {
	position: relative;
	display: flex;
	justify-content: space-between;
	flex: 0 100 auto;
	width: 100%;
	height: auto;
	padding: 0;
	box-sizing: border-box;
	overflow: visible;
	user-select: none;
	-moz-user-select: none;
	-webkit-user-select: none;
	-ms-user-select: none;
	-webkit-touch-callout: none;
}
.ui-draggable, .ui-droppable {
	position: static;
	background-position: top;
}
.__TinyKanban_Column__, .__TinyKanban_Column__:first-child, .__TinyKanban_Column__:last-child {
	position: relative;
	display: flex;
	flex-direction: column;
	border: none;
	min-height: 1.25rem;
	margin: 0 var(--TinyKanban-board-margin);
	padding: 0 0 .125rem;
	height: auto;
	box-sizing: border-box;
	border-radius: var(--TinyKanban-corner-radius);
	overflow: visible;
	flex: 0 100 100%;
	background: var(--TinyKanban-board-bg-color);
	color-adjust: exact;
	-webkit-print-color-adjust: exact;
}
.__TinyKanban_Column__:first-child {margin-left:0}
.__TinyKanban_Column__:last-child {margin-right:0}
.__TinyKanban_Header__ {
	position: sticky;
	top: 0;
	color: var(--TinyKanban-header-color);
	background: #808080;
	text-align: center;
	font-family: var(--TinyKanban-header-font);
	font-size: var(--TinyKanban-header-font-size);
	font-weight: bold;
	line-height: 1rem;
	padding: .375rem 0;
	margin: 0 0 .0625rem;
	border-radius: var(--TinyKanban-corner-radius) var(--TinyKanban-corner-radius) 0 0;
	box-sizing: border-box;
	z-index: 1;
	color-adjust: exact;
}
.__TinyKanban__ button {
	position: absolute;
	top: calc(50% - .625rem);
	width: 1.25rem;
	min-width: 1.25rem;
	max-width: 1.25rem;
	height: 1.25rem;
	min-height: 1.25rem;
	max-height: 1.25rem;
	padding: 0;
	margin: 0;
	background: #fff;
	color: #999;
	text-align: center;
	vertical-align: middle;
	line-height: 1.25rem;
	font-family: sans-serif;
	font-size: 1rem;
	font-weight: bold;
	border: none;
	border-radius: 100%;
	box-sizing: border-box;
	overflow: hidden;
	opacity: 0;
	box-shadow: var(--TinyKanban-shadow);
	transition: opacity var(--TinyKanban-transition-fadeout);
}
.__TinyKanban_Header__ button {
	right: .375rem;
}
ul.__TinyKanban_List__ {
	position: static;
	width: 100%;
	height: 100%;
	flex: 0 100 100%;
	padding: 0;
	margin: 0;
	line-height: 100%;
	box-sizing: border-box;
	overflow: auto;
}
ul.__TinyKanban_List__ > li {
	position: relative;
	font-size: var(--TinyKanban-kanban-font-size);
	line-height: 1rem;
	margin: var(--TinyKanban-kanban-margin) .25rem;
	padding: .125rem .125rem .125rem 1rem;
	color: var(--TinyKanban-kanban-color);
	list-style-type: none;
	vertical-align: middle;
	border: none;
	box-sizing: border-box;
	border-radius: var(--TinyKanban-corner-radius);
	background: linear-gradient(to right, #808080, #808080 .75rem, var(--TinyKanban-kanban-bg-color) .75rem, var(--TinyKanban-kanban-bg-color) 100%);
	box-shadow: var(--TinyKanban-shadow);
	color-adjust: exact;
}
ul.__TinyKanban_List__ > li input, ul.__TinyKanban_List__ > li input:disabled {
	position: relative;
	font-family: var(--TinyKanban-kanban-font);
	font-size: var(--TinyKanban-kanban-font-size);
	font-feature-settings: 'palt' 1;
	margin: 0;
	padding: .0625rem .1875rem .0625rem .125rem;
	line-height: 1rem;
	border: none;
	box-sizing: border-box;
	background: transparent;
	color: var(--TinyKanban-kanban-color);
	width: 100%;
	border-radius: .1875rem;
	transition: background-color var(--TinyKanban-transition-fadeout);
}
ul.__TinyKanban_List__ > li input::placeholder {color:rgba(128,128,128,.5)}
ul.__TinyKanban_List__ > li button {
	position: absolute;
	right: .125rem;
	margin: 0 0 0 .125rem;
}
EOT;

		// 編集権限ありならUIをインタラクティブに
		if (!$readOnly) {
			$body .=<<<EOT
@media screen {
.__TinyKanban_Column__:hover .__TinyKanban_Header__ button, .__TinyKanban_Header__ button:focus {opacity:1; transition:opacity var(--TinyKanban-transition-fadein)}
.__TinyKanban_Column__ .__TinyKanban_Header__ button:hover {background-color:#fff; color:#000; cursor:pointer}
ul.__TinyKanban_List__ > li:hover {cursor:grab}
ul.__TinyKanban_List__ > li input:hover, ul.__TinyKanban_List__ > li input:focus {background-color:rgba(128,128,128,.07); transition:background-color var(--TinyKanban-transition-fadein)}
ul.__TinyKanban_List__ > li:hover button, ul.__TinyKanban_List__ > li:focus button, ul.__TinyKanban_List__ > li button:focus {opacity:1; transition:opacity var(--TinyKanban-transition-fadein)}
ul.__TinyKanban_List__ > li button:hover, ul.__TinyKanban_List__ > li button:focus {color:#000; cursor:pointer; transition:color var(--TinyKanban-transition-fadein)}
}
EOT;
		}

		// 定数に応じて調整
		if (PLUGIN_TINYKANBAN_PROTECT) $body .= "ul.__TinyKanban_List__ > li.__TinyKanban_Protected__ button {display:none}\n";

		// ダークモード設定
		if (PLUGIN_TINYKANBAN_THEME == 2) $body .= "@media screen and (prefers-color-scheme: dark) {\n";
		if (PLUGIN_TINYKANBAN_THEME >= 1) {
			$body .= <<<EOT
:root {
	--TinyKanban-board-bg-color: rgba(128,128,128,.2);	/* ボード背景色 */
	--TinyKanban-kanban-bg-color: #3c3c3c; /* かんばん背景色 */
	--TinyKanban-kanban-color: rgba(255,255,255,.85); /* かんばん文字色 */
}
EOT;
		}
		if (PLUGIN_TINYKANBAN_THEME == 2) $body .= "}\n";

		// JavaScript
		$body .= <<<EOT
</style>
{$jqueryUrl}
<script>/*<!--*/
'use strict';

const __TinyKanban__ = function(id, columns, sortableEles, json, obj) {
	const	self = this;
	this.id = id;
	this.readOnly = '{$readOnly}';
	this.filetime = {$filetime};
	this.postTimer = null;
	this.columns = columns;
	this.sortableEles = sortableEles;
	this.data = json;
	this.getTimer = null;
	this.getHandlers = [];
	if (obj) {
		obj.addGetHandler(this);
	} else {
		this.addGetHandler(this);
	}

	if (document.readyState !== 'loading') {
		self.init();
	} else {
		window.addEventListener('DOMContentLoaded', ()=>{self.init()}, {once: true, passive: true});
	}
};

__TinyKanban__.prototype.init = function(repeated) {
	const	self = this;
	if (!self.readOnly) {
		$(self.sortableEles).sortable({
			connectWith: 'ul.__TinyKanban_List__{$across}',
			update: ()=>{ self.update() },
			cursor: 'grabbing'
		}).disableSelection();
	}

	if (!repeated) {
		self.set(0, self.data);
		if (self.id == 1 && {$interval} > 0) self.getTimer = setTimeout(()=>{self.get()}, {$interval});
	} else {
		self.filetime = 0;
		if (self.getTimer) clearTimeout(self.getTimer);
		self.get();
	}

	return this;
};

__TinyKanban__.prototype.add = function(index) {
	const	ele = $('<li class="ui-state-default" onclick="__pluginTinyKanban_' + this.id + '__.focus(this)"><input type="text" value="" title="" placeholder="Add a title" oninput="__pluginTinyKanban_' + this.id + '__.change(this)" onchange="__pluginTinyKanban_' + this.id + '__.change(this, true)" maxlength="{$maxLength}"/><button title="Remove" onclick="__pluginTinyKanban_' + this.id + '__.remove(this)">&times;</button></li>');
	$('#__TinyKanban_' + this.id + '__ .__TinyKanban_Column__[data-tinykanban-column="' + index + '"] ul').append(ele);
	this.update();
	this.focus(ele.get(0));
};

__TinyKanban__.prototype.remove = function(ele) {
	$(ele).closest('li').remove();
	this.update();
};

__TinyKanban__.prototype.focus = function(ele) {
	$(ele).find('input').focus();
};

__TinyKanban__.prototype.change = function(ele, update = false) {
	const	parent = $(ele).parent();
	if (ele.value) {
		parent.addClass('__TinyKanban_Protected__');
	} else {
		parent.removeClass('__TinyKanban_Protected__');
	}
	if (update) {
		$(ele).attr('title', ele.value);
		this.update('change', parent);
	}
};

__TinyKanban__.prototype.update = function(event, ui) {
	let	data = [];
	$('#__TinyKanban_' + this.id + '__ .__TinyKanban_List__').each((i, list)=>{
		let	column = [];
		$(list).children('li').each((index, item)=>{ column.push($(item).find('input').val()) });
		data.push(column);
	});
	this.post(data);
};

__TinyKanban__.prototype.post = async function(data) {
	const	self = this;
	if (self.postTimer) clearTimeout(self.postTimer);
	self.postTimer = setTimeout(()=>{
		self.postTimer = null;
		$.ajax({
			type: 'POST',
			url: '{$script}?plugin=tinykanban',
			data: {
				query:   'update',
				reffer:  '{$page}',
				id:      self.id,
				columns: self.columns,
				data:    JSON.stringify(self.data)
			},
			timeout: 10000
		}).done((data)=>{
			if (data) {
				self.filetime = parseInt(data);
			} else {
				console.error('tinykanban.inc.php: update failure');
			}
		}).fail(()=>{
			console.error('tinykanban.inc.php: connection error');
		});
	}, 200);
	self.data = data;
};

__TinyKanban__.prototype.addGetHandler = function(handler) {
	this.getHandlers.push(handler);
};

__TinyKanban__.prototype.get = async function() {
	const	self = this;
	if (self.id == 1) {
		let	wait = 1;
		$.ajax({
			type: 'GET',
			url: '{$script}?plugin=tinykanban&query=get&reffer={$page}&filetime=' + self.filetime,
			dataType: 'json',
			timeout: {$interval}
		}).done((data)=>{
			if (data && data.filetime !== undefined) {
				if (data.data) {
					let	index = 0;
					self.getHandlers.forEach((handler) => { handler.set(parseInt(data.filetime), data.data[index++]) });
				}
			}
		}).fail(()=>{
			console.error('tinykanban.inc.php: connection error');
			wait = 2;
		}).always(()=>{
			if ({$interval} > 0) self.getTimer = setTimeout(()=>{self.get()}, {$interval} * wait);
		});
	}
};

__TinyKanban__.prototype.set = function(filetime, data) {
	const	self = this;

	if (!filetime || self.filetime < filetime) {
		self.filetime = filetime;
		let	i = 0;
		$('#__TinyKanban_' + self.id + '__ ul.__TinyKanban_List__ > li').remove();
		data.forEach((column)=>{
			let	j = 0;
			column.forEach((value)=>{
				value = self.escape(value);
				const	ele = $('<li class="ui-state-default' + ((value !== '')? ' __TinyKanban_Protected__' : '') + '"' + (!self.readOnly ? ' onclick="__pluginTinyKanban_' + self.id + '__.focus(this)"' : '') + '><input type="text" value="' + value + '" title="' + value + '" placeholder="Add a title" oninput="__pluginTinyKanban_' + self.id + '__.change(this)" onchange="__pluginTinyKanban_' + self.id + '__.change(this, true)" {$readOnly} maxlength="{$maxLength}"/>' + (!self.readOnly ? '<button title="Remove" onclick="__pluginTinyKanban_' + self.id + '__.remove(this)">&times;</button>' : '') + '</li>');
				$('#__TinyKanban_' + self.id + '__ ul.__TinyKanban_List__[data-tinykanban-list="' + i + '"]').append(ele);
				j++;
			});
			i++;
		});
	}
};

__TinyKanban__.prototype.escape = function(string) { return (typeof string !== 'string')? string : string.replace(/[&'`"<>]/g, function(match){return {'&': '&amp;', "'": '&#x27;', '`': '&#x60;', '"': '&quot;', '<': '&lt;', '>': '&gt;'}[match]}) };
/*-->*/</script>
EOT;
	}

	$body .= '<style>' . $colorStyle . '</style>';
	$body .= $columnEles;
	$body .= '<script>/*<!--*/"use strict";var __pluginTinyKanban_' . $id . '__ = (__pluginTinyKanban_' . $id . '__ === undefined)? new __TinyKanban__(' . $id . ', "' . $columns . '", "#__TinyKanban_' . $id . '__ ul.__TinyKanban_List__", ' . $json . ', __pluginTinyKanban_1__ || null) : __pluginTinyKanban_' . $id . '__.init(true);/*-->*/</script>';
	$body .= '</div>';

	$body = preg_replace("/((\s|\n){1,})/i", ' ', $body);	// 連続空白を単一空白に（※「//」コメント非対応）

	return $body;
}

/* リクエスト受信 */
function plugin_tinykanban_action() {
	global	$vars;
	$result = null;

	if (isset($vars['query'])) {
		$page = $vars['reffer'];

		switch ($vars['query']) {
		case 'update':	/* ページ更新 */
			if (!PKWK_READONLY && (PLUGIN_TINYKANBAN_PUBLIC || (is_editable($page) && is_page_writable($page)))) {	/* 編集権限あり？ */
				$id = (int)$vars['id'];
				$postdata = '';
				foreach (get_source($page) as $line) {
					if (!$result && strpos($line, '#tinykanban') === 0 && --$id === 0) {
						/* 当プラグインの引数にかんばん情報を埋め込む */
						$line = '#tinykanban("' . htmlsc($vars['columns']) . '","' . htmlspecialchars($vars['data']) . '")' . "\n";
						$result = true;
					}
					$postdata .= $line;
				}
				if ($result) {
					page_write($page, $postdata, (PLUGIN_TINYKANBAN_NOTIMESTAMP != 0));
					$result = get_filetime($page);
				}
			}
			break;

		case 'get':	/* 同期のためのページ更新確認 */
			{
				$result = '{}';
				$filetime = get_filetime($page);
				if ((int)$vars['filetime'] >= (int)$filetime) break;	/* まずページ更新時刻を比較し、変わりなければ終了 */
				$source = get_source($page);
				if ($source !== false) {
					/* 当プラグインの引数からかんばん情報を抜き出す */
					$data = '';
					foreach ($source as $line) {
						if (strpos($line, '#tinykanban(') === 0) {
							if ($data) $data .= ',';
							$line = explode('"', $line);
							$cnt = count($line);
							if ($cnt >= 5) {
								$data .= htmlspecialchars_decode(trim($line[$cnt - 2]));
							} else {
								$data .= '[[""]]';
							}
						}
					}
				}
				$result = '{"filetime":' . $filetime . ',"data":' . ($data ? '[' . $data . ']' : 'null') . '}';
			}
			break;
		}
	}

	if ($result) echo $result;
	exit;
}
