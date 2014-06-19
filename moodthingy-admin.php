<?php

define('MOODTHINGY_CSS_DEFAULT', MOODTHINGY_PLUGIN_DIR . '/css/style-custom.css');
define('MOODTHINGY_CSS_FILE', WP_CONTENT_DIR . '/uploads/moodthingy-custom.css');
define('MOODTHINGY_CSS_URI', WP_CONTENT_URL . '/uploads/moodthingy-custom.css');


// duration: can be a string like "1 DAY", "30 DAY", "7 DAY", etc. 
// If blank string or null then remove the interval from the SQL statement completely.
function moodthingy_get_most_clicked_sql( $duration, $limit ) {
	global $wpdb;

	$intervalstring = ($duration) ? " AND (day + INTERVAL " . $duration . ") >= NOW()" : "";

	return "SELECT post_ID, (SUM(emotion_1)) as emotion_1,  (SUM(emotion_2)) as emotion_2, (SUM(emotion_3)) as emotion_3, (SUM(emotion_4)) as emotion_4, (sum(emotion_5)) as emotion_5, (sum(emotion_6)) as emotion_6, ((SUM(emotion_1)) + (SUM(emotion_2)) + (SUM(emotion_3)) + (SUM(emotion_4)) + (SUM(emotion_5)) + (SUM(emotion_6))) AS total FROM ( SELECT post_ID, SUM(votes) as emotion_1, 0 as emotion_2, 0 as emotion_3, 0 as emotion_4, 0 as emotion_5, 0 as emotion_6 FROM {$wpdb->prefix}lydl_poststimestamp WHERE emotion=1" . $intervalstring . " group by post_ID UNION SELECT post_ID, 0, SUM(votes) as emotion_2, 0, 0, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=2" . $intervalstring . " group by post_ID UNION SELECT post_ID, 0, 0, SUM(votes) as emotion_3, 0, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=3" . $intervalstring . " group by post_ID UNION SELECT post_ID, 0, 0, 0, SUM(votes) as emotion_4, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=4" . $intervalstring . " group by post_ID UNION SELECT post_ID, 0, 0, 0, 0, SUM(votes) as emotion_5, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=5" . $intervalstring . " group by post_ID UNION SELECT post_ID, 0, 0, 0, 0, 0, SUM(votes) as emotion_6 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=6" . $intervalstring . " group by post_ID) as test group by post_ID ORDER BY total desc LIMIT " . $limit;
}

function moodthingy_get_moods_sql( $i, $limit ) {

	global $wpdb;

	return "SELECT ID, emotion_" . $i . " as emoted, 
		(emotion_1 + emotion_2 + emotion_3 + emotion_4 + emotion_5 + emotion_6
		) AS total, (
		  emotion_" . $i . " / (
		  SELECT CASE WHEN total=0
		  THEN 0.1
		  ELSE total
		  END 
		) *100
		) AS ranking, (
		  emotion_" . $i . " / (
		  SELECT CASE WHEN total=0
		  THEN 0.1
		  ELSE total
		  END 
		) * (emotion_" . $i . ")
		) AS weighted
		FROM {$wpdb->prefix}lydl_posts WHERE (emotion_" . $i . ">0 AND emotion_1 + emotion_2 + emotion_3 + emotion_4 + emotion_5 + emotion_6>1) 
		ORDER BY weighted DESC , ranking DESC , total DESC LIMIT " . $limit;

}

class MoodThingy_Widget extends WP_Widget {
	function MoodThingy_Widget() {
		// widget actual processes
		$widget_ops = array('classname' => 'moodthingy_widget', 'description' => 'The most popular posts from your MoodThingy PRO widget' );
		$this->WP_Widget('moodthingy_widget', 'MoodThingy PRO', $widget_ops);		
	}

	function form($instance) {
		
		$options = get_option('moodthingy_wp_options');
		
		// outputs the options form on admin
		$title = ($instance['title'] == '') ? 'Popular Posts' : esc_attr($instance['title']);
		$duration = ($instance['duration'] == '') ? '7 DAY' : esc_attr($instance['duration']);
		
		if ( !$number = (int) $instance['number'] )
                $number = 4;
            elseif ( $number < 1 )
                $number = 1;
            elseif ( $number > 10 )
                $number = 10;		
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">
				Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('duration'); ?>">
				Show in this area...
				<select id="<?php echo $this->get_field_id('duration'); ?>" name="<?php echo $this->get_field_name('duration'); ?>">
					<option value="365 DAY"<?php echo ($duration == '365 DAY') ? ' selected="selected"' : ''; ?>>popular posts the past year</option>
					<option value="30 DAY"<?php echo ($duration == '30 DAY') ? ' selected="selected"' : ''; ?>>popular posts the past 30 days</option>
					<option value="7 DAY"<?php echo ($duration == '7 DAY') ? ' selected="selected"' : ''; ?>>popular posts the past 7 days</option>
					<option value="1 DAY"<?php echo ($duration == '1 DAY') ? ' selected="selected"' : ''; ?>>popular posts the past day</option>
				<?php for ($i=1; $i<=$options['numberitems']; $i=$i+1) { ?>
					<option value="MOOD-<?php echo $i; ?>"<?php echo ($duration == 'MOOD-' . $i ) ? ' selected="selected"' : ''; ?>>posts voted <?php echo $options['moodarray'][$i]; ?></option>
				<?php } ?>
				</select>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('number'); ?>">Number of posts to display:</label>
            <input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /><br />
            <small>(at most 10)</small>
        </p>
		<p>
			<fieldset style="width:214px; padding:5px;"  class="widefat">
                <legend>Thumbnail settings</legend>
			<input type="checkbox" class="checkbox" name="<?php echo $this->get_field_name( 'thumbnail-active' ); ?>" <?php echo ($instance['thumbnail']['active']) ? 'checked="checked"' : ''; ?> id="<?php echo $this->get_field_id('thumbnail-active'); ?>"><label for="<?php echo $this->get_field_id('thumbnail-active'); ?>"> Display post thumbnail</label><br>
				<?php if($instance['thumbnail']['active']) : ?>
				<label for="<?php echo $this->get_field_id( 'thumbnail-width' ); ?>">Width:</label> 
				<input id="<?php echo $this->get_field_id( 'thumbnail-width' ); ?>" name="<?php echo $this->get_field_name( 'thumbnail-width' ); ?>" value="<?php echo  $instance['thumbnail']['width']; ?>"  class="widefat" style="width:30px!important" /> px <br />
				<label for="<?php echo $this->get_field_id( 'thumbnail-height' ); ?>">Height:</label> 
				<input id="<?php echo $this->get_field_id( 'thumbnail-height' ); ?>" name="<?php echo $this->get_field_name( 'thumbnail-height' ); ?>" value="<?php echo  $instance['thumbnail']['height']; ?>"  class="widefat" style="width:30px!important" /> px<br />				
				<?php endif; ?>			
			</fieldset>
		</p>        
		<?php
	}

    function update( $new_instance, $old_instance ) {
    
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['number'] = (int) $new_instance['number'];
        $instance['duration'] = $new_instance['duration'];
		$instance['thumbnail']['active'] = $new_instance['thumbnail-active'];				
		$instance['thumbnail']['width'] = is_numeric($new_instance['thumbnail-width']) ? $new_instance['thumbnail-width'] : 15;
		$instance['thumbnail']['height'] = is_numeric($new_instance['thumbnail-height']) ? $new_instance['thumbnail-height'] : 15;

        return $instance;
    }

	function widget($args, $instance) {
		// outputs the content of the widget
		
		global $wpdb; 
		global $nothumb;
		
		extract($args);
		$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);

		if ( !$number = (int) $instance['number'] )
                $number = 4;
            elseif ( $number < 1 )
                $number = 1;
            elseif ( $number > 10 )
                $number = 10;	
		
		if ( substr($instance['duration'], 0, 5) == 'MOOD-' ) {
			$i = substr($instance['duration'], -1);
			$objs = $wpdb->get_results( moodthingy_get_moods_sql( $i, $number ) );
		} else {
			$duration = ($instance['duration'] == '') ? '7 DAY' : esc_attr($instance['duration']);
			$objs = $wpdb->get_results( moodthingy_get_most_clicked_sql( $duration, $number ) );
		}
		
		// print_r( moodthingy_get_most_clicked_sql( $duration, $number ) );
		// moodthingy_get_moods_sql

		echo $before_widget;

		if (sizeof($objs) > 0) {
			if ( !empty( $title ) ) { 
				echo $before_title . $title . $after_title; 
			}
			echo '<ul>';
			foreach ($objs as $obj) {
			
				$postid = ($obj->post_ID | $obj->ID);
				
				// get thumbnail
				if ($instance['thumbnail']['active']) {
					$tbWidth = $instance['thumbnail']['width'];
					$tbHeight = $instance['thumbnail']['height'];					
					
					if (!function_exists('get_the_post_thumbnail')) { // if the Featured Image is not active, show default thumbnail

						$thumb = "<a href=\"".get_permalink($postid)."\" class=\"mdt-nothumb\" title=\"". $title_attr ."\"><img src=\"". $nothumb . "\" alt=\"".$title_attr."\" border=\"0\" class=\"wpp-thumbnail\" width=\"".$tbWidth."\" height=\"".$tbHeight."\" "."/></a>";
					} else {
					
						if (has_post_thumbnail( $postid )) { // if the post has a thumbnail, get it
							$thumb = "<a href=\"".get_permalink( $postid )."\" title=\"". $title_attr ."\">" . get_the_post_thumbnail($postid, array($tbWidth, $tbHeight), array('class' => 'mdt-thumbnail', 'alt' => $title_attr, 'title' => $title_attr) ) . "</a>";
						} else { // try to generate a post thumbnail from first image attached to post. If it fails, use default thumbnail
							$thumb = "<a href=\"".get_permalink($postid)."\" title=\"". $title_attr ."\">" . generate_post_thumbnail($postid, array($tbWidth, $tbHeight), array('class' => 'mdt-thumbnail', 'alt' => $title_attr, 'title' => $title_attr) ) ."</a>";
						}

					}
				}				
												
				echo '<li>' . $thumb . '<a href="' . get_permalink($postid) . '">' . get_the_title($postid) . '</li></a>';
			}
			echo '</ul>';
		} else {
			// echo '<tr><td colspan="3">No one has voted for this yet. Check back soon.</td></tr>';
		}		
		
		echo $after_widget;
	}
}

// Generates a featured image from the first image attached to a post if found.
// Otherwise, returns default thumbnail
function generate_post_thumbnail($id, $dimensions, $atts) {
	global $nothumb;

	// get post attachments
	$attachments = get_children(array('post_parent' => $id, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order'));
	
	// no images have been attached to the post, return default thumbnail
	if ( !$attachments ) return "<img src=\"". $nothumb . "\" alt=\"". $atts['alt'] ."\" border=\"0\" class=\"". $atts['class'] ."\" width=\"". $dimensions[0] ."\" height=\"". $dimensions[1] ."\" "."/>";
	
	$count = count($attachments);
	$first_attachment = array_shift($attachments);			
	$img = wp_get_attachment_image($first_attachment->ID);
				
	if (!empty($img)) { // found an image, use it as Featured Image
		update_post_meta( $id, '_thumbnail_id', $first_attachment->ID );
		return get_the_post_thumbnail($id, $dimensions, $atts);
	} else { // no images have been found, return default thumbnail
		return "<img src=\"". $nothumb . "\" alt=\"". $atts['alt'] ."\" border=\"0\" class=\"". $atts['class'] ."\" width=\"". $dimensions[0] ."\" height=\"". $dimensions[1] ."\" "."/>";
	}
	
}

function moodthingy_wp_menu() {
    // wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js');

	add_dashboard_page('MoodThingy PRO Dashboard', 'MoodThingy PRO Stats', 'manage_options', 'moodthingy', 'moodthingy_dashboard_page');
	add_options_page('MoodThingy PRO Options', 'MoodThingy PRO', 'manage_options', 'moodthingy', 'moodthingy_settings_page');
}

// Add settings link on plugin page
function moodthingy_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=moodthingy">Settings</a>';
  array_unshift($links, $settings_link); 
  return $links; 
}

function moodthingy_settings_page() {
	global $wpdb;
	global $moodthingy_server;
	global $use_centralized_site;
	
	$hidden_field_name = 'moodthingy_submit_hidden';
	$options = get_option('moodthingy_wp_options');

	// http://codex.wordpress.org/Adding_Administration_Menus
	
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	} ?>
	<style>
	.selected { font-weight:bold; color:#000; text-decoration: none; }
	.content { display:none; }
	#settings { display:block; }
	#apikey, #apiname { padding: 5px; width: 200px; }
	</style>
	<script type="text/javascript">

	<?php if ( $use_centralized_site ) : ?>
	jQuery(document).ready(function(){
	
		if ( jQuery('#validkey').val()=='1' ) {
			jQuery('#apiverifyresult').html('Your Website ID and API key have been verified. Your widget will now appear on your blog posts!');
		} else {
			jQuery('#apiverifyresult').html('<span style="color:red;">Your Website ID and/or API key don\'t match our records. Your widget will not appear until it does. Try copying & pasting them again.</span>');
		}
	
		function verify( submit_form_afterward ) {
			// http://stackoverflow.com/questions/1388018/jquery-attaching-an-event-to-multiple-elements-at-one-go
			var jApikey = jQuery("#apikey");
			var jApiname = jQuery("#apiname");
			var jObj = jApikey.add(jApiname);

			var n = jApiname.val();
			n = jQuery.trim(n);
			var k = jApikey.val();
			k = jQuery.trim(k);
			// console.log(n,k);
			jQuery.ajax({
				url: '<?php echo $moodthingy_server; ?>/api/websites/verify/' + n + '/' + k,
				dataType: 'jsonp',
				type: 'get',
				success: function(j) {
					if (j.stat == 'ok') {
						jQuery('#apiverifyresult').html('Your Website ID and API key have been verified. Your widget will appear on your blog posts, but don\'t forget to save your changes!');
						jQuery('#validkey').val('1');
					} else {
						jQuery('#apiverifyresult').html('<span style="color:red;">Your Website ID and/or API key don\'t match our records. Your widget will not appear until it does. Try copying & pasting them again.</span>');
						jQuery('#validkey').val('');
					}
					
					if (submit_form_afterward) jQuery('form#options').submit();
					
				}
			});
		}
	
		var jApikey = jQuery("#apikey");
		var jApiname = jQuery("#apiname");
		var jObj = jApikey.add(jApiname);
					
		jQuery('form#options p.submit input.button-primary').click( function(){ verify(true); } );
		jObj.blur( function(){ verify(); } );
		
	});	
	<?php endif; ?>

	function checkNumberOfItems() {
		iItems = parseInt(jQuery("#dditems option:selected").val());
		// console.log(iItems);
		for ( var i=6; i > 0; i=i-1 ) {
			// console.log(i);
			jQuery('table.moodlistings tr:nth-child(' + i + ')').css('visibility', ((i - iItems) > 0) ? 'hidden' : 'visible'); 
		}
	}

	jQuery(document).ready(function(){
	
		if ( window.location.hash ) {
			var str = window.location.hash.split('#')[1];
			// console.log(str);

			jQuery('a[rel="' + str + '"]').addClass("nav-tab-active").siblings().removeClass("nav-tab-active");
			jQuery(".content:visible").css("display","none");
			jQuery("#"+str).css("display", "block");
		}

		jQuery("#dditems").change( function(){
			checkNumberOfItems();
		} );	
	
		jQuery(".nav-tab").click(function(){		
			var activeTab = jQuery(this).attr("rel");
			if ( typeof(activeTab) != 'undefined' ) {
				jQuery(this).addClass("nav-tab-active").siblings().removeClass("nav-tab-active");
				jQuery(".content:visible").fadeOut("fast", function(){
					jQuery("#"+activeTab).slideDown("fast");
				});				
				return false;
			}
		});	
		
		if (jQuery('input[name="bypasscss"]').is(':checked')) { jQuery('#csswrapper').hide(); } 
		
		jQuery('input[name="bypasscss"]').click(function(){
			jQuery('#csswrapper').css("display", (this.checked) ? "none" : "block");
		});
		
		// http://papermashup.com/jquery-farbtastic-colour-picker/	
		var f = jQuery.farbtastic('#picker');	
		var p = jQuery('#picker').css('opacity', 0.25);
		var selected;
		checkNumberOfItems();
		
		jQuery("input.hexcolors")
			.each( function(){ f.linkTo(this); jQuery(this).css('opacity', 0.75); } )
			.focus( function(){
				
				if (selected) {
					jQuery(selected).css('opacity', 0.75).removeClass('colorwell-selected');
				}
				f.linkTo(this);
				p.css('opacity', 1);
				jQuery(selected = this).css('opacity', 1).addClass('colorwell-selected');
			} );		
	});	

	</script>

	<?php 
	
		if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
			if ( trim($_POST[ 'cleardata' ] == 'DELETE') ) {
				moodthingy_reset_moods();	
			} else {

				$options['showmoodthingylogo']		= ( $_POST[ 'showmoodthingylogo' ] ) ? $_POST[ 'showmoodthingylogo' ] : 'off' ;
				
				$options['showsparkbar'] 			= $_POST[ 'showsparkbar' ];
				$options['sortmoods']				= $_POST[ 'sortmoods' ];
				$options['showinpostsondefault'] 	= $_POST[ 'showinpostsondefault' ];
				$options['bypasscss']				= $_POST[ 'bypasscss' ];
				$options['showtweetfollowup'] 		= ( $_POST[ 'showtweetfollowup' ] ) ? $_POST[ 'showtweetfollowup' ] : 'off' ;

							
				$options['apikey'] 					= $_POST[ 'apikey' ];
				$options['apiname'] 				= $_POST[ 'apiname' ];
				$options['validkey'] 				= $_POST[ 'validkey' ];
	
				$options['hexcolors']				= $_POST[ 'hexcolors' ];			
				$options['moodarray']				= $_POST[ 'moodarray' ];
				$options['translations']			= $_POST[ 'translations' ];
				$options['numberitems']				= $_POST[ 'numberitems' ];

				@file_put_contents(MOODTHINGY_CSS_FILE, stripslashes( $_POST[ 'cssbox' ] ) );
								
				if ($options['showinpostsondefault'] == 'on') {
					add_filter('the_content', 'add_moodthingy_widget_to_posts');
				}
				
				update_option('moodthingy_wp_options', $options);
				echo '<div class="updated"><p><strong>Settings Saved.</strong></p></div>';
			}
		}

	?>	

	<div class="wrap">
		<form id="options" method="post">

		<h2 class="nav-tab-wrapper">
			MoodThingy PRO 
			
			<a class="nav-tab" href="<?php echo admin_url('index.php?page=moodthingy'); ?>">Stats</a>
			<a class="nav-tab nav-tab-active" rel="settings" href="#">Settings</a>
			<a class="nav-tab" rel="customize" href="#">Customization</a>
			<a class="nav-tab" rel="faq" href="#">FAQ</a>
		</h2>	
				
		<div class="container">	
			<div class="content" id="settings">
				<h3 style="clear:both;">Plugin Settings</h3>
			
				<?php if ( $use_centralized_site ) : ?>
				<p>If you have an account on <a href="http://www.moodthingy.com/" target="_blank">MoodThingy.com</a>, register your blog on the website to see stats and other useful information!</p>
					<p>
					<label for="apiname">Your Website ID from moodthingy.com</label><br>
					<input id="apiname" name="apiname" size="13" type="input" value="<?php echo $options['apiname']; ?>">
					</p>
					
					<p>
					<label for="apikey">Your API Key from moodthingy.com</label><br>
					<input id="apikey" name="apikey" type="input" value="<?php echo $options['apikey']; ?>">
					</p>
					
					<div id="apiverifyresult"></div>
				<?php endif; ?>
				
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row">Sparkbars</th>
							<td><input type="checkbox" id="showsparkbar" name="showsparkbar" <?php if ( $options["showsparkbar"]=='on' ) { echo 'checked="true"'; } ?>> <label for="showsparkbar">Show a sparkline (graphical bar graph) above moods.</label></td>
						</tr>

						<tr valign="top">
							<th scope="row">Sorting</th>
							<td><input type="checkbox" id="sortmoods" name="sortmoods" <?php if ( $options["sortmoods"]=='on' ) { echo 'checked="true"'; } ?>> <label for="sortmoods">Automatically sort the moods by popularity.</label></td>
						</tr>
			
						<tr valign="top">
							<th scope="row">Automatic Display</th>
							<td><input type="checkbox" id="showinpostsondefault" name="showinpostsondefault" <?php if ( $options["showinpostsondefault"]=='on' ) { echo 'checked="true"'; } ?>> <label for="showinpostsondefault">Automatically display the MoodThingy widget at the end of each blog post.</label> <p class="description">If this is unchecked, you will need to use the print_moodthingy_widget() PHP function in your templates or use the [moodthingy] shortcode. Check the FAQ for more information.</p></td>
						</tr>
			
						<tr valign="top">
							<th scope="row">Social Media</th>
							<td><input type="checkbox" id="showtweetfollowup" name="showtweetfollowup" <?php if ( $options["showtweetfollowup"]=='on' ) { echo 'checked="true"'; } ?>> <label for="showtweetfollowup">Allow people to tweet and share to Facebook after voting.</label></td>
						</tr>

						<tr valign="top">
							<th scope="row">Logo</th>
							<td><input type="checkbox" id="showmoodthingylogo" name="showmoodthingylogo" <?php if ( $options["showmoodthingylogo"]=='on' ) { echo 'checked="true"'; } ?>> <label for="showmoodthingylogo">Show the MoodThingy logo.</label></td>
						</tr>

					</tbody>
				</table>
				
				
				<h3>Plugin Data</h3>
				
				<table class="form-table">
					<tbody>
			
						<tr valign="top">
							<th scope="row">Clear All Mood Data</th>
							<td><input type="textbox" id="cleardata" name="cleardata"> 
							
							<p class="description">IMPORTANT NOTE: This option will clear ALL votes for ALL postings. This will also clear ALL data in the MoodThingy Stats page. Use this if you were testing MoodThingy for yourself and now want to accept real data. If you're unsure about resetting your data, <a target="_blank" href="http://codex.wordpress.org/Backing_Up_Your_Database">make sure you back up your WordPress database</a> - once you clear the data, there's no way to undo this.</p>
							
							<p><strong>To clear all data, type DELETE in all Caps the textbox above and click on "Save Changes".</strong></p>
							
							</td>
						</tr>			
					</tbody>
				</table>
				
					<input type="hidden" id="validkey" name="validkey" value="<?php echo $options['validkey']; ?>">
					<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
			
					<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
					</p>
				<!-- /form -->	
			</div>
			<div class="content" id="customize">
			
				<h3>Customize Moods</h3>		

				<span style="font-size:108%;">
					Give users a choice of <select name="numberitems" id="dditems">
						<option value="2"<?php if ($options['numberitems'] == '2') echo ' selected'; ?>>2</option>
						<option value="3"<?php if ($options['numberitems'] == '3') echo ' selected'; ?>>3</option>
						<option value="4"<?php if ($options['numberitems'] == '4') echo ' selected'; ?>>4</option>
						<option value="5"<?php if ($options['numberitems'] == '5') echo ' selected'; ?>>5</option>
						<option value="6"<?php if ($options['numberitems'] == '6') echo ' selected'; ?>>6</option>
					</select>
					moods to choose from:
				</span>

				<table class="moodlistings form-table">
					<tbody>
						<tr valign="top">
							<th scope="row">Mood #1</th>
							<td><input name="moodarray[1]" type="text" value="<?php echo $options['moodarray'][1]; ?>"></td>
							<td><input name="hexcolors[1]" class="hexcolors" type="text" value="<?php echo $options['hexcolors'][1]; ?>"></td>
							<td rowspan="6"><div id="picker" style="float:right;"></div></td>
						</tr>

						<tr valign="top">
							<th scope="row">Mood #2</th>
							<td><input name="moodarray[2]" type="text" value="<?php echo $options['moodarray'][2];  ?>"></td>
							<td><input name="hexcolors[2]" class="hexcolors" type="text" value="<?php echo $options['hexcolors'][2]; ?>"></td>
						</tr>

						<tr valign="top">
							<th scope="row">Mood #3</th>
							<td><input name="moodarray[3]" type="text" value="<?php echo $options['moodarray'][3];  ?>"></td>
							<td><input name="hexcolors[3]" class="hexcolors" type="text" value="<?php echo $options['hexcolors'][3]; ?>"></td>							
						</tr>

						<tr valign="top">
							<th scope="row">Mood #4</th>
							<td><input name="moodarray[4]" type="text" value="<?php echo $options['moodarray'][4];  ?>"></td>
							<td><input name="hexcolors[4]" class="hexcolors" type="text" value="<?php echo $options['hexcolors'][4]; ?>"></td>
						</tr>

						<tr valign="top">
							<th scope="row">Mood #5</th>
							<td><input name="moodarray[5]" type="text" value="<?php echo $options['moodarray'][5];  ?>"></td>
							<td><input name="hexcolors[5]" class="hexcolors" type="text" value="<?php echo $options['hexcolors'][5]; ?>"></td>							
						</tr>

						<tr valign="top">
							<th scope="row">Mood #6</th>
							<td><input name="moodarray[6]" type="text" value="<?php echo $options['moodarray'][6];  ?>"></td>
							<td><input name="hexcolors[6]" class="hexcolors" type="text" value="<?php echo $options['hexcolors'][6];  ?>"></td>
						</tr>			
					</tbody>
				</table>
								
				<p class="description">
				Important note: Previous votes will NOT be deleted when you add, remove or change the names of Moods, even when you click on the "Save Changes" button. So if people already clicked "SAD" on a post and you changed "SAD" to "HAPPY," the votes will not be reset to reflect the new mood. You can reset all votes for all posts in the "Settings" tab (the tab to the left of "Customization").
				</p>
				
				<?php // print_r($options); ?>
								
				<h3 style="clear:both;">Customize the Other Text</h3>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row">How does this post make you feel?</th>
							<td><input name="translations[str1]" class="regular-text" type="text" value="<?php echo stripslashes($options['translations']['str1']); ?>">
							<p class="description">This text is seen in the plug-in header, in the upper left hand corner.</p></td>
						</tr>
						<tr valign="top">
							<th scope="row">VOTE FOR THIS</th>
							<td><input name="translations[str2]" class="regular-text" type="text" value="<?php echo stripslashes($options['translations']['str2']); ?>">
							<p class="description">This text is seen when you mouse over a mood.</p></td>
						</tr>
						<tr valign="top">
							<th scope="row">THANKS!</th>
							<td><input name="translations[str3]" class="regular-text" type="text" value="<?php echo stripslashes($options['translations']['str3']); ?>">
							<p class="description">This text is seen after you have voted.</p></td>
						</tr>		
						<tr valign="top">
							<th scope="row">Thanks for rating this! Now tell the world how you feel via Twitter.</th>
							<td>
							<input name="translations[str4]" class="regular-text" type="text" value="<?php echo stripslashes($options['translations']['str4']); ?>">
							<p class="description">Seen when you've just voted and the "Allow people to tweet after voting" is set to On.</p></td>
						</tr>
						<tr valign="top">
							<th scope="row">(Nah, it's cool; just take me back.)</th>
							<td>
							<input name="translations[str5]" class="regular-text" type="text" value="<?php echo stripslashes($options['translations']['str5']); ?>">
							<p class="description">Seen when you've just voted and the "Allow people to tweet after voting" is set to On.</p>
							</td>
						</tr>										
					</tbody>
				</table>
								
				<h3 style="clear:both;">Additional CSS Box</h3>
				
				<div id="csswrapper">
					<textarea style="width:100%;" rows="10" id="cssbox" name="cssbox"><?php echo @file_get_contents(MOODTHINGY_CSS_FILE); ?></textarea>		
					<p class="description">Web Developers: If you are experienced with CSS, you can override the existing MoodThingy CSS here.  It's strongly recommended that you download and <a href="http://getfirebug.com/" target="_blank">use a web development tool such as FireBug</a> before continuing</a>. The file is stored in <code>/wp-content/uploads/moodthingy-custom.css</code>.</p>
				</div>

				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row">Bypass Plugin CSS</th>
							<td><input type="checkbox" name="bypasscss" <?php if ( $options["bypasscss"]=='on' ) { echo 'checked="true"'; } ?>>
							<p class="description">Bypass the default and custom CSS used in the MoodThingy plug-in and use CSS from your theme instead. This may be necessary for specific WordPress Multisite installations. For advanced users only.</p></td>
						</tr>									
					</tbody>
				</table>

				<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				</p>

			</div>	
			<div class="content" id="faq">
				<h3>Does the MoodThingy plug-in include widgets?</h3>
				<p>If your WordPress theme uses widgets, a MoodThingy Widget is available in the Widgets menu which can list the most voted on posts.</p>
				
				<h3>How do I install MoodThingy into my Wordpress templates?</h3>
				<p>If the "Automatically display the MoodThingy widget at the end of each blog post" checkbox is checked, the MoodThingy widget will automatically display on an individual post, right after the comment.</p>
				<p>Otherwise, you can use the WordPress shortcode [moodthingy] to have your MoodThingy widget display.</p>
				<p>If you're gangsta and feel comfortable with PHP and Wordpress templates, you can add the widget anywhere you feel like, by disabling the "Automatically display the MoodThingy widget at the end of each blog post" checkbox add the following bit of code in your Wordpress individual template:</p>
				<code>&lt;?php if ( function_exists('print_moodthingy_widget') ) { print_moodthingy_widget(); } ?&gt;</code>
				<p>On the default TwentyTen Wordpress theme, you should put this on your comment template (comments.php) This will automatically display the widget before your blog post comments. That's it!</p>
			</div>
		</div>
	
	
		</form>
	
	</div>

	<?php 
}

function moodthingy_reset_moods() {
	global $wpdb;
	
	$table_name = $wpdb->prefix.'lydl_posts';
	$table_name2 = $wpdb->prefix.'lydl_poststimestamp';	
 
	$wpdb->query( "DELETE FROM ".$table_name );
	$wpdb->query( "DELETE FROM ".$table_name2 );
	echo '<div class="updated"><p><strong>All Moods have been reset.</strong></p></div>';
}


function moodthingy_dashboard_page() {
	global $wpdb;
	
	$hidden_field_name = 'moodthingy_submit_hidden';
	$options = get_option('moodthingy_wp_options');

	// http://codex.wordpress.org/Adding_Administration_Menus
	
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	} ?>
	<style>
	.selected { font-weight:bold; color:#000; text-decoration: none; }
	.totaltable,.mood_table { display:none; }
	#daily,#mood_1 { display:block; }
	#apikey, #apiname { padding: 5px; width: 200px; }
	</style>
	<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery("#tabs a").click(function(){
			var activeTab = jQuery(this).attr("rel");
			jQuery(this).addClass("button-primary").removeClass("button-secondary").siblings().addClass("button-secondary").removeClass("button-primary");
			jQuery(".mood_table:visible").fadeOut("fast", function(){
				jQuery("#"+activeTab).slideDown("fast");
			});
			
			return false;
		});
		jQuery("#tabs2 a").click(function(){
			var activeTab = jQuery(this).attr("rel");
			jQuery(this).addClass("button-primary").removeClass("button-secondary").siblings().addClass("button-secondary").removeClass("button-primary");
			jQuery(".totaltable:visible").fadeOut("fast", function(){
				jQuery("#"+activeTab).slideDown("fast");
			});
			
			return false;
		});
	});	
	</script>
	
	<?php 
		moodthingy_printmoodtables(); 
	?>	

	<?php 
}

function moodthingy_printmoodtables() {
	global $wpdb, $moods;
	
	$options = get_option('moodthingy_wp_options'); ?>
	
	<div class="wrap">
			<h2 class="nav-tab-wrapper">
			MoodThingy PRO
			
			<a class="nav-tab nav-tab-active" href="<?php echo admin_url('index.php?page=moodthingy'); ?>">Stats</a>
			<a class="nav-tab" rel="settings" href="<?php echo admin_url('options-general.php?page=moodthingy#settings'); ?>">Settings</a>
			<a class="nav-tab" rel="customize" href="<?php echo admin_url('options-general.php?page=moodthingy#customize'); ?>">Customization</a>
			<a class="nav-tab" rel="faq" href="<?php echo admin_url('options-general.php?page=moodthingy#faq'); ?>">FAQ</a>
		</h2>
	
	<h3>Total Most Voted On Posts By Mood</h3>
	<p>To get an accurate read of the posts people care about, we're only counting posts that have more than one vote.</p>
	<div id="tabs">
		<?php for ($i=1; $i<=$options['numberitems']; $i=$i+1) { ?>
		<a href="#" class="<?php if ($i==1) echo 'button-primary'; else echo 'button-secondary'; ?>" rel="mood_<?php echo $i; ?>">Most <?php echo $options['moodarray'][$i]; ?></a> 
		<?php } ?>
	</div>
	
	<?php	
	for ($i=1; $i<=$options['numberitems']; $i=$i+1) {
		$objs = $wpdb->get_results( moodthingy_get_moods_sql($i, 10) );
		
		echo '<div class="mood_table" id="mood_' . $i . '">';
		echo '<h4>Most ' . $options['moodarray'][$i] . '</h4><table class="widefat">';
		echo '<thead><tr><th>Blog post</th><th>Votes</th><th>Total</th><th>Percentage</th><th>Weighted Score</th></tr></thead>';
		if (sizeof($objs) > 0) {
			foreach ($objs as $obj) {
				echo '<tr>';
				echo '<td><a href="' . get_permalink($obj->ID) . '">' . get_the_title($obj->ID) . '</a></td>';
				echo '<td>' . $obj->emoted . '</td>';
				echo '<td>' . $obj->total . '</td>';
				echo '<td>' . $obj->ranking . '%</td>';
				echo '<td>' . $obj->weighted . '</td>';
				echo '</tr>';
			}
		} else {
			echo '<tr><td colspan="3">No one has voted for this yet. Check back soon.</td></tr>';
		}
		echo '</table>';
		echo '</div>';
	} ?>
	
<h3>Most Voted On Posts By Date</h3>
	<div id="tabs2">
		<a href="#" class="button-secondary" rel="totalvotes">Most Voted</a>
		<a href="#" class="button-secondary" rel="monthly">Most Voted in the Past Month</a>
		<a href="#" class="button-secondary" rel="weekly">Most Voted in the Past Week</a>
		<a href="#" class="button-primary" rel="daily">Most Voted in the Past Day</a>
	</div>

<?php
	$objs = $wpdb->get_results( moodthingy_get_most_clicked_sql( NULL, 10 ) );

	echo '<div class="totaltable" id="totalvotes"><h4>Most Voted</h4><table class="widefat">';
	echo '<thead><tr><th>Blog post</th>';
	for ( $i=1; $i <= $options['numberitems']; $i=$i+1 ) {
		echo '<th>' . $options['moodarray'][$i] .'</th>';
	}
	echo '</tr></thead>';
	if (sizeof($objs) > 0) {
		foreach ($objs as $obj) {
			echo '<tr>';
			echo '<td><a href="' . get_permalink($obj->post_ID) . '">' . get_the_title($obj->post_ID) . '</a></td>';
			echo '<td>' . $obj->emotion_1 . '</td>';
			echo '<td>' . $obj->emotion_2 . '</td>';
			if ($options['numberitems']>2) echo '<td>' . $obj->emotion_3 . '</td>';
			if ($options['numberitems']>3) echo '<td>' . $obj->emotion_4 . '</td>';									
			if ($options['numberitems']>4) echo '<td>' . $obj->emotion_5 . '</td>';
			if ($options['numberitems']>5) echo '<td>' . $obj->emotion_6 . '</td>';
			echo '</tr>';
		}
	} else {
		echo '<tr><td colspan="3">No one has voted for this yet. Check back soon.</td></tr>';
	}
	echo '</table></div>';

	$objs = $wpdb->get_results( moodthingy_get_most_clicked_sql( "30 DAY", 10 ) );

	echo '<div class="totaltable" id="monthly"><h4>Most Voted in the Past Month</h4><table class="widefat">';
	echo '<thead><tr><th>Blog post</th>';
	for ( $i=1; $i <= $options['numberitems']; $i=$i+1 ) {
		echo '<th>' . $options['moodarray'][$i] .'</th>';
	}
	echo '</tr></thead>';
	if (sizeof($objs) > 0) {
		foreach ($objs as $obj) {
			echo '<tr>';
			echo '<td><a href="' . get_permalink($obj->post_ID) . '">' . get_the_title($obj->post_ID) . '</a></td>';
			echo '<td>' . $obj->emotion_1 . '</td>';
			echo '<td>' . $obj->emotion_2 . '</td>';
			if ($options['numberitems']>2) echo '<td>' . $obj->emotion_3 . '</td>';
			if ($options['numberitems']>3) echo '<td>' . $obj->emotion_4 . '</td>';									
			if ($options['numberitems']>4) echo '<td>' . $obj->emotion_5 . '</td>';
			if ($options['numberitems']>5) echo '<td>' . $obj->emotion_6 . '</td>';
			echo '</tr>';
		}
	} else {
		echo '<tr><td colspan="3">No one has voted for this yet. Check back soon.</td></tr>';
	}
	echo '</table></div>';

	$objs = $wpdb->get_results( moodthingy_get_most_clicked_sql( "7 DAY", 10 ) );

	echo '<div class="totaltable" id="weekly"><h4>Most Voted in the Past Week</h4><table class="widefat">';
	echo '<thead><tr><th>Blog post</th>';
	for ( $i=1; $i <= $options['numberitems']; $i=$i+1 ) {
		echo '<th>' . $options['moodarray'][$i] .'</th>';
	}
	echo '</tr></thead>';
	if (sizeof($objs) > 0) {
		foreach ($objs as $obj) {
			echo '<tr>';
			echo '<td><a href="' . get_permalink($obj->post_ID) . '">' . get_the_title($obj->post_ID) . '</a></td>';
			echo '<td>' . $obj->emotion_1 . '</td>';
			echo '<td>' . $obj->emotion_2 . '</td>';
			if ($options['numberitems']>2) echo '<td>' . $obj->emotion_3 . '</td>';
			if ($options['numberitems']>3) echo '<td>' . $obj->emotion_4 . '</td>';									
			if ($options['numberitems']>4) echo '<td>' . $obj->emotion_5 . '</td>';
			if ($options['numberitems']>5) echo '<td>' . $obj->emotion_6 . '</td>';
			echo '</tr>';
		}
	} else {
		echo '<tr><td colspan="3">No one has voted for this yet. Check back soon.</td></tr>';
	}
	echo '</table></div>';

	$objs = $wpdb->get_results( moodthingy_get_most_clicked_sql( "1 DAY", 10 ) );


	echo '<div class="totaltable" id="daily"><h4>Most Voted in the Past Day</h4><table class="widefat">';
	echo '<thead><tr><th>Blog post</th>';
	for ( $i=1; $i <= $options['numberitems']; $i=$i+1 ) {
		echo '<th>' . $options['moodarray'][$i] .'</th>';
	}
	echo '</tr></thead>';
	if (sizeof($objs) > 0) {
		foreach ($objs as $obj) {
			echo '<tr>';
			echo '<td><a href="' . get_permalink($obj->post_ID) . '">' . get_the_title($obj->post_ID) . '</a></td>';
			echo '<td>' . $obj->emotion_1 . '</td>';
			echo '<td>' . $obj->emotion_2 . '</td>';
			if ($options['numberitems']>2) echo '<td>' . $obj->emotion_3 . '</td>';
			if ($options['numberitems']>3) echo '<td>' . $obj->emotion_4 . '</td>';									
			if ($options['numberitems']>4) echo '<td>' . $obj->emotion_5 . '</td>';
			if ($options['numberitems']>5) echo '<td>' . $obj->emotion_6 . '</td>';
			echo '</tr>';
		}
	} else {
		echo '<tr><td colspan="3">No one has voted for this yet. Check back soon.</td></tr>';
	}
	echo '</table></div></div>';
}
