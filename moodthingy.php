<?php 
/*
Plugin Name: MoodThingy
Plugin URI: https://github.com/ErnieAtLYD/MoodThingy-WP-Plug-In
Description: Adds a one-click real-time mood rating widget to all of your posts.
Version: 2.0
Author: Ernie Hsiung
E-Mail: e@erniehsiung.com
Author URI: https://github.com/ErnieAtLYD/MoodThingy-WP-Plug-In
*/

// define('MOODTHINGY_PLUGIN_DIR', 'http://localhost:8888/wordpress/wp-content/plugins/moodthingy-pro-plugin');
define('MOODTHINGY_PLUGIN_DIR', plugins_url(basename(dirname(__FILE__))));


global $lydl_db_version;
global $moods;
global $use_centralized_site;
global $moodthingy_server;

$use_centralized_site = FALSE;
$lydl_db_version = "0.6";
$moodthingy_server = "http://www.moodthingy.com";
$moods = array(
	1 => "Fascinated", 
	2 => "Amused", 
	3 => "Sad", 
	4 => "Angry", 
	5 => "Bored", 
	6 => "Excited"
);
$hexcolors = array(
	1 => '#7B11C6',
	2 => '#17C611',
	3 => '#115AC6',
	4 => '#E01D1D',
	5 => '#666666',
	6 => '#DB871A'
);
$translation_strings = array(
	'str1' => 'How does this post make you feel?',
	'str2' => 'VOTE FOR THIS',
	'str3' => 'THANKS!',
	'str4' => 'Thanks for rating this! Now tell the world how you feel through social media.',
	'str5' => "(Nah, it's cool; just take me back.)"
);

$cookie_duration = 14;

require_once( 'moodthingy-admin.php' );

function moodthingy_init() {
	global $moods, $hexcolors, $translation_strings;

	$options = get_option('moodthingy_wp_options');
	$customCSS = @file_get_contents(MOODTHINGY_CSS_FILE);
	
	if ( !$customCSS ) {
		$defaultCSS = @file_get_contents(MOODTHINGY_CSS_DEFAULT);
		@file_put_contents(MOODTHINGY_CSS_FILE, strip_tags($defaultCSS) );
	}
	
	// embed the javascript file that makes the AJAX request
	if ( is_admin() ) { 
		wp_enqueue_script('jquery');

		wp_enqueue_style( 'farbtastic' );
		wp_enqueue_script( 'farbtastic' );
		
	} else {
	
		if ( $options['bypasscss'] != 'on' ) {
			wp_register_style('moodthingy-default-style', MOODTHINGY_PLUGIN_DIR . '/css/style.css' );
			wp_enqueue_style('moodthingy-default-style');
	
			if ( $customCSS ) {
				wp_register_style('moodthingy-custom-style', MOODTHINGY_CSS_URI );
				wp_enqueue_style('moodthingy-custom-style');
			}
		}
	
		wp_enqueue_script( 'my-ajax-request', MOODTHINGY_PLUGIN_DIR . '/js/ajax.js', array( 'jquery' ) );	
		wp_register_script( 'moodthingy-remote', MOODTHINGY_PLUGIN_DIR . '/js/easyXDM.min.js' );	
		wp_enqueue_script( 'moodthingy-remote' );
	}	
	
		
	if (!$options) {
		moodthingy_add_default_options();
	} else {

		if ($options['showinpostsondefault'] == 'on') {
			add_filter('the_content', 'add_moodthingy_widget_to_posts');
		}
		if (empty($options['showtweetfollowup'])) {
			$options['showtweetfollowup'] = 'on';			
		}
		if (empty($options['showmoodthingylogo'])) {
			$options['showmoodthingylogo'] = 'on';			
		}
		if ( empty($options['moodarray'] )) {
			$options['moodarray'] = $moods;
		}
		if ( empty($options['hexcolors'] )) {
			$options['hexcolors'] = $hexcolors;
		}
		if ( empty($options['translations'] )) {
			$options['translations'] = $translation_strings;
		}

		update_option('moodthingy_wp_options', $options);	
			
	}	
	
	
}

function moodthingy_add_default_options() {	
	global $moods, $hexcolors;
	
	$temp = array(
		'showsparkbar' => 'on',
		'showinpostsondefault' => 'on',
		'showtweetfollowup' => 'on',
		'validkey' => '0',
		'moodarray' => $moods,
		'hexcolors' => $hexcolors,
		'sortmoods' => 'on',
		'showmoodthingylogo' => 'on',
		'translations' => $translation_strings,
		'numberitems' => 6	
	);
	
	update_option('moodthingy_wp_options', $temp);
}

function moodthingy_website_and_apikey_match() {
	$options = get_option('moodthingy_wp_options');
	return $options['validkey'] == '1';
}

function moodthingy_get_widget_html() {
	global $wpdb;
	global $post;
	$options = get_option('moodthingy_wp_options');
	
	if ( ( $use_centralized_site == FALSE ) || ($use_centralized_site == TRUE && moodthingy_website_and_apikey_match()) ) {
		$post_id = (int)$post->ID;
		$obj = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}lydl_posts WHERE ID=" . $post_id, ARRAY_A);
		$sum = $obj["emotion_1"]+$obj["emotion_2"]+$obj["emotion_3"]+$obj["emotion_4"]+$obj["emotion_5"]+$obj["emotion_6"];
	
		$widgetcss = '<style type="text/css">#mdr-e1 .percent { color: ' . $options['hexcolors'][1] . '; } .spark1 { background-color: ' . $options['hexcolors'][1] . '; } #mdr-e2 .percent { color:' . $options['hexcolors'][2] . '; } .spark2 { background-color: ' . $options['hexcolors'][2] . '; } #mdr-e3 .percent { color:' . $options['hexcolors'][3] . '; } .spark3 { background-color: ' . $options['hexcolors'][3] . '; } #mdr-e4 .percent { color:' . $options['hexcolors'][4] . '; } .spark4 { background-color: ' . $options['hexcolors'][4] . '; } #mdr-e5 .percent { color: ' . $options['hexcolors'][5] . '; } .spark5 { background-color: ' . $options['hexcolors'][5] . '; } #mdr-e6 .percent { color: ' . $options['hexcolors'][6] . '; } .spark6 { background-color: ' . $options['hexcolors'][6] . '; }</style>';
		
		$widgethtml = '<div id="moodthingy-widget" class="voted"><div id="lyr2"><div class="b">' . $options['translations']['str4'] . ' 
<a id="tweet-button" class="socialmedia" href="https://twitter.com/share?url=' . get_permalink() . '&via=MoodThingy&text=I%20just%20voted%20on%20the%20post%20\"' . get_the_title() . '\"">Share this on Twitter</a> 
<a id="fb-button" class="socialmedia" href="http://www.facebook.com/sharer/sharer.php?s=100&p[title]=' . get_the_title() . '&p[url]=' . get_permalink() . '&p[summary]=I+just+voted+on+this+post+with+MoodThingy!">and on Facebook</a>.</div>
<div class="s"><a href="#" id="clr">' . stripslashes($options['translations']['str5']) . '</a></div></div>
			<div id="lyr1"></div>
			<div id="hdr">';
		if ( $options['showmoodthingylogo'] == 'on' ) {
			$widgethtml .= '<div id="t">
					<a target="_blank" href="http://www.moodthingy.com" title="MoodThingy"><span>MOODTHINGY</span></a>
				</div>';
		}
		$widgethtml .= '<div id="s">' . $options['translations']['str1'] . '</div>
			</div>
			<span id="total"></span><span id="voted"></span>
			<div id="bd" style="">
				<div id="loading" style="display: block;"></div>
				
				<div id="sparkbardiv" style="">
					<div class="sparkbar" style="">
					</div>
				</div>
							
				<ul class="total-' . $options['numberitems'] . '">';
		if ( intval($options['numberitems']) == 6 ) {
		$widgethtml .= '
					<li id="mdr-e6"><div class="cell"><div>
						<span class="m">' . $options['moodarray'][6] .'</span>
						<span class="count"></span><span class="percent"></span>
					</div></div></li>';
		}
		$widgethtml .= '
					<li id="mdr-e1"><div class="cell"><div>
						<span class="m">' . $options['moodarray'][1] . '</span>
						<span class="count"></span><span class="percent"></span>
					</div></div></li>
					<li id="mdr-e2"><div class="cell"><div>
						<span class="m">' . $options['moodarray'][2] .'</span>
						<span class="count"></span><span class="percent"></span>
					</div></div></li>';
		if ( intval($options['numberitems']) >= 5 ) {			
		$widgethtml .= '
					<li id="mdr-e5"><div class="cell"><div>
						<span class="m">' . $options['moodarray'][5] . '</span>
						<span class="count"></span><span class="percent"></span>
					</div></div></li>';
		}
		if ( intval($options['numberitems']) >= 3 ) {
		$widgethtml .= '
					<li id="mdr-e3"><div class="cell"><div>
						<span class="m">' . $options['moodarray'][3] . '</span>
						<span class="count"></span><span class="percent"></span>
					</div></div></li>';
		}
		if ( intval($options['numberitems']) >= 4 ) {
		$widgethtml .= '
					<li id="mdr-e4"><div class="cell"><div>
						<span class="m">' . $options['moodarray'][4] . '</span>
						<span class="count"></span><span class="percent"></span>
					</div></div></li>';
		}
		$widgethtml .= '
				</ul>
			</div>
		</div>';
		return $widgetcss . $widgethtml;
	} else {
		return '';
	}
	
}

function add_moodthingy_widget_to_posts($content) {
	if (is_single()) { $content .= moodthingy_get_widget_html(); }
	return $content;
}

function print_moodthingy_widget ( ) {
	if ( is_single() || is_page() && !(is_home()) ) {
		echo moodthingy_get_widget_html();
	}
}

function print_moodthingy_shortcode ( ) {
	if ( is_single() || is_page() && !(is_home()) ) {
		return moodthingy_get_widget_html();
	}
}

function lydl_js_header() {

  // Define custom JavaScript function
	global $wp_query, $moodthingy_server, $use_centralized_site;
	wp_reset_query();
	$options = get_option('moodthingy_wp_options');
	
	// if we're on a page or post, load the script
	if ( is_single() || is_page() ) {
		// create security token
		$nonce = wp_create_nonce('lydl-moodthingy');	
	
		$id = $wp_query->post->ID;
		
		// http://www.snipe.net/2008/12/fixing-curly-quotes-and-em-dashes-in-php/
		$search = array( chr(145), chr(146), chr(147), chr(148), chr(151), '&#8211;', '&#8217;' );
		$replace = array("'", "'", '"', '"', '-', '-', "'");
		$strnosmartquotes = str_replace($search, $replace, html_entity_decode(get_the_title()));
		
		?>
		<!-- MoodThingy -->
		<script type="text/javascript">
		//<![CDATA[
		var MoodThingyAjax = {
		centralized: '<?php echo $use_centralized_site; ?>',
		cors: '<?php echo $moodthingy_server; ?>',
		ajaxurl: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
		token: '<?php echo $nonce; ?>',
		<?php if ( $use_centralized_site ) : ?>
		siteid: '<?php echo $options['apiname']; ?>',
		api: '<?php echo $options['apikey'] ?>',
		<?php endif; ?>
		id: <?php echo $id; ?>,
		title: '<?php echo addslashes($strnosmartquotes); ?>',
		url: '<?php echo the_permalink(); ?>',
		sparkline: '<?php echo $options['showsparkbar'] ?>',
		sortmoods: '<?php echo $options['sortmoods'] ?>',
		tweet: '<?php echo $options['showtweetfollowup'] ?>',
		votestring: '<?php echo $options['translations']['str2'] ?>',
		thxstring: '<?php echo $options['translations']['str3'] ?>' 
		};
	
		</script>
		<!-- MoodThingy -->
		<?php
	}

} // end of PHP function lydl_js_header

function lydl_store_results($vote, $postid) {
	global $wpdb;
	$wpdb->show_errors();
	
	if ($vote) {

		$table_name = $wpdb->prefix.'lydl_posts';
		$table_name2 = $wpdb->prefix.'lydl_poststimestamp';
		
		// Necessary as there is weirdness to prepare method otherwise
		$votefield = 'emotion_' . $vote;
		
		$votecount = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM ".$table_name." WHERE ID=%s",
				$postid
			)
		);
				
		$votecount = $votecount->$votefield;

		$recordexists = (sizeof($votecount) > 0) ? true : false;
		if ($recordexists) {
			$row = $wpdb->update( 
				$table_name, 
				array( 'ID'=>$postid, $votefield => $votecount+1 ), 
				array( 'ID'=>$postid ) 
			);
		} else {
			$row = $wpdb->insert( 
				$table_name, 
				array( 'ID'=>$postid, $votefield => 1) 
			);
		}

		// update popularpostsdatacache table
		$isincache = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_ID FROM ".$table_name2." WHERE post_ID = %s AND emotion = %s AND day = CURDATE()",
				$postid,
				$vote
			)
		);			
		if ($isincache) {
			$result2 = $wpdb->query( 
				$wpdb->prepare(
					"UPDATE ".$table_name2." SET votes=votes+1 WHERE post_ID = %s AND emotion = %s AND day = CURDATE()", 
					$postid, 
					$vote 
				)
			);
		} else {		
			$result2 = $wpdb->query(
				$wpdb->prepare(
					"INSERT INTO ".$table_name2." (post_ID, votes, emotion, day) VALUES (%s, 1, %s, CURDATE())",
					$postid,
					$vote
				)
			);
		}	
		//$cookie_last = $cookie_duration * 24 * 60 * 60;		
		//setcookie("moodthingy_{$postid}", $vote,  time()+$cookie_last, COOKIEPATH, COOKIE_DOMAIN);

	}
}

function lydl_ajax_populate() {
	global $wpdb;
	global $post;

	$postid = $_POST['postID'];	
	$nonce = $_POST['token'];
	// is this a valid request?
	if (! wp_verify_nonce($nonce, 'lydl-moodthingy') ) die("Oops!");
	
	$obj = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}lydl_posts WHERE ID=" . $postid, ARRAY_A);
	$sum = $obj["emotion_1"]+$obj["emotion_2"]+$obj["emotion_3"]+$obj["emotion_4"]+$obj["emotion_5"]+$obj["emotion_6"];
	
	$voted = '';
	if (isset($_COOKIE['moodthingy_'.$postid])) { 
		$voted = $_COOKIE['moodthingy_'.$postid]; 
	} 

	// generate the response
	$response = json_encode( array( 'success' => true, 
									'voted' => $voted,
									'emotion1' => $obj["emotion_1"], 
									'emotion2' => $obj["emotion_2"], 
									'emotion3' => $obj["emotion_3"], 
									'emotion4' => $obj["emotion_4"],
									'emotion5' => $obj["emotion_5"], 
									'emotion6' => $obj["emotion_6"], 
									'sum' => $sum ) );
	header( "Content-Type: application/json" );
	echo $response;
	exit;
}

function lydl_ajax_submit() {
	$nonce = $_POST['token'];
	// is this a valid request?
	if (! wp_verify_nonce($nonce, 'lydl-moodthingy') ) die("Oops!");

	$vote = $_POST['moodthingyvote'];
	$results_id = $_POST['results_div_id'];
	$postid = $_POST['postID'];

	lydl_store_results($vote, $postid);

	// generate the response
	$response = json_encode( array( 'success' => true, 'vote' => $vote, 'divid' => $results_id ) );
	
	// response output
	header( "Content-Type: application/json" );
	echo $response;

	// IMPORTANT: don't forget to "exit"
	exit;
}

function lydl_install_db_table () {
	global $wpdb;
	global $lydl_db_version;
	
	$table_name = $wpdb->prefix.'lydl_posts';
	$table_name2 = $wpdb->prefix.'lydl_poststimestamp';
	$installed_ver = get_option( "lydl_db_version" );
	
	if( $installed_ver != $lydl_db_version ) {
		$sql = "CREATE TABLE " . $table_name . " (
			`ID` bigint(20) NOT NULL,
			`emotion_1` bigint(20) DEFAULT '0' ,
			`emotion_2` bigint(20) DEFAULT '0' ,
			`emotion_3` bigint(20) DEFAULT '0' ,
			`emotion_4` bigint(20) DEFAULT '0' ,
			`emotion_5` bigint(20) DEFAULT '0' ,
			`emotion_6` bigint(20) DEFAULT '0' ,
			UNIQUE KEY  `ID` (`ID`)
		);";	
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);	
	}

	if( $installed_ver != $lydl_db_version ) {
		$sql = "CREATE TABLE " . $table_name2 . " (
			`post_ID` bigint(20) NOT NULL,
			`day` datetime NOT NULL default '0000-00-00 00:00:00',
			`votes` bigint(20) DEFAULT '0' ,
			`emotion` bigint(20) NOT NULL
		);";	
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);	
	}

	add_option("lydl_db_version", $lydl_db_version);
}

$moodthingy_plugin = plugin_basename(__FILE__); 

add_filter("plugin_action_links_$moodthingy_plugin", 'moodthingy_settings_link' );
register_activation_hook(__FILE__,'lydl_install_db_table');
add_action('init', 'moodthingy_init');
add_action('admin_menu', 'moodthingy_wp_menu');
add_action('wp_head', 'lydl_js_header' );
add_action('wp_ajax_cast_vote', 'lydl_ajax_submit');
add_action('wp_ajax_nopriv_cast_vote', 'lydl_ajax_submit');
add_action('wp_ajax_check_ip', 'lydl_ajax_checkip');
add_action('wp_ajax_populate_post', 'lydl_ajax_populate');
add_action('wp_ajax_nopriv_populate_post', 'lydl_ajax_populate');

function register_MoodThingy_Widget(){
register_widget('MoodThingy_Widget');
}

add_action('init', 'register_MoodThingy_Widget', 1);
add_shortcode( 'moodthingy', 'print_moodthingy_shortcode' );

?>