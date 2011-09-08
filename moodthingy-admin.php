<?php

function moodthingy_wp_menu() {
    // wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js');

	add_dashboard_page('MoodThingy Dashboard', 'MoodThingy Stats', 'manage_options', 'moodthingy', 'moodthingy_dashboard_page');
	add_options_page('MoodThingy Options', 'MoodThingy', 'manage_options', 'moodthingy', 'moodthingy_settings_page');
}

// Add settings link on plugin page
function moodthingy_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=moodthingy">Settings</a>';
  array_unshift($links, $settings_link); 
  return $links; 
}

function moodthingy_settings_page() {
	global $wpdb;
	global $moods;
	global $moodthingy_server;
	
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
	</script>
	
	<?php 
	
		if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
			$options['showsparkbar'] 			= $_POST[ 'showsparkbar' ];
			$options['showinpostsondefault'] 	= $_POST[ 'showinpostsondefault' ];
			$options['apikey'] 					= $_POST[ 'apikey' ];
			$options['apiname'] 				= $_POST[ 'apiname' ];
			$options['validkey'] 				= $_POST[ 'validkey' ];
			
			if ($options['showinpostsondefault'] == 'on') {
				add_filter('the_content', 'add_moodthingy_widget_to_posts');
			} else {
				add_filter('the_content', 'add_moodthingy_widget_to_posts');
			}
			
			update_option('moodthingy_wp_options', $options);
			echo '<div class="updated"><p><strong>Settings Saved.</strong></p></div>';
		}
	

	?>	

	<form id="options" method="post">

	<h3>MoodThingy.com Integration</h3>
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

	<h3 style="clear:both;">Widget Settings</h3>

		<input type="hidden" id="validkey" name="validkey" value="<?php echo $options['validkey']; ?>">
		<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
		<input type="checkbox" id="showsparkbar" name="showsparkbar" <?php if ( $options["showsparkbar"]=='on' ) { echo 'checked="true"'; } ?>> <label for="showsparkbar">Show a sparkline (graphical bar graph) above moods</label> <br>
		<input type="checkbox" id="showinpostsondefault" name="showinpostsondefault" <?php if ( $options["showinpostsondefault"]=='on' ) { echo 'checked="true"'; } ?>> <label for="showinpostsondefault">Automatically display the MoodThingy widget at the end of each blog post</label> 



		<p class="submit">
		<input type="button" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
		</p>
	</form>
	
	<h3>How do I install MoodThingy into my Wordpress templates?</h3>
	<p>By default, the MoodThingy widget will automatically display on an individual post, right after the comment.</p>
	<p>If you're gangsta and feel comfortable with PHP and Wordpress templates, you can add the widget anywhere you feel like, by disabling the above checkbox add the following bit of code in your Wordpress individual template:</p>
	<code>&lt;?php if ( function_exists('print_moodthingy_widget') ) { print_moodthingy_widget(); } ?&gt;</code>
	<p>On the default TwentyTen Wordpress theme, you should put this on your comment template (comments.php) This will automatically display the widget before your blog post comments. That's it!</p>

	<?php 
}


function moodthingy_dashboard_page() {
	global $wpdb;
	global $moods;
	global $moodthingy_server;
	
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
	global $wpdb;
	global $moods; ?>
	
	<h2>MoodThingy Stats</h2>
	
	<h3>Total Most Voted On Posts By Mood</h3>
	<p>To get an accurate read of the posts people care about, we're only counting posts that have more than one vote.</p>
	<div id="tabs">
		<?php for ($i=1; $i<=6; $i=$i+1) { ?>
		<a href="#" class="<?php if ($i==1) echo 'button-primary'; else echo 'button-secondary'; ?>" rel="mood_<?php echo $i; ?>">Most <?php echo $moods[$i]; ?></a> 
		<?php } ?>
	</div>
	
	<?php	
	for ($i=1; $i<=6; $i=$i+1) {
		$objs = $wpdb->get_results("SELECT ID, emotion_" . $i . " as emoted, 
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
		ORDER BY weighted DESC , ranking DESC , total DESC LIMIT 10");
		
		echo '<div class="mood_table" id="mood_' . $i . '">';
		echo '<h4>Most ' . $moods[$i] . '</h4><table class="widefat">';
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

$objs = $wpdb->get_results("SELECT post_ID, (SUM(emotion_1)) as emotion_1,  (SUM(emotion_2)) as emotion_2, (SUM(emotion_3)) as emotion_3, (SUM(emotion_4)) as emotion_4, (sum(emotion_5)) as emotion_5, (sum(emotion_6)) as emotion_6, ((SUM(emotion_1)) + (SUM(emotion_2)) + (SUM(emotion_3)) + (SUM(emotion_4)) + (SUM(emotion_5)) + (SUM(emotion_6))) AS total FROM (
SELECT post_ID, votes as emotion_1, 0 as emotion_2, 0 as emotion_3, 0 as emotion_4, 0 as emotion_5, 0 as emotion_6 FROM {$wpdb->prefix}lydl_poststimestamp WHERE emotion=1 group by post_ID
UNION
SELECT post_ID, 0, votes as emotion_2, 0, 0, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=2 group by post_ID
UNION
SELECT post_ID, 0, 0, votes as emotion_3, 0, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=3 group by post_ID
UNION
SELECT post_ID, 0, 0, 0, votes as emotion_4, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=4 group by post_ID
UNION
SELECT post_ID, 0, 0, 0, 0, votes as emotion_5, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=5 group by post_ID
UNION
SELECT post_ID, 0, 0, 0, 0, 0, votes as emotion_6 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=6 group by post_ID
) as test GROUP BY post_ID ORDER BY total desc LIMIT 10");
	echo '<div class="totaltable" id="totalvotes"><h4>Most Voted</h4><table class="widefat">';
	echo '<thead><tr><th>Blog post</th><th>'. $moods[1] .'</th><th>'. $moods[2] .'</th><th>'. $moods[3] .'</th><th>'. $moods[4] .'</th><th>'. $moods[5] .'</th><th>'. $moods[6] .'</th></tr></thead>';
	if (sizeof($objs) > 0) {
		foreach ($objs as $obj) {
			echo '<tr>';
			echo '<td><a href="' . get_permalink($obj->post_ID) . '">' . get_the_title($obj->post_ID) . '</a></td>';
			echo '<td>' . $obj->emotion_1 . '</td>';
			echo '<td>' . $obj->emotion_2 . '</td>';
			echo '<td>' . $obj->emotion_3 . '</td>';
			echo '<td>' . $obj->emotion_4 . '</td>';									
			echo '<td>' . $obj->emotion_5 . '</td>';
			echo '<td>' . $obj->emotion_6 . '</td>';
			echo '</tr>';
		}
	} else {
		echo '<tr><td colspan="3">No one has voted for this yet. Check back soon.</td></tr>';
	}
	echo '</table></div>';

$objs = $wpdb->get_results("SELECT post_ID, (SUM(emotion_1)) as emotion_1,  (SUM(emotion_2)) as emotion_2, (SUM(emotion_3)) as emotion_3, (SUM(emotion_4)) as emotion_4, (sum(emotion_5)) as emotion_5, (sum(emotion_6)) as emotion_6, ((SUM(emotion_1)) + (SUM(emotion_2)) + (SUM(emotion_3)) + (SUM(emotion_4)) + (SUM(emotion_5)) + (SUM(emotion_6))) AS total FROM (
SELECT post_ID, votes as emotion_1, 0 as emotion_2, 0 as emotion_3, 0 as emotion_4, 0 as emotion_5, 0 as emotion_6 FROM {$wpdb->prefix}lydl_poststimestamp WHERE emotion=1 AND (day + INTERVAL 30 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, votes as emotion_2, 0, 0, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=2 AND (day + INTERVAL 30 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, 0, votes as emotion_3, 0, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=3 AND (day + INTERVAL 30 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, 0, 0, votes as emotion_4, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=4 AND (day + INTERVAL 30 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, 0, 0, 0, votes as emotion_5, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=5 AND (day + INTERVAL 30 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, 0, 0, 0, 0, votes as emotion_6 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=6 AND (day + INTERVAL 30 DAY) >= NOW() group by post_ID
) as test group by post_ID ORDER BY total desc LIMIT 10");
	echo '<div class="totaltable" id="monthly"><h4>Most Voted in the Past Month</h4><table class="widefat">';
	echo '<thead><tr><th>Blog post</th><th>'. $moods[1] .'</th><th>'. $moods[2] .'</th><th>'. $moods[3] .'</th><th>'. $moods[4] .'</th><th>'. $moods[5] .'</th><th>'. $moods[6] .'</th></tr></thead>';
	if (sizeof($objs) > 0) {
		foreach ($objs as $obj) {
			echo '<tr>';
			echo '<td><a href="' . get_permalink($obj->post_ID) . '">' . get_the_title($obj->post_ID) . '</a></td>';
			echo '<td>' . $obj->emotion_1 . '</td>';
			echo '<td>' . $obj->emotion_2 . '</td>';
			echo '<td>' . $obj->emotion_3 . '</td>';
			echo '<td>' . $obj->emotion_4 . '</td>';									
			echo '<td>' . $obj->emotion_5 . '</td>';
			echo '<td>' . $obj->emotion_6 . '</td>';
			echo '</tr>';
		}
	} else {
		echo '<tr><td colspan="3">No one has voted for this yet. Check back soon.</td></tr>';
	}
	echo '</table></div>';

$objs = $wpdb->get_results("SELECT post_ID, (SUM(emotion_1)) as emotion_1,  (SUM(emotion_2)) as emotion_2, (SUM(emotion_3)) as emotion_3, (SUM(emotion_4)) as emotion_4, (sum(emotion_5)) as emotion_5, (sum(emotion_6)) as emotion_6, ((SUM(emotion_1)) + (SUM(emotion_2)) + (SUM(emotion_3)) + (SUM(emotion_4)) + (SUM(emotion_5)) + (SUM(emotion_6))) AS total FROM (
SELECT post_ID, votes as emotion_1, 0 as emotion_2, 0 as emotion_3, 0 as emotion_4, 0 as emotion_5, 0 as emotion_6 FROM {$wpdb->prefix}lydl_poststimestamp WHERE emotion=1 AND (day + INTERVAL 7 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, votes as emotion_2, 0, 0, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=2 AND (day + INTERVAL 7 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, 0, votes as emotion_3, 0, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=3 AND (day + INTERVAL 7 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, 0, 0, votes as emotion_4, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=4 AND (day + INTERVAL 7 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, 0, 0, 0, votes as emotion_5, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=5 AND (day + INTERVAL 7 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, 0, 0, 0, 0, votes as emotion_6 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=6 AND (day + INTERVAL 7 DAY) >= NOW() group by post_ID
) as test group by post_ID ORDER BY total desc LIMIT 10");
	echo '<div class="totaltable" id="weekly"><h4>Most Voted in the Past Week</h4><table class="widefat">';
	echo '<thead><tr><th>Blog post</th><th>'. $moods[1] .'</th><th>'. $moods[2] .'</th><th>'. $moods[3] .'</th><th>'. $moods[4] .'</th><th>'. $moods[5] .'</th><th>'. $moods[6] .'</th</tr></thead>';
	if (sizeof($objs) > 0) {
		foreach ($objs as $obj) {
			echo '<tr>';
			echo '<td><a href="' . get_permalink($obj->post_ID) . '">' . get_the_title($obj->post_ID) . '</a></td>';
			echo '<td>' . $obj->emotion_1 . '</td>';
			echo '<td>' . $obj->emotion_2 . '</td>';
			echo '<td>' . $obj->emotion_3 . '</td>';
			echo '<td>' . $obj->emotion_4 . '</td>';									
			echo '<td>' . $obj->emotion_5 . '</td>';
			echo '<td>' . $obj->emotion_6 . '</td>';
			echo '</tr>';
		}
	} else {
		echo '<tr><td colspan="3">No one has voted for this yet. Check back soon.</td></tr>';
	}
	echo '</table></div>';

$objs = $wpdb->get_results("SELECT post_ID, (SUM(emotion_1)) as emotion_1,  (SUM(emotion_2)) as emotion_2, (SUM(emotion_3)) as emotion_3, (SUM(emotion_4)) as emotion_4, (sum(emotion_5)) as emotion_5, (sum(emotion_6)) as emotion_6, ((SUM(emotion_1)) + (SUM(emotion_2)) + (SUM(emotion_3)) + (SUM(emotion_4)) + (SUM(emotion_5)) + (SUM(emotion_6))) AS total FROM (
SELECT post_ID, votes as emotion_1, 0 as emotion_2, 0 as emotion_3, 0 as emotion_4, 0 as emotion_5, 0 as emotion_6 FROM {$wpdb->prefix}lydl_poststimestamp WHERE emotion=1 AND (day + INTERVAL 1 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, votes as emotion_2, 0, 0, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=2 AND (day + INTERVAL 1 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, 0, votes as emotion_3, 0, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=3 AND (day + INTERVAL 1 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, 0, 0, votes as emotion_4, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=4 AND (day + INTERVAL 1 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, 0, 0, 0, votes as emotion_5, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=5 AND (day + INTERVAL 1 DAY) >= NOW() group by post_ID
UNION
SELECT post_ID, 0, 0, 0, 0, 0, votes as emotion_6 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=6 AND (day + INTERVAL 1 DAY) >= NOW() group by post_ID
) as test group by post_ID ORDER BY total desc LIMIT 10");
	echo '<div class="totaltable" id="daily"><h4>Most Voted in the Past Day</h4><table class="widefat">';
	echo '<thead><tr><th>Blog post</th><th>'. $moods[1] .'</th><th>'. $moods[2] .'</th><th>'. $moods[3] .'</th><th>'. $moods[4] .'</th><th>'. $moods[5] .'</th><th>'. $moods[6] .'</th></tr></thead>';
	if (sizeof($objs) > 0) {
		foreach ($objs as $obj) {
			echo '<tr>';
			echo '<td><a href="' . get_permalink($obj->post_ID) . '">' . get_the_title($obj->post_ID) . '</a></td>';
			echo '<td>' . $obj->emotion_1 . '</td>';
			echo '<td>' . $obj->emotion_2 . '</td>';
			echo '<td>' . $obj->emotion_3 . '</td>';
			echo '<td>' . $obj->emotion_4 . '</td>';									
			echo '<td>' . $obj->emotion_5 . '</td>';
			echo '<td>' . $obj->emotion_6 . '</td>';
			echo '</tr>';
		}
	} else {
		echo '<tr><td colspan="3">No one has voted for this yet. Check back soon.</td></tr>';
	}
	echo '</table></div>';
}

?>