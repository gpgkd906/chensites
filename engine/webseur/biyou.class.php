<?php

class biyou {

	static private $pages = null;

	static public function content($site) {
		//固定ページ変更
		wp_update_post(array(
				"ID" => 1,
				"post_title" => "コンセプト",
				"post_content" => '<h3>[plagesubsite key=キャッチコピー]</h3><img src="' . App::$site . '/wp-content/plugins/penseur/engine/resource/biyou/upload/img01.jpg" /><div class="concept">[plagesubsite key=ボディコピー displayer=App::nl2p]</div>'));
		self::$pages = $pages = array(
			"店舗紹介" => wp_insert_post(array(
					"ID" => 2,
					"post_name" => "shop", "post_title" => "店舗紹介", 
					"post_status" => "publish", "post_type" => "page", "post_author" => 1,
					"post_content" => '<div class="pageCon shop"><table class="shopInfo"><tbody>
        	<tr><th>店名</th><td>[plagesubsite key=店名]</td></tr>
        	<tr><th>住所</th><td>[plagesubsite key=郵便番号]<br>[plagesubsite key=住所]</td></tr>
        	<tr><th>地図</th><td><div id="gMap">[plagesubsite key=住所 displayer=App::googlemap]</div></td></tr>
            <tr><th>営業時間</th><td>[plagesubsite key=営業時間]</td></tr>
        	<tr><th>定休日</th><td>[plagesubsite key=定休日]</td></tr>
        	<tr><th>電話番号</th><td><a href="tel:[plagesubsite key=電話 displayer=App::trimint]" class="tel">[plagesubsite key=電話]</a></td></tr>
        	<tr><th>平均予算</th><td>[plagesubsite key=平均予算]</td></tr>
        	<tr><th>予約</th><td>[plagesubsite key=予約]</td></tr>
        	<tr><th>クレジットカード利用</th><td>VISA,MasterCard,その他</td></tr>
        	<tr><th>公式サイト</th><td><a href="[plagesubsite key=公式サイト]" target="_blank" class="otherLink">[plagesubsite key=公式サイト]</a></td></tr>
        	<tr><th>備考</th><td>ご不明な点はお気軽にお電話にてご連絡ください。</td></tr>
        </tbody></table></div><h2 class="title"><span>ギャラリー</span></h2>')),
			"メニュー" => wp_insert_post(array(
					"post_name" => "menu", "post_title" => "メニュー", 
					"post_status" => "publish", "post_type" => "page", "post_author" => 1,
					"post_content" => '[plagesubsite key=メニュー displayer=App::menu_format]')),
			"スタッフ" => wp_insert_post(array(
					"post_name" => "staff", "post_title" => "スタッフ", 
					"post_status" => "publish", "post_type" => "page", "post_author" => 1,
					"post_content" => ""					
			)),
			"求人" => wp_insert_post(array(
					"post_name" => "recruit", "post_title" => "求人", 
					"post_status" => "publish", "post_type" => "page", "post_author" => 1,
					"post_content" => ""
			))
		);
		$pages_descripts = array(
			"店舗紹介" => "Shop",
			"メニュー" => "Menu",
			"スタッフ" => "Staff",
			"求人" => "Recruit"
		);
		//メニュー調整
		$_nav_menu_selected_id = wp_update_nav_menu_object( 0, array('menu-name' => "Global menu") );
		$post = array(
			'menu-item-db-id' => array(0), 'menu-item-object-id' => array(-5), 'menu-item-object' => array(""),
			'menu-item-parent-id' => array(0), 'menu-item-position' => array(0), 
			'menu-item-type' => array("custom"), 'menu-item-title' => array("ホーム"),
			'menu-item-url' => array(get_option("siteurl")), 'menu-item-description' => array("Home"),
			'menu-item-attr-title' => array(""), 'menu-item-target' => array(""),
			'menu-item-classes' => array(""), 'menu-item-xfn' => array(""), 
		);
		$step = 1;
		foreach($pages as $name => $page_id) {
			$post['menu-item-db-id'][$step] = 0;			
			$post['menu-item-parent-id'][$step] = 0;
			$post['menu-item-position'][$step] = $step;
			$post['menu-item-title'][$step] = $name;
			if($name === "求人") {
				$post['menu-item-object-id'][$step] = -5;
				$post['menu-item-object'][$step] = "";
				$post['menu-item-type'][$step] = "custom";
				$post['menu-item-url'][$step] = "http://cp-plage.com/lp1/";
				$post['menu-item-target'][$step] = "_blank";
			} else {
				$post['menu-item-object-id'][$step] = $page_id;
				$post['menu-item-object'][$step] = "page";
				$post['menu-item-type'][$step] = "post_type";
				$post['menu-item-url'][$step] = "";
				$post['menu-item-target'][$step] = "";
			}
			$post['menu-item-description'][$step] = $pages_descripts[$name];
			$post['menu-item-attr-title'][$step] = "";
			$post['menu-item-classes'][$step] = "";
			$post['menu-item-xfn'][$step] = "";
			$step ++;
		}
		$_POST = array_merge($_POST, $post);
		add_action("wp_update_nav_menu_item", "App::open_nav", 10, 3);
		wp_nav_menu_update_menu_items($_nav_menu_selected_id, "Global menu");
		remove_action("wp_update_nav_menu_item", "App::open_nav");
		set_theme_mod( 'nav_menu_locations', array(
				"primary" => $_nav_menu_selected_id,
				"footer" => $_nav_menu_selected_id
		));
	}

	static public function options($site) {
		update_option("blogname", $site["店名"]);
		update_option("blogdescription", $site["店名"]);
		update_option("sticky_posts", @unserialize("a:1:{i:0;i:1;}"));
		update_option("widget_nav_menu", @unserialize('a:2:{i:1;a:0:{}s:12:"_multiwidget";i:1;}'));
		update_option("widget_categories", @unserialize('a:2:{s:12:"_multiwidget";i:1;i:1;a:0:{}}'));
		update_option("sidebars_widgets", @unserialize('a:3:{s:19:"wp_inactive_widgets";a:0:{}s:9:"sidebar-1";a:0:{}s:13:"array_version";i:3;}'));
		$pages = self::$pages;
		$menu_post = get_post($pages["メニュー"]);
		$config = array (
			'logo' => App::$site . '/wp-content/plugins/penseur/engine/resource/biyou/upload/plagePcLogo.gif',
			'contactAddress' => $site["郵便番号"] . $site["住所"],
			'contactTel' => $site["電話"],
			'contactTime' => $site["営業時間"],
			'productHeading' => '',
			"product1Img" => App::$site . "/wp-content/plugins/penseur/engine/resource/biyou/banners/banner_m1.png",
			"product1Name" =>"",
			"product1Link" =>"",
			"product2Img" => App::$site . "/wp-content/plugins/penseur/engine/resource/biyou/banners/banner_m2.png",
			"product2Name" =>"",
			"product2Link" =>"",
			"product3Img" => App::$site . "/wp-content/plugins/penseur/engine/resource/biyou/banners/banner_m3.png",
			"product3Name" =>"",
			"product3Link" =>"",
			"banner1Img" => App::$site . "/wp-content/plugins/penseur/engine/resource/biyou/upload/btn_menu.jpg",
			"banner1Name" =>"",
			"banner1Link" => $menu_post->guid,
			"banner2Img" => App::$site . "/wp-content/plugins/penseur/engine/resource/biyou/upload/btn_recruit.jpg",
			"banner2Name" =>"",
			"banner2Link" =>"http://cp-plage.com/lp1/",
			"banner3Img" =>"",
			"banner3Name" =>"",
			"banner3Link" =>"",
		);
		update_option("cTpl_rwd005_orange_theme_options", $config);
		//top_image
		$top_image = App::$site . "/wp-content/plugins/penseur/engine/resource/biyou/upload/img_top3.jpg";
		set_theme_mod("header_image", $top_image);
	}
	
}