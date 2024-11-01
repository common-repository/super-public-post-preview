<?php
/*
 * Plugin Name: Super Public Post Preview Plugin
 * Version: 1.0.8
 * Description: Create and share link to anonymous users for preview any post before it is published.
 * Author: INVELITY
 * Author URI: https://invelity.com/
 * Plugin URI: https://invelity.com/
 *
 */

if (! defined( 'ABSPATH' ) ){ die('Plugin SUPER Public Post Preview can not run !!!'); }

define('SUPER_TIME_FUTURE',apply_filters('sppp_filter_time_future','+ 1 month'));

	if(file_exists(plugin_dir_path( __FILE__ ).'/premium.php')){ include_once(plugin_dir_path( __FILE__ ).'/premium.php'); }

	function super_public_post_preview_load_plugin_textdomain($domain)
	{
	    $locale = apply_filters('plugin_locale', get_locale(), $domain);
	
	    load_textdomain($domain, WP_LANG_DIR.'/'. 'super_public_post_preview_textdomain-'.$locale.'.mo');
	    load_plugin_textdomain('super_public_post_preview_textdomain', FALSE, dirname(plugin_basename(__FILE__)).'/languages');
	}

	add_action('init', 'super_public_post_preview_load_plugin_textdomain', 10, 2);



	if ( ! is_admin()){ add_filter( 'pre_get_posts', 'super_show_public_preview' ); }
	
	function super_show_public_preview( $query ) {
		if ($query->is_main_query() and $query->is_single()) { //start only if exists only single post
				if(!isset($_REQUEST['ipreview'])){ return $query; }
				add_filter( 'posts_results', 'super_set_post_to_publish', 10, 2 );
		}
		return $query;
	}


function super_set_post_to_publish( $posts ) {
	if ( empty( $posts ) ) { return; } // return, if not exist posts
		if(get_current_user_id()==null){ return $posts; }
	$armeta = get_post_meta($posts[0]->ID,'ipreview',true); 
	if(!$armeta){ $armeta=array(0=>'false',1=>date('d.m.Y'),2=>date('d.m.Y',strtotime(SUPER_TIME_FUTURE)), 3=>''); }
	if(isset($_REQUEST['ipreview'])){$ipreview=sanitize_text_field($_REQUEST['ipreview']); } else { $ipreview=''; }
	if(!isset($ipreview) || trim($ipreview) != md5('super') || $posts[0]->post_status == 'publish' || $armeta[0] <> 'true'){ return $posts; }
	$now = new DateTime();
    $startdate = new DateTime($armeta[1]);
    $enddate = new DateTime($armeta[2]);
if($startdate <= $now && $now <= $enddate) { }else{ return $posts; }

	remove_filter( 'posts_results', 'set_post_to_publish', 10 ); //remove previous filter
    add_action('wp_head','super_pp_noindex_nofollow');

	$postType = $posts[0]->post_type;
	$postTypes = get_post_types();
    if (in_array($postType,$postTypes)) { //in array

			$posts[0]->post_status = 'publish'; //set post to publish
			$posts[0]->post_password=$armeta[3];
    } else { } //in array
	return $posts;
   }
   
   	add_action('admin_init','super_set_post_to_publish_run');

	function super_set_post_to_publish_run(){
	$screens = get_post_types();
	if(get_option('ipreviewscreens')){ $screens = get_option('ipreviewscreens'); }
	if(isset($_REQUEST['action'])){ $action = sanitize_text_field($_REQUEST['action']); } else{ $action=''; }

	if (isset($action) and $action== 'edit' ) { 
	add_meta_box("super_set_post_to_publish", __("Preview"), "super_set_post_to_publish_div",$screens,'side','default'); 
	}
	
	foreach($screens as $s){ add_filter( 'postbox_classes_'.$s.'_super_set_post_to_publish', 'add_super_metabox_post_to_publish_meta_box_classes' ); }
	}

	function add_super_metabox_post_to_publish_meta_box_classes($classes=array()){
	global $post; 
	$armeta = get_post_meta($post->ID, 'ipreview',true);
	if(!$armeta){ $armeta=array(0=>'false',1=>date('d.m.Y'),2=>date('d.m.Y',strtotime(SUPER_TIME_FUTURE)), 3=>''); }
	if( $armeta[0]=='true' ){  $classes[] = 'trueactive'; } else{ $classes[] = 'falseactive'; }
	return $classes;
	}

	function super_set_post_to_publish_div(){
	global $post;
			if($post->post_status == 'publish'){ ?>
			<style>#super_set_post_to_publish{	display:none; }</style>
			<script> jQuery(function(){ jQuery('#super_set_post_to_publish').remove(); });</script>
			<?php return; }

	$armeta = get_post_meta($post->ID, 'ipreview',true);
	if(!$armeta){ $armeta=array(0=>'false',1=>date('d.m.Y'),2=>date('d.m.Y',strtotime(SUPER_TIME_FUTURE)), 3=>''); }
	$link = get_permalink($post->ID,true);
	$clink = explode('?',$link); $slink = '';
	//foreach($clink as $k=>$c){ if($k==0){ $slink .= $c . '?'; }else{ $slink .= $c . '&'; } }
	$slink = $clink[0] . '?';
	?>
	<style> #super_set_post_to_publish h2, #super_set_post_to_publish span.toggle-indicator{ background-color:#990000; color:#FFFFFF !important;}  #super_set_post_to_publish.trueactive h2, #super_set_post_to_publish.trueactive span.toggle-indicator{ background-color: #009900; } #super_set_post_to_publish.falseactive h2, #super_set_post_to_publish.falseactive span.toggle-indicator{ background-color: #990000; }</style>
	<input <?php if($armeta[0]=='true'){ ?>checked="checked"<?php } ?> id="super_preview_checkbox" name="super_preview_checkbox" type="checkbox" /> <?php _e('Allow'); ?>&nbsp;<span style="text-transform: lowercase;"><?php _e('Preview'); ?></span>&nbsp;<span id="super_preview_ajax_content"></span><div style="position:relative; top:-5px;" class="spinner"></div>
	<div style="padding-top:5px;"><input onclick="this.select();" style="width:100%;" name="super_pp_link_text" type="text" value="<?php print site_url().'?p='.$post->ID.'&post_type='.$post->post_type.'&ipreview='.md5('super'); ?><?php //print $slink . 'ipreview='.md5('super'); print '&p='; print $post->ID; ?>&preview=true" /></div>
	<?php if(function_exists('super_preview_post_enq_datepicker')){ super_preview_post_enq_datepicker(); } ?>
	<script>
	jQuery(document).ready(function(){
	jQuery('#super_preview_checkbox').on('change',function(){    
	if(jQuery(this).is(':checked')) { var ichecked = 'true'; jQuery('#super_set_post_to_publish').removeClass('falseactive'); jQuery('#super_set_post_to_publish').addClass('trueactive'); } else { var ichecked = 'false'; jQuery('#super_set_post_to_publish').removeClass('trueactive'); }

	<?php if(!function_exists('super_preview_post_enq_datepicker')){ $ajax_nonce = wp_create_nonce( "superpp" ); ?>
	jQuery(this).attr('disabled',true);
		jQuery('#super_set_post_to_publish .spinner').css('visibility','visible');
	jQuery.ajax({
			url: '<?php print apply_filters('super_preview_post_filter_ajaxurl',admin_url( 'admin-ajax.php' )); ?>',
			type: 'post',
			data: {
				action: 'super_enable_preview', checked: ichecked, id: '<?php print $post->ID; ?>', security: '<?php echo $ajax_nonce; ?>'
			},
			success: function( html ) {
				jQuery('#super_preview_ajax_content').html( html );
				jQuery('#super_set_post_to_publish .spinner').css('visibility','hidden');
				jQuery('#super_preview_checkbox').attr('disabled',false);
			}
		});
 	<?php } ?>
	});
	
	});
	</script>
	<?php
	}
	
	add_action('wp_ajax_super_enable_preview','super_enable_preview_func'); //run ajax only for logged in users
	
	function super_enable_preview_func(){ 
	check_ajax_referer( 'superpp', 'security' );
	$armeta = array();
	if(isset($_REQUEST['checked'])){ $armeta[0]=sanitize_text_field($_REQUEST['checked']); } else{ $armeta[0]='false'; }
	if(isset($_REQUEST['from'])){ $from = sanitize_text_field($_REQUEST['from']); $armeta[1] = $from; } else{ $armeta[1]=date('d.m.Y'); }
	if(isset($_REQUEST['to'])){ $to = sanitize_text_field($_REQUEST['to']); $armeta[2] = $to; } else{ $armeta[2]=date('d.m.Y',strotime(SUPER_TIME_FUTURE)); }
	if(isset($_REQUEST['super_pp_password'])){ 
				$pass = sanitize_text_field($_REQUEST['super_pp_password']); 
				if(trim($pass)<>'' and $armeta[0]=='true'){ $armeta[3]=trim($pass); } else{ $armeta[3]=''; }
											} else { $armeta[3]=''; }

if(isset($_REQUEST['id']))	{ //id 
	$id = intval($_REQUEST['id']);
if(!$id){ } else { update_post_meta( $id, 'ipreview', $armeta); }
							} //id
	die();
	}
	
	function super_pp_noindex_nofollow(){
	echo apply_filters('super_pp_filter_noindex_nofollow','<meta name="robots" content="noindex,nofollow" />');
	}


add_action('admin_enqueue_scripts',function(){
$plugin_url = plugin_dir_url( __FILE__ );
wp_enqueue_style( 'jquery-ui-css', $plugin_url . '/assets/css/jquery-ui.min.css', array(), false, 'all' );
wp_enqueue_style( 'jquery-ui-structure-css', $plugin_url . '/assets/css/jquery-ui.structure.min.css', array(), false, 'all' );
wp_enqueue_style( 'jquery-ui-theme-css', $plugin_url . '/assets/css/jquery-ui.theme.min.css', array(), false, 'all' );
});