<?php

add_action( 'admin_menu', 'super_post_preview_plugin_menu' );

function super_post_preview_plugin_menu() {
	add_options_page( 
		'Super Preview Post Options',
		'Super Preview Post',
		'manage_options',
		'super_preview_post.php',
		'super_preview_post_page'
	);
}

function super_preview_post_page(){ 
	if(isset($_POST['action'])){ $action = sanitize_text_field($_POST['action']); } else{ $action=''; }
	if(isset($_POST['posttypes'])){ $posttypes = $_POST['posttypes']; } else{ $posttypes=array(); }
	if(is_array($posttypes)){ if(count($posttypes)>0){ foreach($posttypes as $k=>$p){ $posttypes[$k]=sanitize_text_field($p); } } else{ $posttypes=array(); } } else{ $posttypes[0]=sanitize_text_field($posttypes); } 
if(isset($action) and $action=='ipreviewscreensaction'){ if(is_array($posttypes)){ $par = $posttypes; } else{ $par = array(); } update_option('ipreviewscreens',$par); }
$screens = get_post_types();
$checkscreens = get_option('ipreviewscreens',$screens);
?>
<h1 class="wp-heading-inline">SUPER Preview Post</h1><hr />
<div style="padding:15px;">
<h3><?php _e('Select post types','super_public_post_preview_textdomain'); ?>:</h3>
<form action="" method="post">
<input name="action" type="hidden" value="ipreviewscreensaction" />
<select size="<?php print count($screens); ?>" name="posttypes[]" multiple="multiple">
<?php foreach($screens as $s){ if(in_array($s,$checkscreens)){ print '<option selected="selected" value="'.$s.'">'.$s.'</option>'; } else{ print '<option>'.$s.'</option>'; } } ?>
</select><br/>
<input style="margin-top:5px;" class="button button-primary button-large" type="submit" value="<?php _e('Save'); ?>" />
</form></div>
<hr />
<?php }

function super_preview_post_enq_admin_scripts(){
wp_enqueue_script('jquery-ui-datepicker');
}

add_action( 'admin_enqueue_scripts', 'super_preview_post_enq_admin_scripts' );

function super_preview_post_enq_datepicker(){
global $post;
$armeta = get_post_meta($post->ID, 'ipreview',true);
if(!$armeta){ $armeta=array(0=>'false',1=>date('d.m.Y'),2=>date('d.m.Y',strtotime(SUPER_TIME_FUTURE)), 3=>''); }
?>
<style type="text/css">
.super_pp_calendar{ padding-top:5px; text-align:right; }
.super_pp_calendar input[type=text]{ width:90%; display:inline-block; }
.super_pp_calendar:before{ content: "\f508"; font-family:Dashicons; display:inline-block; width:8%; margin-right:2px; }
</style>
<div class="super_pp_calendar"><input placeholder="<?php _e('Date since','super_public_post_preview_textdomain'); ?>" id="super_pp_from" name="from" type="text" value="<?php print date('d.m.Y',strtotime($armeta[1])); ?>" /></div>
<div class="super_pp_calendar"><input placeholder="<?php _e('Date until','super_public_post_preview_textdomain'); ?>" id="super_pp_to" name="to" type="text" value="<?php print date('d.m.Y',strtotime($armeta[2])); ?>" /></div>
<?php super_pp_password_input($armeta[3]); ?>
<div id="supersubmitdiv" style="padding:5px; text-align:right;"><button class="button button-primary button-large" id="super_pp_submit" ><?php _e('Save'); ?></button></div>
<script>
  jQuery( function() {

    var dateFormat = "dd.mm.yy",
      from = jQuery( "#super_pp_from" )
        .datepicker({
          defaultDate: "today",
          changeMonth: true,
          dateFormat: 'dd.mm.yy',                 
          numberOfMonths: 1
        })
        .on( "change", function() {
          to.datepicker( "option", "minDate", getDate( this ) );
        }),
      to = jQuery( "#super_pp_to" ).datepicker({
        defaultDate: "+1w",
        changeMonth: true,
        dateFormat: 'dd.mm.yy',                 
        numberOfMonths: 1
      })
      .on( "change", function() {
        from.datepicker( "option", "maxDate", getDate( this ) );
      });
 
    function getDate( element ) {
      var date;
      try {
        date = jQuery.datepicker.parseDate( dateFormat, element.value );
      } catch( error ) {
        date = null;
      }
 
      return date;
    }
  } );
  
  
  	jQuery(document).ready(function(){
	
	jQuery('#super_pp_submit').on('click',function(){
	jQuery(this).attr('disabled',true);
	jQuery('#super_set_post_to_publish .spinner').css('visibility','visible');
	if(jQuery('#super_preview_checkbox').is(':checked')) { var ichecked = 'true'; } else { var ichecked = 'false'; }
	<?php $ajax_nonce = wp_create_nonce( "superpp" ); ?>
	jQuery.ajax({
			url: '<?php print admin_url( 'admin-ajax.php' ); ?>',
			type: 'post',
			data: {
				action: 'super_enable_preview', checked: ichecked, id: '<?php print $post->ID; ?>', from: jQuery('#super_pp_from').val(), to: jQuery('#super_pp_to').val(), super_pp_password: jQuery('#super_pp_password').val(), security: '<?php echo $ajax_nonce; ?>'
			},
			success: function( html ) {
				jQuery('#super_preview_ajax_content').html( html );
					jQuery('#supersubmitdiv button').attr('disabled',false);
					jQuery('#super_set_post_to_publish .spinner').css('visibility','hidden');
			},
		    error: function(){ },
		});
		return false;
		});
	
	});

   </script>
<?php
}

function super_pp_password_input($pass){
?>
<div style="width:100%; padding-top:5px; text-align:left; padding-left:5px;"><input <?php if(trim($pass)<>''){ print 'checked="checked"'; } ?> name="super_pp_showpassword" id="super_pp_showpassword" type="checkbox" /> <label><?php _e('Password'); ?></label><input autocomplete="off" style="width:100%; display:none;" placeholder="<?php _e('Password'); ?>" id="super_pp_password" name="super_pp_password" type="password" /></div>
<script>
	jQuery(document).ready(function(){
	
	jQuery('#super_preview_checkbox').on('change',function(){
	if(jQuery(this).is(':checked')){ jQuery('#super_pp_showpassword').attr('disabled',false); } else{ jQuery('#super_pp_showpassword').attr('disabled',true); jQuery('#super_pp_password').fadeOut(500); jQuery('#super_pp_password').val(''); jQuery('#super_pp_showpassword').prop('checked',false); }
	});
	
	jQuery('#super_pp_showpassword').on('change',function(){
	if(jQuery(this).is(':checked')) { jQuery('#super_pp_password').fadeIn(500); } else { jQuery('#super_pp_password').fadeOut(500); jQuery('#super_pp_password').val(''); }
	
	});
	
	});
</script>
<?php
}

add_filter( 'protected_title_format', 'super_pp_remove_protected_text' );
function super_pp_remove_protected_text() {
	if(isset($_REQUEST['ipreview'])){
return '%s';
}
}