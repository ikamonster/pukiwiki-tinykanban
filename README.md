# PukiWiki用プラグイン<br>簡易かんばんボード tinykanban.inc.php

かんばん方式の簡易ToDoリストを表示する[PukiWiki](https://pukiwiki.osdn.jp/)用プラグイン。  



|対象PukiWikiバージョン|対象PHPバージョン|
|:---:|:---:|
|PukiWiki 1.5.3 ~ 1.5.4 (UTF-8)|PHP 7.4 ~ 8.1|

<br>

## インストール

下記 GitHub ページからダウンロードした tinykanban.inc.php を PukiWiki の plugin ディレクトリに配置してください。

[https://github.com/ikamonster/pukiwiki-tinykanban](https://github.com/ikamonster/pukiwiki-tinykanban)

<br>

## 使い方

```
#tinykanban(["列名1[:色1][|列名2[:色2][|...]]"])
```

列名と色の組を「|」で区切って必要なだけ羅列する。必ず全体を「"」で囲むこと。  
すべて省略すると「To Do」「Doing」「Done」の３列になる。

<br>

## 使用例

```
#tinykanban()
#tinykanban("予定|進行中|完了")
#tinykanban("提案:orange|着手:#e00000|完了:#0c0|却下:rgb(128,128,128)")
```

<br>

## かんばんボードの操作法
![fig1](https://user-images.githubusercontent.com/3040830/150647496-19319665-76a6-43f0-a3b5-9db077478caa.png)

- ヘッダーの「＋」ボタンをクリックするとかんばんが追加されます。
- かんばんをクリックすると名前を入力できます。
- かんばんの端をドラッグ＆ドロップすることで列の間を移動させることができます。
- かんばんの名前を消去すると横に「×」ボタンが現れ、クリックするとそのかんばんを削除できます。

<br>

## ご注意

- 追加・編集したかんばん情報は、当プラグインを埋め込んだページに直接書き込まれます（標準 comment プラグインと似た仕組み）。ページへの書き込みはバックグラウンドで行われ、衝突を無視して常に上書きします。そのため、プライベートなウィキや編集制限されたページでのご利用をお勧めします。
- お勧めしませんが複数ユーザーで同時に編集したい場合は、定数 PLUGIN_TINYKANBAN_SYNC_INTERVAL に適当な同期間隔を設定してください。他ユーザーの更新内容がほぼリアルタイムに（設定した秒数の遅れで）自分の画面に反映されるため、衝突が起こりにくくなります。サーバーへの問い合わせがバックグラウンドで定期実行されるため、負荷や通信量の増加にご注意ください。
- かんばんのドラッグ＆ドロップ操作に jQuery UI を利用しています。なお、jQuery UI はタッチ操作に対応していません（jQuery UI v1.13現在）。

<br>

## 設定

ソース内の下記の定数で動作を制御することができます。

|定数名|値|既定値|意味|
|:---|:---:|:---|:---|
|PLUGIN_TINYKANBAN_JQUERY_URL|URL|'https[]()://code.jquery.com/jquery-3.6.1.min.js'|[jQuery](https://jquery.com/) のURL（すでに読み込まれていて不要な場合は空にする）|
|PLUGIN_TINYKANBAN_JQUERYUI_URL|URL|'https[]()://code.jquery.com/ui/1.13.2/jquery-ui.min.js'|[jQuery UI](https://jqueryui.com/) のURL（すでに読み込まれていて不要な場合は空にする）|
|PLUGIN_TINYKANBAN_ADDJS_URL|URL|''|追加 JavaScriptの URL（jQuery UI をタッチ操作に対応させるハック [jquery.ui.touch-punch.js](https://github.com/furf/jquery-ui-touch-punch) 等必要に応じて）|
|PLUGIN_TINYKANBAN_THEME|0 ~ 2|0|0：ライトテーマ, 1：ダークテーマ, 2：自動|
|PLUGIN_TINYKANBAN_DEFAULTCOLOR|HTMLカラーコード|'#aabbcc'|列のデフォルト色|
|PLUGIN_TINYKANBAN_SYNC_INTERVAL|数値|0|更新同期間隔（秒）。0 なら同期しない|
|PLUGIN_TINYKANBAN_MAXLENGTH|数値|80|かんばん名の最大文字数|
|PLUGIN_TINYKANBAN_PROTECT|0 or 1|1|1：名前が空のかんばんのみ削除できる<br>0：名前付きのかんばんも削除できる|
|PLUGIN_TINYKANBAN_ACROSS|0 or 1|0|1：ページ内に複数のかんばんボードがあるとき、かんばんがボードを跨いで移動できる<br>0：かんばんがボードを跨げない|
|PLUGIN_TINYKANBAN_PUBLIC|0 or 1|0|1：編集権限のないユーザーにもかんばんの変更を許可<br>0：かんばんの変更には編集権限が必須|
