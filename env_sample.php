<?php
// ReachOut Framework Configuration

// スクリプト情報
define('SCRIPT_VER', '0.2.1');
define('SCRIPT_MODIFIED', '2026/03/11');
// ディレクトリ設定
define('DS', '/');
define('LAYOUT_DIR', 'layout');
define('CSS_DIR', 'css');
define('CACHE_DIR', 'tmp');
// サイト設定
define('SITE_NAME', 'ReachOut - Framework For HTML');
define('TOP_PAGE', 'top.php');
define('LAYOUT_DEFAULT_FILE', 'default.php');
define('HEADER_FILE', 'header.php');
define('CHARSET', 'UTF-8');
define('HTTP', 'https://');
// CSSタグテンプレート
define('CSS_TAG', '<link rel="stylesheet" href="%s">');
// キャッシュ設定
define('CACHE_FLG', false);
define('CACHE_LIFE_TIME', 7200);
define('CACHE_STOP', false);
define('NO_CACHE_DIR', array('feed'));
// ファイル設定
define('INDEX_FILES', array('index.html', 'index.php', 'index.htm'));
