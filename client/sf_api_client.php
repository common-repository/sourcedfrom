<?php
/**
* SourcedFrom API Server for WordPress
* Version: 0.4
* Author: SourcedFrom
* Author URI: http://sourcedfrom.com
*
* @package SourcedFrom
*/

/**
* Last Mod: 5 Aug 2009.
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


function sf_display_accounts() {

	if ( isset($_GET['access']) ) {
		sf_display_blogs();
		return;
	}
	
	global $user_ID;
	$sf_wp_prefs = get_usermeta($user_ID,'sf_access_url');
	if ( !$sf_wp_prefs ) { sf_client_settings(); return; }		//if not setup goto 

	$page_display = 1;
	if (isset($_GET['page_display'])) {
		$page_display = intval($_GET['page_display']);
	}	
	
	//for each line in sf client access address settings
	$accounts = explode("\n", $sf_wp_prefs);

	if ( count($accounts) === 0 ) {
		sf_client_settings();
		return;
	}
	if ( count($accounts) === 1 ) {
		$_GET['access'] = $accounts[0];
		sf_display_blogs();
		return;
	}
	
	//What entry type: Post or Page.
	if ( strpos(sf_currentURL(), 'sourcedfrom_plugin_page') ) {
		//is Page
		$entry_type = 'Page';
		$icon_type = 'icon-edit-pages';
		$logout_script = 'edit-pages.php';
	} else {
		//is Post
		$entry_type = 'Post';
		$icon_type = 'icon-edit';
		$logout_script = 'edit.php';
	}	

	$nav_link_base = sf_currentURL();
	$nav_link_base = preg_replace('/sourcedfrom\.php.*/i', "sourcedfrom.php", sf_currentURL());
	$nav_link_base = preg_replace('/&page_display=.*/i', '', $nav_link_base);
	//$nav_link_base_edit = preg_replace('/page=.*/i', "page=sourcedfrom_plugin", sf_currentURL());
	
echo'<div class="wrap">
';
	$add_tablenav_css = '';
	if (SF_WP_VER_GTE_27) {	
		echo '	<div id="'.$icon_type.'" class="icon32"><br /></div>
		';
		$add_tablenav_css = ' style="margin-top:0"';
	} else {
		$add_tablenav_css = ' style="height:2em;margin-bottom:7px"';
	}
	
echo '
<h2>SourcedFrom Accounts</h2>
<div class="tablenav"'.$add_tablenav_css.'>
';	
	
	//If more than 10 entries returned show nav pages.
	$total_accounts = count($accounts);
	if ( $total_accounts > 10 ) {
		sf_page_toggle(intval($total_accounts), $total_accounts, $page_display);
	} //end if more than 10 entries.

echo '
</div>

<table class="widefat post fixed" cellspacing="0">
	<thead>
		<tr>
			<th scope="col" id="username" class="manage-column column-title" style="">Account</th>
		</tr>
	</thead>';
	if (SF_WP_VER_GTE_27) {
echo '		
	<tfoot>
		<tr>
			<th scope="col" class="manage-column column-title" style="">Account</th>
		</tr>
	</tfoot>
	';
	}
echo '	
	<tbody id="users" class="list:user user-list">
	';
	
	$c = 0;
	if ( $page_display > 1 ) {
		$i = ($page_display - 1) * 10;
	} else {
		$i = $page_display - 1;
	}
	$x = $i + 10;
	if ( $x > count($accounts) ) {
		$x = count($accounts);
	}
	
	//Foreach publisher returned:
	for($i; $i < $x; ++$i):

	$account = $accounts[$i];
	//foreach ($accounts as $account):

		preg_match('@account\=([^&]+)@i', $account, $matches);
		if (!$matches) {
			$matches[1] = '';
		}
		$account_name = urldecode($matches[1]);
		if ($account_name == '') {
			$account_name = $account;
		}
		
		//$account = preg_replace('@account\=([^&]+)@i', '', $account);
		//Set class alternate for table.
		if ( $c === 0 ) {
			$call_alternate = 'alternate ';
			$c = 1;
		} else {
			$call_alternate = '';
			$c = 0;
		}

	echo '
<tr class="account '.$call_alternate.'">

	<td class="username column-username"><strong><a href="'.$nav_link_base.'&amp;access='.urlencode($account).'">'. $account_name .'</a></strong>
		<div class="row-actions">
			<span class=\'edit\'>
				<span class=\'view\'><a href="'.$nav_link_base.'&amp;access='.urlencode($account).'" title="Access this account" rel="permalink">Access</a></span>
			</span>
		</div>	
	</td>
	
</tr>
';
	endfor; //end foreach publisher.
	
echo '
	</tbody>
</table>

<div class="tablenav">

<div class="alignright">
';
	//If more than 10 entries returned show nav pages.
	if ( $total_accounts > 10 ) {
		sf_page_toggle(intval($total_accounts), $total_accounts, $page_display);
	} //end if more than 10 entries.

	echo '
</div>

</div>

<div class="clear" style="padding-top:10px"></div>
</div>
';

	return;

} //end func.


function sf_display_blogs() {
	
	//What entry type: Post or Page.
	if ( strpos(sf_currentURL(), 'sourcedfrom_plugin_page') ) {
		//is Page
		$entry_type = 'Page';
		$icon_type = 'icon-edit-pages';
		$logout_script = 'edit-pages.php';
	} else {
		//is Post
		$entry_type = 'Post';
		$icon_type = 'icon-edit';
		$logout_script = 'edit.php';
	}
	
	if ( !isset($_GET['access']) ) { return false; }
	$account = $_GET['access'];
	
	if ( !isset($_GET['account']) ) {
		$_GET['account'] = '';
	}
	
	if ( isset($_GET['blog']) ) {
		sf_display_entries();
		return;
	}
	
	$page_display = 1;
	if (isset($_GET['page_display'])) {
		$page_display = intval($_GET['page_display']);
	}

	//if only one blog goto sf_display_entries

	$results_xml = sf_getBlogs($page_display);
	if( $results_xml === false ) { return false; }
	
	//if returned method getentries go to:
	if ( $results_xml->method == 'sf.getBlogEntries' ) {
		sf_display_entries($results_xml->blog_id);
		return;
	}
	
	
	global $user_ID;

	$nav_link_base = sf_currentURL();
	$nav_link_base = preg_replace('/sourcedfrom_plugin.*/i', "sourcedfrom_plugin", sf_currentURL());
	$nav_link_base = preg_replace('/&/i', '&amp;', $nav_link_base);
 
	echo '<div class="wrap">
	';

	$add_tablenav_css = '';
	$add_column_avatar_img_css = '';
	if (SF_WP_VER_GTE_27) {	
			echo '	<div id="'.$icon_type.'" class="icon32"><br /></div>
			';
		$add_tablenav_css = ' style="margin-top:0;"';		
	} else {
		$add_tablenav_css = ' style="height:2em;margin-bottom:7px;"';
		$add_column_avatar_img_css =  ' style="float:left;margin-right:9px;margin-top:3px;"';
	}	
	
	echo '
<h2>SourcedFrom / '.$results_xml->account.'</h2>
';

	global $user_ID;
	$sf_wp_prefs = get_usermeta($user_ID,'sf_access_url');
	if ( !$sf_wp_prefs ) { sf_client_settings(); return; }		//if not setup goto 

	//for each line in sf client access address settings
	$accounts = explode("\n", $sf_wp_prefs);
	if ( count($accounts) > 1 ) {
		echo '

<ul class="subsubsub">
<li><a href="'.$nav_link_base.'">Accounts</a> /</li>
<li><a href="'.$nav_link_base.'&amp;access='.urlencode($_GET['access']).'" class="current">'.$results_xml->account.'</a></li></ul>
';
	}
echo '
<div class="tablenav"'.$add_tablenav_css.'>
<div class="alignleft actions">
<h3 id="sf_h3">Showing <strong>'.$results_xml->account.'</strong> blogs</h3>
</div>
';

	//If more than 10 entries returned show nav pages.
	$total_entries = intval(preg_replace('/,/','',$results_xml->total_blogs)); 		//remove comma devides.
	if ( $total_entries > 10 ) {
		sf_page_toggle(intval($total_entries), $results_xml->total_blogs, $page_display);
	} //end if more than 10 entries.

	$mode_link = sf_currentURL();
	$mode_link = preg_replace('/&mode=list/i', '', sf_currentURL());
	$mode_link = preg_replace('/&mode=excerpt/i', '', sf_currentURL());
	$mode_link = preg_replace('/&/i', '&amp;', $mode_link);
	if ( preg_match('/mode=excerpt/i', sf_currentURL()) ) {
		$mode_link_excerpt = ' class="current"';
	} else {
		$mode_link_list = ' class="current"';
	}

	echo '
</div>

<table class="widefat post fixed" cellspacing="0">
	<thead>
		<tr>
			<th scope="col" id="title" class="manage-column column-title" style="">Blog</th>
			<th scope="col" id="author_sf" class="manage-column column-blogsaccount" style="">Account</th>
		</tr>
	</thead>';
	if (SF_WP_VER_GTE_27) {
echo '		
	<tfoot>
		<tr>
			<th scope="col"  class="manage-column column-title" style="">Blog</th>
			<th scope="col"  class="manage-column column-blogsaccount" style="">Account</th>
		</tr>
	</tfoot>
	';
	}
echo '	
	<tbody>
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
	//Foreach entry returned:
	//if none found.
	if ($results_xml->total_blogs > 0) {
		foreach ($results_xml->blogs->blog as $blog) {

			//Set class alternate for table.
			if ( $c === 0 ) {
				$call_alternate = 'alternate ';
				$c = 1;
			} else {
				$call_alternate = '';
				$c = 0;
			}
			
			echo '
<tr id=\'blog-'. $blog->id .'\' class=\''.$call_alternate.'author-self status-draft iedit\' valign="top">
			<td class="post-title column-title"><strong><a class="row-title" href="'.$nav_link_base.'&amp;access='.urlencode($account).'&amp;account='.urlencode($_GET['account']).'&amp;blog='.$blog->id.'" title="View entries in '.$blog->title.'">'. $blog->title .'</a></strong>
				<div class="row-actions">
					<span class=\'edit\'>
						<a href="'.$nav_link_base.'&amp;access='.urlencode($account).'&amp;account='.urlencode($_GET['account']).'&amp;blog='.$blog->id.'" title="View entries in '.$blog->title.'">View entries</a> | </span><span class=\'view\'><a href="'. $blog->link .'" title="Visit Blog &quot;'. $blog->title .'&quot;" rel="permalink" target="_blank">Visit Blog</a>
					</span>
				</div>	
			</td>
			<td class="author column-author"><img src="'.$gravatar_host.$blog->owner_avatar.'?s=32&amp;d=http://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536" alt="" class="avatar avatar-32 photo" height="32" width="32"'.$add_column_avatar_img_css.' />' . $blog->owner .'</td>
</tr>
';

		} //end foreach entry.
	}
	
	//if none found.
	if ($results_xml->total_blogs == 0) {
		echo '
<tr id=\'author-000\' class=\'author-self status-draft iedit\' valign="top">
	<td class="post-title column-author"><strong>No blogs found</strong></td>
</tr>
';
	} //end if none found
	
	echo '
	</tbody>
</table>

<div class="tablenav">

<div class="alignright">
';
	//If more than 10 entries returned show nav pages.
	if ( $total_entries > 10 ) {
		sf_page_toggle(intval($total_entries), $results_xml->total_blogs, $page_display);
	} //end if more than 10 entries.

	echo '
</div>

</div>

<div class="clear" style="padding-top:10px"></div>
</div>
';

} //end func.


function sf_display_entries($blog_id = '') {

	//What entry type: Post or Page.
	if ( strpos(sf_currentURL(), 'sourcedfrom_plugin_page') ) {
		//is Page
		$entry_type = 'Page';
		$icon_type = 'icon-edit-pages';
		$logout_script = 'edit-pages.php';
	} else {
		//is Post
		$entry_type = 'Post';
		$icon_type = 'icon-edit';
		$logout_script = 'edit.php';
	}

	$page_display = 1;
	if (isset($_GET['page_display'])) {
		$page_display = intval($_GET['page_display']);
	}
	
	$results_xml = sf_getBlogEntries($page_display, $blog_id);
	if( $results_xml === false ) { echo '<div class="wrap" style="padding-top:1em"><div class="error" style="padding:5px;"><strong>SourcedFrom Error</strong>: The source server is not responding properly at this time, please try again.</div></div>'; return; }
	
	global $user_ID;
	$sf_wp_prefs = get_usermeta($user_ID,'sf_access_url');
	if ( !$sf_wp_prefs ) { sf_client_settings(); return; }		//if not setup goto 

	$nav_link_base = sf_currentURL();
	$nav_link_base = preg_replace('/sourcedfrom_plugin.*/i', "sourcedfrom_plugin", sf_currentURL());
	$nav_link_base = preg_replace('/&/i', '&amp;', $nav_link_base);
	if ( $entry_type == 'Page' ) {
		$nav_link_base_accounts = preg_replace('/sourcedfrom_plugin_page.*/i', 'sourcedfrom_plugin_page', $nav_link_base);
	} else {
		$nav_link_base_accounts = preg_replace('/sourcedfrom_plugin\..*/i', 'sourcedfrom_plugin', $nav_link_base);
	}	
	
	
	$current_link = sf_currentURL();
	$current_link = preg_replace('#&#i', '&amp;', $current_link);
	
	$current_access_url = $_GET['access'];
	$current_access_url = preg_replace('#&account=#i', '&amp;account=', $current_access_url);
	$current_access_url = preg_replace('#\\r#', '', $current_access_url);
	$current_access_url = preg_replace('#\\n#', '', $current_access_url);

	$search_term = '';
	if ( isset($_GET['search']) ) {
		$search_term = urldecode($_GET['search']);
	}
	
	if ( !isset($_GET['account']) ) {
		$_GET['account'] = '';
	}	
 
	echo '<div class="wrap">
	';
	$search_box_css = '';
	if (SF_WP_VER_GTE_27) {	
		echo '	<div id="'.$icon_type.'" class="icon32"><br /></div>
		';
	} else {
		$search_box_css = ' style="margin-top:-2.85em;"';
	}
	
	if (!isset($_GET['blog'])) {
		$_GET['blog'] = '101';
	}
	
	echo '
<h2>SourcedFrom '.$entry_type.'s</h2>
';
	if ( $results_xml->format != 'feed' ) {
		echo '
<form id="posts-filter" class="search-form" action="" method="get">
<input type="hidden" name="page" value="sourcedfrom_plugin" />
<input type="hidden" name="access" value="'.$current_access_url.'" />
<input type="hidden" name="account" value="'.urlencode($_GET['account']).'" />
<input type="hidden" name="blog" value="'.$_GET['blog'].'" />
<p id="post-search" class="search-box"'.$search_box_css.'>
	<input type="text" class="search-input" id="post-search-input" name="search" value="'.$search_term.'" />
	<input type="submit" value="Search '.$entry_type.'s" class="button" />
</p>
</form>
';
	} else {
		if (isset($_GET['warn'])) {
			$set_val = '0';
			if ( $_GET['warn'] == 'gv' ) {
				$set_val = 'gv';
			}
			delete_usermeta($user_ID, 'sf_feed_warn');
			sf_update_usermeta('sf_feed_warn', $set_val);
		}
		$sf_feed_warn = get_usermeta($user_ID,'sf_feed_warn');
		if ( $sf_feed_warn != '0' ) {
			if ( trim(urldecode($_GET['access'])) == 'http://globalvoicesonline.org/feed/' ) {
				if ( trim($sf_feed_warn) != 'gv' ) {
					echo '<div class="updated" style="padding:5px;"><strong>Note</strong>: These articles from <a href="http://globalvoicesonline.org/" target="_blank">Global Voices</a> are available for publishing under Creative Commons. <a href="'.$current_link.'&amp;warn=gv">Ok, close message</a>.</div>';
				}
			} else { 
				if ( trim($sf_feed_warn) != '0' ) {
					echo '<div class="updated" style="padding:5px;"><strong>Note</strong>: Make sure you have the content creators permission prior to publishing. <a href="'.$current_link.'&amp;warn=0">Ok, don\'t warn again</a>.</div>';
				}
			}
		}	
	}	

	echo '
<ul class="subsubsub">';
	//for each line in sf client access address settings
	$accounts = explode("\n", $sf_wp_prefs);
	if ( count($accounts) > 1 ) {
		echo '
<li><a href="'.$nav_link_base_accounts.'">Accounts</a> /</li>
';
	}
	
	echo '
<li><a href="'.$nav_link_base.'&amp;access='.urlencode($_GET['access']).'">'.$results_xml->account.'</a> /</li>
<li><a href="'.$current_link.'" class="current">'.$results_xml->title.'</a></li></ul>
';

$add_tablenav_css = '';
$add_column_title_css = '';
$add_column_avatar_img_css = '';
if (!SF_WP_VER_GTE_27) {
	$add_tablenav_css = ' style="height:2em;margin-bottom:7px;"';
	$add_column_title_css =  ' style="width:40%;"';
	$add_column_avatar_img_css =  ' style="float:left;margin-right:9px;margin-top:3px;"';
}
	
echo '
<div class="tablenav"'.$add_tablenav_css.'>
';

echo '

<div class="alignleft actions">
<h3 id="sf_h3">'.$entry_type.'s available for publishing</h3>
</div>
';

	//If more than 10 entries returned show nav pages.
	$total_entries = intval(preg_replace('/,/','',$results_xml->total_entries)); 		//remove comma devides.
	if ( $total_entries > 10 ) {
		sf_page_toggle(intval($total_entries), $results_xml->total_entries, $page_display, $blog_id);
	} //end if more than 10 entries.

	$mode_link = sf_currentURL();
	$mode_link = preg_replace('/&mode=list/i', '', sf_currentURL());
	$mode_link = preg_replace('/&mode=excerpt/i', '', sf_currentURL());
	$mode_link = preg_replace('/&/i', '&amp;', $mode_link);
	if ( preg_match('/mode=excerpt/i', sf_currentURL()) ) {
		$mode_link_list= '';
		$mode_link_excerpt = ' class="current"';
	} else {
		$mode_link_list = ' class="current"';
		$mode_link_excerpt = '';
	}

	$mode_style = ''; $mode_style_dash = '';
	$mode_img_src_list = '../wp-includes/images/blank.gif';
	$mode_img_src_excerpt = '../wp-includes/images/blank.gif';
	if (!SF_WP_VER_GTE_27) {
		if ( $total_entries > 10 ) {
			$mode_style = ' style="text-align: right; float: right; padding: 5px 9px 0 0;"';
		} else {
			$mode_style = ' style="text-align: right; float: right; padding-top: 5px;"';
		}
		$mode_img_src_list = get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/images/mode_list.gif';
		$mode_img_src_excerpt = get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/images/mode_excerpt.gif';
	}
	
	echo '
<div class="view-switch"'.$mode_style.'>
	<a href="'.$mode_link.'"><img'.$mode_link_list.' id="view-switch-list" src="'.$mode_img_src_list.'" width="20" height="20" title="List View" alt="List View" style="border:none;" /></a>
	<a href="'.$mode_link.'&amp;mode=excerpt"><img'.$mode_link_excerpt.' id="view-switch-excerpt" src="'.$mode_img_src_excerpt.'" width="20" height="20" title="Excerpt View" alt="Excerpt View" style="border:none;" /></a>
</div>

</div>

<table class="widefat post fixed" cellspacing="0">
	<thead>
		<tr>
			<th scope="col" id="title" class="manage-column column-title"'.$add_column_title_css.'>'.$entry_type .'</th>
			';
		if ( $results_xml->format != 'feed' ) {
			echo '
			<th scope="col" id="account_sf" class="manage-column column-entriesauthor" style="">Account</th>';
		}
			echo '
			<th scope="col" id="author_sf" class="manage-column column-entriesauthor" style="">Author</th>
			<th scope="col" id="date_sf" class="manage-column column-sfdate" style="">Date</th>
		</tr>
	</thead>';
	if (SF_WP_VER_GTE_27) {
echo '	
	<tfoot>
		<tr>
			<th scope="col"  class="manage-column column-title" style="">'.$entry_type .'</th>
			';
		if ( $results_xml->format != 'feed' ) {
			echo '
			<th scope="col"  class="manage-column column-entriesauthor" style="">Account</th>';
		}
			echo '
			<th scope="col"  class="manage-column column-entriesauthor" style="">Author</th>
			<th scope="col"  class="manage-column column-sfdate" style="">Date</th>
		</tr>
	</tfoot>';
	}
echo '		
	<tbody>
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
	//Foreach entry returned:
	foreach ($results_xml->entries->entry as $entry) {

		//Set class alternate for table.
		if ( $c === 0 ) {
			$call_alternate = 'alternate ';
			$c = 1;
		} else {
			$call_alternate = '';
			$c = 0;
		}
			
		$show_entry_excerpt = '';
		if (isset($_GET['mode'])) {
			if ( $_GET['mode'] == 'excerpt' ) {
				$show_entry_excerpt = '<p>'.$entry->description.'</p>
				';
			}
		}
if ( $results_xml->format == 'feed' ) {
	$entry->id = urlencode($entry->guid);
}	
		echo '
<tr id=\'post-'. $entry->id .'\' class=\''.$call_alternate.'author-self status-draft iedit\' valign="top">
		<td class="post-title column-title"><strong><a class="row-title" href="'.$nav_link_base.'&amp;access='.urlencode($_GET['access']).'&amp;account='.urlencode($_GET['account']).'&amp;blog='.$results_xml->blog_id.'&amp;blog_account='.urlencode($entry->account).'&amp;post='.$entry->id.'&amp;publish=true&amp;type='.strtolower($entry_type).'" title="Publish this '.$entry_type .'">'. $entry->title .'</a></strong>
			'.$show_entry_excerpt.'
			<div class="row-actions">
				<span class=\'edit\'>
					<a href="'.$nav_link_base.'&amp;access='.urlencode($_GET['access']).'&amp;account='.urlencode($_GET['account']).'&amp;blog='.$results_xml->blog_id.'&amp;blog_account='.urlencode($entry->account).'&amp;post='.$entry->id.'&amp;publish=true&amp;type='.strtolower($entry_type).'" title="Publish this '.$entry_type .'">Publish</a> | </span><span class=\'view\'><a href="'. $entry->guid .'" title="View &quot;'. $entry->title .'&quot;" rel="permalink" target="_blank">View</a>
				</span>
			</div>	
		</td>
		';
if ( $results_xml->format != 'feed' ) {
	echo '
		<td class="author column-author"><img src="'.$gravatar_host.$entry->account_avatar.'?s=32&amp;d=http://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536" alt="" class="avatar avatar-32 photo" height="32" width="32"'.$add_column_avatar_img_css.' />' . $entry->account .'</td>';
}
	echo '
		<td class="author column-author"><img src="'.$gravatar_host.$entry->author_avatar.'?s=32&amp;d=http://www.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536" alt="" class="avatar avatar-32 photo" height="32" width="32"'.$add_column_avatar_img_css.' />'. $entry->author .'</td>
		<td class="date column-date"><abbr title="'. $entry->pubDate .'">'. $entry->pubDate_pretty .'</abbr></td>
</tr>
';

	} //end foreach entry.
	
	//if none found.
	if ($results_xml->total_entries == 0) {
		echo '
<tr id=\'author-000\' class=\'author-self status-draft iedit\' valign="top">
	<td class="post-title column-author"><strong>No entries found</strong></td>
</tr>
';
	} //end if none found
	
	echo '
	</tbody>
</table>

<div class="tablenav">

<div class="alignright">
';
	//If more than 10 entries returned show nav pages.
	$total_entries = intval(preg_replace('/,/','',$results_xml->total_entries)); 			//remove comma devides.
	if ( $total_entries > 10 ) {
		sf_page_toggle(intval($total_entries), $results_xml->total_entries, $page_display, $blog_id);
	} //end if more than 10 entries.

	echo '
</div>

</div>

<div class="clear" style="padding-top:10px"></div>

</div>
';

} //end func.


function sf_client_settings() {
echo'<div class="wrap">
';
	if (SF_WP_VER_GTE_27) {	
		echo '	<div id="icon-options-general" class="icon32"><br /></div>
		';
	}
	
	global $user_ID;
	$sf_wp_prefs = get_usermeta($user_ID,'sf_access_url');			
	
echo '
<h2>SourcedFrom Access Settings (Client)</h2>';

	global $sf_display_msg;
	if ( $sf_display_msg ) { echo $sf_display_msg; }
	
	//v0.2 add default Global Voices feed on activate
	$add_feed_text = '';
	if (SF_WP_VER_GTE_27) { 
		$add_feed_text = 'or a feed (rss, atom, rdf) to publish from ';
	}

echo '<div class="clear" style="padding-top:5px"></div>

<form name="form1" method="post" action="">
<input type="hidden" name="option_page" value="SourcedFrom Access" />
<input type="hidden" name="action" value="update_sourcedfrom_access" />
<input type="hidden" name="_wp_http_referer" value="/wordpress/wp-admin/options-reading.php" />

<table class="form-table">
<tr valign="top">
<th scope="row">Enter your Access Address '.$add_feed_text.'(url)</th>
<td>
<p><label for="access_url">Can add multiple addresses if have, one per line.</label></p>
<p><textarea rows="4" name="access_url" cols="50" id="access_url" class="large-text code">'.$sf_wp_prefs.'</textarea></p>
<span class="setting-description">Treat your Access Address as a secret, like a password!</span>
</td>
</tr>
</table>
<p class="submit"><input type="submit" name="Submit" class="button-primary" value="Save Changes" /></p>
</form>
</div>
';

} //end func.


//On Saving entry
function sf_save_entry($post_id, $post) {

	//TO DO:
	//If WP user is contributor and save post pending review and sets user to someone else, following save shows WP error 
	//'you are not allowed to edit this post'. Create some redirect solution back to posts list following save if different user set.
	if ( $post->post_type === 'revision' ) {
		return;
	}

	//if saving as server with access setting
	if ( isset($_POST['sf_option_post_access']) ) {
		global $wpdb, $table_prefix;
		if ( $_POST['sf_option_post_access'] == '0' ) {
			//update db with this post auth
			$wpdb->query( $wpdb->prepare( "DELETE FROM ".$table_prefix."publishers_posts WHERE post_id = '".$post_id."'" ) );		
			$wpdb->query( $wpdb->prepare( "INSERT INTO ".$table_prefix."publishers_posts ( `publisher_id` ,`post_id` ) VALUES ( '0', '$post_id' )" ) );	//can change publisher_id to single or list of to specifcally authorize.
		} else {
			$wpdb->query( $wpdb->prepare( "DELETE FROM ".$table_prefix."publishers_posts WHERE post_id = '".$post_id."'" ) );
		}
		$wpdb->flush();
		if ( get_post_meta($post_id, '_sf_option_post_access') ) {
			update_post_meta($post_id, '_sf_option_post_access', $_POST['sf_option_post_access']);
		} else {
			add_post_meta($post_id, '_sf_option_post_access', $_POST['sf_option_post_access'], true);
		}
		return;
	}
	
	//only run sf if editing sf entry.
	if ( get_post_meta($post_id, '_sf_access', true) == false ) {
		return;
	}
	
	//remember if check add images checkbox.
	$sf_option_add_images = '';
	if ( isset($_POST['sf_option_add_images']) ) {
		$sf_option_add_images = $_POST['sf_option_add_images'];
	}
	if ( $sf_option_add_images == 'true' ) {
		//If checked, upload any media within entry to the media library.
		require_once('sf_add_media.php');
		sf_add_all_media($post_id);
	} else {
		$sf_option_add_images = 'false';
	}
	
	global $user_ID;
	sf_update_usermeta('sf_option_add_images', $sf_option_add_images);

	//If clicked sf-publish for the first time set the post_author_override_sf_user to none.
	if ( !isset($_POST['post_author_override_sf_user']) ) {
		$new_user_username = '-2';
	} else {
		$new_user_username = substr($_POST['post_author_override_sf_user'], 0, strpos($_POST['post_author_override_sf_user'], '['));
		$new_user_avatar = substr($_POST['post_author_override_sf_user'], strpos($_POST['post_author_override_sf_user'], '[') + 1, 32);
	}

	if ( get_post_meta($post_id, '_sf_author_as_wp_user') ) {
		update_post_meta($post_id, '_sf_author_as_wp_user', $new_user_username);
	} else {
		add_post_meta($post_id, '_sf_author_as_wp_user', $new_user_username, true);
	}

	if ( $new_user_username != '-1' && $new_user_username != '-2' && $new_user_username != '' ) {
		$user_id = sf_add_new_user($new_user_username, $new_user_avatar, $post_id);
	}

	return;

} //end func.


//On Save entry if set SF author for post/page.
function sf_add_new_user($new_user_username, $new_user_avatar, $post_id) {

	require_once( ABSPATH . WPINC . '/registration.php');
	
	$display_name = $new_user_username;
	$new_user_username = sanitize_user($new_user_username);
	
	//Remove any non valid characters (only A to Z 0 to 9 and single spaces) so can be made WP username:
	$new_user_username = preg_replace("/[^a-z0-9\\ ]/i", "", $new_user_username);
	$new_user_username = preg_replace("/  /i", " ", $new_user_username);

	if ( !validate_username($new_user_username) ) { return; }
	
	if ( username_exists($new_user_username) ) {
		//attribute the post/page to this newly created user.
		$user_id = username_exists($new_user_username);
		global $wpdb;
		$wpdb->query("
		UPDATE $wpdb->posts SET post_author = $user_id
		WHERE ID = $post_id");
		return;
	}

	//make a random password, if want to give new wp user actual access to this wp, update password and inform them so they can login.
	$user_pass = mt_rand(1, 9999999); //7 long
	
	//will use this current uses email for new user so can be controlled by this user, can manually change email and inform new user of access to this account if want.
    global $wpdb;
    $user_login = $wpdb->escape($new_user_username);
    $display_name = $wpdb->escape($display_name);
    $nickname = $display_name;
    $userdata = compact('user_login', 'display_name', 'nickname', 'user_pass');
    $user_id =  wp_insert_user($userdata);
	
	if ( !$user_id ) { return false; }
	
	//change the default subscriber role to controibutor.
	$user = new WP_User($user_id);
	$user->set_role("contributor");
	$user = NULL; unset($user);
	
	//now attribute the post/page to this newly created user.
	global $wpdb;
	$wpdb->query("
	UPDATE $wpdb->posts SET post_author = $user_id
	WHERE ID = $post_id");
	
/**
 * Add special user meta data so can facilitate avatars for users when using get_avatar() function in theme templates, wont change in admin users list.
 *	
 * HOW TO:
 * Use the PHP code below in your theme template file as desired (NOTE: Make sure you have the Publishers’s permission to use their avatar!):
 *
<?php
	if ( get_usermeta(get_the_author_ID(), 'sf_author_avatar') ) {
		if ( function_exists('is_ssl') ) {
			if ( is_ssl() ) {
				$gravatar_host = 'https://secure.gravatar.com/avatar/'; 
			} else {
				$gravatar_host = 'http://www.gravatar.com/avatar/';
			}
		} else {
			$gravatar_host = 'http://www.gravatar.com/avatar/';
		}
		echo "<img alt='' src='".$gravatar_host.get_usermeta(get_the_author_ID(), 'sf_author_avatar').'?s=32&d=http%3A%2F%2Fwww.gravatar.com%2Favatar%2Fad516503a11cd5ca435acc9bb6523536'." class='avatar avatar-32 photo avatar-default' height='32' width='32' />"; 
	} else {	
		echo get_avatar(get_the_author_email(), '32');
	}
?>
 */
 
	//add the SF user meta for avatar:
	$wpdb->query( $wpdb->prepare( "
	INSERT INTO $wpdb->usermeta
	( user_id, meta_key, meta_value )
	VALUES ( %d, %s, %s )", 
    $user_id, 'sf_author_avatar', $new_user_avatar ) );

	return $user_id;

} //end func.


?>