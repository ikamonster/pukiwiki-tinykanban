<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// ika.skin.php v1.2.4
// Copyright 2020 M.Taniguchi
// License: GPL v3 or (at your option) any later version
//
// Ika skin
// https://ika.monster/?PukiWiki/ika.skin.php

// ------------------------------------------------------------
// Settings for Ika skin

if (!defined('IKASKIN_TITLE'))             define('IKASKIN_TITLE',             0); // Header title (0:Page title, 1:Wiki title)
if (!defined('IKASKIN_THEME'))             define('IKASKIN_THEME',             0); // Color theme (0:Light, 1:Dark, 2:Adaptive)
if (!defined('IKASKIN_LINKCOLOR_LIGHT'))   define('IKASKIN_LINKCOLOR_LIGHT',  ''); // Link color for light theme (ex. '#0000ff')
if (!defined('IKASKIN_LINKCOLOR_DARK'))    define('IKASKIN_LINKCOLOR_DARK',   ''); // Link color for dark theme (ex. '#0000ff')
if (!defined('IKASKIN_SHOW_LASTMODIFIED')) define('IKASKIN_SHOW_LASTMODIFIED', 0); // Show last modified (0:No, 1:Yes)
if (!defined('IKASKIN_FONT_SIZE'))         define('IKASKIN_FONT_SIZE',         0); // Font size (px, 0:Default)
if (!defined('IKASKIN_LINE_HEIGHT'))       define('IKASKIN_LINE_HEIGHT',       0); // Line height (em, 0:Default)
if (!defined('IKASKIN_MENU_WIDTH'))        define('IKASKIN_MENU_WIDTH',        0); // Width of MenuBar (px, 0:Default)
if (!defined('IKASKIN_BODY_WIDTH'))        define('IKASKIN_BODY_WIDTH',        0); // Width of article body (px, 0:Default)
if (!defined('IKASKIN_WORDWRAP'))          define('IKASKIN_WORDWRAP',          1); // Line breaking rules (0:Break, 1:Wrap)
if (!defined('IKASKIN_SIMPLIFY'))          define('IKASKIN_SIMPLIFY',          0); // Simplify decorating style (0:No, 1:Yes)
if (!defined('IKASKIN_COPYRIGHT'))         define('IKASKIN_COPYRIGHT',         0); // Admin's name prefix (0:"Site admin", 1:"©" or any string)
if (!defined('IKASKIN_MENU_ORDER'))        define('IKASKIN_MENU_ORDER',        0); // MenuBars order (0:MenuBar->Article->RightBar, 1:RightBar->Article->MenuBar)
if (!defined('IKASKIN_TABLE_ZEBRA'))       define('IKASKIN_TABLE_ZEBRA',       0); // Zebra-striped tables (0:No, 1:Yes)
if (!defined('IKASKIN_LOGO'))              define('IKASKIN_LOGO',             ''); // Site logo image path (ex. 'image/pukiwiki.png') or SVG tag (ex. '<svg ...>...</svg>')
if (!defined('IKASKIN_FAVICON'))           define('IKASKIN_FAVICON',          ''); // favicon image path (ex. '/favicon.ico')
if (!defined('IKASKIN_APPLETOUCHICON'))    define('IKASKIN_APPLETOUCHICON',   ''); // 180x180px PNG icon image path (ex. 'apple-touch-icon.png')
if (!defined('IKASKIN_CSS'))               define('IKASKIN_CSS',              ''); // Additional CSS file path (ex. 'mystyle.css')
if (!defined('IKASKIN_DISUSE_MAINJS'))     define('IKASKIN_DISUSE_MAINJS',     0); // Disuse main.js (0:No, 1:Yes)
if (!defined('IKASKIN_DISUSE_SEARCH2JS'))  define('IKASKIN_DISUSE_SEARCH2JS',  0); // Disuse search2.js (0:No, 1:Yes)

// ------------------------------------------------------------
// Settings (define before here, if you want)

// Set site identities
$_IMAGE['skin']['logo']    = IKASKIN_LOGO; // Sample: 'pukiwiki.png';
$_IMAGE['skin']['favicon'] = IKASKIN_FAVICON; // Sample: 'image/favicon.ico';

// Show / Hide navigation bar UI at your choice
// NOTE: This is not stop their functionalities!
if (!defined('PKWK_SKIN_SHOW_NAVBAR')) define('PKWK_SKIN_SHOW_NAVBAR', 1); // 0, 1

// Unuse - Must be 0 only for compatibility
if (!defined('SKIN_DEFAULT_DISABLE_TOPICPATH')) define('SKIN_DEFAULT_DISABLE_TOPICPATH', 0);
if (!defined('PKWK_SKIN_SHOW_TOOLBAR')) define('PKWK_SKIN_SHOW_TOOLBAR', 0);

// ------------------------------------------------------------
// Code start

// Prohibit direct access
if (!defined('UI_LANG')) die('UI_LANG is not set');
if (!isset($_LANG)) die('$_LANG is not set');
if (!defined('PKWK_READONLY')) die('PKWK_READONLY is not set');

$lang  = &$_LANG['skin'];
$link  = &$_LINK;
$image = &$_IMAGE['skin'];
$rw    = !PKWK_READONLY;

global	$rss_max;
$pregFlag = 'i' . (PKWK_UTF8_ENABLE ? 'u' : '');
$isHome = ($title === $defaultpage);
$thisPageUri = get_page_uri($title, PKWK_URI_ABSOLUTE);
$pageTitle = htmlsc($page_title);
$longTitle = (!$isHome ? $title . ' - ' : '') . $pageTitle;
$modifiedDate = date('Y-m-d\TH:i:sP', get_filetime($title));
$pageName = preg_replace('/(\<[^\>]+\>|\s$)/' . $pregFlag, '', $page);
$theme = array('_ikaskin_theme_light_', '_ikaskin_theme_dark_', '_ikaskin_theme_adaptive_')[(IKASKIN_THEME >= 0 && IKASKIN_THEME <= 2)? (int)IKASKIN_THEME : 0];
$wordbreak = (IKASKIN_WORDWRAP)? ' _ikaskin_wordwrap_' : ' _ikaskin_wordbreak_';
$simplify = (IKASKIN_SIMPLIFY)? ' _ikaskin_simplify_' : '';
$appleTouchIcon = (IKASKIN_APPLETOUCHICON)? '<link rel="apple-touch-icon" href="' . IKASKIN_APPLETOUCHICON . '" />' : '';
$author = (IKASKIN_COPYRIGHT)? 'author' : 'publisher';

// MenuBar
$menu = exist_plugin_convert('menu')? do_plugin_convert('menu') : false;
// RightBar
$rightbar = exist_plugin_convert('rightbar')? do_plugin_convert('rightbar') : false;

$cssProperties = '';
if (IKASKIN_LINKCOLOR_LIGHT) $cssProperties .= '--color-link-user:' . IKASKIN_LINKCOLOR_LIGHT . ';';
if (IKASKIN_LINKCOLOR_DARK) $cssProperties .= '--dcolor-link-user:' . IKASKIN_LINKCOLOR_DARK . ';';
if (IKASKIN_FONT_SIZE) $cssProperties .= '--font-size-user:' . (float)IKASKIN_FONT_SIZE . 'px;';
if (IKASKIN_LINE_HEIGHT) $cssProperties .= '--line-height-user:' . (float)IKASKIN_LINE_HEIGHT . 'em;';
if (IKASKIN_MENU_WIDTH) $cssProperties .= '--menu-width-user:' . (float)IKASKIN_MENU_WIDTH . 'px;';
if (IKASKIN_BODY_WIDTH) $cssProperties .= '--body-width-user:' . (float)IKASKIN_BODY_WIDTH . 'px;';
if (IKASKIN_MENU_ORDER == 1) $cssProperties .= '--order-menubar:3;--order-rightbar:1;';
if (($menu && IKASKIN_MENU_ORDER == 0) || ($rightbar && IKASKIN_MENU_ORDER == 1)) $cssProperties .= '--margin-article-left:var(--margin-ui);';
if (($menu && IKASKIN_MENU_ORDER == 1) || ($rightbar && IKASKIN_MENU_ORDER == 0)) $cssProperties .= '--margin-article-right:var(--margin-ui);';
if ($cssProperties) $cssProperties = ':root{' . $cssProperties . '}';
if (IKASKIN_TABLE_ZEBRA) $cssProperties .= 'tr:nth-child(even){background-color:var(--color-bg-table)}';

// ------------------------------------------------------------
// Output

// HTTP headers
pkwk_common_headers();
header('Cache-control: no-cache');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=' . CONTENT_CHARSET);
?>
<!DOCTYPE html>
<html lang="<?php echo LANG ?>" class="<?php echo $theme . $wordbreak . $simplify ?>" itemscope="itemscope" itemtype="http://schema.org/WebPage">
<head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb#">
<meta charset="UTF-8" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CONTENT_CHARSET ?>" />
<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1,viewport-fit=cover" />
<meta name="content-language" content="<?php echo LANG ?>" itemprop="inLanguage" />
<meta name="<?php echo $author ?>" content="<?php echo $modifier ?>" itemprop="<?php echo $author ?>" />
<meta name="title" content="<?php echo $longTitle ?>" itemprop="headline" />
<meta name="date" content="<?php echo $modifiedDate ?>" itemprop="datePublished" />
<meta name="classification" content="wiki" />
<meta name="format-detection" content="telephone=no" />
<meta name="theme-color" content="#ffffff" />
<?php if ($nofollow || ! $is_read)  { ?><meta name="robots" content="noindex,nofollow,noarchive" /><?php } ?>
<?php if ($html_meta_referrer_policy) { ?><meta name="referrer" content="<?php echo htmlsc(html_meta_referrer_policy) ?>" /><?php } ?>

<title><?php echo $longTitle; ?></title>

<?php if ($image['favicon']) echo '<link rel="icon" href="' . $image['favicon'] . "\" />\n"; ?>
<link rel="index" href="<?php echo get_script_uri(); ?>" title="<?php echo $pageTitle; ?>" />
<?php if ($modifierlink && $modifierlink != 'http://pukiwiki.example.com/') echo '<link rel="publisher" href="' . $modifierlink . "\" />\n"; ?>
<?php if ($rss_max > 0 && exist_plugin_action('rss')) echo '<link rel="alternate" type="application/rss+xml" title="RSS" href="' . $link['rss'] . '" />'; // RSS auto-discovery ?>

<?php if (!IKASKIN_DISUSE_MAINJS) echo '<script type="text/javascript" src="skin/main.js" defer></script>' . "\n"; ?>
<?php if (!IKASKIN_DISUSE_SEARCH2JS) echo '<script type="text/javascript" src="skin/search2.js" defer></script>' . "\n"; ?>

<link rel="canonical" href="<?php echo $thisPageUri; ?>" />
<link rel="alternate" hreflang="<?php echo LANG ?>" href="<?php echo $thisPageUri; ?>" />
<?php echo $appleTouchIcon ?>

<?php
echo "<style>@charset 'UTF-8';:root{--mode-width:0;--font-body:sans-serif;--font-headline:var(--font-body);--font-size:15px;--line-height:1.55em;--font-size-body:var(--font-size-user, var(--font-size));--line-height-body:var(--line-height-user, var(--line-height));--font-ui:var(--font-body);--font-size-ui:calc(var(--font-size) * 13 / 15);--line-height-ui:1.25em;--font-code:Consolas,Monaco,'Roboto Mono',monospace;--font-size-code:calc(var(--font-size) * 12 / 15);--line-height-code:1.35em;--font-size-table:calc(var(--font-size) * 12 / 15);--margin-paragraph:1em;--margin-paragraph-body:1em;--margin-block:1.25em;--margin-ui:40px;--border-radius:8px;--body-width:var(--body-width-user, 100%);--menu-width:var(--menu-width-user, 160px);--color-border:rgba(0,0,0,.5);--color-border-section:var(--color-border);--color-border-header:var(--color-border);--color-border-footer:var(--color-border);--color-added:rgba(0,0,0,.5);--color-removed:#ccc;--color-search:rgba(0,0,0,.5)}@media screen{:root{--color-bg:var(--color-bg-user, #fff);--color:rgba(0,0,0,.9);--color-headline:var(--color);--color-bg-headline:var(--color-bg);--color-menu:var(--color);--color-bg-menu:var(--color-bg);--color-link:var(--color-link-user, rgba(0,0,0,.6));--color-link-underline:var(--color-link-user, rgba(0,0,0,.2));--color-link-visited:var(--color-link);--color-logo:var(--color);--color-border:rgba(0,0,0,.2);--color-input:var(--color);--color-bg-input:#fff;--color-code:rgba(0,0,0,.7);--color-bg-code:rgba(128,128,128,.06);--color-bg-table:rgba(128,128,128,.06);--color-attention:#e70473;--color-added:#e70473;--color-bg-added:transparent;--color-removed:#00c1c1;--color-bg-removed:transparent;--color-search:var(--color-attention);--color-bg-search:transparent;--color-border-section:rgba(0,0,0,.5);--color-border-header:var(--color-border-section);--color-border-footer:var(--color-border-section);--color-bg-footer:transparent;--dcolor-bg:var(--dcolor-bg-user, #000);--dcolor:rgba(255,255,255,.9);--dcolor-headline:var(--dcolor);--dcolor-bg-headline:var(--dcolor-bg);--dcolor-menu:var(--dcolor);--dcolor-bg-menu:var(--dcolor-bg);--dcolor-link:var(--dcolor-link-user, rgba(255,255,255,.6));--dcolor-link-underline:var(--dcolor-link-user, rgba(255,255,255,.3));--dcolor-link-visited:var(--dcolor-link);--dcolor-logo:var(--dcolor);--dcolor-border:rgba(255,255,255,.3);--dcolor-input:var(--dcolor);--dcolor-bg-input:#000;--dcolor-code:rgba(255,255,255,.8);--dcolor-bg-code:rgba(128,128,128,.333);--dcolor-bg-table:rgba(128,128,128,.225);--dcolor-attention:#e70473;--dcolor-added:#e70473;--dcolor-bg-added:transparent;--dcolor-removed:#00c1c1;--dcolor-bg-removed:transparent;--dcolor-search:var(--dcolor-attention);--dcolor-bg-search:transparent;--dcolor-border-section:rgba(255,255,255,.5);--dcolor-border-header:var(--dcolor-border-section);--dcolor-border-footer:var(--dcolor-border-section);--dcolor-bg-footer:transparent}}html{color:#000;background:#fff}article,blockquote,body,code,dd,div,dl,dt,fieldset,footer,form,h1,h2,h3,h4,h5,h6,header,input,legend,li,main,nav,ol,p,pre,section,td,textarea,th,ul{margin:0;padding:0}table{border-collapse:collapse;border-spacing:0}fieldset,img{border:0}address,caption,cite,code,dfn,em,strong,th,var{font-style:normal;font-weight:400}ol,ul{list-style:none}caption,th{text-align:left}h1,h2,h3,h4,h5,h6{font-size:100%;font-weight:400}q:after,q:before{content:''}abbr,acronym{border:0;font-variant:normal}sup{vertical-align:text-top}sub{vertical-align:text-bottom}input,select,textarea{font-family:inherit;font-size:inherit;font-weight:inherit;font-size:100%}legend{color:#000}#yui3-css-stamp.cssreset{display:none}a,button,input[type=button i],input[type=file i]::-webkit-file-upload-button,input[type=reset i],input[type=submit i]{outline:0 none;tap-highlight-color:transparent;-webkit-tap-highlight-color:transparent}iframe{vertical-align:bottom}html{font-family:var(--font-body);font-size:var(--font-size);line-height:var(--line-height);background-color:var(--color-bg);color:var(--color);overflow-wrap:break-word;-webkit-tap-highlight-color:transparent;tap-highlight-color:transparent;-webkit-touch-callout:none;-webkit-text-size-adjust:100%}body,html{position:relative;z-index:0;width:auto;max-width:100%;min-width:0;height:auto;min-height:100vh;margin:0;padding:0;box-sizing:border-box}article,aside,code,dd,details,dialog,div,dl,dt,fieldset,form,hgroup,hr,nav,p,pre{position:relative;margin-top:var(--margin-block);margin-bottom:var(--margin-block);flex:0 100 auto}#body{display:display;flex:0 100000 100%;font-size:var(--font-size-body);line-height:var(--line-height-body);margin:0;z-index:0;overflow:visible}#body>:first-child{margin-top:0}p{margin-top:var(--margin-paragraph);margin-bottom:var(--margin-paragraph);overflow:visible}#body p{display:block;margin-block-start:var(--margin-paragraph-body);margin-block-end:var(--margin-paragraph-body);margin-inline-start:0;margin-inline-end:0}a{color:var(--color-link);text-decoration:underline 1px dotted var(--color-link-underline)}a:hover{text-decoration:underline 1px solid var(--color-link)}a:visited{color:var(--color-link-visited)}#footer,#menubar,#rightbar{position:relative;font-family:var(--font-ui);font-size:var(--font-size-ui);line-height:var(--line-height-ui);box-sizing:border-box;z-index:0}#menubar,#navigator,#rightbar,.anchor_super,.jumpmenu,.note_hr,button{user-select:none;-moz-user-select:none;-webkit-user-select:none;-ms-user-select:none}code,pre{font-family:var(--font-code);font-feature-settings:'fwid' 1,'tnum' 1,'zero' 1,'lnum' 1;font-size:var(--font-size-code);line-height:var(--line-height-code);white-space:pre-wrap;word-wrap:break-word;line-break:anywhere;tab-size:4;-moz-tab-size:4}pre{background-color:var(--color-bg-code);color:var(--color-code);position:relative;width:100%;max-width:100%;min-width:0;height:auto;border:1px solid var(--color-border);border-radius:var(--border-radius);box-sizing:border-box;padding:.5em .75em;margin:var(--margin-block) 0}#body pre{margin:var(--margin-paragraph-body) 0}img{border:none;vertical-align:middle}embed,iframe,img,object,video{max-width:100%;height:auto}.spacer,iframe{clear:both}body{display:flex;flex-flow:column nowrap;align-items:stretch;justify-content:flex-start;align-content:flex-start}body>div,body>p{margin:0}.anchor_super,.jumpmenu{position:absolute;visibility:hidden;vertical-align:super;font-size:var(--font-size-ui);font-weight:400;line-height:1em;width:0;max-width:0;height:0;max-height:0;margin:0;padding:0;border:none;letter-spacing:0;text-indent:0;box-shadow:none}.anchor_super,.anchor_super:visited,.jumpmenu,.jumpmenu:visited{text-decoration:none;color:var(--color-border)}:hover>.anchor_super{visibility:visible;position:absolute;min-height:0;min-width:0;margin:0;padding:0;border:none}sup{vertical-align:super}sub{vertical-align:sub}#contents{display:flex;flex-flow:row nowrap;align-items:stretch;justify-content:var(--justify-content,center);align-content:space-between;flex-grow:10000;max-width:100%;height:auto;margin:0 var(--margin-ui);word-break:break-all;overflow-wrap:break-word;word-wrap:break-word}#_Skin_Article_{order:var(--order-article,2);flex:10000 1 100%;display:flex;flex-flow:column nowrap;align-items:stretch;justify-content:center;align-content:stretch;margin:0;margin-left:var(--margin-article-left,0);margin-right:var(--margin-article-right,0);width:100%;min-width:0;max-width:var(--body-width,100%)}#navigator{clear:both;display:inline-block;float:right;text-align:right}#navigator>a{display:inline-block}#navigator>span{display:inline-block;margin:0 .25em;color:var(--color-border-section)}#_Skin_Copyright_{display:inline-block;float:left;text-align:left}#menubar,#rightbar{position:relative;top:0;left:0;color:var(--color-menu);background-color:var(--color-bg-menu);width:var(--menu-width);min-width:var(--menu-width);max-width:var(--menu-width);margin:0;word-break:break-all}#menubar{order:var(--order-menubar,1);flex:0 1 var(--menu-width)}#rightbar{order:var(--order-rightbar,3);flex:0 1 var(--menu-width)}#menubar li,#rightbar li{margin-top:.3em;margin-bottom:.3em;line-height:1.1em}#menubar ul>li,#rightbar ul>li{list-style-type:none}#menubar.spacer,#rightbar.spacer{min-width:0;max-width:calc(var(--menu-width) + var(--margin-ui));flex-basis:calc(100vw - var(--menu-width) - var(--body-width) - var(--margin-ui) * 4)}#header{display:flex;flex-flow:row nowrap;line-height:1.1em;font-family:var(--font-headline);vertical-align:middle;word-break:break-all;max-width:100%;border-bottom:3px double var(--color-border-header);padding:1em var(--margin-ui);margin:0 0 calc(var(--margin-block) * 1.5)}#header>section{display:flex;flex-flow:column nowrap;align-items:stretch;justify-content:center;align-content:center;flex:0 1 auto}#header>section:nth-child(2){margin:0 4px;flex:1 1 auto}#header a{color:var(--color);text-decoration:none}#logo{display:block;height:40px;width:40px;padding-right:9px;fill:var(--color-logo);stroke:var(--color-logo)}#page_title{display:block;font-size:122%;font-weight:700;line-height:1.25em;margin:0 0 .3em;padding:0}#page_title a{color:var(--color);text-decoration:none}#topic-path{font-size:88%;font-weight:700;margin:0;user-select:auto;-moz-user-select:auto;-webkit-user-select:auto;-ms-user-select:auto}#note{clear:both;margin:var(--margin-block) 0 auto;font-size:90%;word-break:break-all}.note_super{vertical-align:super;font-size:80%;text-decoration:none}#note .note_super{vertical-align:middle;font-size:100%;display:inline-block;margin-right:.333em}#attach,#lastmodified,#related{clear:both;flex-grow:1;font-family:var(--font-headline);font-size:80%;margin:2em 0 0}#footer{margin:var(--margin-block) 0 0;padding:1em var(--margin-ui) 1em;vertical-align:middle;display:flex;font-family:var(--font-headline);flex-flow:column nowrap;background:var(--color-bg-footer);border-top:1px double var(--color-border-footer)}#footer a{color:var(--color);text-decoration:none}#footer>section{margin:0}#footer>section>*{margin:.5em 0}h1,h2,h3,h4,h5,h6,hr,table{padding:0;clear:both}h1,h2,h3,h4,h5,h6{position:relative;color:var(--color-headline);margin:var(--margin-block) 0;padding:.2em 0;font-family:var(--font-headline);font-weight:700;vertical-align:middle;background-color:var(--color-bg-headline);border:0 solid transparent;border-radius:1px;box-sizing:border-box;line-height:var(--line-height-ui)}h1:first-child,h2:first-child,h3:first-child,h4:first-child,h5:first-child,h6:first-child{padding-top:0}h1{font-size:249%}h2{font-size:207%}h3{font-size:173%}h4{font-size:144%}h5{font-size:120%}h6{font-size:100%}#menubar h1,#rightbar h1{font-size:161%}#menubar h2,#rightbar h2{font-size:146%}#menubar h3,#rightbar h3{font-size:133%}#menubar h4,#rightbar h4{font-size:121%}#menubar h5,#rightbar h5{font-size:110%}#menubar h6,#rightbar h6{font-size:100%}h1,h2{margin-top:calc(var(--margin-block) * 1.5)}h3{margin-top:calc(var(--margin-block) * 1.25);border-bottom:var(--border-headline,1px solid var(--color-border))}h1:first-child,h2:first-child,h3:first-child,h4:first-child,h5:first-child,h6:first-child,hr+h1,hr+h2,hr+h3,hr+h4,hr+h5,hr+h6{margin-top:0}b,strong{font-weight:700;font-style:inherit}em{font-weight:inherit;font-style:italic}table,tbody,td,th,thead{position:relative;box-sizing:border-box;width:auto;vertical-align:middle;border:1px solid var(--color-border);border-collapse:collapse;border-spacing:0;-webkit-background-clip:padding-box;background-clip:padding-box;-webkit-border-horizontal-spacing:0;-webkit-border-vertical-spacing:0}table{position:relative;line-height:var(--line-height);width:auto;max-width:initial;font-size:var(--font-size-table);padding:0;text-align:left;color:inherit}tbody:empty,thead:empty{display:none}td,th{padding:.5em;margin:0}.style_th,th,thead td{text-align:center;font-weight:700}li,ol,ul{text-indent:0;list-style-position:inside;vertical-align:middle}#body ol,#body ul{margin-top:var(--margin-paragraph);margin-bottom:var(--margin-paragraph-body)}#body li ol,#body li ul{margin-top:0;margin-bottom:0}li{margin-top:.25em;margin-bottom:.25em}ul>li{list-style-type:disc}ol>li{list-style-type:decimal}ol>li>ol>li,ul>li>ol>li{list-style-type:lower-roman}ol>li>ol>li>ol>li,ol>li>ul>li>ol>li,ul>li>ol>li>ol>li,ul>li>ul>li>ol>li{list-style-type:lower-alpha}li>ol,li>ul{margin-left:1.5em}input[type=date],input[type=datetime-local],input[type=datetime],input[type=email],input[type=month],input[type=number],input[type=password],input[type=range],input[type=search],input[type=tel],input[type=text],input[type=time],input[type=url],input[type=week],select,textarea{position:relative;max-width:100%;padding:.25em .5em;border:1px solid var(--color-border);box-sizing:border-box;color:var(--color-input);background-color:var(--color-bg-input);white-space:pre-wrap}button,input[type=button],input[type=file],input[type=reset],input[type=submit]{font-family:var(--font-ui);font-size:var(--font-size-ui);user-select:none;-moz-user-select:none;-webkit-user-select:none;-ms-user-select:none;vertical-align:middle;line-height:1em;min-width:5em;cursor:pointer}button,input[type=button],input[type=reset],input[type=submit]{padding:.5em 1em;border:var(--color-border);box-sizing:border-box;border-radius:var(--border-radius);color:var(--color-bg);background-color:var(--color)}input[type=file]{margin-top:.666em;margin-bottom:.666em}button:disabled,input[type=button]:disabled,input[type=file]:disabled,input[type=reset]:disabled,input[type=submit]:disabled{opacity:.5;cursor:auto}hr{border-width:1px 0 0;border-style:solid;border-color:var(--color-border)}.contents{display:inline-block;width:auto;font-size:88%;line-height:1.25em;border:1px solid var(--color-border);padding:.5em 1em .5em 1em;box-sizing:border-box}.contents,.contents ol,.contents ul{line-height:1.25em}.contents ul>li{list-style-type:decimal}.contents ul>li>ul>li{list-style-type:lower-roman}.contents ul>li>ul>li>ul>li{list-style-type:lower-alpha}.attention,.attention a,.attention a:visited{color:var(--color-attention)}strong.word0,strong.word1,strong.word2,strong.word3,strong.word4,strong.word5,strong.word6,strong.word7,strong.word8,strong.word9{color:var(--color-search);background-color:var(--color-bg-search)}svg{width:auto;height:auto}dt{margin-bottom:0}dd{display:block;margin-top:0;margin-inline-start:2em}blockquote{margin-left:2em;font-size:97.5%}#_Skin_ModalScreen_,._Skin_MenuButton_{display:none}html::-webkit-scrollbar{border:none;box-sizing:border-box;padding:0;margin:0;background:0 0}html::-webkit-scrollbar-track{background:0 0}html::-webkit-scrollbar-thumb{border:none;background:var(--color-bg);border-radius:1.5px;border:1px solid var(--color-border-section);overflow:hidden}html::-webkit-scrollbar-track{background:0 0}.clear{margin:0;clear:both}div.counter{font-size:70%}.diff_added{color:var(--color-added);background-color:var(--color-bg-added)}.diff_removed{color:var(--color-removed);background-color:var(--color-bg-removed)}.edit_form{clear:both}form>div{z-index:1}span.counter{font-size:70%}div.img_margin{margin-right:1em}span.topicpath-slash{margin:0 .0333em;color:var(--color)}span.topicpath-top{display:none}tr.bugtrack_list_header th{background-color:#ffc}tr.bugtrack_state_proposal td{background-color:#ccf}tr.bugtrack_state_accept td{background-color:#fc9}tr.bugrack_state_resolved td{background-color:#cfc}tr.bugtrack_state_pending td{background-color:#fcf}tr.bugtrack_state_cancel td{background-color:#ccc}tr.bugtrack_state_undef td{background-color:#f33}.search-result-page-summary{font-size:70%;color:gray;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.style_calendar td,.style_calendar th{padding:2px 1px;box-sizing:border-box;text-align:center;vertical-align:middle;white-space:nowrap}.style_td_caltop{white-space:pre-wrap}table[summary='calendar frame'] .style_calendar{margin:0}.__plugin_new{font-size:70%;line-height:1em;font-weight:700;text-align:center;vertical-align:middle;white-space:nowrap}#PluginTabs{display:block;margin-bottom:0;padding:0;font-size:var(--font-size);-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}#body ul#PluginTabs{margin:0}.PluginTab{list-style:none;display:inline-block;font-size:92.5%;min-width:6em;padding:.5em;margin:0;box-sizing:border-box;border:0 solid var(--color-border-section);border-width:1px 1px 0;border-radius:var(--border-radius) var(--border-radius) 0 0;text-align:center;vertical_align:middle;cursor:pointer;line-height:var(--line-height-ui)}.PluginTab[data-active='1']{font-weight:700;cursor:auto}#PluginTabContent{margin-top:calc(var(--margin-block) * 2)}#footer div#_p_recaptcha3_terms{font-size:7px}@media print{a{text-decoration:none}#PluginTabs,#_Skin_ModalScreen_,#_p_recaptcha3_terms,#attach,#menubar,#navigator,#related,#rightbar,#toolbar,.anchor_super,.jumpmenu{display:none}}@media (max-width:767px){:root{--margin-ui:16px;--mode-width:1}h1{font-size:161%}h2{font-size:146.5%}h3{font-size:133%}h4{font-size:121%}h5{font-size:110%}h6{font-size:100%}#contents{margin:0;padding-left:var(--margin-ui);padding-right:var(--margin-ui);max-width:100%;flex-direction:column}#_Skin_Article_{padding:0;margin:0 auto;order:1;flex:10000 1 auto;justify-content:flex-start}#menubar,#rightbar{font-size:var(--font-size);line-height:var(--line-height);margin:var(--margin-ui) auto 0;padding:var(--margin-ui);box-sizing:border-box;border:1px solid var(--color-border-section);min-width:100%;max-width:100%;border-radius:var(--border-radius);order:2;flex:1 1 auto}#rightbar{order:3}#_Skin_ModalScreen_{display:none;position:absolute;left:0;top:0;box-sizing:border-box;min-width:100%;min-height:100%;max-height:initial;height:auto;margin:0;padding:0 var(--margin-ui);overflow:auto;z-index:1999999990;pointer-events:auto;visibility:visible;flex-flow:column nowrap;align-items:stretch;justify-content:flex-start;align-content:flex-start;background-color:transparent;background-image:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAQAAAAEAQMAAACTPww9AAAABlBMVEUAAAAAAAClZ7nPAAAAAXRSTlMAQObYZgAAABBJREFUCNdjaGBgYFBgYAAAA+gAoRKK458AAAAASUVORK5CYII=');background-repeat:repeat}#_Skin_ModalScreen_ #menubar{max-height:initial;height:auto;margin:var(--margin-ui) auto;order:1;overflow:hidden}@media screen and (prefers-color-scheme:dark),screen and (light-level:dim),screen and (environment-blending:additive){#_Skin_ModalScreen_{background-image:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACAQMAAABIeJ9nAAAABlBMVEUAAAAAAAClZ7nPAAAAAXRSTlMAQObYZgAAAAxJREFUCNdjaGBwAAABxADBSUUqSQAAAABJRU5ErkJggg==')}}#menubar{pointer-events:auto}._Skin_MenuButton_{position:absolute;top:6px;right:6px;display:block;z-index:1;width:36px;min-width:36px;max-width:36px;height:36px;min-height:36px;max-height:36px;font-size:0;line-height:0;color:var(--color);background-color:var(--color-bg);box-sizing:border-box;border:1px solid var(--color-border-section);border-radius:var(--border-radius);text-align:center;vertical-align:middle;font-weight:700;margin:0;padding:0;fill:var(--color);stroke-width:0;cursor:pointer}#_Skin_MenuOpenButton_{position:relative;top:0;left:0}#body{max-width:calc(100vw - var(--margin-ui) * 2);overflow:hidden;font-size:calc(var(--font-size-body) * .96);display:flex;flex-direction:column;flex:0 100000 100%}td input[type=date],td input[type=datetime-local],td input[type=datetime],td input[type=email],td input[type=file],td input[type=month],td input[type=number],td input[type=password],td input[type=range],td input[type=search],td input[type=tel],td input[type=text],td input[type=time],td input[type=url],td input[type=week],td textarea{width:auto;max-width:75vw}div>textarea{width:auto;max-width:100%}#menubar.spacer,#rightbar.spacer{display:none}}html._ikaskin_simplify_ h3{border-bottom:none}html._ikaskin_wordwrap_ #body h1,html._ikaskin_wordwrap_ #body h2,html._ikaskin_wordwrap_ #body h3,html._ikaskin_wordwrap_ #body h4,html._ikaskin_wordwrap_ #body h5,html._ikaskin_wordwrap_ #body h6,html._ikaskin_wordwrap_ #body li,html._ikaskin_wordwrap_ #body p{word-break:normal;overflow-wrap:normal;word-wrap:normal;line-break:strict}html._ikaskin_wordbreak_ #body h1,html._ikaskin_wordbreak_ #body h2,html._ikaskin_wordbreak_ #body h3,html._ikaskin_wordbreak_ #body h4,html._ikaskin_wordbreak_ #body h5,html._ikaskin_wordbreak_ #body h6,html._ikaskin_wordbreak_ #body li,html._ikaskin_wordbreak_ #body p{word-break:break-all;overflow-wrap:break-word;word-wrap:break-word;line-break:loose}@media screen{html._ikaskin_theme_dark_{--color-bg:var(--dcolor-bg);--color:var(--dcolor);--color-headline:var(--dcolor-headline);--color-bg-headline:var(--dcolor-bg-headline);--color-menu:var(--dcolor-menu);--color-bg-menu:var(--dcolor-bg-menu);--color-link:var(--dcolor-link);--color-link-underline:var(--dcolor-link-underline);--color-link-visited:var(--dcolor-link-visited);--color-logo:var(--dcolor-logo);--color-border:var(--dcolor-border);--color-input:var(--dcolor-input);--color-bg-input:var(--dcolor-bg-input);--color-code:var(--dcolor-code);--color-bg-code:var(--dcolor-bg-code);--color-bg-table:var(--dcolor-bg-table);--color-attention:var(--dcolor-attention);--color-added:var(--dcolor-added);--color-bg-added:var(--dcolor-bg-added);--color-removed:var(--dcolor-removed);--color-bg-removed:var(--dcolor-bg-removed);--color-search:var(--dcolor-search);--color-bg-search:var(--dcolor-bg-search);--color-border-section:var(--dcolor-border-section);--color-border-header:var(--dcolor-border-header);--color-border-footer:var(--dcolor-border-footer);--color-bg-footer:var(--dcolor-bg-footer)}}@media screen and (prefers-color-scheme:dark),screen and (light-level:dim),screen and (environment-blending:additive){html._ikaskin_theme_adaptive_{--color-bg:var(--dcolor-bg);--color:var(--dcolor);--color-headline:var(--dcolor-headline);--color-bg-headline:var(--dcolor-bg-headline);--color-menu:var(--dcolor-menu);--color-bg-menu:var(--dcolor-bg-menu);--color-link:var(--dcolor-link);--color-link-underline:var(--dcolor-link-underline);--color-link-visited:var(--dcolor-link-visited);--color-logo:var(--dcolor-logo);--color-border:var(--dcolor-border);--color-input:var(--dcolor-input);--color-bg-input:var(--dcolor-bg-input);--color-code:var(--dcolor-code);--color-bg-code:var(--dcolor-bg-code);--color-bg-table:var(--dcolor-bg-table);--color-attention:var(--dcolor-attention);--color-added:var(--dcolor-added);--color-bg-added:var(--dcolor-bg-added);--color-removed:var(--dcolor-removed);--color-bg-removed:var(--dcolor-bg-removed);--color-search:var(--dcolor-search);--color-bg-search:var(--dcolor-bg-search);--color-border-section:var(--dcolor-border-section);--color-border-header:var(--dcolor-border-header);--color-border-footer:var(--dcolor-border-footer);--color-bg-footer:var(--dcolor-bg-footer)}}</style>";
if ($cssProperties) echo '<style>' . $cssProperties . '</style>';
if (IKASKIN_CSS) echo '<link rel="stylesheet" type="text/css" href="' . SKIN_DIR . IKASKIN_CSS . "\" />\n";
?>

<?php if (exist_plugin_convert('pwa')) echo do_plugin_convert('pwa'); // PWA plugin ?>

<?php echo $head_tag ?>
</head>
<body>
<?php echo $html_scripting_data ?>

<header id="header">
	<section>
		<a href="<?php echo $link['top'] ?>"></span><?php
		if ($image['logo']) {
			echo (strpos($image['logo'], '<svg') === 0)? $image['logo'] : '<img id="logo" src="' . htmlsc($image['logo']) . '" width="40" height="40" alt="' . $pageTitle . '" />';
		} else {
			echo '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" id="logo" x="0px" y="0px" width="40px" height="40px" viewBox="0 0 80 80" stroke-width="0"><title>' . $pageTitle . '</title><path d="M68.62,35.931c-0.075-2.254-0.412-4.441-0.979-6.535c0.001-0.003,0.003-0.007,0.004-0.01 c-2.101-7.74-7.369-14.175-14.328-17.829c-7.479,2.933-16.388,8.989-24.704,17.306c-6.427,6.427-11.504,13.208-14.833,19.409 c3.704,8.541,11.452,14.918,20.829,16.721c-0.007-0.002-0.014-0.004-0.02-0.004c-10.06,6.035-18.989,7.816-23.065,3.738 c-3.719-3.719-2.563-11.48,2.256-20.455c-0.671-1.549-1.211-3.166-1.603-4.842C1.727,58.193-2.311,71.645,3.149,77.104 c5.886,5.887,21.063,0.732,37.16-11.6c15.674-0.146,28.335-12.896,28.335-28.604c0-0.327-0.009-0.652-0.019-0.976 C68.624,35.926,68.622,35.929,68.62,35.931z M39.926,47.545c-3.169,0-5.738-2.568-5.738-5.738s2.569-5.739,5.738-5.739 c3.17,0,5.739,2.569,5.739,5.739S43.097,47.545,39.926,47.545z M47.777,9.352c-2.462-0.69-5.059-1.061-7.741-1.061 c-15.8,0-28.608,12.809-28.608,28.608c0,2.246,0.259,4.433,0.749,6.53c3.907-5.519,8.707-11.221,14.244-16.759 C33.514,19.578,40.876,13.694,47.777,9.352z M68.477,11.774c3.321,3.32,2.756,9.862-0.832,17.612 c0.569,2.093,0.905,4.282,0.981,6.538c9.851-14.31,13.543-27.207,8.226-32.524c-4.886-4.887-16.173-2.165-29.074,5.953 c1.938,0.543,3.791,1.286,5.539,2.205C59.982,8.943,65.513,8.81,68.477,11.774z"/></svg>';
		}
		?></a>
	</section>

	<section>
		<?php
		$topicPath = '';
		if ($is_page) {
			require_once(PLUGIN_DIR . 'topicpath.inc.php');
			$topicPath = ($isHome)? $title : plugin_topicpath_inline();
			if ($topicPath) {
				preg_match('/href\s?=\s?"([^"]*)"/' . $pregFlag, $page, $matches);
				$page = $matches[1];
				$tmp = end(explode('</span>', $topicPath));
				$topicPath = str_replace($tmp, '<a href="' . $page . '">' . $tmp . '</a>', $topicPath);
			} else {
				$topicPath = $page;
			}
		}
		if (!$topicPath) $topicPath = $page;
		if (!IKASKIN_TITLE) {
			echo '<h1 id="page_title">' . $topicPath . '</h1><div id="topic-path"><a href="' . $link['top'] . '">' . $pageTitle . '</a></div>';
		} else {
			echo '<h1 id="page_title"><a href="' . $link['top'] . '">' . $pageTitle . '</a></h1><div id="topic-path">' . $topicPath . '</div>';
		}
		?>
	</section>
</header>

<main id="contents">
	<article id="_Skin_Article_">
		<div id="body"><?php echo $body ?></div>
		<?php
			if ($notes != '') echo '<div id="note">' . $notes . "</div>\n";
			if (IKASKIN_SHOW_LASTMODIFIED && $lastmodified != '') echo '<div id="lastmodified">Last modified: ' . $lastmodified . "</div>\n";
			if ($related != '') echo '<div id="related">Link: ' . $related . "</div>\n";
			if ($attaches != '') echo '<div id="attach">' . $attaches . "</div>\n";
		?>
	</article>

	<?php echo ($menu)? '<section id="menubar">' . $menu . '</section>' : '<section id="menubar" class="spacer"></section>'; ?>
	<?php echo ($rightbar)? '<section id="rightbar">' . $rightbar . '</section>' : '<section id="rightbar" class="spacer"></section>'; ?>
</main>

<footer id="footer">
	<section>
		<nav id="navigator">
			<?php
if (PKWK_SKIN_SHOW_NAVBAR) {
	function _navigator($key, $value = '', $javascript = ''){
		$lang = & $GLOBALS['_LANG']['skin'];
		$link = & $GLOBALS['_LINK'];
		if (! isset($lang[$key])) { echo 'LANG NOT FOUND'; return false; }
		if (! isset($link[$key])) { echo 'LINK NOT FOUND'; return false; }

		return '<a href="' . $link[$key] . '" ' . $javascript . '>' . (($value === '') ? $lang[$key] : $value) . '</a>';
	}

	$tools = '';
	$havePageinfo = exist_plugin_inline('pageinfo');
	if ($havePageinfo) $tools .= (($tools)? '<span>|</span>' : '') . do_plugin_inline('pageinfo', 'Information', $_v); // Page information plugin
	if ($is_page && $rw) $tools .= (($tools)? '<span>|</span>' : '') . _navigator('edit');
	if ($is_page && $rw && (bool)ini_get('file_uploads')) $tools .= (($tools)? '<span>|</span>' : '') . _navigator('upload');
	if ($is_page && !$havePageinfo) $tools .= (($tools)? '<span>|</span>' : '') . _navigator('diff');
	if ($is_page && $rw && $is_read && $function_freeze && !$havePageinfo) $tools .= (($tools)? '<span>|</span>' : '') . ((!$is_freeze)? _navigator('freeze') : _navigator('unfreeze'));
	if ($is_page && !$havePageinfo) $tools .= (($tools)? '<span>|</span>' : '') . _navigator('rename');
	if ($rw) $tools .= (($tools)? '<span>|</span>' : '') . _navigator('new');
	$tools .= (($tools)? '<span>|</span>' : '') . _navigator('list');
	$tools .= (($tools)? '<span>|</span>' : '') . _navigator('search');
	if ($enable_login && !$havePageinfo) $tools .= (($tools)? '<span>|</span>' : '') . _navigator('login'); else if ($enable_logout && !$havePageinfo) $tools .= (($tools)? '<span>|</span>' : '') . _navigator('logout');
	echo $tools;
}
?>
		</nav>

		<?php
		if ($modifier) {
			$adminPrefix = (is_string(IKASKIN_COPYRIGHT) && IKASKIN_COPYRIGHT)? (IKASKIN_COPYRIGHT . ' ') : ((IKASKIN_COPYRIGHT)? '&copy; ' : 'Site admin: ');
			if ($modifierlink && strpos($modifierlink, 'pukiwiki.example.com') === false) {
				$adminPrefix = $adminPrefix . '<a href="' . $modifierlink . '">' . $modifier . '</a>';
			} else
			if ($modifier == 'anonymous') {
				$adminPrefix = $adminPrefix . $modifier;
			} else $adminPrefix = '';
			if ($adminPrefix) echo '<div id="_Skin_Copyright_">' . $adminPrefix . "</div>\n";
		}
		?>
	</section>

	<?php if (exist_plugin_convert('screensaver')) echo do_plugin_convert('screensaver'); // ScreenSaver plugin ?>
	<?php if (exist_plugin_convert('recaptcha3')) echo do_plugin_convert('recaptcha3'); // reCAPTCHA v3 plugin ?>
</footer>

<?php if (PKWK_ALLOW_JAVASCRIPT && $menu) { ?>
<script>/*<!--*/
'use strict';
var	__Skin__ = function() {
	const self = this;
	this.screen = null;
	this.article = null;
	this.menu = null;
	this.mobile = 0;

	var	body = document.body;
	var	header = document.getElementById('header');
	self.article = document.getElementById('_Skin_Article_');
	self.menu = document.getElementById('menubar');

	if (body && header && self.article && self.menu) {
		header.insertAdjacentHTML('beforeend', '<section><button class="_Skin_MenuButton_" id="_Skin_MenuOpenButton_"><svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="16px" height="16px" viewBox="0 0 16 16"><title>Menu</title><path d="M15.999,3.2H0V0h15.999V3.2z M15.999,6.399H0v3.2h15.999V6.399z M15.999,12.8H0v3.199h15.999V12.8z"/></svg></button></section>');
		document.getElementById('_Skin_MenuOpenButton_').addEventListener('click', function(e) { e.stopPropagation(); e.preventDefault(); self.Open(); }, {passive: false});

		self.menu.insertAdjacentHTML('beforeend', '<button class="_Skin_MenuButton_" id="_Skin_MenuCloseButton_"><svg version="1.1" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="16px" height="16px" viewBox="0 0 16 16"><title>Close</title><path d="M15.995,2.285l-5.713,5.712l5.713,5.712l-2.285,2.286l-5.712-5.713l-5.713,5.713L0,13.71l5.713-5.712L0,2.285L2.285,0 l5.713,5.713L13.71,0L15.995,2.285z"/></svg></button>');
		self.menu.addEventListener('click', function(e) { e.stopPropagation(); }, {passive: false});
		self.menu.addEventListener('touchmove', function(e) { e.stopPropagation(); }, {passive: false});
		self.menu.addEventListener('wheel', function(e) { e.stopPropagation(); }, {passive: false});
		document.getElementById('_Skin_MenuCloseButton_').addEventListener('click', function(e) { e.stopPropagation(); e.preventDefault(); self.Close(); }, {passive: false});

		self.screen = document.createElement('section');
		self.screen.addEventListener('click', function(e) { e.stopPropagation(); e.preventDefault(); self.Close(); }, {passive: false});
		self.screen.id = '_Skin_ModalScreen_';
		body.insertAdjacentElement('beforeend', self.screen);

		window.addEventListener('resize', function() { self.Change(); });

		self.Change();
	}
};
__Skin__.prototype.Change = function() {
	var	mobile_ = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--mode-width'));
	if (this.mobile != mobile_) {
		this.mobile = mobile_;

		this.Close();

		if (this.mobile) {
			if (this.screen) this.screen.insertAdjacentElement('afterbegin', this.menu);
		} else {
			if (this.article) this.article.insertAdjacentElement('afterend', this.menu);
		}
	}
};
__Skin__.prototype.Open = function() { if (this.screen) this.screen.style.display = 'block'; };
__Skin__.prototype.Close = function() { if (this.screen) this.screen.style.display = 'none'; };
window.addEventListener('DOMContentLoaded', function() { new __Skin__();  }, false);
/*-->*/</script>
<?php } ?>

<?php if (exist_plugin_convert('jsonld')) echo do_plugin_convert('jsonld'); // JSON-LD plugin ?>

</body>
</html>
