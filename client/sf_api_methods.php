<?php
/**
* SourcedFrom API Method Calls (and hopefully returns)
* Version: 0.5
* Author: SourcedFrom
* Author URI: http://sourcedfrom.com
*
* @package SourcedFrom
*/

/**
* Last Mod: 21 Aug 2009.
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


define("SF_RETURN_NEXT_IF_SINGLE", true);	//if only one blog found in method sf.getBlogs then go directly to sf.getBlogEntries


function sf_getBlogs($page_display = 1) {
	//[page_display] denotes which set of records to return, (default) 0||1 = 1 to 10 entries, 2 = 11 to 20 entries etc.
	
	if ( !isset($_GET['access']) ) { return false; }

	if ( !preg_match('#&account=#i', urldecode($_GET['access'])) ) {
		return _sf_parse_feed2sfXml(_sf_fetch_feed(urldecode($_GET['access'])), 'feed');
	}
	
	$access_address = $_GET['access'];
	if ( SF_RETURN_NEXT_IF_SINGLE ) {
		$access_address = $access_address.'&method=sf.getBlogs&returnNextIfSingle=true&page='.$page_display;
	} else {
		$access_address = $access_address.'&method=sf.getBlogs&page='.$page_display;
	}
	$access_address = preg_replace('#&&#', '&', $access_address);
	//print "call: " . $access_address . "\n";														//FOR DEBUGING RESULTS
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->read_timeout = 15;
	$success = $snoop->fetch($access_address);
	
	if ($success) {
		return _sf_parse_feed2sfXml($snoop->results, 'sf');
	} else {
		$snoop = NULL; unset($snoop);
		echo '<div class="error" style="padding:5px;"><strong>Error</strong>: Snoopy not responding, check the Access Address is valid URL.</div>';
		return false;
	}
}


function sf_getBlogEntries($page_display = 1, $blog_id = '') {
	//[page_display] denotes which set of records to return, (default) 0||1 = 1 to 10 entries, 2 = 11 to 20 entries etc.
	
	if ( !isset($_GET['access']) ) { return false; }

	if ( !preg_match('#&account=#i', urldecode($_GET['access'])) ) {
		return _sf_parse_feed2sfXml(_sf_fetch_feed(urldecode($_GET['access'])), 'feed', $page_display);
	}
	
	if ( !isset($_GET['blog']) && $blog_id === '' ) { return false; }
	
	if ( $blog_id != '' ) {
		$_GET['blog'] = $blog_id;
	}
	
	$access_address = $_GET['access'];
	
	$search_term = '';
	if ( isset($_GET['search']) ) {
		$search_term = '&search='.urlencode($_GET['search']);
	}	
	
	$access_address = $access_address.'&method=sf.getBlogEntries&blog='.$_GET['blog'].'&page='.$page_display.$search_term;

	$access_address = preg_replace('#&&#', '&', $access_address);
	//print "call: " . $access_address . "\n";														//FOR DEBUGING RESULTS
	
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->read_timeout = 15;
	$success = $snoop->fetch($access_address);
	if ($success) {
		return _sf_parse_feed2sfXml($snoop->results, 'sf');
	} else {
		$snoop = NULL; unset($snoop);
		echo '<div class="error" style="padding:5px;"><strong>Error</strong>: Snoopy not responding, check the Access Address is valid URL.</div>';
		return false;
	}
}


function sf_getEntry() {
	//[page_display] denotes which set of records to return, (default) 0||1 = 1 to 10 entries, 2 = 11 to 20 entries etc.
	
	if ( !isset($_GET['access']) ) {
		//if editing post/page use meta to retrieve entry.
		if ( !isset($_GET['post']) ) { return -1; }
		$access_uri = get_post_meta($_GET['post'], '_sf_access', true);
		$blog_id = get_post_meta($_GET['post'], '_sf_blog', true);
		$post_id = get_post_meta($_GET['post'], '_sf_post', true);
	} else {
		$access_uri = $_GET['access'];
		$blog_id = intval($_GET['blog']);
		$post_id = $_GET['post'];
	}
	
	//only run sf if editing sf entry.
	if ( $access_uri == '' || $blog_id  == '' || $post_id  == '' ) {
		return -1;
	}
	
	if ( !preg_match('#&account=#i', urldecode($access_uri)) ) {
		return _sf_parse_feed2sfXml(_sf_fetch_feed(urldecode($access_uri)), 'feed', '1', urldecode($post_id), $post_id);
	}
	
	$post_id = intval($post_id);
	
	$access_address = $access_uri.'&method=sf.getEntry&blog='.$blog_id.'&post='.$post_id;
	$access_address = preg_replace('#&&#', '&', $access_address);
	//print "call: " . $access_address . "\n";														//FOR DEBUGING RESULTS
	
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->read_timeout = 15;
	$success = $snoop->fetch($access_address);
	if ($success) {
		return _sf_parse_feed2sfXml($snoop->results, 'sf');
	} else {
		$snoop = NULL; unset($snoop);
		echo '<div class="error" style="padding:5px;"><strong>Error</strong>: Snoopy not responding, check the Access Address is valid URL.</div>';
		return false;
	}
}


function _sf_fetch_feed($url) {
	//feeds only work with WP version 2.7 or newer
	if (!SF_WP_VER_GTE_27) { return; }

	//have included this class to work with older than wp 2.8.1, then may as well use this so can set cache to 1 hour rather than 12
	//also have added wp captions exception and allow aceptable videos like youtube
	require_once ('class-feed.php');
	$feed = new SimplePie();
	$feed->set_feed_url($url);
	$feed->set_cache_class('WP_Feed_Cache');
	$feed->set_file_class('WP_SimplePie_File');
	
	//strip these attributes but leave: class and style so WP captions works, comment out next line to disable and strip all attribs
	$feed->strip_attributes(array('bgsound','expr','id','onclick','onerror','onfinish','onmouseover','onmouseout','onfocus','onblur','lowsrc','dynsrc'));
	
	$feed->set_cache_duration(apply_filters('wp_feed_cache_transient_lifetime', 3600)); //refer to class-feed.php to set cache period
	$feed->init();
	$feed->handle_content_type();
	if ( $feed->error() )
		return new WP_Error('simplepie-error', $feed->error());		
		
	return $feed;

	//return fetch_feed( $url );
}


function _sf_parse_feed2sfXml($data, $format = 'sf', $page_display = 1, $get_guid = '', $get_guid_raw = '') {
	if (!SF_WP_VER_GTE_27 && $format == 'feed') {
		echo '<div class="error" style="padding:5px;"><strong>Error</strong>: The SourcedFrom plugin requires WordPress version 2.7 or newer to work with Feeds, please <a href="http://wordpress.org">upgrade your WordPress version</a>.</div>';
		return false;
	}
	
	if ( is_wp_error($data) ) {
		echo '<div class="wrap" style="padding-top:1em"><div class="error" style="padding:5px;"><strong>SourcedFrom Error</strong>: '.$data->get_error_message().'</div></div>';
		return false;
   }

	if ( $format == 'sf' ) {
	
		if ( preg_match('#_api_server_rsp#i', $data) == false ) {
			echo '<div class="error" style="padding:5px;"><strong>SourcedFrom Error</strong>: The accounts API server is not responding properly at this time, please try again.</div>';
			return false;
		}
		$data = iconv("UTF-8","UTF-8//IGNORE",$data);
		$results_xml = simplexml_load_string($data, NULL, LIBXML_NOWARNING);
		if( $results_xml == false ) { echo '<div class="error" style="padding:5px;"><strong>SourcedFrom Error</strong>: The accounts API server is not responding properly at this time, please try again.</div>'; return false; }
		if ( $results_xml->err ) {
			echo '<div class="error" style="padding:5px;"><strong>Error</strong>: '.$results_xml->err['msg'].'</div>';
			return false;
		}
		
		return $results_xml;
	
	} else { //must be feed

		//parse simplePie to SourcedFrom xml format for presenting	
		$title = $data->get_title();
		$items = $data->get_items();
		$total_entris = count($items);
		
		$method = 'sf.getBlogEntries';
		if ( $get_guid != '' ) {
			$method = 'sf.getEntry';
		}
		
		$account = 'Feed';
		if ( $get_guid != '' ) {
			$account = $title;
		}		
		

$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?> 
<wp_api_server_rsp status=\"ok\" version=\"0.1\">
	<method>$method</method>
	<account><![CDATA[$account]]></account>
	<format>feed</format>
	<account_avatar></account_avatar>
	<publisher><![CDATA[]]></publisher>
	<title><![CDATA[$title]]></title>
	<link></link>
	<blog_id>101</blog_id>
	<total_entries>$total_entris</total_entries>
	<display_range></display_range>
	<entries>";
	
	$c = 0; $x = 0; $y = 10;
	$y = $page_display .'0';
	$y = intval($y);
	
	$x = $page_display - 1;
	$x = $x .'0';
	$x = intval($x);
	if ( $page_display === 1 ) {
		$x = 0;
	}
	//print "x: $x\n";
	//print "y: $y\n";
	
	foreach($items as $item) {
	//print "guid: $get_guid\n";
	//print "get_guid_raw: $get_guid_raw\n";
		if ( $get_guid != '' ) {
			//is getEntry request, find and return entry
			if ( $item->get_link() == $get_guid || $item->get_link() == $get_guid_raw  ) {
				$entry_title = $item->get_title();
				$description = substr(_sf_html2txt($item->get_description()), 0, 255);
				$content = $item->get_content();
				if ( $content == '' ) {
					$content = $item->get_description();
				}
				
				//print "get_content: $content\n";
				$author = $item->get_author()->name;
				$author_avatar = '';
				if ( $item->get_author()->email != NULL ) {
					$author_avatar = md5($item->get_author()->email);
				}
				$guid = $item->get_link();
				$pubdate = $item->get_date();
				$attrib = $account;
				if ( $account == '' ) {
					$attrib = $author;
				}
				$entry_footer_code = '<p class="vcard author"><a href="http://sourcedfrom.com" title="SourcedFrom"><img style="border: 0px none;margin:0 0 -6px 0;padding:0;" src="http://sourcedfrom.com/analytics/token.png" alt="SourcedFrom" height="21" width="15" /></a>&nbsp;Sourced from:&nbsp;<a class="url fn" style="margin:0;padding:0;" href="'.apply_filters('the_permalink_rss', $guid).'">'.$attrib.'</a>';
				$xml .= "	
				<entry>
					<title><![CDATA[$entry_title]]></title>
					<id>$guid</id>
					<guid isPermaLink=\"true\">$guid</guid>
					<pubDate>$pubdate</pubDate>
					<pubDate_pretty>$pubdate</pubDate_pretty>
					<author><![CDATA[$author]]></author>
					<author_avatar>$author_avatar</author_avatar>
					<description><![CDATA[$description]]></description>
					<account><![CDATA[$account]]></account>
					<account_avatar></account_avatar>
					<content><![CDATA[". trim($content.$entry_footer_code) ."]]></content>
				</entry>
				";
				break;
			}
			continue;
		}
	
		if ( $x === $y ) { break; }
		if ( $c < $x ) { $c++; continue; }
		
			$entry_title = $item->get_title();
			$description = substr(_sf_html2txt($item->get_description()), 0, 255);
			if ( strlen(_sf_html2txt($item->get_description())) > 255 ) {
				$description .= ' [...]';
			}
			if ( $description == '' ) {
				$description = substr(_sf_html2txt($item->get_content()), 0, 255);
				if ( strlen(_sf_html2txt($item->get_content())) > 255 ) {
					$description .= ' [...]';
				}
			}
			
			$author = '';
			if ( $item->get_author()->name != NULL ) {
				$author = $item->get_author()->name;
			}
			$author_avatar = '';
			if ( $item->get_author()->email != NULL ) {
				$author_avatar = md5($item->get_author()->email);
			}
			$guid = $item->get_link();
			$pubdate = $item->get_date();
		
		$xml .= "	
		<entry>
			<title><![CDATA[$entry_title]]></title>
			<id>$x</id>
			<guid isPermaLink=\"true\">$guid</guid>
			<pubDate>$pubdate</pubDate>
			<pubDate_pretty>$pubdate</pubDate_pretty>
			<author><![CDATA[$author]]></author>
			<author_avatar>$author_avatar</author_avatar>
			<description><![CDATA[$description]]></description>
			<account>Feed</account>
			<account_avatar></account_avatar>
		</entry>
		";
		$x++;
		$c++;
	} //end foreach		
$xml .= "
	</entries>
</wp_api_server_rsp>";

//print "xml: $xml\n";	//DEBUG

		$xml = iconv("UTF-8","UTF-8//IGNORE",$xml);
		$results_xml = simplexml_load_string($xml, NULL, LIBXML_NOWARNING);
		if( $results_xml == false ) { echo '<div class="error" style="padding:5px;"><strong>SourcedFrom Error</strong>: The Feed server is not responding properly at this time, please try again.</div>'; return false; }
		if ( $results_xml->err ) {
			echo '<div class="error" style="padding:5px;"><strong>Error</strong>: '.$results_xml->err['msg'].'</div>';
			return false;
		}
		
		return $results_xml;
	}
}


//Strips all tags out of html returning just the text
function _sf_html2txt($html) {
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
				   '@  @s',
				   '@  @s',
				   '@\t@s'								// Stip out tabs
	);	
	$text2 = preg_replace($search2, ' ', $text);
	unset($search2);
	return trim($text2);
} //end func.


?>