<?php
/**
 * @package penseur
 * @version 0.1
 */
/*
Plugin Name: Webseur
Plugin URI: http://www.penseur.co.jp
Description: webseurのプラグイン
Author: penseur
Version: 1.0
Author URI: http://www.penseur.co.jp/
*/
require_once dirname(__FILE__)."/engine/App.class.php";

//options
add_action("network_admin_menu", function() {
		add_object_page(__("店舗データ読込", "penseur"), "店舗データ読込", "manage_options", "App::initialization", "App::initialization");
	});

add_action("admin_menu", function() {
		add_object_page(__("店舗情報管理", "penseur"), "店舗情報管理", "manage_options", "App::shop_manage", "App::shop_manage");
});

add_shortcode("plagesubsite", function($atts) {
		$key = $displayer = null;
		if(isset($atts["key"])) {
			$key = $atts["key"];
		}
		if(isset($atts["displayer"])) {
			$displayer = $atts["displayer"];
		}
		if(empty($key)) {
			return trigger_error("不明なキー", E_USER_ERROR);
		}
		return App::display_shop_info($key, $displayer);
});

