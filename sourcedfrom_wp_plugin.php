<?php
/*
Plugin Name: SourcedFrom
Plugin URI: http://sourcedfrom.com/wordpress/
Description: Be able to publish posts from feeds and authorized WordPress sites.
Version: 0.5
Author: Mark Ashcroft
Author URI: http://sourcedfrom.com
*/

/**
* Last Mod: 21 Sep 2009.
* 
* Copyright (C) 2009 Mark W. B. Ashcroft
* 
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
* 
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
* 
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
* 
* For more information please contact us: mail [AT] sourcedfrom [DOT] com
*/

//error_reporting(E_ALL);																		//debug

define("SF_WP_VER_GTE_25", version_compare($wp_version, '2.5', '>='));
define("SF_WP_VER_GTE_26", version_compare($wp_version, '2.6', '>='));
define("SF_WP_VER_GTE_27", version_compare($wp_version, '2.7', '>='));
define("SF_WP_VER_GTE_28", version_compare($wp_version, '2.8', '>='));

if ( ! defined( 'WP_CONTENT_DIR' ) ) {														//introduced in wp 2.6
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}
if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );							
}
define( 'WP_MUPLUGIN_DIR', WP_CONTENT_DIR . '/mu-plugins' );

define("SF_USE_AS_CLIENT", false);

add_action('init', 'sf_manage_session', 10);
add_action('init','sf_api_int', 1);															//SourcedFrom api server.

require_once('client/sf_api_client.php');
require_once('client/sf_api_methods.php');													//The SourcedFrom API Method Calls.
if (!SF_USE_AS_CLIENT) { require_once('server/sf_publisher.php'); }

$sf_error_msg = '';																			//global


//Manage the SF Session, create login cookie.
function sf_manage_session() {
	if ( preg_match('#sourcedfrom|sourcedfrom_plugin#i', sf_currentURL()) == false ) { return false; }	//only run if in wp administration area

	global $user_ID;
	
	if ( isset($_POST['action']) ) {
		if ( $_POST['action'] == 'update_sourcedfrom_access' ) {
			delete_usermeta($user_ID, 'sf_access_url');
			sf_update_usermeta('sf_access_url', $_POST['access_url']);
			sf_display_msg('message', '<strong>Settings saved.</strong>');
		}
	}

	//If saved Publisher
	if ( isset($_POST['action']) ) {
		if ( $_POST['action'] == 'edit_sourcedfrom_publisher' ) {
			if ( sf_edit_publisher_onSave() == 'saved' ) {
				$mode_link = preg_replace('/sourcedfrom_plugin_add_publisher/i', 'sourcedfrom_plugin_publisher', sf_currentURL());
				wp_redirect($mode_link.'&message=new');
			}
		}
	}

	//If saved Server settings (so will show: Publishers, New Publisher in SF menu)
	if ( isset($_POST['action']) ) {
		if ( $_POST['action'] == 'update_sourcedfrom_server' ) {
			//must have an account name!
			if ( $_POST['sf_account_name'] != '' ) {
				delete_option("sf_account_name");
				add_option("sf_account_name", $_POST['sf_account_name'], '', 'yes');	
			}
		}
	}
	
	//If Publishing:
	if (isset($_GET['publish'])) {
		$results_xml = sf_getEntry();
		if( $results_xml === false ) { echo '<div class="wrap" style="padding-top:1em"><div class="error" style="padding:5px;"><strong>SourcedFrom Error</strong>: The source server is not responding properly at this time, please try again.</div></div>'; return; }
	
		global $PHP_SELF;
		$PHP_SELF = sf_currentURL();														//Set to prevent wp error.
		include(ABSPATH . 'wp-includes/vars.php');											//Sets $pagenow to prevent wp error in comments.
		require_once('includes/admin.php');
		$_POST['post_status'] = 'draft';
		if ( $_GET['type'] == 'page' ) {
			$_POST['post_type'] = 'page';													//is Page
			unset($_POST['post_category']);
		} else {
			$_POST['post_type'] = 'post';													//is Post
		}	
		$_POST['post_title'] = $results_xml->entries->entry->title;
		$_POST['content'] = $results_xml->entries->entry->content;
		//print "post_content: " . $_POST['content'] ."\n";
		//exit;
		global $user_ID;
		$_POST['user_ID'] = $user_ID;														//Set to prevent wp error.
		$_POST['temp_ID'] = '';																//Set to prevent wp error.
		$entryID = wp_write_post();															//create new draft post or page.
		//store author and entry IDs as post/page meta data so can be retrived on later edits.
		add_post_meta($entryID, '_sf_access', $_GET['access']);
		add_post_meta($entryID, '_sf_blog', intval($_GET['blog']));
		if ( $results_xml->format != 'feed' ) {
			add_post_meta($entryID, '_sf_post', intval($_GET['post']));
		} else {
			add_post_meta($entryID, '_sf_post', $_GET['post']);
		}
		$redirect_url = sf_currentURL();
		$redirect_url = preg_replace('/wp-admin\/.*/i', '', $redirect_url);
		if ( $_GET['type'] == 'page' ) {
			wp_redirect($redirect_url.'wp-admin/page.php?action=edit&post='.$entryID);		//is Page
		} else {
			wp_redirect($redirect_url.'wp-admin/post.php?action=edit&post='.$entryID);		//is Post
		}
	} //end publish.
	
} //end func.


add_action('admin_head', 'sf_addStyleSheet');
if (!SF_WP_VER_GTE_27) {
	add_action('wp_head', 'sf_addStyleSheet_forwardCompat');
}

add_action('admin_menu', 'sourcedfrom_plugin_settings');									//for Sub Menus

add_action('edit_form_advanced', 'sf_options_controls');									//for Posts
add_action('edit_page_form', 'sf_options_controls');										//for Pages

add_action('save_post', 'sf_save_entry', 10, 2);											//On Saving entry


//Set the SF menu items.
function sourcedfrom_plugin_settings() {
	if (!SF_WP_VER_GTE_25) {
		echo '<div class="error" style="padding:5px;"><strong>Error</strong>: The SourcedFrom plugin requires WordPress version 2.5 or newer, please <a href="http://wordpress.org">upgrade your WordPress version</a>.</div>';
		return;
	}
	
	//SF top level menu
	//__FILE__
	$sf_icon = get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/images/transparent_icon.gif';
	if (!SF_WP_VER_GTE_28) {
		$sf_icon = get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/images/sf_menu_static.png';
	}
    add_menu_page('sourcedfrom_plugin', 'SourcedFrom', 1, 'sourcedfrom_plugin', 'sourcedfrom_plugin', $sf_icon);
    add_submenu_page('sourcedfrom_plugin', 'SourcedFrom', 'Posts', 1, 'sourcedfrom_plugin', 'sourcedfrom_plugin');
	add_submenu_page('sourcedfrom_plugin', 'SourcedFrom', 'Pages', 1, 'sourcedfrom_plugin_page', 'sourcedfrom_plugin');

	//only show if there is a valid SF client 'Access Address' entered:
	global $user_ID;
	//if ( get_usermeta($user_ID,'sf_access_url') ) {
	//}
	
	if (!SF_USE_AS_CLIENT) {
		//only administrator can access, ie level 8
		if ( get_option('sf_account_name') ) {
			add_submenu_page('sourcedfrom_plugin', 'SourcedFrom', 'Publishers', 8, 'sourcedfrom_plugin_publisher', 'sf_display_publishers');
			add_submenu_page('sourcedfrom_plugin', 'SourcedFrom', 'Add Publisher', 8, 'sourcedfrom_plugin_add_publisher', 'sf_edit_publisher');
		}
		add_submenu_page('sourcedfrom_plugin', 'SourcedFrom', 'Server Settings', 8, 'sourcedfrom_plugin_server', 'sf_server_settings');		
	}
	
	add_submenu_page('sourcedfrom_plugin', 'SourcedFrom', 'Client Settings', 1, 'sourcedfrom_plugin_client', 'sf_client_settings');
	add_submenu_page('sourcedfrom_plugin', 'SourcedFrom', 'User Guide', 1, 'sourcedfrom_plugin_userguide', 'sf_user_guide');	
	
	if (!get_usermeta($user_ID,'sf_installed_client') && !get_usermeta($user_ID,'sf_access_url')) {
		//add_action('after_plugin_row', 'sf_note_plugin_setup'); //hum, maybe dont need
		//v0.2 add default Global Voices feed on activate
		if (SF_WP_VER_GTE_27) { 
			delete_usermeta($user_ID, 'sf_access_url');
			sf_update_usermeta('sf_access_url', 'http://globalvoicesonline.org/feed/');
		}
		delete_usermeta($user_ID, 'sf_installed_client');
		sf_update_usermeta("sf_installed_client", 'true');
	}

} //end func.


function sf_note_plugin_setup() {
	if (SF_USE_AS_CLIENT) {
		echo '	<tr><td colspan="5" class="plugin-update">
				<strong>Note</strong>: <a href="admin.php?page=sourcedfrom_plugin_client">Setup SourcedFrom client</a>.
				</td></tr>';
	} else {
		echo '	<tr><td colspan="5" class="plugin-update">
				<strong>Note</strong>: SourcedFrom can be setup as a client: <a href="admin.php?page=sourcedfrom_plugin_client">setup client</a>
				and/or as a server: <a href="admin.php?page=sourcedfrom_plugin_server">setup server</a>.
				</td></tr>';
	}
} //end func.


function sf_user_guide() {
	echo '<div style="padding:2em"><p><a href="http://sourcedfrom.com/wordpress/userguide.php" target="_blank">SourcedFrom Plugin for WordPress User Guide &raquo;</a></p></div>';
} //end func.


//Adds the Admin Style Sheet (css file) to make entries listed pritty.
function sf_addStyleSheet() {
	echo '<link rel="stylesheet" type="text/css" media="all" href="'.get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/css/admin-style.css" />'."\n";
}


//Adds wp_head Style Sheet (css file) for Forward compatibility.
function sf_addStyleSheet_forwardCompat() {
	echo '<link rel="stylesheet" type="text/css" media="all" href="'.get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/css/fwdcpt-style.css" />'."\n";
}


//SourcedFrom api feed if server
function sf_api_int() {
	if (!SF_USE_AS_CLIENT) {
		add_feed('api', 'sf_api');
	}
}
function sf_api() {
	include('server/sf_api_server.php'); 
}


//Start the plugin display.
function sourcedfrom_plugin() {
	if (!SF_WP_VER_GTE_25) {
		echo '<div class="error" style="padding:5px;"><strong>Error</strong>: The SourcedFrom plugin requires WordPress version 2.5 or newer, please <a href="http://wordpress.org">upgrade your WordPress version</a>.</div>';
		return;
	}
	
	sf_display_accounts();
	
	return;
} //end func.


//Get current url for log in/out redirection.
function sf_currentURL() {
	//TO DO test and get address working with WPMU sub domain setup
	$pageURL = 'http';
	if ( isset($_SERVER["HTTPS"]) ) {
		if ($_SERVER["HTTPS"] == "on") { $pageURL .= "s"; }
	}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}

	return $pageURL;
} //end func.


//Update user meta data
function sf_update_usermeta($meta_key, $meta_value) {

	if (!SF_WP_VER_GTE_25) { return false; }
	
	global $wpdb;
	global $user_ID;
	
	if ($user_ID == '') { return; }
	if ($user_ID == 0) { return; }
	
	if ( get_usermeta($user_ID,$meta_key) ) {
		$wpdb->query("
		UPDATE $wpdb->usermeta SET `meta_value` = '$meta_value'
		WHERE `user_id` = $user_ID AND `meta_key` = '$meta_key' ");
	} else {
		$wpdb->query( $wpdb->prepare( "
		INSERT INTO $wpdb->usermeta
		( user_id, meta_key, meta_value )
		VALUES ( %d, %s, %s )", 
		 $user_ID, $meta_key, $meta_value) );
	}
	
} //end func.


function sf_server_settings() {
	include_once('server/sf_server_settings.php');
} //end func.


$sf_display_msg = false;	//avaliable globally
function sf_display_msg($type = 'error', $msg = 'new') {
	global $sf_display_msg;
	if ( $type === 'error' ) {
		$sf_display_msg = $sf_display_msg.'<div id="sf_error"><strong>ERROR</strong>: '.$msg.'<br /></div>';
	}
	if ( $type === 'updated_publisher' ) {	
		$sf_display_msg = $sf_display_msg.'<div id="message" class="updated fade">
		<p><strong>User updated.</strong></p>
			<p><a href="users.php">&larr; Back to Authors and Users</a></p>
		</div>';
	}
	if ( $type === 'message' ) {	
		$sf_display_msg = $sf_display_msg.'<div id="message" class="updated fade cs_message">
		<p>'.$msg.'</p>
		</div>';
	}		
} //end func.


function sf_options_controls() {
	//What entry type: Post or Page.
	if ( strpos(sf_currentURL(), 'sourcedfrom_plugin_page') ) {
		$logout_script = 'edit-pages.php';													//is Page
		$isWhatEntryType = 'Page';
	} else {
		$logout_script = 'edit.php';														//is Post
		$isWhatEntryType = 'Post';
	}

	//get authors list:
	$results_xml = sf_getEntry();
	if( $results_xml === false ) { echo '<div class="error" style="padding:5px;"><strong>SourcedFrom Error</strong>: The '.$isWhatEntryType.'s source API server is not responding properly at this time, please try again.</div>'; return; }
	
	if ( !SF_USE_AS_CLIENT && $results_xml === -1 && get_option('sf_installed') ) {
		$set_selected_1 = ' selected="selected"';
		$set_selected_0 = '';
		if ( isset($_GET['post']) ) {
			if ( get_post_meta($_GET['post'], '_sf_option_post_access') ) {
				if ( get_post_meta($_GET['post'], '_sf_option_post_access', true) == '0' ) {
					$set_selected_0 = ' selected="selected"';
					$set_selected_1 = '';
				}
			}
		}
		echo '
		<div id="sf_tag_controls_meta" class="postbox">
			<h3>SourcedFrom - Settings</h3>
			<div class="inside">
				<div class="sf_options_form">
					<!-- entry meta -->
					<div class="post-meta">
						<select name="sf_option_post_access" id="sf_option_post_access">
							<option value="1"'.$set_selected_1.'>Available to Publishers with access to this blog</option>
							<option value="0"'.$set_selected_0.'>Not available</option>
						</select>
					</div>
				</div>
				<div class="clear"></div>
			</div>
		</div>	
';
	}
	
	if ( $results_xml === -1 ) { return; }		//only run sf if editing sf entry.
	
		
	if ( $results_xml->entries->entry->can_modify == 'false' && $results_xml->entries->entry->license_cc == 'false' || $results_xml->entries->entry->license_cc == 'true' && $results_xml->entries->entry->cc_modifications == 'no' ) {
		echo '<div class="updated" style="padding:5px;"><strong>Please Note</strong>: The content in this '.strtolower($isWhatEntryType).' is not permitted to be modified as specified by the owner.</div>';
	}		
	
	//The SF author:
	$this_selected_true = false;
	$this_selected = '';
	$the_sf_author_set = '-1';																//no user.
	if ( get_post_meta($_GET['post'], '_sf_author_as_wp_user') ) {
		$the_sf_author_set = get_post_meta($_GET['post'], '_sf_author_as_wp_user', true);
	}
	if ( $the_sf_author_set == $results_xml->entries->entry->account ) { $this_selected = ' selected=\'selected\''; $this_selected_true = true; }
	$author_options = '					<option value=\''.$results_xml->entries->entry->account.'['.$results_xml->entries->entry->account_avatar.']'.'\''.$this_selected.'>'.$results_xml->entries->entry->account.'</option>';
	$this_selected = '';																	//reset
	if ( $results_xml->entries->entry->author ) {
		if ( $the_sf_author_set == $results_xml->entries->entry->author ) { $this_selected = ' selected=\'selected\''; $this_selected_true = true; }
		$author_options = $author_options . '
								<option value=\''.$results_xml->entries->entry->author.'['.$results_xml->entries->entry->author_avatar.']'.'\''.$this_selected.'>'.$results_xml->entries->entry->author.'</option>';
	 }
	 $this_selected = '';																	//reset
	 if ( $this_selected_true === false ) { $this_selected = ' selected=\'selected\''; }
	 	$author_options = '<option value=\'-1\''.$this_selected.'>Don\'t override use WordPress Author</option>
			'.$author_options; 
	
	$entry_license = '';
	if ( $results_xml->entry->license_descriptive ) {
		$entry_license = $results_xml->entry->license_descriptive;
	}
	
	if ($results_xml->entries->entry->license_cc == 'true') { 
		//what license, set for footer and meta:

		require_once('cc_jurisdictions.php'); 
		$jurisdiction_array = sf_creativecommons_jurisdiction($results_xml->entries->entry->cc_jurisdiction);
		
		$nc = ''; $nd = ''; $sa = ''; $nc_text = '';  $nd_text = '';  $sa_text = '';
		
		if ($results_xml->entries->entry->cc_commercial == 'false') {
			$nc = "nc-";
			$nc_text = "Noncommercial-";
		}
		if ($results_xml->entries->entry->cc_modifications == 'no') {
			$nd = "nd-";
			$nd_text = "No Derivative-";
		}
		if ($results_xml->entries->entry->cc_modifications == 'sa') {
			$sa = "sa-";
			$sa_text = "Share Alike-";
		}
		$cc_license = "by-" . $nc . $nd . $sa;
		if ( substr($cc_license, strlen($cc_license) - 1, 1) == '-' ) {
			$cc_license = substr($cc_license, 0, strlen($cc_license) - 1);
		}
					
		$cc_text = "Attribution-" . $nc_text . $nd_text . $sa_text;
		if ( substr($cc_text, strlen($cc_text) - 1, 1) == '-' ) {
			$cc_text = substr($cc_text, 0, strlen($cc_text) - 1);
		}

		$cc_footer = "CC-" . strtoupper($cc_license);

		if ($results_xml->entries->entry->cc_jurisdiction != 'Unported') { 
			$entry_license = '<p><a rel="license" href="http://creativecommons.org/licenses/'.$cc_license . '/' . $jurisdiction_array['license'] . '/' . $jurisdiction_array['code'] . '/"><img alt="Creative Commons License" style="border-width:0;float:right;padding-left:5px;" src="'.get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/images/cc_'. $cc_license.'.png" /></a>
			This '.strtolower($isWhatEntryType).' is licensed under a <a rel="license" href="http://creativecommons.org/licenses/'.$cc_license . '/' . $jurisdiction_array['license'] . '/' . $jurisdiction_array['code'] .'/">Creative Commons '. $cc_text . ' ' . $jurisdiction_array['country'] . ' ' . $jurisdiction_array['license'] .' License</a>.</p>';
		} else {
			$entry_license = '<p><a rel="license" href="http://creativecommons.org/licenses/'.$cc_license . '/' . $jurisdiction_array['license'] . '/"><img alt="Creative Commons License" style="border-width:0;float:right;padding-left:5px;" src="'.get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/images/cc_'. $cc_license.'.png" /></a>
			This '.strtolower($isWhatEntryType).' is licensed under a <a rel="license" href="http://creativecommons.org/licenses/'.$cc_license . '/' . $jurisdiction_array['license'] . '/">Creative Commons '. $cc_text . ' ' . $jurisdiction_array['country'] . ' ' . $jurisdiction_array['license'] .' License</a>.</p>';
		}
				
	 }
	 
	//if copyright
	if ($results_xml->entries->entry->license_copyright == 'true') { 
		$entry_license = '<p>This entry is licensed under a [&#169; '.$results_xml->entries->entry->copyright_text.'] License.</p>';
	}
	
	//if source was a feed and the entry is no longer listed skip this:
	if ( $results_xml->entries->entry->account != '' ) {
		echo '
	<div id="sf_tag_controls_author" class="postbox">
		<h3>SourcedFrom - Author</h3>
		<div class="inside">
			<div class="sf_options_form">
				<!-- author meta -->
				<div class="meta-options">
					<p>Override the WordPress author with this SourcedFrom author as the \''.$isWhatEntryType.' Author\'</p>
					<select name=\'post_author_override_sf_user\'>
							'.$author_options.'
					</select>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>	
';
	}

if ($results_xml->entries->entry->license_cc == 'true' || $results_xml->entries->entry->license_copyright == 'true' ) { 
	echo '
	<div id="sf_tag_controls_meta" class="postbox">
		<h3>SourcedFrom - License</h3>
		<div class="inside">
			<div class="sf_options_form">
				<!-- entry meta -->
				<div class="post-meta">
							<p>Account: '.$results_xml->entries->entry->account.'. Author: '.$results_xml->entries->entry->author.'. Date: '.$results_xml->entries->entry->pubDate_pretty.'. View the original <a href="'.$results_xml->entries->entry->guid.'">permalink</a>.</p>
							'.$entry_license.'
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>	
';
} else {
//if source was a feed and the entry is no longer listed skip this:
	if ( $results_xml->entries->entry->account != '' ) {
		echo '
	<div id="sf_tag_controls_meta" class="postbox">
		<h3>SourcedFrom - Information</h3>
		<div class="inside">
			<div class="sf_options_form">
				<!-- entry meta -->
				<div class="post-meta">
							<p>Account: '.$results_xml->entries->entry->account.'. Author: '.$results_xml->entries->entry->author.'. Date: '.$results_xml->entries->entry->pubDate_pretty.'. View the original <a href="'.$results_xml->entries->entry->guid.'">permalink</a>.</p>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>	
';
	}
}
	
	//Only works if cURL is found.
	if (!function_exists('curl_init')) {
		echo '
		<div id="sf_tag_controls_options" class="postbox">
			<h3>SourcedFrom - Options</h3>
			<div class="inside">
				<div class="sf_options_form">
					<div class="meta-options">
						<label for="sf_option_upload_images" class="selectit"><input type="checkbox" value="upload" name="sf_option_nada" id="sf_option_upload_images" checked="checked" DISABLED /> Add any images/files within this entry into the Media Library</label>
						<p>(Sorry this feature only works if your web server supports <a href="http://en.wikipedia.org/wiki/CURL" target="_blank">cURL</a>!)</p>
					</div>
				</div>
				<div class="clear"></div>
			</div>
		</div>
';
	} else {
		$add_images_checked = '';
		global $user_ID;
		$sf_wp_prefs = get_usermeta($user_ID,'sf_option_add_images');
		if ( $sf_wp_prefs == 'true' ) {
			$add_images_checked = ' checked="checked"';
		}
		echo '
		<div id="sf_tag_controls" class="postbox">
			<h3>SourcedFrom - Options</h3>
			<div class="inside">		
				<div id="sf_options_form">
					<div class="meta-options">
						<label for="sf_option_add_images" class="selectit"><input type="checkbox" value="true" name="sf_option_add_images" id="sf_option_add_images"'.$add_images_checked.' /> Add any images/files within this entry into the Media Library (excludes embedded objects like flash video)</label>
					</div>
				</div>
				<div class="clear"></div>
			</div>
		</div>
';
	}
	
} //end func.


function sf_page_toggle($total_entries, $total_entries_readable, $page = 1, $blog_id = '') {
	$total_entries = preg_replace("#,#", '', $total_entries); //remove comma devides.
	$total_entries = intval($total_entries);

	//gets the current page number. default is '' = 0,10; 2 = 10,10; 3 = 20,10 (and so on).
	if ( $page == '0' || $page == '' || $page == '1' ) {
		$PN_current_page = 1;
	} else {
		$PN_current_page = $page;
	}

	$PN_total_pages = $total_entries / 10;
	$PN_total_pages = sf_sgn($PN_total_pages)*sf_p_ceil(abs($PN_total_pages), 0);

	$PN_range_start = $PN_current_page - 2;

	$PN_range_finish = $PN_range_start + 6;
	if ($PN_range_finish > $PN_total_pages) {
		$PN_range_finish = $PN_total_pages;
		$PN_range_start = $PN_range_finish - 4;
	}

	if ($PN_range_start < 1) {
		$i = 1;
		$PN_range_start = 1;
	}
	
	$mode_is_excerpt = 0;
	$current_url = sf_currentURL();
	if ( strpos($current_url, "mode=excerpt") ) {
		$current_url = preg_replace('/&mode=excerpt/i', '', $current_url);
		$mode_is_excerpt = 1;
	}
	$current_url = preg_replace('/&page_display=.*/i', '', $current_url);
	if ( $mode_is_excerpt == 1 ) {
		$current_url = $current_url . "&mode=excerpt";
	}
	
	$current_url = preg_replace('/&/i', '&amp;', $current_url);
	
	$current_url = $current_url . "&amp;";
	
	//entry range
	$start_range = $PN_current_page . "0";
	$start_range = intval($start_range) - 10;
	if ($start_range === 0) { 
		$start_range = 1; 
	} else {
		$start_range++;
	}
	$end_range = $start_range + 9;
	if ($end_range > $total_entries) {
		$end_range = $total_entries;
	}
	
	
	if (SF_WP_VER_GTE_27) {
		echo '<div class="tablenav-pages"><span class="displaying-num">Displaying '.$start_range.'&#8211;'.$end_range.' of '.$total_entries_readable.'</span>';
	} else {
		echo '<div class="tablenav-pages"><span class="displaying-num">Displaying '.$start_range.'&#8211;'.$end_range.' of '.$total_entries_readable.'&nbsp;&nbsp;&nbsp;</span>';
	}
	
	if ($PN_current_page > 1) {
		$PN_prev_page_link = $PN_current_page - 1;
		if ($blog_id == '') {
			echo '<a class=\'prev page-numbers\' href="'.$current_url.'page_display='.$PN_prev_page_link.'">&laquo;</a> ';
		} else {
			echo '<a class=\'prev page-numbers\' href="'.$current_url.'blog='.$blog_id.'&amp;page_display='.$PN_prev_page_link.'">&laquo;</a> ';
		}
	}
	
	if ($PN_current_page > 3 && $PN_total_pages > 5) {
		echo '<a class=\'page-numbers\' href="'.$current_url.'page_display=1">1</a> ... ';
	}

	$i = $PN_range_start;
	$x = 5;
	$c = 0;
	while ($c < $x) {
		if ($PN_total_pages == 1) { break; }
		if ($c >= $PN_total_pages) { break; }
		if ($i == $PN_current_page) {
			echo '<span class=\'page-numbers current\'>'.$i.'</span> ';
		} else {
			if ($blog_id == '') {
				echo '<a class=\'page-numbers\' href="'.$current_url.'page_display='.$i.'">' . $i . '</a> ';
			} else {
				echo '<a class=\'page-numbers\' href="'.$current_url.'blog='.$blog_id.'&amp;page_display='.$i.'">' . $i . '</a> ';
			}
		}

		$i++;
		$c++;			
	}

	if ($PN_total_pages > $PN_current_page) {
		$PN_next_page_link = $PN_current_page + 1;
		if ($blog_id == '') {
			echo ' <a class=\'next page-numbers\' href="'.$current_url.'page_display='.$PN_next_page_link.'">&raquo;</a>';
		} else {
			echo ' <a class=\'next page-numbers\' href="'.$current_url.'blog='.$blog_id.'&amp;page_display='.$PN_next_page_link.'">&raquo;</a>';
		}
	}

	echo '</div>
	';

} //end func.


function sf_p_ceil($val, $d) { return ceil($val * pow (10, $d) )/ pow (10, $d) ; }
function sf_sgn($x) { return $x ? ($x>0 ? 1 : -1) : 0; }


function is_sf_plugin_active($blog = 1) {
	//if wpmu get blog details:
	if( function_exists( 'is_site_admin' ) ) {
		$active_plugins = get_blog_option($blog, 'active_plugins');
	} else {
		$active_plugins = get_option('active_plugins');
	}
 	foreach ($active_plugins as $active_plugin) {
		if ( strpos($active_plugin, 'sourcedfrom') ) {
			return true;
		}
	}
	return false;
} //end func.


function sf_install() {
	global $wpdb, $table_prefix, $blog_id;
	
	//If WPMU
	if( function_exists( 'is_site_admin' ) ) {
		$table_prefix_sf = preg_replace('#'.$blog_id.'_#', '', $table_prefix);
	} else {
	//single WP
		$table_prefix_sf = $table_prefix;
	}
	
	$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset COLLATE utf8_general_ci";

	$sql = "CREATE TABLE IF NOT EXISTS ".$table_prefix."publishers_posts (
	 auth_id bigint(20) NOT NULL auto_increment,
	 publisher_id bigint(20) NOT NULL default 0,
	 post_id bigint(20) NOT NULL default 0,
	 PRIMARY KEY (auth_id),
	 KEY publisher_id (publisher_id), 
	 KEY post_id (post_id)
	) $charset_collate";

	$wpdb->query( $sql );

	$sql = "CREATE TABLE IF NOT EXISTS ".$table_prefix_sf."publishers (
	 publisher_id bigint(20) NOT NULL auto_increment,
	 name varchar(255) default NULL,
	 publisher_email varchar(100) NOT NULL default '',
	 token varchar(32) NOT NULL default '',
	 publisher_registered TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	 PRIMARY KEY (publisher_id),
	 KEY token (token)
	 ) $charset_collate";

	$wpdb->query( $sql );
	
	$sql = "CREATE TABLE IF NOT EXISTS ".$table_prefix_sf."publishers_blogs (
	 auth_id bigint(20) NOT NULL auto_increment,
	 publisher_id bigint(20) NOT NULL default 0,
	 blog_id bigint(20) NOT NULL default 0,
	 PRIMARY KEY (auth_id),
	 KEY publisher_id (publisher_id), 
	 KEY blog_id (blog_id)
	) $charset_collate";

	$wpdb->query( $sql );
	
	$wpdb->flush();
	
	add_option("sf_installed", 'true', '', 'yes');
} //end func.


?>