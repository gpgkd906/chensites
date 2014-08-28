<?php
if(!function_exists("wp_nav_menu_update_menu_items")) {
	require_once( ABSPATH . 'wp-admin/includes/nav-menu.php' );
}

require_once "webseur/biyou.class.php";
require_once "webseur/riyou.class.php";
require_once "webseur/seimen.class.php";

class App {
	
	private static $instance;
	private static $paths = array();
	private static $csv;
	private static $form;
	private static $view_path;
	private static $info = null;
	public static $site = null;

	public static function initialization() {
		$app = self::getSingleton();
		$app->init();
	}

	public function init() {
		$form = self::$form->create("importer");
		$form->add_file("csv")->must_be(checker::Exists);
		$form->submit(function($data) {
				set_time_limit(900);
				ini_set("auto_detect_line_endings", "1");
				self::$csv->encode("shift_jis", "utf8");
				self::$csv->open($_FILES["csv"]["tmp_name"]);
				$header = self::$csv->get();
				while($row = self::$csv->get()) {
					$row = array_combine($header, $row);
					$this->optimize_site($row);
				}
			});
		$this->flush(get_defined_vars(), "init");
	}
	
	public static function shop_manage() {
		$app = self::getSingleton();
		$app->manage();
	}

	public function manage() {
		$info = get_option("plagesubsite");
		$form = self::$form->create("manage");
		foreach($info as $key => $value) {
			if($key === "メニュー") {
				$val = array();
				foreach($value["items"] as $item_name =>  $item) {
					$val[] = $item_name . " " . $item;
				}
				$val = join(PHP_EOL, $val);
				$value = $val . PHP_EOL . $value["info"];
			}
			if(mb_strlen($value) < 20) {
				$text = $form->add_text($key, $value);
				$text->size(100);
			} else {
				$textarea = $form->add_textarea($key, $value);
				$textarea->cols(100);
				$textarea->rows(5);
			}
		}
		$form->submit(function($data) {
				$type = $this->get_shop_type($data);
				$data["メニュー"] = $this->optimize_menu($data["メニュー"], $type);
				update_option("plagesubsite", $data);
			});
		$this->flush(get_defined_vars(), "manage");
	}

	public static function shop_display($key, $displayer = null) {
		$app = self::getSingleton();
		$app->display_shop_info($key, $displayer);
	}

	public static function display_shop_info($key, $displayer = null) {
		if(self::$info === null) {
			self::$info = get_option("plagesubsite");
		}
		$target = self::$info[$key];
		if($displayer === null) {
			return nl2br($target);
		} else {
			if(is_callable($displayer)) {
				return call_user_func($displayer, $target);
			}
		}
	}

	private function add_site($code, $title) {
		$path = join("/", array_merge(self::$paths, array($code, "")));
		$action = "create";
		$blog_id = wpmu_create_blog($_SERVER["HTTP_HOST"], $path, $title, 1 , array( 'public' => 1 ), 1);
		if(is_wp_error($blog_id)) {
			$blog_id = get_blog_id_from_url($_SERVER["HTTP_HOST"], $path);
			$action = "update";
		}
		return array($blog_id, $action);
	}

	private function optimize_site($site) {
		if($type = $this->get_shop_type($site)) {
			$site["メニュー"] = $this->optimize_menu($site["メニュー"], $type);
			list($site_id, $action) = $this->add_site($type . "/" . $site["アルファベット"], $site["店名"]);
			unset($site["アルファベット"]);
			switch_to_blog($site_id);
			update_option("plagesubsite", $site);
			$this->setup_theme($site);
			if($action == "create") {
				call_user_func("{$type}::content", $site);
			}
			call_user_func("{$type}::options", $site);	
			restore_current_blog();
		}
	}
	
	static public function open_nav($menu_id, $item_id, $args) {
		wp_update_post(array(
				"ID" => $item_id,
				"post_status" => "publish"
		));
	}

	private function get_shop_type($site) {
		$targets = array("美容" => "biyou", "理容" => "riyou", "製麺" => "seimen");
		if(isset($site["種別"]) && isset($targets[$site["種別"]])) {
			$target = $targets[$site["種別"]];
		} else {
			$target = false;
		}
		return $target;
	}

	private function setup_theme($site) {
		$target = $this->get_shop_type($site);
		$theme = wp_get_theme($target);
		switch_theme($theme->get_stylesheet());
	}
	
	private function optimize_menu($raw_menu, $type) {
		if($type == "seimen") {
			return seimen::optimize_menu($raw_menu);
		}
		$menu = array("items" => array(), "info" => array());
		$mode = "items";
		foreach(explode(PHP_EOL, $raw_menu) as $line) {
			$line = trim($line);
			if(empty($line)) {
				$mode = "info";
			}
			if($mode === "items") {
				$item = array();
				foreach(preg_split("/\s+/", $line) as $cell) {
					if(strpos($cell, "円")) {
						$item_name = join(" ", $item);
						$menu["items"][$item_name] = $cell;
					} else {
						$item[] = $cell;
					}
				}
			} else {
				$menu["info"][] = $line;
			}
		}
		$menu["info"] = join(PHP_EOL, $menu["info"]);
		return $menu;
	}
	
	public static function getSingleton() {
		if(empty(self::$instance)) {
			self::$instance = new App;
		}
		return self::$instance;
	}

	private function __construct() {
		$curdir = dirname(__FILE__) . "/";
		require $curdir . "base.php";
		require $curdir . "model_driver/mysql.php";
		require $curdir . "model.php";
		require $curdir . "csv/csv.class.php";
		require $curdir . "form2/form2.class.php";
		self::$csv = new csv;
		self::$form = new form2;
		self::$site = get_option("siteurl");
		$tmp = split("/", $_SERVER["REQUEST_URI"]);
		foreach($tmp as $cell) {
			if($cell === "wp-admin") {
				break;
			}
			self::$paths[] = $cell;
		}
		self::$view_path = $curdir . "view/";
	}

	private function flush($vars, $callee) {
		extract($vars);
		$view = $callee . ".html";
		require self::$view_path . $view;
	}	

	static public function nl2p($raw) {
		$lines = array();
		foreach(explode(PHP_EOL, $raw) as $line) {
			$lines[] = "<p>" . $line . "</p>";
		}
		return join("", $lines);
	}
	
	static public function trimint($number) {
		return join("", preg_split("/\D+/", $number));
	}

	static public function googlemap($address) {
		return '<iframe width="425" height="350" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.co.jp/maps?sspn=0.0135527,0.0219729&amp;q=' . $address . '&amp;ie=UTF8&amp;hq=&amp;hnear=&amp;spn=0.006295,0.006295&amp;t=m&amp;iwloc=A&amp;output=embed"></iframe><br /><small><a href="https://maps.google.co.jp/maps?sspn=0.0135527,0.0219729&amp;q=' . $address . '&amp;ie=UTF8&amp;hq=&amp;hnear=&amp;spn=0.006295,0.006295&amp;t=m&amp;iwloc=A&amp;source=embed" style="color:#0000FF;text-align:left">大きな地図で見る</a></small>';
	}

	static public function menu_format($value) {
		$menu = array('<div class="priceArea"><ul>');
		foreach($value["items"] as $item => $price) {
			$menu[] = '<li><h3>' . $item . '</h3><p class="price">' . $price . '</p></li>';
		}
		$menu[] = "</ul></div>";
		//、の後ろに空白あるいは改行がある場合、空白と改行を省く
		$value["info"] = preg_replace("/、[\s　]+/", "、", $value["info"]);
		$menu[] = '<div class="caption">' . nl2br($value["info"]) . '</div>';
		return join("", $menu);
	}
}