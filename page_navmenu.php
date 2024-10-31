<?php
/*
Plugin Name: Page navigation menu
Plugin URI: http://wallgrenconsulting.se
Description: The administrator is able to select a menu(from menu editor) in post/page editor. The navigation menu will be outputed with help from page_navigation_menu() function.
Version: 1.0
Author: Mikael Wallgren
Author URI: http://wallgrenconsulting.se
*/

register_activation_hook(__FILE__,'page_navigation_menu_install');
add_action('do_meta_boxes','page_navigation_menu_box');
add_action('save_post','page_navigation_menu_save_postdata');

function page_navigation_menu($slug = null){
    global $post,$wpdb;
	if(!isset($slug)){
		$r = mysql_fetch_object(mysql_query("SELECT slug FROM ".$wpdb->prefix."page_navigation_menu WHERE contentid = '".$post->ID."'"));
		$slug = $r->slug;
	}
    $menuhtml = '<ul id="page_navigation_menu">';
    $menui = 0;
    $items = wp_get_nav_menu_items($slug);
    foreach ($items as $menuitem) {
	$class = '';
	$submenu = '';
	if ($menuitem->object_id == $post->ID) {
	    $class = "current";
	} else if ($menuitem->object_id == $post->post_parent) {
	    $class = "current";
	}
	if(0==$menui){
	    $class .= ' first';
	}
	if($menuitem->menu_item_parent){
	    $class .= ' submenu';
	}
	if(""!=$class){
	    $class = 'class="'.$class.'"';
	}
	$menuhtml .= '<li '.$class.'><a href="' . $menuitem->url . '">' . $menuitem->title . '</a></li>';
	if(''!=$submenu){
	    $menuhtml .= '<li>'.$submenu.'</li>';
	}
	$menui++;
    }
    $menuhtml .= '</ul>';
    echo $menuhtml;
}

function page_navigation_menu_box(){
    global $wp_meta_boxes;
    add_meta_box('page_navigation_menu_page','Page menu','page_navigation_menu_box_content','page','side','low');
    add_meta_box('page_navigation_menu_page','Page menu','page_navigation_menu_box_content','post','side','low');
}

function page_navigation_menu_box_content($post) {
    global $wpdb;
    wp_nonce_field(plugin_basename(__FILE__),'page_navigation_menu_savemenu');
    $menus = wp_get_nav_menus();
    $r = mysql_fetch_assoc(mysql_query("SELECT name, slug FROM ".$wpdb->prefix."page_navigation_menu WHERE contentid = '".$post->ID."'"));
    echo '<label for="page_navigation_menu_sidemenu">Select menu</label><br /><select id="page_navigation_menu_sidemenu" name="page_navigation_menu_sidemenu">';
    echo '<option value="0">--None--</option>';
    foreach($menus as $menuitem){
	$selected = ($r['slug']==$menuitem->slug)?' selected="selected"':'';
	echo '<option value="'.$menuitem->slug.'"'.$selected.'>'.$menuitem->name.'</option>';
    }
    echo '</select>';
}

function page_navigation_menu_save_postdata($post_id) {
    global $wpdb;
    if(wp_is_post_revision($post_id)){
	return;
    }
    if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE){
	return;
    }
    if(!wp_verify_nonce($_POST['page_navigation_menu_savemenu'],plugin_basename(__FILE__))){
	return;
    }
    if ('page'==$_POST['post_type']){
	if(!current_user_can('edit_page',$post_id)){
	    return;
	}
    }
    else{
	if(!current_user_can('edit_post',$post_id)){
	    return;
	}
    }
    $q = mysql_query("SELECT id FROM ".$wpdb->prefix."page_navigation_menu WHERE contentid = '".$post_id."'");
    $menu = wp_get_nav_menus(array('menu'=>$_POST['page_navigation_menu_sidemenu']));
    $menuname = '';
    foreach($menu as $menuitem){
	if($_POST['page_navigation_menu_sidemenu']==$menuitem->slug){
	    $menuname = $menuitem->name;
	    break;
	}
    }
    if(mysql_num_rows($q)){
	mysql_query("UPDATE ".$wpdb->prefix."page_navigation_menu SET name = '".$menuname."', slug = '".$_POST['page_navigation_menu_sidemenu']."' WHERE contentid = '".$post_id."'");
    }
    else {
	mysql_query("INSERT INTO ".$wpdb->prefix."page_navigation_menu(name, slug, contentid) VALUES('".$menuname."','".$_POST['page_navigation_menu_sidemenu']."','".$post_id."')");
    }
}

function page_navigation_menu_install() {
    global $wpdb;
    $table_name = $wpdb->prefix."page_navigation_menu";
    $sql = "CREATE TABLE ".$table_name." (
    id INT(6) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    contentid INT(9) NOT NULL,
    PRIMARY KEY id (id));";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

?>