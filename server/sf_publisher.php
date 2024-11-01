<?php
/**
* SourcedFrom Publisher Settings for WordPress
* Version: 0.1
* Author: SourcedFrom
* Author URI: http://sourcedfrom.com
*
* @package SourcedFrom
*/

/**
* Last Mod: 11 July 2009.
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


if( function_exists( 'is_site_admin' ) ) { 
	define("SF_USE_MAIN_BLOG_AS_API_SERVER", false); 										//Set to false if want WPMU (if) individual blogs to act as The source server.		
}

add_action('delete_post','sf_delete_post_reference', 1, 1);


function sf_edit_publisher_onSave() {

	if ( isset($_POST['action']) ) {
		if ( $_POST['action'] == 'edit_sourcedfrom_publisher' ) {
			$name = $_POST['sf_publisher_name'];
			$publisher_email = $_POST['sf_publisher_email'];
			$token = md5(uniqid(rand(), true));	//random 32 character (a 128 bit hex number) identifier as token.

			if ( $name != '' && $publisher_email != '' && is_email($publisher_email) != false ) {
				global $wpdb, $table_prefix, $blog_id;
	
				//If WPMU
				if( function_exists( 'is_site_admin' ) ) {
					$table_prefix_sf = preg_replace('#'.$blog_id.'_#', '', $table_prefix);
				} else {
				//single WP
					$table_prefix_sf = $table_prefix;
				}
				
				$is_edit = false;
				
				if ( isset($_POST['is_edit']) ) {
					//edit
					//check this name or email address is not already being used by another Publisher
					$already_has_this = $wpdb->get_row( $wpdb->prepare("SELECT publisher_id, name, publisher_email FROM ".$table_prefix_sf."publishers WHERE (name = '".$name."' OR publisher_email = '".$publisher_email."') AND publisher_id != '".$_POST['publisher_id']."'") );
					if ( $already_has_this ) {
						sf_display_msg('error', 'This name or email address is already being used by a Publisher! Please use unique details.');
						return;
					}
					$is_edit = true;
					$wpdb->query( $wpdb->prepare( "UPDATE ".$table_prefix_sf."publishers SET name = '$name', publisher_email = '$publisher_email' WHERE publisher_id = '".$_POST['publisher_id']."' LIMIT 1" ) );
					$res = $wpdb->get_row( $wpdb->prepare("SELECT token FROM ".$table_prefix_sf."publishers WHERE publisher_id = '".$_POST['publisher_id']."' LIMIT 1") );
					$token = $res->token;	
					$wpdb->flush();
				} else {
					//new
					if (!get_option('sf_installed')) { sf_install(); }	//make sure SF is installed 
					//check this name or email address is not already being used by another Publisher
					$already_has_this = $wpdb->get_row( $wpdb->prepare("SELECT name, publisher_email FROM ".$table_prefix_sf."publishers WHERE name = '".$name."' OR publisher_email = '".$publisher_email."'") );
					if ( ! $already_has_this  ) {
						$wpdb->query( $wpdb->prepare( "INSERT INTO ".$table_prefix_sf."publishers ( `name` ,`publisher_email` ,`token` ) VALUES ( '$name', '$publisher_email', '$token' )" ) );
						$wpdb->flush();
					} else {
						sf_display_msg('error', 'This name or email address is already being used by a Publisher! Please use unique details.');
						return;
					}
				} //end if (edit)
				
				if ( isset($_POST['sf_email_publisher_access_address']) ) {
					if ( $_POST['sf_email_publisher_access_address'] == 'true' ) {
						
						//If WPMU
						if( function_exists( 'is_site_admin' ) ) {
							if ( SF_USE_MAIN_BLOG_AS_API_SERVER ) {
								$sf_api_server_address = get_blog_details(1)->siteurl;
							} else {
								$sf_api_server_address = get_bloginfo('url');
							}
						} else {
							$sf_api_server_address = get_bloginfo('url');
						}
							
						//NOTE: If going from fancy to ugly permalinks then Publishers access addresses won't work. If this is likley then only use the ugly $access_address!
						if ( get_option('permalink_structure') == '' ) {
							//default (ugly) permalink
							$access_address = $sf_api_server_address . '?feed=api&token='.$token.'&account='.urlencode(get_option('sf_account_name'));
						} else {
							//fancy permalink
							$access_address = $sf_api_server_address . '/feed/api?token='.$token.'&account='.urlencode(get_option('sf_account_name'));
						}
							
						$email_subject = '['.get_option('sf_account_name').'] Your Publishing Access Details';
						$email_message = 
"You have been registered as a Publisher for ".get_option('sf_account_name').".

Your Access Address is: ".$access_address."
(Treat this as a secret like a password!)

Copy/Paste your Access Address (above) into your WordPress (administration dashboard) -> Settings -> SourcedFrom Client.

If you don't have the SourcedFrom plugin for WordPress installed you can download it for free at: http://sourcedfrom.com/wordpress/.

";
						$email_home = preg_replace('#www\.#i', '', $_SERVER['SERVER_NAME']);
						$email_from_header = 'From: '.get_option('sf_account_name').' <wordpress@'.$email_home.'>';	
						wp_mail($publisher_email, $email_subject, $email_message, $email_from_header);
					}
				} //end if email to publisher
				
				//remember if check send publisher email.
				$sf_send_publisher_email = 'true';
				if ( !isset($_POST['sf_email_publisher_access_address']) ) {
					$sf_send_publisher_email = 'false';
				}
				sf_update_usermeta('sf_send_publisher_email', $sf_send_publisher_email);
				
				if ($is_edit) {
					sf_display_msg('message', '<strong>Publisher updated.</strong>');
					return 'updated';
				} else {
					return 'saved';
				}
				
			} else {
				sf_display_msg('error', 'You must enter a name and email address!');
				return;
			}

		}
	}

} //end func.


function sf_edit_publisher() {
//check: get_option('sf_account_name') has some data else goto sevrer_settings and display error message, you must first enter a name!

echo'<div class="wrap">
';
	if (SF_WP_VER_GTE_27) {	
		echo '	<div id="icon-users" class="icon32"><br /></div>
		';
	}

	$action_text = 'Add New Publisher';
	$action_text_button = 'Add Publisher';
	$publisher_name = '';
	$publisher_email = '';
	$is_edit_from_field = '';
	
	//If edit
	if ( isset($_GET['publisher_id']) ) {
		global $wpdb, $table_prefix, $publisher_id, $blog_id;
		
		//If WPMU
		if( function_exists( 'is_site_admin' ) ) {
			$table_prefix_sf = preg_replace('#'.$blog_id.'_#', '', $table_prefix);
		} else {
		//single WP
			$table_prefix_sf = $table_prefix;
		}		
		
		$res = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$table_prefix_sf."publishers WHERE publisher_id = '".intval($_GET['publisher_id'])."'") );

		$publisher_id = $res->publisher_id;
		$publisher_name = $res->name;
		$publisher_email = $res->publisher_email;

		$is_edit_from_field = '<input type="hidden" name="is_edit" value="1" />
<input type="hidden" name="publisher_id" value="'.$publisher_id.'" />
';
		
		$wpdb->flush();
		
		$action_text = 'Edit Publisher';
		$action_text_button = 'Edit Publisher';
	}

	$send_email_checked = '';
	global $user_ID;
	$sf_wp_prefs = get_usermeta($user_ID,'sf_send_publisher_email');
	if ( $sf_wp_prefs == 'true' ) {
		$send_email_checked = ' checked="checked"';
	}	
	
echo '
<h2>SourcedFrom / ' .$action_text.'</h2>'; ?>

<div class="clear" style="padding-top:5px"></div>
<?php
	global $sf_display_msg;
	if ( $sf_display_msg ) { echo $sf_display_msg; }
?>
<form name="form1" method="post" action="">
<input type="hidden" name="option_page" value="Add Publisher" />
<input type="hidden" name="action" value="edit_sourcedfrom_publisher" />
<input type="hidden" name="_wp_http_referer" value="/wordpress/wp-admin/users.php" />
<?php echo $is_edit_from_field; ?>

<table class="form-table">

<tr class="form-field form-required">
<th scope="row"><label for="sf_publisher_name">Nam<span style="display:none;">_</span>e (required)</label></th>
<td><input type="text" name="sf_publisher_name" id="sf_publisher_name" value="<?php echo $publisher_name; ?>" />
<span class="setting-description">Name or Organization.</span></td>
</tr>

<tr class="form-field form-required">
<th scope="row"><label for="sf_publisher_email">E-mai<span style="display:none;">_</span>l (required)</label></th>
<td><input type="text" name="sf_publisher_email" id="sf_publisher_email" value="<?php echo $publisher_email; ?>" /></td>
</tr>

<tr valign="top">
<th scope="row">E-mail access details</th>
<td><label for="sf_email_publisher_access_address"><input type="checkbox" name="sf_email_publisher_access_address" id="sf_email_publisher_access_address" value="true"<?php echo $send_email_checked; ?> /> Send an E-mail to this Publisher with their access address details.</label></td>
</tr>

</table>
<p class="submit"><input type="submit" name="Submit" class="button-primary" value="<?php echo $action_text_button; ?>" /></p>
</form>
</div>

<?php
} //end func.


function sf_display_publishers() {

	if ( isset($_GET['action']) ) {
		if ( $_GET['action'] == 'publisher_access' && isset($_GET['publisher']) && isset($_GET['publisher_name']) ) {
			sf_display_blogs_publishers_access($_GET['publisher'], $_GET['publisher_name']);
			return;
		}
	}

	global $wpdb, $table_prefix;

	//If delete Publisher
	if ( isset($_POST['action']) ) {
		if ( $_POST['action'] == 'delete_sourcedfrom_publisher' && $_POST['publisher_id'] != '' ) {
			global $blog_id;
			//If WPMU
			if( function_exists( 'is_site_admin' ) ) {
				$table_prefix_sf = preg_replace('#'.$blog_id.'_#', '', $table_prefix);
			} else {
			//single WP
				$table_prefix_sf = $table_prefix;
			}			
			foreach($_REQUEST['publisher_id'] as $publisher_id) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM ".$table_prefix_sf."publishers WHERE publisher_id = '".$publisher_id."' LIMIT 1" ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM ".$table_prefix_sf."publishers_blogs WHERE publisher_id = '".$publisher_id."'" ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM ".$table_prefix_sf."publishers_posts WHERE publisher_id = '".$publisher_id."'" ) );
			}
			$wpdb->flush();
			$_GET['action'] = 'done_delete';
		}
	} //end if delete publisher.
	//If delete Publisher check
	if ( isset($_REQUEST['action']) ) {
		if ( $_REQUEST['action'] == 'delete_publisher' ) {
		$dont_delete = false;
		if ( isset($_POST['publishers'][0]) && isset($_POST['delete_selected']) ) {
			if ( $_POST['delete_selected'] != '1' ) {
				$dont_delete = true;
			}
		}
		if ( isset($_GET['publisher']) ) {
			$_POST['publishers'][0] = $_GET['publisher'].'-'.$_GET['publisher_name'];
		}
		if ( empty($_POST['publishers']) ) { $dont_delete = true; }
		
		if ($dont_delete === false) {
				echo'<div class="wrap">
				';
					if (SF_WP_VER_GTE_27) {	
						echo '	<div id="icon-users" class="icon32"><br /></div>
						';
					}
				echo '
				<h2>SourcedFrom Delete Publisher</h2>
				<div class="clear" style="padding-top:5px"></div>
				<form name="form1" method="post" action="">
				<input type="hidden" name="option_page" value="Publishers" />
				<input type="hidden" name="action" value="delete_sourcedfrom_publisher" />
				<input type="hidden" name="_wp_http_referer" value="/wordpress/wp-admin/users.php" />
				';
				$auth_to_delete = true;
				foreach($_POST['publishers'] as $key) {
					//echo "Publisher_ID: " . substr($key, 0, strpos($key, '-')) . "\n";
					//echo "Publisher_NAME: " . substr($key, strpos($key, '-') + 1). "\n"; 
					if ( sf_authorized_to_delete(substr($key, 0, strpos($key, '-'))) ) {
						echo '			<input type="hidden" name="publisher_id[]" value="'.substr($key, 0, strpos($key, '-')).'" />'. "\n";
						echo '<p>Are you sure you wish to delete <strong>'.substr($key, strpos($key, '-') + 1).' (ID: '.substr($key, 0, strpos($key, '-')).')</strong> and all references to them?</p>'. "\n";	
					} else {
						$auth_to_delete = false;
						echo '<p>Your can\'t delete <strong>'.substr($key, strpos($key, '-') + 1).' (ID: '.substr($key, 0, strpos($key, '-')).')</strong> because they have access to other blogs too!</p>';
					}
				}
				if ($auth_to_delete) {
					echo '
					<p class="submit"><input type="submit" name="yes_delete" class="button-primary" value="Confirm Deletion" /></p>
					</form>
					</div>
					';
				} else {
					echo '</form></div>';
				}
				return;	
			}
		}
	} //end if delete publisher check.

	$page_display = 1;
	if (isset($_GET['page_display'])) {
		$page_display = intval($_GET['page_display']);
	}
	if ($page_display != '') { $limit_from = $page_display - 1; } //$paged = 1;
	$limit_from = $limit_from . "0";

	//If search
	$search_term = '';
	global $blog_id;
	//If WPMU
	if( function_exists( 'is_site_admin' ) ) {
		$table_prefix_sf = preg_replace('#'.$blog_id.'_#', '', $table_prefix);
	} else {
	//single WP
		$table_prefix_sf = $table_prefix;
	}	
	if ( isset($_GET['publishersearch']) ) {
		$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM ".$table_prefix_sf."publishers 
			WHERE name LIKE '".urldecode($_GET['publishersearch'])."%%'
			ORDER BY publisher_id ASC 
			LIMIT " . $limit_from . ", 10";
			$search_term = $_GET['publishersearch'];
	} else {
		$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM ".$table_prefix_sf."publishers 
			ORDER BY publisher_id ASC 
			LIMIT " . $limit_from . ", 10";
	}

	$publishers = $wpdb->get_results( $wpdb->prepare($sql), ARRAY_A  );

	$sqlTotal = "SELECT FOUND_ROWS();";
	$resTotal = $wpdb->get_results( $wpdb->prepare($sqlTotal), ARRAY_A );
	$total_publishers = $resTotal[0]['FOUND_ROWS()'];
	if ( $total_publishers >= 0 ) { 
	} else {
		$total_publishers = 0;
	}
		
	$nav_link_base = sf_currentURL();
	$nav_link_base = preg_replace('/sourcedfrom\.php.*/i', "sourcedfrom.php", sf_currentURL());
	$nav_link_base = preg_replace('/&page_display=.*/i', "", $nav_link_base);
	$nav_link_base_edit = preg_replace('/page=.*/i', "page=sourcedfrom_plugin_add_publisher", sf_currentURL());
	
	if ( isset($_GET['message']) ) {
		if ($_GET['message'] == 'new') {
			//work out newly created publisher by getting their id for link in message.
			$sql = "SELECT * FROM ".$table_prefix_sf."publishers ORDER BY publisher_id DESC LIMIT 1";
			$publisher_res = $wpdb->get_results( $wpdb->prepare($sql), ARRAY_A  );
			$publisher_id = $publisher_res[0]['publisher_id'];
			$publisher_name = $publisher_res[0]['name'];
			sf_display_msg('message', '<strong>Now <a href="'.$nav_link_base.'&amp;action=publisher_access&amp;publisher='.$publisher_id.'&amp;publisher_name='.urlencode($publisher_name).'">setup access for '.$publisher_name.'</a></strong>.');
		}
	}

	$wpdb->flush();
	
echo '<div class="wrap">
';
	
	$search_box_css = '';
	if (SF_WP_VER_GTE_27) {	
		echo '	<div id="icon-users" class="icon32"><br /></div>
		';
	} else {
		$search_box_css = ' style="margin-top:-2.85em;"';
	}
	
echo '<h2>SourcedFrom / Publishers</h2>

<form id="posts-filter" class="search-form" action="" method="get">
<input type="hidden" name="page" value="sourcedfrom_plugin_publisher" />
<p id="post-search" class="search-box"'.$search_box_css.'>
	<input type="text" class="search-input" id="user-search-input" name="publishersearch" value="'.$search_term.'" />
	<input type="submit" value="Search Publishers" class="button" />
</p>
</form>
';

//if none found.
if ($total_publishers == 0) {
	echo '<p><br/>No publishers found.</p></div></div>';
	return;
}
	
$add_cb_js = '';
$add_tablenav_css = '';
$add_column_avatar_img_css = '';
if (!SF_WP_VER_GTE_27) {
	$add_cb_js = ' onclick="checkAll(document.getElementById(\'publishersFM\'));"';
	echo "<script type='text/javascript' src='".get_bloginfo('wpurl')."/wp-admin/js/forms.js?ver=20080317'></script>
	";
	$add_tablenav_css = ' style="height:2em;margin-bottom:7px;"';
	$add_column_avatar_img_css =  ' style="float:left;margin-right:9px;margin-top:3px;"';
} else {
	$add_tablenav_css = ' style="margin-top:2.73em"';
}


echo '
<div class="tablenav"'.$add_tablenav_css.'>
';

	//If more than 10 entries returned show nav pages.
	if ( $total_publishers > 10 ) {
		sf_page_toggle(intval($total_publishers), $total_publishers, $page_display);
	} //end if more than 10 entries.

	
	if ( isset($_GET['publishersearch']) ) {
		echo '<p><a href="admin.php?page=sourcedfrom_plugin_publisher">&larr; Back to All Publishers</a></p>';
	}
	
echo '
</div>
';

global $sf_display_msg;
if ( $sf_display_msg ) { echo $sf_display_msg; }

echo '
<form id="publishersFM" action="" method="post">
<input type="hidden" name="action" value="delete_publisher" />
<table class="widefat post fixed" cellspacing="0">
	<thead>
		<tr>
			<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox"' .$add_cb_js.' /></th>
			<th scope="col" id="username" class="manage-column column-title" style="">Name</th>
			<th scope="col" id="email_sf" class="manage-column column-email" style="">Email</th>
			<th scope="col" id="accessaddress_sf" class="manage-column column-accessaddress" style="">Access Address</th>
		</tr>
	</thead>';
	if (SF_WP_VER_GTE_27) {
echo '	
	<tfoot>
		<tr>
			<th scope="col" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
			<th scope="col" class="manage-column column-title" style="">Name</th>
			<th scope="col" class="manage-column column-email" style="">Email</th>
			<th scope="col" class="manage-column column-accessaddress" style="">Access Address</th>
		</tr>
	</tfoot>';
	}
echo '
	<tbody id="users" class="list:user user-list">
	
	';
	
	if ( function_exists('is_ssl') ) {
		if ( is_ssl() ) {
			$gravatar_host = 'https://secure.gravatar.com/avatar/'; 
		} else {
			$gravatar_host = 'http://www.gravatar.com/avatar/';
		}
	} else {
		$gravatar_host = 'http://www.gravatar.com/avatar/';
	}	
	
	$c = 0;
	//Foreach publisher returned:
	foreach ($publishers as $publisher): 

		//Set class alternate for table.
		if ( $c === 0 ) {
			$call_alternate = 'alternate ';
			$c = 1;
		} else {
			$call_alternate = '';
			$c = 0;
		}

		$buddy_icon_style = '';
		if (!SF_WP_VER_GTE_27) {
			$buddy_icon_style = ' padding-right: 5px;';
		}

		//If WPMU
		if( function_exists( 'is_site_admin' ) ) {
			if ( SF_USE_MAIN_BLOG_AS_API_SERVER ) {
				$sf_api_server_address = get_blog_details(1)->siteurl;
			} else {
				$sf_api_server_address = get_bloginfo('url');
			}
		} else {
			$sf_api_server_address = get_bloginfo('url');
		}

		//NOTE: If going from fancy to ugly permalinks then Publishers access addresses won't work. If this is likley then only use the ugly $access_address!
		if ( get_option('permalink_structure') == '' ) {
			//default (ugly) permalink
			$access_address = $sf_api_server_address . '?feed=api&amp;token='.$publisher['token'].'&account='.urlencode(get_option('sf_account_name'));
		} else {
			//fancy permalink
			$access_address = $sf_api_server_address . '/feed/api?token='.$publisher['token'].'&amp;account='.urlencode(get_option('sf_account_name'));
		}

	echo '
<tr id="author-'.$publisher['publisher_id'].'" class="'.$call_alternate.'">

<th scope="row" class="check-column"><input type="checkbox" name="publishers[]" class="administrator user_cb" value="'.$publisher['publisher_id']."-".$publisher['name'].'" /></th>

	<td class="username column-username"><strong><a href="'.$nav_link_base.'&amp;action=publisher_access&amp;publisher='.$publisher['publisher_id'].'&amp;publisher_name='.urlencode($publisher['name']).'"><img src="'.$gravatar_host.md5(strtolower($publisher['publisher_email'])).'?s=32&amp;d=http://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536" alt="" class="avatar avatar-32 photo" height="32" width="32"'.$add_column_avatar_img_css.' />' . $publisher['name'] .'</a></strong>
		<div class="row-actions">
			<span class=\'edit\'>
				<span class=\'view\'><a href="'.$nav_link_base.'&amp;action=publisher_access&amp;publisher='.$publisher['publisher_id'].'&amp;publisher_name='.urlencode($publisher['name']).'" title="Can access these blogs for publishing" rel="permalink">Access these blogs</a></span> | <span class=\'view\'><a href="'.$nav_link_base_edit.'&amp;publisher_id='.$publisher['publisher_id'].'" title="Edit '. $publisher['name'] .'" rel="permalink">Edit</a></span> | <span class=\'view\'><a href="'.$nav_link_base.'&amp;action=delete_publisher&amp;publisher='.$publisher['publisher_id'].'&amp;publisher_name='.urlencode($publisher['name']).'" title="Delete '. $publisher['name'] .'" rel="permalink">Delete</a></span>
			</span>
		</div>	
	</td>
	
	<td class="author column-author"><a href="mailto:'.$publisher['publisher_email'].'">'. $publisher['publisher_email'] .'</a></td>
	
	<td class="author column-author"><a href="'.$access_address.'">'. $access_address .'</a></td>
	
</tr>
';
	endforeach; //end foreach publisher.
	
echo '
	</tbody>
</table>

<div class="tablenav">

<div class="alignleft actions">
<select name="delete_selected">
<option value="" selected="selected">Bulk Actions</option>
<option value="1">Delete</option>
</select>
<input type="submit" value="Apply" name="delete_submit" id="doaction2" class="button-secondary action" />
</div>

<div class="alignright">
';
	//If more than 10 entries returned show nav pages.
	if ( $total_publishers > 10 ) {
		sf_page_toggle(intval($total_publishers), $total_publishers, $page_display);
	} //end if more than 10 entries.
	
	echo '
</div>

</div>
</form>

<div class="clear" style="padding-top:10px"></div>
</div>
';

} //end func.



function sf_display_blogs_publishers_access($publisher_id, $publisher_name) {
	global $wpdb, $table_prefix, $blog_id;

	//If WPMU
	if( function_exists( 'is_site_admin' ) ) {
		$table_prefix_sf = preg_replace('#'.$blog_id.'_#', '', $table_prefix);
	} else {
	//single WP
		$table_prefix_sf = $table_prefix;
	}		
	
	//If save
	if ( isset($_POST['action']) ) {
		if ( $_POST['action'] == 'publisher_access' ) {
		
			if ( isset($_POST['publisher_access_selected']) ) {
				if ( $_POST['publisher_access_selected'] == '1' ) {
					$blog_semiarray_delete = '';
					$y = 0;
					foreach($_POST['blogs_all'] as $blog_all) {
						//print "blog_all: $blog_all\n";
						if ($y === 0) {
							$blog_semiarray_delete = $blog_semiarray_delete ."publisher_id = '".$_POST['publisher_id']."' AND blog_id = '".$blog_all."'";
						} else {
							$blog_semiarray_delete = $blog_semiarray_delete ." OR publisher_id = '".$_POST['publisher_id']."' AND blog_id = '".$blog_all."'";
						}
						$y++;
					}
			
					//first delete old references to be overwriten
					//print "sql: DELETE FROM ".$table_prefix_sf."publishers_blogs WHERE ".$blog_semiarray_delete."\n";
					$wpdb->query( $wpdb->prepare( "DELETE FROM ".$table_prefix_sf."publishers_blogs WHERE ".$blog_semiarray_delete) );
					sf_display_msg('message', '<strong>Publishers\' access updated.</strong>');
				}
			}
			
			//now save new if any selected
			if ( isset($_POST['blogs'][0]) && isset($_POST['publisher_access_selected']) ) {
				if ( $_POST['publisher_access_selected'] == '1' ) {
					//SAVE
					foreach($_POST['blogs'] as $blog) {
						$wpdb->query( $wpdb->prepare( "INSERT INTO ".$table_prefix_sf."publishers_blogs (publisher_id, blog_id) VALUES ('".$_POST['publisher_id']."', '".$blog."')" ) );
					}
					$wpdb->flush();
				}
			}
		}
	} //end if.
	
	
	$page_display = 1;
	if (isset($_GET['page_display'])) {
		$page_display = intval($_GET['page_display']);
	}
	if ($page_display != '') { $limit_from = $page_display - 1; } //$paged = 1;
	$limit_from = $limit_from . "0";

	//If WPMU
	if( function_exists( 'is_site_admin' ) ) {
		//TO DO maybe try 25 blogs rather than just 10!
		if ( $blog_id == 1 ) {
			$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM ".$table_prefix_sf."blogs 
				WHERE archived = '0' AND spam = '0' AND deleted ='0' 
				ORDER BY blog_id ASC 
				LIMIT " . $limit_from . ", 10";
		} else {
			$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM ".$table_prefix_sf."blogs 
				WHERE archived = '0' AND spam = '0' AND deleted ='0' AND blog_id = '$blog_id' 
				ORDER BY blog_id ASC 
				LIMIT " . $limit_from . ", 10";

		}
		$blogs = $wpdb->get_results( $wpdb->prepare($sql), ARRAY_A  );
		//TO DO will need to also call name in wp_#_options [option_name = 'blogname' ANSWER in option_value]
		$sqlTotal = "SELECT FOUND_ROWS();";
		$resTotal = $wpdb->get_results( $wpdb->prepare($sqlTotal), ARRAY_A );
		$total_blogs = $resTotal[0]['FOUND_ROWS()'];
		if ( $total_blogs >= 0 ) { 
		} else {
			$total_blogs = 0;
		}

	} else {
	//If just WP
		$sql = "SELECT option_name, option_value FROM ".$table_prefix_sf."options
			WHERE
			option_name = 'siteurl' OR
			option_name = 'blogname'";
			
		$blogs = $wpdb->get_results( $wpdb->prepare($sql), ARRAY_A  );
		foreach ($blogs as $blog): 
			if ( $blog['option_name'] == 'blogname' ) {
				$blog_name = $blog['option_value'];
			}
			if ( $blog['option_name'] == 'siteurl' ) {
				$blog_address = $blog['option_value'];
			}
		endforeach;

		$total_blogs = 1;
		$blogs = array();
		$blogs[0]['blog_id'] = '1';
		$blogs[0]['name'] = $blog_name;
		$blogs[0]['address'] = $blog_address;
	}

	//TO DO maybe try 25 blogs rather than just 10!
	$blogs_selected = $wpdb->get_results( $wpdb->prepare("SELECT blog_id FROM ".$table_prefix_sf."publishers_blogs WHERE publisher_id = '".$publisher_id."' ORDER BY blog_id ASC"), ARRAY_A  );

	$wpdb->flush();

	$nav_link_base = sf_currentURL();
	$nav_link_base = preg_replace('/sourcedfrom\.php.*/i', "sourcedfrom.php", sf_currentURL());
	$nav_link_base_edit = preg_replace('/page=.*/i', "page=sourcedfrom_plugin", sf_currentURL());

echo'<div class="wrap">
';
	if (SF_WP_VER_GTE_27) {	
		echo '	<div id="icon-users" class="icon32"><br /></div>
		';
	}
	
	
	
echo '
<h2>SourcedFrom Access Blogs</h2>
';

$add_cb_js = '';
$add_tablenav_css = '';
if (!SF_WP_VER_GTE_27) {
	$add_cb_js = ' onclick="checkAll(document.getElementById(\'publishersAccessFM\'));"';
	echo "<script type='text/javascript' src='".get_bloginfo('wpurl')."/wp-admin/js/forms.js?ver=20080317'></script>
	";
	$add_tablenav_css = ' style="height:2em;margin-bottom:7px"';
} else {
	$add_tablenav_css = ' style="margin-top:2.73em"';
}
	
echo '
<div class="tablenav"'.$add_tablenav_css.'>
';

	global $sf_display_msg;
	if ( $sf_display_msg ) { echo $sf_display_msg; }

	//if none found.
	if ($total_blogs == 0) {
		echo '<p><br/>No blogs found.</p></div>';
		return;
	}

echo '<div class="alignright">';	
	
	//If more than 10 entries returned show nav pages.
	if (SF_WP_VER_GTE_27) {
		if ( $total_blogs > 10 ) {
			sf_page_toggle(intval($total_blogs), $total_blogs, $page_display);
		} //end if more than 10 entries.
	}
	
echo '</div>

<h3 id="sf_h3"><strong>'.$publisher_name.'</strong> can publish from these blogs</h3>
</div>

<form id="publishersAccessFM" action="" method="post">
<input type="hidden" name="action" value="publisher_access" />
<input type="hidden" name="publisher_id" value="'.$publisher_id.'" />
<table class="widefat post fixed" cellspacing="0">
	<thead>
		<tr>
			<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox"'.$add_cb_js.' /></th>
			<th scope="col" id="username" class="manage-column column-title" style="">Blog</th>
			<th scope="col" id="accessaddress_sf" class="manage-column column-accessaddress" style="">Address</th>
		</tr>
	</thead>';
	if (SF_WP_VER_GTE_27) {
echo '	
	<tfoot>
		<tr>
			<th scope="col" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
			<th scope="col" class="manage-column column-title" style="">Blog</th>
			<th scope="col" class="manage-column column-accessaddress" style="">Address</th>
		</tr>
	</tfoot>';
	}
echo '	
	<tbody id="users" class="list:user user-list">
	
	';

	$c = 0;
	$x = 1 + $limit_from;
	if ( $blog_id != 1 ) { $x = intval($blogs[0]['blog_id']); }
//var_dump($blogs);	
	//Foreach publisher returned:
	foreach ($blogs as $blog): 
		//if wpmu get blog details:
		if( function_exists( 'is_site_admin' ) ) {	
			$blog_mu = $wpdb->get_results( $wpdb->prepare("SELECT option_value FROM ".$table_prefix_sf.$blog['blog_id']."_options WHERE option_name = 'blogname'"), ARRAY_A  );	 
			//$blog['blog_id'] = $blog_id;	
			$blog['name'] = $blog_mu[0]['option_value'];
			$blog['address'] = 'http://'.$blog['domain'].$blog['path'];
		}
	
/* print "blogID: " . $blog['blog_id'] . "\n";	
print "blogname: " . $blog['name'] . "\n";
print "blogaddress: " . $blog['address'] . "\n"; */
	
		//Set class alternate for table.
		if ( $c === 0 ) {
			$call_alternate = 'alternate ';
			$c = 1;
		} else {
			$call_alternate = '';
			$c = 0;
		}

		$buddy_icon_style = '';
		if (!SF_WP_VER_GTE_27) {
			$buddy_icon_style = ' padding-right: 5px;';
		}
		
		$is_blog_selected = '';
		if ( isset($blogs_selected) ) {
			foreach ($blogs_selected as $blog_selected): 
				if ( $blog_selected['blog_id'] == $blog['blog_id'] ) {
					$is_blog_selected = ' checked="checked"';
				}
			endforeach;
		}

	echo '
<tr id="blog-'.$blog['blog_id'].'" class="'.$call_alternate.'">

<th scope="row" class="check-column"><input type="checkbox" name="blogs[]" class="administrator user_cb" value="'.$blog['blog_id'].'"'.$is_blog_selected.' /></th>

	<td class="username column-username"><input type="hidden" name="blogs_all[]" value="'.$blog['blog_id'].'" /><strong><a href="'.$blog['address'].'">' . $blog['name'] .'</a></strong></td>
	
	<td class="author column-author"><a href="'.$blog['address'].'">'. $blog['address'] .'</a></td>
	
</tr>
';
		$x++;
	endforeach; //end foreach publisher.
	$wpdb->flush();
echo '
	</tbody>
</table>

<div class="tablenav">

<div class="alignleft actions">
<select name="publisher_access_selected">
<option value="" selected="selected">Bulk Actions</option>
<option value="1">Allow Access</option>
</select>
<input type="submit" value="Apply" name="publisher_access_submit" id="doaction2" class="button-secondary action" />
</div>

<div class="alignright">
';
	//If more than 10 entries returned show nav pages.
	if (SF_WP_VER_GTE_27) {
		if ( $total_blogs > 10 ) {
			sf_page_toggle(intval($total_blogs), $total_blogs, $page_display);
		} //end if more than 10 entries.
	}
	echo '
</div>

</div>
</form>

<div class="clear" style="padding-top:10px"></div>
</div>
';

} //end func.


function sf_authorized_to_delete($publisher_id) {
//if wpmu and not main blog make sure this publisher has only access tp this blog otherw
	//If WPMU
	if( function_exists( 'is_site_admin' ) ) {
		global $blog_id;
		if ( $blog_id == 1 ) {
			//is main blog administrator
			return true;
		} else {
			//check only has access to this blog
			global $wpdb, $table_prefix, $blog_id;
			$table_prefix_sf = preg_replace('#'.$blog_id.'_#', '', $table_prefix);
			$blogs_selected = $wpdb->get_results( $wpdb->prepare("SELECT blog_id FROM ".$table_prefix_sf."publishers_blogs WHERE publisher_id = '".$publisher_id."' ORDER BY blog_id ASC"), ARRAY_A  );
			$wpdb->flush();	
			if ( count($blogs_selected) > 1 ) { 
				return false;
			} else {
				foreach ($blogs_selected as $blog_selected): 
					if ( $blog_selected['blog_id'] == $blog_id ) {
						return true;
					}
				endforeach;
			}				
		}
	} else {
		//is single wp so must be true
		return true;
	}
	return false;
} //end func.


//on WP delete post/page, delete post/page reference in wp_publishers_post table
function sf_delete_post_reference($post_id) {
	
	if ($post_id === NULL) { return; }
	
	if ( get_post_meta($_GET['post'], '_sf_option_post_access') ) {
		if ( get_post_meta($_GET['post'], '_sf_option_post_access', true) == '0' ) {
			global $wpdb, $table_prefix;	
			$wpdb->query( $wpdb->prepare( "DELETE FROM ".$table_prefix."publishers_posts WHERE post_id = '".$post_id."'" ) );
			$wpdb->flush();
		}
	}

	return;
} //end func.

?>
