<?php
/*
	index.php --- Reach Out ... ヘッダとフッタを共通化するスクリプト
	
	version 0.1.5 --- 2012/01/19 by yoshi
*/
/* ----- 設定値（適時変更） ----- */
define("DS", "/");
// サイト名
define("SITE_NAME", "ReachOut - Framework For HTML");
// トップページファイル名
define("TOP_PAGE", "top.php");
// レイアウトディレクトリ名
define("LAYOUT_DIR", "layout");
// レイアウトファイル名（初期値）
define("LAYOUT_DEFAULT_FILE", "default.php");
// ＜header＞ タグに挿入するファイル名
define("HEADER_FILE", "header.php");
// 文字コード
define("CHARSET", "UTF-8");
// CSSディレクトリ名
define("CSS_DIR", "css");
// CSSタグ
define("CSS_TAG", '<link rel="stylesheet" type="text/css" href="%s" />');
// キャッシュ（使うなら true, 使わないならば false ）
define("CACHE", false);
// キャッシュ LifeTime 秒
define("CACHE_LIFE_TIME", 7200);
// キャッシュディレクトリ名
define("CACHE_DIR", "tmp");
// キャッシュ一時停止
define("CACHE_STOP", false);
// キャッシュクラス PEAR Cache_Lite
//require_once("lib/Cache_Lite/Lite.php");

// html 出力クラス
class Html {
	
	/* ---------- 設定値（適時変更）---------- */
	protected $topPage = TOP_PAGE;
	protected $layoutDir = LAYOUT_DIR;
	protected $layoutFile = LAYOUT_DEFAULT_FILE;
	protected $cssDir = CSS_DIR;
	protected $charset = CHARSET;
	protected $index = array("index.html","index.php","index.htm");	// 省略時ファイル
	protected $siteName = SITE_NAME;
	protected $headerFile = HEADER_FILE;
	protected $r2br = false;			// 改行をbrタグに変換（default: false）
	protected $cacheFlg = CACHE;
	protected $cacheLifeTime = CACHE_LIFE_TIME;
	protected $cacheDir = CACHE_DIR;
	protected $cacheStop = CACHE_STOP;
	protected $noCacheDir = array("feed");	// キャッシュしないディレクトリ
	protected $scriptVer = "0.1.5";
	protected $scriptModified = "2012/01/19";
	protected $title = "";
	protected $contents = "";
	protected $dir = "";
	protected $file = "";
	protected $base = "";
	protected $baseName = "";
	protected $running_timers = array();
	protected $homeDir = "";
	protected $homeUrl = "";
	protected $homeRealPath = "";
	protected $css = "";
	protected $pageUrl = "";
	protected $pageUrlFull = "";
	protected $host = "";
	protected $header = "";
	protected $layout = "";
	protected $errorMessage = "";
	protected $errorNumber = "";
	protected $rendering = "";
	protected $cache = false;
	
	function __construct() {
		// レンダリング時間用のタイマー開始
		$this->start_timer();
		// ホスト名を取得
		$this->set_host();
		// ホームURLをセット
		$this->set_homedir(dirname($_SERVER["PHP_SELF"]));
		// キャッシュ
		$this->set_cache();
		// 出力
		$this->output();
	}
	/* ---------- setter & getter ----------- */
	function set_dirname($dirname) {
		$this->dir = $this->_adjust_dir($dirname);
	}
	function set_host() {
		$this->host = "http://" . $_SERVER["HTTP_HOST"];
	}
	function set_homedir($dirname) {
		$this->homeDir = $this->_adjust_dir($dirname);
		if ($this->homeDir) {
			$this->homeUrl = $this->homeDir;
		} else {
			$this->homeUrl = DS;
		}
		$this->homeRealPath = realpath($this->homeDir);
	}
	function set_file($file) {
		$this->file = $file;
		$this->pageUrl = $this->homeDir . DS . $this->file;
		$this->pageUrlFull = $this->host . $this->homeDir . DS;
		if ($this->dir) $this->pageUrlFull .= $this->file;
	}
	function set_base($base) {
		$this->base = $base;
		if ($baseName = substr($base, 0,strpos($base, "."))) $this->baseName = $baseName;
	}
	function set_title($title = "") {
		if (!$title) $title = $this->baseName;
		if ($this->siteName) $this->title = $this->siteName . " - ";
		$this->title .= $title;
	}
	function set_cache() {
		if (!$this->cacheFlg) {
			$this->cache = false;
			return false;
		}
		$options = array(
			'cacheDir' => $this->cacheDir . DS,
			'lifeTime' => $this->cacheLifeTime
		);
		$this->cache = new Cache_Lite($options);
		return true;
	}
	/* ----------- method ----------- */
	// 出力
	function output() {
		// リクエストファイルを受信
		$this->receive_file();
		// キャッシュ出力
		if (($this->cache) and 
			($data = $this->cache->get($this->file)) and
			($this->check_cache($this->file))) {
			if ($this->cacheStop) {
				// キャッシュ削除
				$this->cache->remove($this->file);
			} else {
				echo $data;
				return true;
			}
		}
		// ファイルの読み込み
		$this->read_file();
		// レイアウトファイルを選択
		if ($this->select_layout()) {
			// レンダリング時間用のタイマー終了
			$this->stop_timer();
			// プロパティを展開
			extract(get_object_vars($this));
			// レイアウトを表示
			ob_start();
			include($this->layout);
			$buffer = ob_get_contents();
			@ob_end_flush();
			// キャッシュに保存
			if (($this->cache) and (!$this->errorNumber)) $this->cache->save($buffer);
			return true;
		} else {
			// エラー表示
			$this->outputError();
			return false;
		}
	}
	// リクエストファイルを受信
	function receive_file() {
		if (!($file = $this->_h("file"))) $file = $this->topPage;
		$this->set_file($file);
	}
	// ファイルの読み込み
	function read_file() {
		// コンテンツ読み込み
		$this->read_contents();
		// ヘッダ読み込み
		$this->read_header();
	}
	// コンテンツの読み込み
	function read_contents() {
		if ($this->check_file($this->file)) {
			// プロパティを展開
			extract(get_object_vars($this));
			ob_start();
			include($this->file);
			$this->contents = ob_get_contents();
			@ob_end_clean();
			// 設定値読み込み
			$this->get_config($this->contents);
			// 改行をbrに変換
			if ($this->r2br) $this->contents = $this->_r2br($this->contents);
			// カレントディレクトリのcssのタグをセット
			$this->set_current_css();
		} else {
			$func = "error" . $this->errorNumber;
			$this->$func();
		}
	}
	// レイアウトのディレクトリを設定
	function select_layout() {
		if ($this->_read_layout(realpath($this->dir))) return true;
		$this->errorNumber = "404";
		$this->errorMessage = "not found layout : " . $this->layoutFile;
		return false;
	}
	// レイアウトを再帰的に検索
	function _read_layout($dir) {
		$layoutFile = $dir;
		if ($this->layoutDir) $layoutFile .= DS . $this->layoutDir;
		$layoutFile .= DS . $this->layoutFile;
		if (is_file($layoutFile)) {
			$this->layout = $layoutFile;
			return true;
		} else {
			if (($dir == DS) or ($dir == $this->homeRealPath)) return false;
			return $this->_read_layout(dirname($dir));
		}
	}
	// ファイルの存在チェック
	function check_file($file) {
		if (file_exists($file)) {
			if (is_file($file)) {
				$dirname = dirname($file);
			} else {
				$dirname = $file;
				if (!$file = $this->check_index($dirname)) {
					$this->errorNumber = "403";
					return false;
				}
			}
			$this->set_dirname($dirname);
			$this->set_file($file);
			$this->set_base(basename($file));
			return true;
		} else {
			$this->errorNumber = "404";
			return false;
		}
	}
	// 省略時ファイルの存在チェック
	function check_index($dirname) {
		if (!(substr($dirname, -1) == DS)) $dirname .= DS;
		foreach ($this->index as $value) {
			$file = $dirname . $value;
			if (file_exists($file)) return $file;
		}
		return false;
	}
	// GETデータをチェック
	function _h($name) {
		if (isset($_GET[$name]) and ($_GET[$name])) {
			return htmlspecialchars($_GET[$name], ENT_QUOTES, $this->charset);
		} else {
			return false;
		}
	}
	// コンテンツから設定値取得
	function get_config($contents) {
		// タイトル
		if (preg_match('/config_title:"([^"]+)"/', $contents, $matches)) {
			$this->set_title($matches[1]);
		} else {
			$this->set_title();
		}
		// レイアウト
		if (preg_match('/config_layout:"([^"]+)"/', $contents, $matches)) {
			$this->layoutFile = $matches[1];
		}
		// 改行変換フラグ
		if (preg_match('/config_br:"([^"]+)"/', $contents, $matches)) {
			if (strtolower($matches[1]) == "yes") $this->r2br = true;
		}
	}
	// カレントディレクトリのcss
	function set_current_css() {
		$out = "";
		$path = realpath($this->dir);
		if (is_dir($path . DS . $this->cssDir)) {
			$d = dir($path . DS . $this->cssDir);
			while (false !== ($entry = $d->read())) {
				if (preg_match('/\.css/', $entry)) {
					$css = $this->homeDir;
					if ($this->dir) $css .= DS . $this->dir;
					if ($this->cssDir) $css .= DS . $this->cssDir;
					$css .= DS . $entry;
					$out .= sprintf(CSS_TAG, $css) . "\n";
				}
			}
			$d->close();
		}
		$this->css = $out . $this->css;
		return $out;
	}
	// <header> タグに挿入するファイルを読み込む
	function read_header() {
		$file = realpath($this->dir);
		if ($this->layoutDir) $file .= DS . $this->layoutDir;
		$file .= DS . $this->headerFile;
		if (is_file($file)) {
			ob_start();
			include($file);
			$this->header = ob_get_contents();
			@ob_end_clean();
		}
	}
	// キャッシュするディレクトリかチェック
	function check_cache($file) {
		if (!$this->cache) return false;
		$dir = explode(DS, $file);
		if (array_search($dir[0], $this->noCacheDir) === false) {
			return true;
		} else {
			return false;
		}
	}
	// 改行をbrタグに変換
	function _r2br($contents) {
		$work = preg_split("/(\n|\r)/", $contents);
		$res = "";
		foreach ($work as $val) {
			if (preg_match("/>$/", $val)) {
				$res .= $val;
			} else {
				$res .= $val . "<br />";
			}
		}
		return $res;
	}
	// ディレクトリ加工
	function _adjust_dir($dirname) {
		if (($dirname == DS) or
			($dirname == ".") or
			($dirname == "." . DS)) {
			return "";
		}
		if (substr($dirname, -1) == DS) {
			return substr($dirname, 0, -1);
		}
		return $dirname;
	}
	// エラー出力
	function outputError() {
		$func = "error" . $this->errorNumber;
		$this->$func();
		echo '<html><body>';
		echo $this->contents;
		echo '</body></html>';
	}
	// 403 Forbidden
	function error403() {
		header("HTTP/1.0 403 Forbidden");
		$this->contents = $this->error_page("403 Forbidden");
	}
	// 404 Not Found
	function error404() {
		header("HTTP/1.1 404 Not Found");
		$this->contents = $this->error_page("404 Not Found");
	}
	// エラーコンテンツ
	function error_page($message) {
		return '<div class="error"><h3>' . $message . '</h3>' . $this->errorMessage . '</div>';
	}
	// タイマー開始
	function start_timer($k = false) {
		if (!$k) $k = get_class($this);
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$this->running_timers[$k] = $time;
	}
	// タイマー終了
	function stop_timer($k = false) {
		if (!$k) $k = get_class($this);
		$time = microtime();
		$time = explode(" ", $time);
		$time = $time[1] + $time[0];
		$endtime = $time;
		$this->rendering = $endtime - $this->running_timers[$k];
		return $this->rendering;
	}
}
// インスタンス作成
$html = new Html();
