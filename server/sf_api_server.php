<?php
/**
* SourcedFrom API Server for WordPress
* Version: 0.5
* Author: SourcedFrom
* Author URI: http://sourcedfrom.com
*
* @package SourcedFrom
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


	if ( !isset($_GET['token']) ) { sf_fail_api(); }
	if ( !isset($_GET['method']) ) { sf_fail_api('919', 'hello world!'); }

	$the_method = $_GET['method'];
	if ( $the_method == 'sf.getBlogs' || $the_method == 'sf.getBlogEntries' || $the_method == 'sf.searchBlogEntries' || $the_method == 'sf.getEntry') {
		//ok
	} else {
		sf_fail_api();
	}

	//Authenticate publisher.
	$publisher_token = $_GET['token'];
	if ( strlen($publisher_token) != 32 ) { sf_fail_api(); }

	global $wpdb, $table_prefix, $publisher_id, $blog_id;
	
	//TO DO with new BLOGID_CURRENT_SITE definition in wp-config.php should use this in future as $blog_id != BLOGID_CURRENT_SITE
	if ( $blog_id != 1 && get_option('sf_status') == 'false' || get_option('sf_status') == '' ) {
		sf_fail_api('527', 'This blogs API server is turned off.');
	}
	if ( $blog_id == 1 && get_option('sf_status') == 'false' || get_option('sf_status') == '' ) {
		sf_fail_api('528', 'This blogs API server is turned off.');
	}
	
	//If WPMU
	if( function_exists( 'is_site_admin' ) ) {
		$table_prefix_sf = preg_replace('#'.$blog_id.'_#', '', $table_prefix);
	} else {
	//single WP
		$table_prefix_sf = $table_prefix;
	}		

	$res = $wpdb->get_row( $wpdb->prepare("SELECT publisher_id, name, publisher_email FROM ".$table_prefix_sf."publishers WHERE token = '".$publisher_token."'") );
	if ( $res == NULL ) { sf_fail_api('155', 'Invalid Authentication.'); }
	
	//global $publisher_id;
	//global $publisher_name;
	$publisher_id = $res->publisher_id;
	$publisher_name = $res->name;
	$publisher_email = $res->publisher_email;

	$wpdb->flush();

	if ( !$publisher_id ) { sf_fail_api('155', 'Invalid Authentication.'); }
	if ( !$publisher_name ) { sf_fail_api('155', 'Invalid Authentication.'); }
	
	//Get the Avatar and convert to URL source for inclution in API output:
	global $avatar_uri;
	if ( substr(get_option('sf_avatar_uri'), 0, 4) == 'http' ) {
		$avatar_uri = get_option('sf_avatar_uri');
	} elseif ( strpos(get_option('sf_avatar_uri'), '@') ) {
		$avatar_uri = get_option('sf_avatar_uri');
	} else {
		$avatar_uri = get_settings('admin_email');
	}
	
	$avatar_uri = md5( strtolower( $avatar_uri ) );
	
	global $total_entries;	//will be used as global.
	$total_entries = 0;
	
	global $blog_passed;	//will be used as global.
	$blog_passed = '';
	
	global $limit_from;		//will be used as global.
	$limit_from = 0;
	
	global $page_display;	//will be used as global.
	$page_display = 1;
	
	if (isset($_GET['page'])) {
		$page_display = intval($_GET['page']);
	}
	if ($page_display != '') { $limit_from = $page_display - 1; }
	$limit_from = $limit_from . "0";
	
	$search_term = '';
	if ( isset($_GET['search']) ) {
		 $search_term = urldecode($_GET['search']);
	}	
	
	if ( $the_method == 'sf.getBlogEntries' || $the_method == 'sf.searchBlogEntries' || $the_method == 'sf.getEntry' ) {
		if ( $the_method == 'sf.getEntry' ) {
			if ( !isset($_GET['post']) ) { sf_fail_api('127', 'Invalid Request.'); }
			$pageposts = _sf_getEntry();
			sf_resultsFeed($the_method, $publisher_name, $pageposts, $publisher_email);
		} else {
			$pageposts = _sf_getEntries($search_term);
			sf_resultsFeed($the_method, $publisher_name, $pageposts);
		}
	} else {
		$blogs = _sf_getBlogs($publisher_id);
		global $total_entries;
		if ( isset($_GET['returnNextIfSingle']) && $total_entries == 1 ) {
			if ( $_GET['returnNextIfSingle'] == 'true' && $page_display == 1 ) {
				//run get entries and return that.
				global $blog_passed;
				$blog_passed = $blogs[0]['blog_id'];
				$pageposts = _sf_getEntries($search_term);
				$the_method = 'sf.getBlogEntries';
				sf_resultsFeed($the_method, $publisher_name, $pageposts);
			} else {
				sf_resultsFeedBlogs($publisher_id, $publisher_name, $blogs);
			}
		} else {
			sf_resultsFeedBlogs($publisher_id, $publisher_name, $blogs);
		}
	}

function _sf_getEntries($search_term = '') {	
	global $wpdb, $table_prefix, $publisher_id, $blog_id;
	
	//If WPMU
	if( function_exists( 'is_site_admin' ) ) {
		$table_prefix_sf = preg_replace('#'.$blog_id.'_#', '', $table_prefix);
	} else {
	//single WP
		$table_prefix_sf = $table_prefix;
	}
	if (isset($_GET['blog'])) {
		$blog_request = $_GET['blog'];
	} else {
		$blog_request = $blog_id;
	}
	global $blog_passed;
	if ( $blog_passed != '' ) {
		$blog_request = $blog_passed;
	}
	
	//if wpmu get blog details:
	if( function_exists( 'is_site_admin' ) ) {
		$sql = "SELECT option_name, option_value FROM ".$table_prefix_sf.$blog_request."_options WHERE option_name = 'sf_pp' || option_name = 'sf_status' ORDER BY option_id";
		$blog_mu = $wpdb->get_results( $wpdb->prepare($sql), ARRAY_A  );
		$wpdb->flush();
		$sf_pp = ''; $sf_status = '';
		$n=0;
		if ( $blog_mu != NULL ) {
			foreach ($blog_mu as $blog_mu_value) {
				if ( $blog_mu_value['option_name'] == 'sf_pp' ) { $sf_pp = $blog_mu[$n]['option_value']; }
				if ( $blog_mu_value['option_name'] == 'sf_status' ) { $sf_status = $blog_mu[$n]['option_value']; }
				$n++;
			}
		}
		if ( $sf_pp == '' || $sf_status == 'false' || $sf_status == '' ) { $sf_pp = get_option('sf_pp'); }
	} else {
		$sf_pp = get_option('sf_pp');
	}

	//If WPMU
	if( function_exists( 'is_site_admin' ) ) {
		if ( $sf_pp == 'post' ) {
			$sf_pp_sql = $table_prefix_sf.$blog_request."_posts.post_type = 'post'";	
		} elseif ( $sf_pp == 'page' ) {
			$sf_pp_sql = $table_prefix_sf.$blog_request."_posts.post_type = 'page'";
		} else {
			$sf_pp_sql = $table_prefix_sf.$blog_request."_posts.post_type = 'post' 
					OR ".$table_prefix_sf.$blog_request."_posts.post_type = 'page'";
		}
	} else {
		if ( $sf_pp == 'post' ) {
			$sf_pp_sql = $table_prefix_sf."posts.post_type = 'post'";	
		} elseif ( $sf_pp == 'page' ) {
			$sf_pp_sql = $table_prefix_sf."posts.post_type = 'page'";
		} else {
			$sf_pp_sql = $table_prefix_sf."posts.post_type = 'post' 
					OR ".$table_prefix_sf."posts.post_type = 'page'";
		}	
	}
	
	$sf_search_sql = '';
	if ( $search_term != '' ) {
		//Filter seach terms for better results:
		//to stop wp prepare from chucking use %% rather than just %
		$terms=explode(' ', $search_term);
		$clauses=array();
		foreach($terms as $term) {
		    $clean=trim(preg_replace('/[^a-z0-9]/i', '', $term));			//remove any chars you don't want to be searching - adjust to suit
			if (strlen($clean) < 3) { continue; }							//dont include words less than 3 characters
			if ($clean == 'the' || $clean == 'was') { continue; }			//dont include common, get's too many results
			if (substr($clean, 0, 1) == 's') { $clean = ' '.$clean; }		//so wp prepare will pass
		    if (!empty($clean)) {
				//If WPMU
				if( function_exists( 'is_site_admin' ) ) {
					$clauses[]=$table_prefix_sf.$blog_request."_posts.post_title LIKE '%%".mysql_escape_string($clean)."%%' OR ".$table_prefix_sf.$blog_request."_posts.post_content LIKE '%%".mysql_escape_string($clean)."%%'";
				} else {
					$clauses[]=$table_prefix_sf."posts.post_title LIKE '%%".mysql_escape_string($clean)."%%' OR ".$table_prefix_sf."posts.post_content LIKE '%%".mysql_escape_string($clean)."%%'";
				}
			}
		}
		if (!empty($clauses)) {   
		    //concatenate the clauses together with AND or OR, depending on
		    $seach_filter=' ( '.implode(' AND ', $clauses).' ) ';
		}
		//end filter.
		$sf_search_sql = " AND ".$seach_filter." ";
	}
	
	//first check if authorized to access the blog requested:
	global $publisher_id;	
	$res = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$table_prefix_sf."publishers_blogs WHERE publisher_id = '".$publisher_id."' AND blog_id = '".$blog_request."'") );
	if ( !$res ) { sf_fail_api('125', 'You are not authorized to access this blog.'); }

	global $limit_from;
	
	//NOTE: sql checks for string "Sourced from:" within wp_#_posts.post_content to see if post is an already syndicated post, prevents re-syndication.
		
	//If WPMU
	if( function_exists( 'is_site_admin' ) ) {
		//now check if wp_#_publishers_posts table exists:	
		$res = $wpdb->get_results( $wpdb->prepare("SHOW TABLES LIKE \"".$table_prefix_sf.$blog_request."_publishers_posts\"") );
		if ( $res ) { 
			$sql = "SELECT SQL_CALC_FOUND_ROWS ".$table_prefix_sf.$blog_request."_posts . * , ".$table_prefix_sf.$blog_request."_publishers_posts . * 
			FROM ".$table_prefix_sf.$blog_request."_posts 
			LEFT JOIN ".$table_prefix_sf.$blog_request."_publishers_posts ON ".$table_prefix_sf.$blog_request."_posts.ID = ".$table_prefix_sf.$blog_request."_publishers_posts.post_id 
			WHERE 
				".$table_prefix_sf.$blog_request."_posts.post_content NOT LIKE '%%Sourced from:%%' 
				".$sf_search_sql."
			AND
				".$table_prefix_sf.$blog_request."_posts.post_status = 'publish' 
			AND
			( 
				".$table_prefix_sf.$blog_request."_publishers_posts.publisher_id IS NULL 
				OR ".$table_prefix_sf.$blog_request."_publishers_posts.publisher_id > 0 
				AND ".$table_prefix_sf.$blog_request."_publishers_posts.publisher_id = '".$publisher_id."' 
			) AND (
				".$sf_pp_sql."
			)
			ORDER BY ".$table_prefix_sf.$blog_request."_posts.ID DESC 
			LIMIT " . $limit_from . ", 10";
		} else {
			$sql = "SELECT SQL_CALC_FOUND_ROWS * 
			FROM ".$table_prefix_sf.$blog_request."_posts 
			WHERE
				".$table_prefix_sf.$blog_request."_posts.post_content NOT LIKE '%%Sourced from:%%' 
				".$sf_search_sql."
			AND
				".$table_prefix_sf.$blog_request."_posts.post_status = 'publish' 
			AND (
				".$sf_pp_sql."
			)
			ORDER BY ".$table_prefix_sf.$blog_request."_posts.ID DESC 
			LIMIT " . $limit_from . ", 10";
		}
	} else {
			$sql = "SELECT SQL_CALC_FOUND_ROWS ".$table_prefix."posts . * , ".$table_prefix_sf."publishers_posts . * 
			FROM ".$table_prefix_sf."posts 
			LEFT JOIN ".$table_prefix_sf."publishers_posts ON ".$table_prefix_sf."posts.ID = ".$table_prefix_sf."publishers_posts.post_id 
			WHERE 
				".$table_prefix_sf."posts.post_content NOT LIKE '%%Sourced from:%%' 
				".$sf_search_sql."
				AND
				".$table_prefix_sf."posts.post_status = 'publish' 	
				AND (
				".$table_prefix_sf."publishers_posts.publisher_id IS NULL 
				OR ".$table_prefix_sf."publishers_posts.publisher_id > 0 
				AND ".$table_prefix_sf."publishers_posts.publisher_id = '".$publisher_id."' 
			) AND (
				".$sf_pp_sql."
			) 
			ORDER BY ".$table_prefix_sf."posts.ID DESC 
			LIMIT " . $limit_from . ", 10";
	}
	
//print "sql_orig: ".$sql ."\n";
//print "sql_prep: ".$wpdb->prepare($sql) ."\n";
//exit;
	$pageposts = $wpdb->get_results( $wpdb->prepare($sql), ARRAY_A  );
		
	$sqlTotal = "SELECT FOUND_ROWS();";
	$resTotal = $wpdb->get_results( $wpdb->prepare($sqlTotal), ARRAY_A );
	global $total_entries;
	$total_entries = $resTotal[0]['FOUND_ROWS()'];
	if ( $total_entries >= 0 ) { 
	} else {
		$total_entries = 0;
	}
		
	$wpdb->flush();
	return $pageposts;
} //end func.




function _sf_getEntry() {	
	global $wpdb, $table_prefix, $publisher_id, $blog_id;
	
	//If WPMU
	if( function_exists( 'is_site_admin' ) ) {
		$table_prefix_sf = preg_replace('#'.$blog_id.'_#', '', $table_prefix);
	} else {
	//single WP
		$table_prefix_sf = $table_prefix;
	}
	
	if (isset($_GET['blog'])) {
		$blog_request = $_GET['blog'];
	} else {
		$blog_request = $blog_id;
	}	
	
	$post_request = intval($_GET['post']);
	
	//first check if authorized to access the blog requested:
	global $publisher_id;
	$res = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$table_prefix_sf."publishers_blogs WHERE publisher_id = '".$publisher_id."' AND blog_id = '".$blog_request."'") );
	if ( !$res ) { sf_fail_api('126', 'You are not authorized to access this entry.'); }

	//If WPMU
	if( function_exists( 'is_site_admin' ) ) {
		//now check if wp_#_publishers_posts table exists:
		$res = $wpdb->get_results( $wpdb->prepare("SHOW TABLES LIKE \"".$table_prefix_sf.$blog_request."_publishers_posts\"") );
		if ( $res ) { 
			$sql = "SELECT ".$table_prefix_sf.$blog_request."_posts . * , ".$table_prefix_sf.$blog_request."_publishers_posts . * 
			FROM ".$table_prefix_sf.$blog_request."_posts 
			LEFT JOIN ".$table_prefix_sf.$blog_request."_publishers_posts ON ".$table_prefix_sf.$blog_request."_posts.ID = ".$table_prefix_sf.$blog_request."_publishers_posts.post_id 
			WHERE ( 
				".$table_prefix_sf.$blog_request."_publishers_posts.publisher_id IS NULL 
				OR ".$table_prefix_sf.$blog_request."_publishers_posts.publisher_id > 0 
				AND ".$table_prefix_sf.$blog_request."_publishers_posts.publisher_id = '".$publisher_id."' 
			) AND ( 
				".$table_prefix_sf.$blog_request."_posts.ID = '".$post_request."' 
			) AND (
				".$table_prefix_sf.$blog_request."_posts.post_status = 'publish' 				
			) AND (
				".$table_prefix_sf.$blog_request."_posts.post_content NOT LIKE '%%Sourced from:%%' 	
			)";
		} else {
			$sql = "SELECT * 
			FROM ".$table_prefix_sf.$blog_request."_posts 
			WHERE
				".$table_prefix_sf.$blog_request."_posts.ID = '".$post_request."'
			AND (
				".$table_prefix_sf.$blog_request."_posts.post_content NOT LIKE '%%Sourced from:%%' 
			) AND (
				".$table_prefix_sf.$blog_request."_posts.post_status = 'publish' 
			)";
		}
	} else {
			$sql = "SELECT ".$table_prefix_sf."posts . * , ".$table_prefix_sf."publishers_posts . * 
			FROM ".$table_prefix_sf."posts 
			LEFT JOIN ".$table_prefix_sf."publishers_posts ON ".$table_prefix_sf."posts.ID = ".$table_prefix_sf."publishers_posts.post_id 
			WHERE ( 
				".$table_prefix_sf."publishers_posts.publisher_id IS NULL 
				OR ".$table_prefix_sf."publishers_posts.publisher_id > 0 
				AND ".$table_prefix_sf."publishers_posts.publisher_id = '".$publisher_id."' 
			) AND ( 
				".$table_prefix_sf."posts.ID = '".$post_request."' 
			) AND (
				".$table_prefix_sf."posts.post_content NOT LIKE '%%Sourced from:%%' 
			) AND (
				".$table_prefix_sf."posts.post_status = 'publish' 
			)
			";
	}

//print "sql: $sql\n";
//print "sql_prep: ".$wpdb->prepare($sql) ."\n";

	$pageposts = $wpdb->get_results( $wpdb->prepare($sql), ARRAY_A  );
	
	global $total_entries;
	$total_entries = 0;
	if ($pageposts) {
		$total_entries = 1;
	}
		
	$wpdb->flush();
	return $pageposts;
} //end func.


function _sf_getBlogs($publisher_id) {	
	global $wpdb, $table_prefix, $blog_id;
	
	//If WPMU
	if( function_exists( 'is_site_admin' ) ) {
		$table_prefix_sf = preg_replace('#'.$blog_id.'_#', '', $table_prefix);
	} else {
	//single WP
		$table_prefix_sf = $table_prefix;
	}	
	
	global $limit_from;
	
	$sql = "SELECT SQL_CALC_FOUND_ROWS * 
		FROM ".$table_prefix_sf."publishers_blogs 
		WHERE publisher_id = '".$publisher_id."' 
		ORDER BY auth_id ASC 
		LIMIT " . $limit_from . ", 10";
		
	//If WPMU	
	if( function_exists( 'is_site_admin' ) ) {
		//TO DO maybe try 25 blogs rather than just 10!
		$sql = "SELECT SQL_CALC_FOUND_ROWS 
			".$table_prefix_sf."blogs.blog_id, ".$table_prefix_sf."publishers_blogs.blog_id,  ".$table_prefix_sf."blogs.domain, ".$table_prefix_sf."blogs.path 
			FROM ".$table_prefix_sf."blogs, ".$table_prefix_sf."publishers_blogs 
			WHERE ".$table_prefix_sf."publishers_blogs.blog_id = ".$table_prefix_sf."blogs.blog_id AND publisher_id = '".$publisher_id."' AND 
			(".$table_prefix_sf."blogs.archived = '0' AND ".$table_prefix_sf."blogs.spam = '0' AND ".$table_prefix_sf."blogs.deleted ='0') 
			ORDER BY ".$table_prefix_sf."blogs.blog_id ASC 
			LIMIT " . $limit_from . ", 10";

			if ($blog_id != 1) {
				$sql = "SELECT SQL_CALC_FOUND_ROWS 
				".$table_prefix_sf."blogs.blog_id, ".$table_prefix_sf."publishers_blogs.blog_id,  ".$table_prefix_sf."blogs.domain, ".$table_prefix_sf."blogs.path 
				FROM ".$table_prefix_sf."blogs, ".$table_prefix_sf."publishers_blogs 
				WHERE ".$table_prefix_sf."publishers_blogs.blog_id = ".$table_prefix_sf."blogs.blog_id AND publisher_id = '".$publisher_id."' AND 
				(".$table_prefix_sf."blogs.archived = '0' AND ".$table_prefix_sf."blogs.spam = '0' AND ".$table_prefix_sf."blogs.deleted ='0') AND
				(".$table_prefix_sf."blogs.blog_id = '".$blog_id."') 
				ORDER BY ".$table_prefix_sf."blogs.blog_id ASC 
				LIMIT " . $limit_from . ", 10";		
			}

	}
//print "sql: $sql\n";
	$blogs = $wpdb->get_results( $sql, ARRAY_A );

	$sqlTotal = "SELECT FOUND_ROWS();";
	$resTotal = $wpdb->get_results( $sqlTotal, ARRAY_A );
	global $total_entries;
	$total_entries = $resTotal[0]['FOUND_ROWS()'];
	if ( $total_entries >= 0 ) { 
	} else {
		$total_entries = 0;
	}
		
	$wpdb->flush();
	
	return $blogs;
} //end func.	

?>
<?php 

function sf_resultsFeed($the_method, $publisher_name, $pageposts, $publisher_email = '') {
	header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
	echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; 
	
	if (isset($_GET['blog'])) {
		$blog_request = $_GET['blog'];
	} else {
		global $blog_id;
		$blog_request = $blog_id;
	}	
	global $blog_passed;
	if ( $blog_passed != '' ) {
		$blog_request = $blog_passed;
	}
	
?>

<wp_api_server_rsp status="ok" version="0.1">
	
	<method><?php echo $the_method; ?></method>
	<account><![CDATA[<?php echo sf_html2txt(get_option('sf_account_name')); ?>]]></account>
	<account_avatar><![CDATA[<?php global $avatar_uri; echo $avatar_uri; ?>]]></account_avatar>
	<publisher><![CDATA[<?php echo sf_html2txt($publisher_name); ?>]]></publisher>
	<title><![CDATA[<?php
	//if wpmu
	if( function_exists( 'is_site_admin' ) ) {
		echo  get_blog_details( $blog_request )->blogname;
	} else {
		bloginfo_rss('name'); } ?>]]></title>
	<link><?php 
	bloginfo_rss('url') ?></link>
	<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></pubDate>
	<generator>http://sourcedfrom.com/wordpress?wpv=<?php echo get_bloginfo_rss( 'version' ); ?>&amp;sfv=0.1</generator>
	<language><?php echo get_option('rss_language'); ?></language>
	<blog_id><?php echo $blog_request; ?></blog_id>
	<total_entries><?php global $total_entries; echo $total_entries; ?></total_entries>
	<display_range><?php
		global $page_display;
		$range_start = intval(($page_display * 10) - 9);
		echo $range_start . ' - ' . $page_display . '0';
	?></display_range>
	
	<entries>
		<?php
		//if wpmu get blog details:
			if( function_exists( 'is_site_admin' ) ) {
				global $wpdb, $table_prefix, $blog_id;
				$table_prefix_sf = preg_replace('#'.$blog_id.'_#', '', $table_prefix);
				$sql = "SELECT option_name, option_value FROM ".$table_prefix_sf.$blog_request."_options WHERE 
				option_name = 'blogname' OR option_name = 'admin_email' OR option_name = 'sf_account_name' OR option_name = 'sf_sourcedfrom_username' OR option_name = 'sf_avatar_uri' 
				OR option_name = 'sf_can_modify' OR option_name = 'sf_use_copyright' OR option_name = 'sf_copyright_text' OR option_name = 'sf_use_cc'
				OR option_name = 'sf_cc_modifications' OR option_name = 'sf_cc_commercial' OR option_name = 'sf_cc_jurisdiction' OR option_name = 'sf_status'
				ORDER BY option_id";
				$blog_mu = $wpdb->get_results( $wpdb->prepare($sql), ARRAY_A  );
				$wpdb->flush();
//print "sql: $sql\n";			
//var_dump($blog_mu);	
				$blogname = ''; $admin_email = ''; $sf_account_name = ''; $sf_sourcedfrom_username = ''; $sf_avatar_uri = ''; $sf_can_modify = ''; $sf_use_copyright = ''; 
				$sf_copyright_text = ''; $sf_use_cc = ''; $sf_cc_modifications = ''; $sf_cc_commercial = ''; $sf_cc_jurisdiction = ''; $sf_status = '';
				$n=0;
				foreach ($blog_mu as $blog_mu_value) {
					if ( $blog_mu_value['option_name'] == 'blogname' ) { $blogname = $blog_mu[$n]['option_value']; }
					if ( $blog_mu_value['option_name'] == 'admin_email' ) { $admin_email = $blog_mu[$n]['option_value']; }
					if ( $blog_mu_value['option_name'] == 'sf_account_name' ) { $sf_account_name = $blog_mu[$n]['option_value']; }
					if ( $blog_mu_value['option_name'] == 'sf_sourcedfrom_username' ) { $sf_sourcedfrom_username = $blog_mu[$n]['option_value']; }
					if ( $blog_mu_value['option_name'] == 'sf_avatar_uri' ) { $sf_avatar_uri = $blog_mu[$n]['option_value']; }
					if ( $blog_mu_value['option_name'] == 'sf_can_modify' ) { $sf_can_modify = $blog_mu[$n]['option_value']; }
					if ( $blog_mu_value['option_name'] == 'sf_use_copyright' ) { $sf_use_copyright = $blog_mu[$n]['option_value']; }
					if ( $blog_mu_value['option_name'] == 'sf_copyright_text' ) { $sf_copyright_text = $blog_mu[$n]['option_value']; }
					if ( $blog_mu_value['option_name'] == 'sf_use_cc' ) { $sf_use_cc = $blog_mu[$n]['option_value']; }
					if ( $blog_mu_value['option_name'] == 'sf_cc_modifications' ) { $sf_cc_modifications = $blog_mu[$n]['option_value']; }
					if ( $blog_mu_value['option_name'] == 'sf_cc_commercial' ) { $sf_cc_commercial = $blog_mu[$n]['option_value']; }
					if ( $blog_mu_value['option_name'] == 'sf_cc_jurisdiction' ) { $sf_cc_jurisdiction = $blog_mu[$n]['option_value']; }
					if ( $blog_mu_value['option_name'] == 'sf_status' ) { $sf_status = $blog_mu[$n]['option_value']; }
					$n++;
				}
				if ( $sf_status == 'false' || $sf_status == '' || is_sf_plugin_active($blog_request) === false ) {
					$blogname = ''; $admin_email = ''; $sf_account_name = ''; $sf_avatar_uri = ''; $sf_can_modify = ''; $sf_use_copyright = ''; 
					$sf_copyright_text = ''; $sf_use_cc = ''; $sf_cc_modifications = ''; $sf_cc_commercial = ''; $sf_cc_jurisdiction = '';
				}
				if ( $sf_account_name == '' ) { $sf_account_name = get_option('sf_account_name'); }
				if ( $sf_sourcedfrom_username == '' ) { $sf_sourcedfrom_username = get_option('sf_sourcedfrom_username'); }
				if ( $sf_avatar_uri == '' ) { 
					$sf_avatar_uri = get_option('sf_avatar_uri');  
					if ( substr(get_option('sf_avatar_uri'), 0, 4) == 'http' ) {
						$sf_avatar_uri = get_option('sf_avatar_uri');
					} elseif ( strpos(get_option('sf_avatar_uri'), '@') ) {
						$sf_avatar_uri = get_option('sf_avatar_uri');
					} else {
						$sf_avatar_uri = get_settings('admin_email');
					}
				}
				if ( $sf_can_modify == '' ) { $sf_can_modify = get_option('sf_can_modify'); }
				if ( $sf_use_copyright == '' ) { $sf_use_copyright = get_option('sf_use_copyright'); }
				if ( $sf_copyright_text == '' ) { $sf_copyright_text = get_option('sf_copyright_text'); }
				if ( $sf_use_cc == '' ) { $sf_use_cc = get_option('sf_use_cc'); }
				if ( $sf_cc_modifications == '' ) { $sf_cc_modifications = get_option('sf_cc_modifications'); }
				if ( $sf_cc_commercial == '' ) { $sf_cc_commercial = get_option('sf_cc_commercial'); }
				if ( $sf_cc_jurisdiction == '' ) { $sf_cc_jurisdiction = get_option('sf_cc_jurisdiction'); }
				$sf_avatar_uri = md5( strtolower( $sf_avatar_uri ) );
			
			} else {
			
				$sf_account_name = get_option('sf_account_name');
				$sf_sourcedfrom_username = get_option('sf_sourcedfrom_username');
				$sf_avatar_uri = get_option('sf_avatar_uri');  
				if ( substr(get_option('sf_avatar_uri'), 0, 4) == 'http' ) {
					$sf_avatar_uri = get_option('sf_avatar_uri');
				} elseif ( strpos(get_option('sf_avatar_uri'), '@') ) {
					$sf_avatar_uri = get_option('sf_avatar_uri');
				} else {
					$sf_avatar_uri = get_settings('admin_email');
				}
				$sf_can_modify = get_option('sf_can_modify');
				$sf_use_copyright = get_option('sf_use_copyright');
				$sf_copyright_text = get_option('sf_copyright_text');
				$sf_use_cc = get_option('sf_use_cc');
				$sf_cc_modifications = get_option('sf_cc_modifications');
				$sf_cc_commercial = get_option('sf_cc_commercial');
				$sf_cc_jurisdiction = get_option('sf_cc_jurisdiction');
				$sf_avatar_uri = md5( strtolower( $sf_avatar_uri ) );
			
			}

		foreach ($pageposts as $post): 
			//var_dump($post);
			//print "title: " . $post['post_title'] . "\n";
			$post_excerpt = sf_html2txt($post['post_content']);
			if (strlen($post_excerpt) > 255) {
				$post_excerpt = substr($post_excerpt,0,249) . ' [...]';
			}

?>
		
		<entry>
			<title><![CDATA[<?php echo sf_html2txt($post['post_title']); ?>]]></title>
			<id><?php echo $post['ID']; ?></id><?php
		//if wpmu:
		if( function_exists( 'is_site_admin' ) ) {	?>
		
			<guid isPermaLink="true"><?php echo apply_filters('the_permalink_rss', get_blog_permalink($blog_request, $post['ID'])); ?></guid>
		<?php } else { ?>
			
			<guid isPermaLink="true"><?php echo apply_filters('the_permalink_rss', get_permalink($post['ID'])); ?></guid>
		<?php }  ?>
	<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', $post['post_date_gmt'], false); ?></pubDate>
			<pubDate_pretty><?php echo mysql2date('D, d M Y h:i A', $post['post_date_gmt'], false); ?></pubDate_pretty>
			<author><![CDATA[<?php echo sf_html2txt(get_author_name($post['post_author'])); ?>]]></author>
			<author_avatar><![CDATA[<?php $user_info = get_userdata($post['post_author']); echo md5( strtolower( $user_info->user_email ) ); ?>]]></author_avatar>
			<description><![CDATA[<?php echo sf_html2txt($post_excerpt); ?>]]></description>
			<?php
			if ( $the_method == 'sf.getEntry' ) {
				if ( strpos(dirname(__FILE__), "mu-plugins") == true ) {
					require_once(WP_MUPLUGIN_DIR . '/sourcedfrom/cc_jurisdictions.php'); 
				} else {
					require_once(WP_PLUGIN_DIR . '/sourcedfrom/cc_jurisdictions.php'); 
				}			
				$jurisdiction_array = sf_creativecommons_jurisdiction($sf_cc_jurisdiction);	
				$nc = '';
				$nd = '';
				$sa = '';
				$sa_text = '';
				$nd_text = '';
				$nc_text = '';
				if ($sf_cc_commercial == 'false') {
					$nc = "nc-";
					$nc_text = "Noncommercial-";
				}
				if ($sf_cc_modifications == 'no') {
					$nd = "nd-";
					$nd_text = "No Derivative-";
				}
				if ($sf_cc_modifications == 'sa') {
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
				if ( $sf_sourcedfrom_username != '' ) {
					//API CALL TO SOUREDFROM SERVER FOR AN ENTRY TOKEN
					//if wpmu:
					if( function_exists( 'is_site_admin' ) ) {
						$guid = apply_filters('the_permalink_rss', get_blog_permalink($blog_request, $post['ID']));
					} else {
						$guid = apply_filters('the_permalink_rss', get_permalink($post['ID']));
					}
					$entry_token_url = sf_getEntryToken($sf_sourcedfrom_username,$publisher_name,$publisher_email,$guid,$post['post_title'],$post_excerpt);
				} else {
					$entry_token_url = 'http://sourcedfrom.com/analytics/token.png';
				}
				//if wpmu:
				if( function_exists( 'is_site_admin' ) ) {
					$entry_footer_code = '<p class="vcard author"><a href="http://sourcedfrom.com" title="SourcedFrom"><img style="border: 0px none;margin:0 0 -6px 0;padding:0;" src="'.$entry_token_url.'" alt="SourcedFrom" height="21" width="15" /></a>&nbsp;Sourced from:&nbsp;<a class="url fn" style="margin:0;padding:0;" href="'.apply_filters('the_permalink_rss', get_blog_permalink($blog_request, $post['ID'])).'">'.$sf_account_name.'</a>';
				} else {
					$entry_footer_code = '<p class="vcard author"><a href="http://sourcedfrom.com" title="SourcedFrom"><img style="border: 0px none;margin:0 0 -6px 0;padding:0;" src="'.$entry_token_url.'" alt="SourcedFrom" height="21" width="15" /></a>&nbsp;Sourced from:&nbsp;<a class="url fn" style="margin:0;padding:0;" href="'.apply_filters('the_permalink_rss', get_permalink()).'">'.$sf_account_name.'</a>';
				}	
				if ($sf_use_cc == 'true') { 
					if ($sf_cc_jurisdiction != 'Unported') { 
						$entry_footer_code_cc = '&nbsp;(<a rel="license" href="http://creativecommons.org/licenses/' . $cc_license . '/' . $jurisdiction_array['license'] . '/' . $jurisdiction_array['code'] . '/" title="Creative Commons License" style="font-size:80%">' . $cc_footer . '</a>)';
					} else {
						$entry_footer_code_cc = '&nbsp;(<a rel="license" href="http://creativecommons.org/licenses/' . $cc_license . '/' . $jurisdiction_array['license'] . '/" title="Creative Commons License" style="font-size:80%">' . $cc_footer . '</a>)';	
					}
					$entry_footer_code = $entry_footer_code . $entry_footer_code_cc;
				} //if cc
				if ($sf_use_copyright == 'true') { 
					$entry_footer_code_cc = '&nbsp;(<span style="font-size:80%">&#169; '.$sf_copyright_text.'</span>)';
					$entry_footer_code = $entry_footer_code . $entry_footer_code_cc;
				} //if copyright
				$entry_footer_code = $entry_footer_code.'</p>';
/**
* NOTE: can uncomment the $entry_footer_code = ''; line below if you don't want any footer attatched to the entry, YOU SHOULD KNOW BEFORE DOING THIS the reason for the footer is:
*	-Guidline from Google, dealing with duplicate content [http://googlewebmastercentral.blogspot.com/2006/12/deftly-dealing-with-duplicate-content.html]
*	-If Creative Commons or Copyright licensing is used
*	-As a curtisy to the content creator, link-back to orgional source entry
*	-If content creator is a SourcedFrom Anylytics user, to facilitate statistical anylitics for user
*	-Used in this script to know whether the post source is not origional to this blog, prevents syndicated posts being re-syndicated
**/
//$entry_footer_code = ''

			$post_content = iconv("UTF-8","UTF-8//IGNORE",$post['post_content']);
			$post_content = preg_replace("/(Ã|Â)/", "", $post_content);
			
			?>
<content><![CDATA[<?php echo $post_content.$entry_footer_code; ?>]]></content>
			<can_modify><?php echo $sf_can_modify; ?></can_modify>
			<license_copyright><?php echo $sf_use_copyright; ?></license_copyright>
			<copyright_text><?php echo sf_html2txt($sf_copyright_text); ?></copyright_text>
			<license_cc><?php echo $sf_use_cc; ?></license_cc>
			<cc_modifications><?php echo $sf_cc_modifications; ?></cc_modifications>
			<cc_commercial><?php echo $sf_cc_commercial; ?></cc_commercial>
			<cc_jurisdiction><?php echo $sf_cc_jurisdiction; ?></cc_jurisdiction>
<?php } //end if sf.getEntry ?>
<account><![CDATA[<?php echo sf_html2txt($sf_account_name); ?>]]></account>
			<account_avatar><?php echo $sf_avatar_uri; ?></account_avatar>
		</entry>
	
	<?php endforeach; ?>
</entries>
	
</wp_api_server_rsp>
<?php

} //end func.


function sf_resultsFeedBlogs($publisher_id, $publisher_name, $blogs) {
	header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
	echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; 
?>

<wp_api_server_rsp status="ok" version="0.1">
	
	<method>sf.getBlogs</method>
	<account><![CDATA[<?php echo sf_html2txt(get_option('sf_account_name')); ?>]]></account>
	<account_avatar><![CDATA[<?php global $avatar_uri; echo $avatar_uri; ?>]]></account_avatar>
	<publisher><![CDATA[<?php echo sf_html2txt($publisher_name); ?>]]></publisher>
	<link><?php bloginfo_rss('url') ?></link>
	<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></pubDate>
	<generator>http://sourcedfrom.com/wordpress?wpv=<?php echo get_bloginfo_rss( 'version' ); ?>&amp;sfv=0.1</generator>
	<language><?php echo get_option('rss_language'); ?></language>
	<total_blogs><?php global $total_entries; echo $total_entries; ?></total_blogs>
	<display_range><?php
		global $page_display;
		$range_start = intval(($page_display * 10) - 9);
		echo $range_start . ' - ' . $page_display . '0';
	?></display_range>
	
	<blogs>
		<?php 
	if ( $total_entries > 0 ) {
		foreach ($blogs as $blog): 
			//var_dump($blog);

			//if wpmu get blog details:
			if( function_exists( 'is_site_admin' ) ) {
				global $wpdb, $table_prefix, $blog_id;
				$table_prefix_sf = preg_replace('#'.$blog_id.'_#', '', $table_prefix);
				$sql = "SELECT option_name, option_value FROM ".$table_prefix_sf.$blog['blog_id']."_options WHERE 
				option_name = 'blogname' OR option_name = 'admin_email' OR option_name = 'sf_account_name' OR option_name = 'sf_avatar_uri' ORDER BY option_id";
				$blog_mu = $wpdb->get_results( $wpdb->prepare($sql), ARRAY_A  );
				$wpdb->flush();
		?>

		<blog>
			<id><?php echo $blog['blog_id']; ?></id>
			<title><![CDATA[<?php echo sf_html2txt($blog_mu[0]['option_value']); ?>]]></title>
			<link><?php 
			//TO DO make work with vhost and subdomains.
			echo 'http://'.$blog['domain'].$blog['path']; ?></link>
			<owner><![CDATA[<?php 
			//check if this blog has own Sf Server Account settings and use if does:
			if ( isset($blog_mu[2]['option_value']) ) {
				echo $blog_mu[2]['option_value'];
			} else {
				echo get_option('sf_account_name'); 
			}	
				?>]]></owner>
			<owner_avatar><?php
			//Get the Avatar and convert to URL source for inclution in API output:
			if ( isset($blog_mu[3]['option_value']) ) {
				if ( substr($blog_mu[3]['option_value'], 0, 4) == 'http' ) {
					$avatar_uri2 = $blog_mu[3]['option_value'];
				} elseif ( strpos($blog_mu[3]['option_value'], '@') ) {
					$avatar_uri2 = $blog_mu[3]['option_value'];
				} else {
					$avatar_uri2 = $blog_mu[1]['option_value'];
				}
				echo md5( strtolower( $avatar_uri2 ) );
			} else {
				echo $avatar_uri;
			}
			
	?></owner_avatar>

		</blog>
			
	<?php 	} else { ?>

		<blog>
			<id><?php echo $blog['blog_id']; ?></id>
			<title><![CDATA[<?php echo sf_html2txt(get_bloginfo('name')); ?>]]></title>
			<link><?php echo get_bloginfo('url'); ?></link>
			<owner><![CDATA[<?php echo sf_html2txt(get_option('sf_account_name')); ?>]]></owner>
		</blog>			
			
		<?php
			}
		endforeach; 
	} //end if has blogs.	
		?>
		
	</blogs>
	
</wp_api_server_rsp>
<?php

} //end func.


//API call to SourcedFrom for an entry token.
function sf_getEntryToken($account, $publisher, $publisher_email, $guid, $title, $description = '') {
	
	if ( $account == '' || $publisher == '' || $publisher_email == '' || $guid == '' ) { return 'http://sourcedfrom.com/analytics/token.png'; }
	
	// Using the SourcedFrom class.
	require_once("SourcedFrom.class.php");
	$sourcedfrom = new SourcedFrom;

	$sourcedfrom->account = $account;	// SourcedFrom Analytics username
	$sourcedfrom->guid = $guid;

	// These are optional parameters:
	$sourcedfrom->title = $title;			
	$sourcedfrom->description = $description;
	$sourcedfrom->publisher = $publisher;
	$sourcedfrom->publisher_email = $publisher_email;
			
	if ( $sourcedfrom->request_analytics_token() ) {
			
		// Successfully got token ID.
		return $sourcedfrom->token_url;
				
	} else {
			
		// Failed, but still return default token img.
		//echo 'error: ' . $sourcedfrom->error_message;
		return 'http://sourcedfrom.com/analytics/token.png';
			
	}
	
	return 'http://sourcedfrom.com/analytics/token.png';
	
	
	$account = md5(strtolower($account));
	$publisher = urlencode($publisher);
	$publisher_email = md5(strtolower($publisher_email));
	$guid = urlencode($guid);
	$title = urlencode(substr($title, 0, 255));
	$description = urlencode(substr($description, 0, 255));
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->read_timeout = 20;
//print "<sfgetTokenCALL>http://sourcedfrom.com/analytics/token/token-generator.php?account=".$account.'&publisher='.$publisher.'&publisher_email='.$publisher_email.'&guid='.$guid.'&title='.$title.'&description='.$description."</sfgetTokenCALL>\n";
	$success = $snoop->fetch('http://sourcedfrom.com/analytics/token/token-generator.php?account='.$account.'&publisher='.$publisher.'&publisher_email='.$publisher_email.'&guid='.$guid.'&title='.$title.'&description='.$description);
	if ($success) {
		$res = $snoop->results;
		$pos = strpos($res, 'sf_api_server_rsp status="ok"');
		if ($pos === false) { return 'http://sourcedfrom.com/analytics/token.png'; }
		preg_match('#<token_url>(.*)<\/token_url>#s', $res, $token_url);
		return $token_url[1];
	} else {
		$snoop = NULL; unset($snoop);
		return 'http://sourcedfrom.com/analytics/token.png';
	}
	return 'http://sourcedfrom.com/analytics/token.png';
} //end func.


//Strips all tags out of html returning just the text
function sf_html2txt($html) {				
	$html = iconv("UTF-8","UTF-8//IGNORE",$html);		//convert iregular utf-8 to html
	$html = preg_replace("/(Ã|Â)/", "", $html);

	$html = strip_tags($html);
	$search = array('@<script[^>]*?>.*?</script>@si',	// Strip out javascript
	               '@<style[^>]*?>.*?</style>@siU',		// Strip style tags properly
	               '@<[\/\!]*?[^<>]*?>@si',				// Strip out HTML tags
	               '@<![\s\S]*?--[ \t\n\r]*>@',			// Strip multi-line comments including CDATA
				   '@\[caption.*?\[/caption\]@si'		// Strip out wp code
	);
	$text = preg_replace($search, '', $html);
	unset($search);
	$search2 = array('@\n@s',							// Stip out new lines
				   '@\r@s',								// Stip out returns
				   '@\t@s',								// Stip out tabs  
				   '@  @s'								// Stip out double spaces
	);	
	$text2 = preg_replace($search2, ' ', $text);
	unset($search2);
	return trim($text2);
} //end func.


function sf_fail_api($error_code = '111', $error_msg = 'Not valid request.') {
header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>
<?robots index="no" follow="no"?>
';

if ( $error_code === '919' ) {
echo '<?xml-stylesheet type="text/xsl" href="'.get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/css/style.xsl" ?>
';
}
echo '
<sf_api_server_rsp status="fail" version="0.1">
<err code="'.$error_code.'" msg="'.$error_msg.'" />
</sf_api_server_rsp>';
exit;
} //end func.


?>