<?php
/*
PukiWiki - Yet another WikiWikiWeb clone.
tinykanban.inc.php, v1.0 2022 M.Taniguchi
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

●当プラグインは1ページにつき1つだけ有効です。
　ページ内に複数記述した場合、2つ目以降は無視されます。
*/

/////////////////////////////////////////////////
// 簡易かんばんボードプラグイン（tinykanban.inc.php）
if (!defined('PLUGIN_TINYKANBAN_JQUERY_URL'))    define('PLUGIN_TINYKANBAN_JQUERY_URL',    'https://code.jquery.com/jquery-3.6.0.min.js');        // jQuery のURL（すでに読み込まれていて不要な場合は空にする）
if (!defined('PLUGIN_TINYKANBAN_JQUERYUI_URL'))  define('PLUGIN_TINYKANBAN_JQUERYUI_URL',  'https://code.jquery.com/ui/1.13.0/jquery-ui.min.js'); // jQuery UI のURL（すでに読み込まれていて不要な場合は空にする）
if (!defined('PLUGIN_TINYKANBAN_ADDJS_URL'))     define('PLUGIN_TINYKANBAN_ADDJS_URL',     '');                                                   // 追加JavaScriptのURL（jQuery UIをタッチ操作に対応させるハック jquery.ui.touch-punch.js 等必要に応じて）
if (!defined('PLUGIN_TINYKANBAN_DEFAULTCOLOR'))  define('PLUGIN_TINYKANBAN_DEFAULTCOLOR',  '#aabbcc');                                            // 列のデフォルト色
if (!defined('PLUGIN_TINYKANBAN_SYNC_INTERVAL')) define('PLUGIN_TINYKANBAN_SYNC_INTERVAL', 0);                                                    // 更新同期間隔（秒）。0なら同期しない
if (!defined('PLUGIN_TINYKANBAN_MAXLENGTH'))     define('PLUGIN_TINYKANBAN_MAXLENGTH',     80);                                                   // かんばん名の最大文字数
if (!defined('PLUGIN_TINYKANBAN_PROTECT'))       define('PLUGIN_TINYKANBAN_PROTECT',       1);                                                    // 1：名前が空のかんばんのみ削除できる, 0：名前付きのかんばんも削除できる
if (!defined('PLUGIN_TINYKANBAN_PUBLIC'))        define('PLUGIN_TINYKANBAN_PUBLIC',        0);                                                    // 1：編集権限のないユーザーにもかんばんの変更を許可, 0：かんばんの変更には編集権限が必須

function plugin_tinykanban_convert() {
	global	$vars;
	static	$included = 0;
	if ($included++ > 0) return null;

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
		$lists .= ($lists ? ',' : '') . '#__TinyKanban_List_' . $i . '__';
		$columnEles .= '<div class="__TinyKanban_Column__" data-tinykanban-column="' . $i . '"><div class="__TinyKanban_Header__">' . $name . '<button title="Add" onclick="__pluginTinyKanban__.add(' . $i . ')">&plus;</button></div><ul id="__TinyKanban_List_' . $i . '__" class="__TinyKanban_List__"></ul></div>';
		$colorStyle .= '#__TinyKanban__ .__TinyKanban_Column__[data-tinykanban-column="' . $i . '"] .__TinyKanban_Header__{background-color:' . $color . "}\n";
		$colorStyle .= '#__TinyKanban__ .__TinyKanban_Column__[data-tinykanban-column="' . $i . '"] li{background:linear-gradient(to right,' . $color . ',' . $color . " 12px,var(--TinyKanban-kanban-bg-color) 12px,var(--TinyKanban-kanban-bg-color) 100%)}\n";
	}

	// スタイルを定義
	$body = <<<EOT
<div class="__TinyKanban__" id="__TinyKanban__">
<style>
:root {
	--TinyKanban-board-bg-color: rgba(128,128,128,.07);	/* ボード背景色 */
	--TinyKanban-board-margin: 2px;	/* ボード間隔 */
	--TinyKanban-header-color: #fff; /*  ヘッダー文字色 */
	--TinyKanban-header-font: sans-serif; /* ヘッダーフォント */
	--TinyKanban-header-font-size: 16px; /* ヘッダー文字サイズ */
	--TinyKanban-kanban-bg-color: #fff; /* かんばん背景色 */
	--TinyKanban-kanban-color: rgba(0,0,0,.9); /* かんばん文字色 */
	--TinyKanban-kanban-font: sans-serif; /* かんばんフォント */
	--TinyKanban-kanban-font-size: 13px; /* かんばん文字サイズ */
	--TinyKanban-kanban-margin: 4px; /* かんばん間隔 */
	--TinyKanban-corner-radius: 5px; /* 角丸半径 */
	--TinyKanban-shadow: 0 0 1px rgba(0,0,0,.13), 0 1px 3px rgba(0,0,0,.2); /* 影 */
	--TinyKanban-transition-fadein: 17ms; /* フェードイン時間 */
	--TinyKanban-transition-fadeout: 125ms; /* フェードアウト時間 */
}
#__TinyKanban__ {
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
.ui-draggable, .ui-droppable {background-position:top}
#__TinyKanban__ .__TinyKanban_Column__, #__TinyKanban__ .__TinyKanban_Column__:first-child, #__TinyKanban__ .__TinyKanban_Column__:last-child {
	position: relative;
	display: flex;
	flex-direction: column;
	border: none;
	min-height: 20px;
	margin: 0 var(--TinyKanban-board-margin);
	padding: 0 0 2px;
	height: auto;
	box-sizing: border-box;
	border-radius: var(--TinyKanban-corner-radius);
	overflow: visible;
	flex: 0 100 100%;
	background: var(--TinyKanban-board-bg-color);
}
#__TinyKanban__ .__TinyKanban_Column__:first-child {margin-left:0}
#__TinyKanban__ .__TinyKanban_Column__:last-child {margin-right:0}
#__TinyKanban__ .__TinyKanban_Header__ {
	position: sticky;
	top: 0;
	color: var(--TinyKanban-header-color);
	background: #808080;
	text-align: center;
	font-family: var(--TinyKanban-header-font);
	font-size: var(--TinyKanban-header-font-size);
	font-weight: bold;
	line-height: 1em;
	padding: 6px 0;
	margin: 0 0 1px;
	border-radius: var(--TinyKanban-corner-radius) var(--TinyKanban-corner-radius) 0 0;
	box-sizing: border-box;
	z-index: 1;
}
#__TinyKanban__ button {
	position: absolute;
	top: calc(50% - 10px);
	width: 20px;
	min-width: 20px;
	max-width: 20px;
	height: 20px;
	min-height: 20px;
	max-height: 20px;
	padding: 0;
	margin: 0;
	background: #fff;
	color: #999;
	text-align: center;
	vertical-align: middle;
	line-height: 20px;
	font-family: sans-serif;
	font-size: 16px;
	font-weight: bold;
	border: none;
	border-radius: 100%;
	box-sizing: border-box;
	overflow: hidden;
	opacity: 0;
	box-shadow: var(--TinyKanban-shadow);
	transition: opacity var(--TinyKanban-transition-fadeout);
}
#__TinyKanban__ .__TinyKanban_Header__ button {
	right: 6px;
}
#__TinyKanban__ ul.__TinyKanban_List__ {
	width: 100%;
	height: 100%;
	flex: 0 100 100%;
	padding: 0;
	margin: 0;
	line-height: 100%;
	box-sizing: border-box;
	overflow: auto;
}
#__TinyKanban__ .__TinyKanban_List__::-webkit-scrollbar {
	width: 5px;
	border: 0 none;
	box-sizing: border-box;
	padding: 0;
	margin: 0;
}
#__TinyKanban__ .__TinyKanban_List__::-webkit-scrollbar-track {background:transparent}
#__TinyKanban__ .__TinyKanban_List__::-webkit-scrollbar-thumb {
	background: rgba(128,128,128,.25);
	border-radius: 2px;
}
#__TinyKanban__ ul.__TinyKanban_List__ > li {
	position: relative;
	font-size: var(--TinyKanban-kanban-font-size);
	line-height: 1em;
	margin: var(--TinyKanban-kanban-margin) 4px;
	padding: 2px 2px 2px 16px;
	color: var(--TinyKanban-kanban-color);
	list-style-type: none;
	vertical-align: middle;
	border: none;
	box-sizing: border-box;
	border-radius: var(--TinyKanban-corner-radius);
	background: linear-gradient(to right, #808080, #808080 12px, var(--TinyKanban-kanban-bg-color) 12px, var(--TinyKanban-kanban-bg-color) 100%);
	box-shadow: var(--TinyKanban-shadow);
}
#__TinyKanban__ ul.__TinyKanban_List__ > li input, #__TinyKanban__ ul.__TinyKanban_List__ > li input:disabled {
	font-family: var(--TinyKanban-kanban-font);
	font-size: var(--TinyKanban-kanban-font-size);
	font-feature-settings: 'palt' 1;
	margin: 0;
	padding: 1px 3px 1px 2px;
	line-height: 1em;
	border: none;
	box-sizing: border-box;
	background: var(--TinyKanban-kanban-bg-color);
	color: var(--TinyKanban-kanban-color);
	width: 100%;
	border-radius: 3px;
	transition: background-color var(--TinyKanban-transition-fadeout);
}
#__TinyKanban__ ul.__TinyKanban_List__ > li input::placeholder {color:rgba(128,128,128,.333)}
#__TinyKanban__ ul.__TinyKanban_List__ > li button {
	right: 2px;
	margin: 0 0 0 2px;
}
${colorStyle}
EOT;

	// 編集権限ありならUIをインタラクティブに
	if (!$readOnly) {
		$body .=<<<EOT
#__TinyKanban__ .__TinyKanban_Column__:hover .__TinyKanban_Header__ button {opacity:1; transition:opacity var(--TinyKanban-transition-fadein)}
#__TinyKanban__ .__TinyKanban_Column__ .__TinyKanban_Header__ button:hover {background-color:#fff; color:#000; cursor:pointer}
#__TinyKanban__ ul.__TinyKanban_List__ > li:hover {cursor:grab}
#__TinyKanban__ ul.__TinyKanban_List__ > li input:hover {background-color:rgba(128,128,128,.07); transition:background-color var(--TinyKanban-transition-fadein)}
#__TinyKanban__ ul.__TinyKanban_List__ > li:hover button {opacity:1; transition:opacity var(--TinyKanban-transition-fadein)}
#__TinyKanban__ ul.__TinyKanban_List__ > li button:hover {color:#000; cursor:pointer; transition:color var(--TinyKanban-transition-fadein)}
EOT;
	}

	// 定数に応じて調整
	if (PLUGIN_TINYKANBAN_PROTECT) $body .= "#__TinyKanban__ ul.__TinyKanban_List__ > li.__TinyKanban_Protected__ button {display:none}\n";

	// JavaScript
	$body .= <<<EOT
</style>
${jqueryUrl}
<script>
'use strict';

var	__TinyKanban__ = function() {
	const	self = this;
	this.readOnly = '${readOnly}';
	this.filetime = ${filetime};
	this.postTimer = null;
	this.getTimer = null;
	this.data = null;

	if (document.readyState !== 'loading') self.init();
	else window.addEventListener('DOMContentLoaded', ()=>{self.init()}, {once: true, passive: true});
}

// 初期化
__TinyKanban__.prototype.init = function(repeated) {
	const	self = this;
	if (!self.readOnly) {
		$('${lists}').sortable({
			connectWith: '.__TinyKanban_List__',
			update: ()=>{ self.update() },
			cursor: 'grabbing'
		}).disableSelection();
	}

	if (!repeated) {
		/*<!--*/self.set(${json});/*-->*/
		if (${interval} > 0) self.getTimer = setTimeout(()=>{self.get()}, ${interval});
	} else {
		self.filetime = 0;
		if (self.getTimer) clearTimeout(self.getTimer);
		self.get();
	}


	return this;
}

// かんばん追加
__TinyKanban__.prototype.add = function(index) {
	const	ele = $('<li class="ui-state-default" onclick="__pluginTinyKanban__.focus(this)"><input type="text" value="" title="" placeholder="Add a title" oninput="__pluginTinyKanban__.change(this)" onchange="__pluginTinyKanban__.change(this, true)" maxlength="${maxLength}"/><button title="Remove" onclick="__pluginTinyKanban__.remove(this)">&times;</button></li>');
	$('.__TinyKanban_Column__[data-tinykanban-column="' + index + '"] ul').append(ele);
	this.update();
}

// かんばん削除
__TinyKanban__.prototype.remove = function(ele) {
	$(ele).closest('li').remove();
	this.update();
}

// かんばん名入力フォーカス
__TinyKanban__.prototype.focus = function(ele) {
	$(ele).find('input').focus();
}

// かんばん名更新
__TinyKanban__.prototype.change = function(ele, update = false) {
	const	parent = $(ele).parent();
	if (ele.value) parent.addClass('__TinyKanban_Protected__');
	else parent.removeClass('__TinyKanban_Protected__');
	if (update) {
		$(ele).attr('title', ele.value);
		this.update('change', parent);
	}
}

// DOM更新
__TinyKanban__.prototype.update = function(event, ui) {
	let	data = [];
	$('#__TinyKanban__ .__TinyKanban_List__').each((i, list)=>{
		let	column = [];
		$(list).children('li').each((index, item)=>{ column.push($(item).find('input').val()) });
		data.push(column);
	});
	this.post(data);
}

// ページ更新要求送信
__TinyKanban__.prototype.post = async function(data) {
	const	self = this;
	if (self.postTimer) clearTimeout(self.postTimer);
	self.postTimer = setTimeout(()=>{
		self.postTimer = null;
		$.ajax({
			type: 'POST',
			url: './?plugin=tinykanban',
			data: {
				query:   'update',
				reffer:  '${page}',
				columns: '${columns}',
				data:    JSON.stringify(self.data)
			},
			timeout: 10000
		}).done((data)=>{
			if (data) self.filetime = parseInt(data);
			else console.error('tinykanban.inc.php: update failure');
		}).fail(()=>{
			console.error('tinykanban.inc.php: connection error');
		});
	}, 200);
	self.data = data;
}

// ページ更新確認要求送信
__TinyKanban__.prototype.get = async function() {
	const	self = this;
	let	wait = 1;
	$.ajax({
		type: 'GET',
		url: './?plugin=tinykanban&query=get&reffer=${page}&filetime=' + self.filetime,
		dataType: 'json',
		timeout: ${interval}
	}).done((data)=>{
		if (data && data.filetime !== undefined) {
			self.filetime = parseInt(data.filetime);
			if (data.data) {
				self.set(data.data);
			} else {
				console.error('tinykanban.inc.php: get_source error');
				wait = 10;
			}
		}
	}).fail(()=>{
		console.error('tinykanban.inc.php: connection error');
		wait = 2;
	}).always(()=>{
		if (${interval} > 0) self.getTimer = setTimeout(()=>{self.get()}, ${interval} * wait);
	});
}

// JSONに基づいてかんばん要素を追加
__TinyKanban__.prototype.set = function(data) {
	const	self = this;
	let	i = 0;
	$('ul.__TinyKanban_List__ > li').remove();
	data.forEach((column)=>{
		let	j = 0;
		column.forEach((value)=>{
			value = self.escape(value);
			const	ele = $('<li class="ui-state-default' + ((value !== '')? ' __TinyKanban_Protected__' : '') + '"' + (!self.readOnly ? ' onclick="__pluginTinyKanban__.focus(this)"' : '') + '><input type="text" value="' + value + '" title="' + value + '" placeholder="Add a title" oninput="__pluginTinyKanban__.change(this)" onchange="__pluginTinyKanban__.change(this, true)" ${readOnly} maxlength="${maxLength}"/>' + (!self.readOnly ? '<button title="Remove" onclick="__pluginTinyKanban__.remove(this)">&times;</button>' : '') + '</li>');
			$('#__TinyKanban_List_' + i + '__').append(ele);
			j++;
		});
		i++;
	});
}

// 文字列エスケープ
__TinyKanban__.prototype.escape = function(string) { return (typeof string !== 'string')? string : string.replace(/[&'`"<>]/g, function(match){return {'&': '&amp;', "'": '&#x27;', '`': '&#x60;', '"': '&quot;', '<': '&lt;', '>': '&gt;'}[match]}) }

// 起動
var __pluginTinyKanban__ = (__pluginTinyKanban__ === undefined)? new __TinyKanban__() : __pluginTinyKanban__.init(true);
</script>
EOT;

	$body .= $columnEles . '</div>';
	return $body;
}

// リクエスト受信
function plugin_tinykanban_action() {
	global	$vars;
	$result = null;

	if (isset($vars['query'])) {
		$page = $vars['reffer'];

		switch ($vars['query']) {
		case 'update':	// ページ更新
			if (!PKWK_READONLY && (PLUGIN_TINYKANBAN_PUBLIC || (is_editable($page) && is_page_writable($page)))) {	// 編集権限あり？
				$postdata = '';
				foreach (get_source($page) as $line) {
					if (!$result && strpos($line, '#tinykanban(') === 0) {
						// 当プラグインの引数にかんばん情報を埋め込む
						$line = '#tinykanban("' . htmlsc($vars['columns']) . '","' . htmlspecialchars($vars['data']) . '")' . "\n";
						$result = true;
					}
					$postdata .= $line;
				}
				if ($result) {
					page_write($page, $postdata);
					$result = get_filetime($page);
				}
			}
			break;

		case 'get':	// 同期のためのページ更新確認
			{
				$result = '{}';
				$filetime = get_filetime($page);
				if ((int)$vars['filetime'] >= (int)$filetime) break;	// まずページ更新時刻を比較し、変わりなければ終了
				$source = get_source($page);
				if ($source !== false) {
					// 当プラグインの引数からかんばん情報を抜き出す
					foreach ($source as $line) {
						if (strpos($line, '#tinykanban(') === 0) {
							$line = explode('"', $line);
							$data = $line[count($line) - 2];
							break;
						}
					}
				}
				$result = '{"filetime":' . $filetime . ',"data":' . ($data ? htmlspecialchars_decode($data) : 'null') . '}';
			}
			break;
		}
	}

	if ($result) echo $result;
	exit;
}
