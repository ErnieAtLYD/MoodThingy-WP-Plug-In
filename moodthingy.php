<?php 
/*
Plugin Name: MoodThingy
Plugin URI: N/A
Description: Adds a list of emotions to ask how a person is feeling to be used as an instant emotional feedback loop.
Version: 0.5.1 BETA
Author: Ernie Hsiung
E-Mail: ernie@moodthingy.com
Author URI: http://www.moodthingy.com
*/

// Don't forget to:
// LOCK the comments


global $lydl_db_version;
global $moods;
global $moodthingy_server;

$lydl_db_version = "0.6";
$moodthingy_server = "http://www.moodthingy.com";
$moods = array(1 => "Fascinated", 2 => "Amused", 3 => "Sad", 4 => "Angry", 5 => "Bored", 6 => "Excited");
$cookie_duration = 14;

require_once( 'moodthingy-admin.php' );

function moodthingy_init() {
	$options = get_option('moodthingy_wp_options');
	if (!$options) {
		moodthingy_add_default_options();
	} else {
		if ($options['showinpostsondefault'] == 'on') {
			add_filter('the_content', 'add_moodthingy_widget_to_posts');
		}
	}
}

function moodthingy_add_default_options() {	
	$temp = array(
		'showsparkbar' => 'on',
		'showinpostsondefault' => 'on',
		'validkey' => '0'
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
	global $moods;

	if ( moodthingy_website_and_apikey_match() ) {
		$post_id = (int)$post->ID;
		$obj = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}lydl_posts WHERE ID=" . $post_id, ARRAY_A);
		$sum = $obj["emotion_1"]+$obj["emotion_2"]+$obj["emotion_3"]+$obj["emotion_4"]+$obj["emotion_5"]+$obj["emotion_6"];
	
		$widgethtml = '
		<div id="moodthingy-widget">
			<div id="hdr">
				<div id="t">
					<a target="_blank" href="http://www.moodthingy.com" title="MoodThingy"><span>MOODTHINGY</span></a>
				</div>
				<div id="s">How does this post make you feel?</div>
			</div>
			<span id="total"></span><span id="voted"></span>
			<div id="bd" style="">
				<div id="loading" style="display: block;"></div>
				
				<div id="sparkbardiv" style="">
					<div class="sparkbar" style="">
					</div>
				</div>
							
				<ul>
					<li id="mdr-e6"><div class="cell"><div>
						<span class="m">' . $moods[6] .'</span>
						<span class="count"></span><span class="percent"></span>
					</div></div></li>
					<li id="mdr-e1"><div class="cell"><div>
						<span class="m">' . $moods[1] . '</span>
						<span class="count"></span><span class="percent"></span>
					</div></div></li>
					<li id="mdr-e2"><div class="cell"><div>
						<span class="m">' . $moods[2] .'</span>
						<span class="count"></span><span class="percent"></span>
					</div></div></li>
					<li id="mdr-e5"><div class="cell"><div>
						<span class="m">' . $moods[5] . '</span>
						<span class="count"></span><span class="percent"></span>
					</div></div></li>
					<li id="mdr-e3"><div class="cell"><div>
						<span class="m">' . $moods[3] . '</span>
						<span class="count"></span><span class="percent"></span>
					</div></div></li>
					<li id="mdr-e4"><div class="cell"><div>
						<span class="m">' . $moods[4] . '</span>
						<span class="count"></span><span class="percent"></span>
					</div></div></li>
				</ul>
			</div>
		</div>';
		return $widgethtml;
	} else {
		return '';
	}
	
}

function add_moodthingy_widget_to_posts($content) {
	if (is_single()) { $content .= moodthingy_get_widget_html(); }
	return $content;
}

function print_moodthingy_widget () {
	echo moodthingy_get_widget_html();
}

function lydl_js_header() {

  // Define custom JavaScript function
	global $wp_query, $moodthingy_server;
	wp_reset_query();
	$options = get_option('moodthingy_wp_options');
	
	// if we're on a page or post, load the script
	if ( is_single() ) {
		// create security token
		$nonce = wp_create_nonce('lydl-moodthingy');	
	
		$id = $wp_query->post->ID;
		?>
		<!-- MoodThingy -->
		<link type="text/css" rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ ) ?>css/style.css?ver=2" />			
		<script type="text/javascript">
		//<![CDATA[
		var MoodThingyAjax = {
		cors: '<?php echo $moodthingy_server; ?>',
		ajaxurl: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
		token: '<?php echo $nonce; ?>',
		siteid: '<?php echo $options['apiname']; ?>',
		api: '<?php echo $options['apikey'] ?>',
		id: <?php echo $id; ?>,
		title: '<?php echo addslashes(get_the_title()); ?>',
		url: '<?php the_permalink(); ?>' 
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
		$votecount = $wpdb->get_var("SELECT emotion_" . $vote . " FROM " . $table_name . " WHERE ID=" . $postid);
		
		$recordexists = (sizeof($votecount) > 0) ? true : false;
		if ($recordexists) {
			$row = $wpdb->update( $table_name, array( 'ID'=>$postid, 'emotion_' . $vote => $votecount+1 ), array( 'ID' => $postid ) );
		} else {
			$row = $wpdb->insert( $table_name, array( 'ID'=>$postid, 'emotion_' . $vote => 1 ) );
		}

		// update popularpostsdatacache table
		$isincache = $wpdb->get_results("SELECT post_ID FROM ".$table_name2." WHERE post_ID = '".$postid."' AND emotion='".$vote."' AND day = CURDATE()");			
		if ($isincache) {
			$result2 = $wpdb->query("UPDATE ".$table_name2." SET votes=votes+1 WHERE post_ID = '".$postid."' AND emotion='".$vote."' AND day = CURDATE()");
		} else {		
			$result2 = $wpdb->query("INSERT INTO ".$table_name2." (post_ID, votes, emotion, day) VALUES ('".$postid."', 1, ".$vote.", CURDATE())");
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

// embed the javascript file that makes the AJAX request
if ( is_admin() ) { 
	wp_enqueue_script('jquery');
} else {
	wp_enqueue_script( 'my-ajax-request', plugin_dir_url( __FILE__ ) . 'js/ajax.js', array( 'jquery' ) );
	
	wp_register_script( 'moodthingy-remote', plugins_url( '/js/easyXDM.min.js', __FILE__ ) );
	wp_enqueue_script( 'moodthingy-remote' );
//		<script type="text/javascript">easyXDM.DomHelper.requiresJSON("json2.js");</script>
	
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
