<?php
/**
* SourcedFrom Add Entry Media to WordPress Media Library
* Version: 0.1
* Author: SourcedFrom
* Author URI: http://sourcedfrom.com
*
* @package SourcedFrom
*/

/**
* Last Mod: 5 May 2009.
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


function sf_add_all_media($post_id) {
	
	//Only works if cURL is found.
	if (!function_exists('curl_init')) {
		return false;
	}
	
	//for each img src in body:
	$post_content  = $_POST['content'];
	$post_content  = preg_replace("/&lt;/", "<", $post_content );
	$post_content  = preg_replace("/&gt;/", ">", $post_content );
	$post_content  = preg_replace("#\\\\\"#", '"', $post_content );
	$post_content  = preg_replace("#\\\\\'#", "'", $post_content );

		//print "post_content : 
	//" . $post_content  . "\n";

	$c = 0; //set count for each image found.
	$src_code_allready = array();
	if ( preg_match_all("/src=(\'|\")([^`]*?)([\"]type=\"application\/x\-shockwave\-flash|\"|\')/is", $post_content, $src_matches) == true ) { //look through all img tags
		foreach ($src_matches[0] as $src_meta) {
			//Go through images.
			$src_code = $src_matches[2][$c];
			$src_code_full = $src_matches[0][$c];
			//print "src_code: " . $src_matches[0][$c] . "\n";
			$src_code_escape = preg_quote($src_code, '/');
			
			//check if in the skip list
			if ( sf_skip_this_list($src_code_full) === true ) {
				$c++;
				continue;	
			} //else  is ok

			//make sure is unique src so as to not upload the same file repeatyed times.
			//make array of found on each add to array then here check this new src is not allready in array.
			$isNewSrc = true;
			foreach ($src_code_allready as $src_code_allready_item) {
				if ($src_code_allready_item == $src_code) {
					$isNewSrc = false;
				}
			}
			if ($isNewSrc === true) {
				$src_code_allready[] = $src_code;
			} else {
				$c++;
				continue;
			}
			
			if (strpos($src_code, "?")) {
				//remove any querry in src string cause is causing failure eg: http://files.com/images/img1.jpg?w=166&h=127 MUST BE: http://files.com/images/img1.jpg
				$src_code = substr($src_code, 0, strpos($src_code, "?"));
			}
			
			preg_match("/[^\/]+$/",$src_code,$file_matches);
			$image_name = $file_matches[0];
			
			//print "src_code: $src_code\n";
			//print "image_name: $image_name\n";
			//print "post_id: $post_id\n";		
			
			$add_res = sf_upload_media_item($src_code, $image_name, $post_id);
			if ($add_res == false) {
				$c++;
				continue;
			}

			$post_content = preg_replace("/$src_code_escape/", trim($add_res), $post_content);

			$c++;
		} //end foreach src found.
	} //end if.
	
	//now update DB with revised post_content field for post/page.
	global $wpdb;
	$sql = "UPDATE $wpdb->posts SET post_content = '".mysql_real_escape_string($post_content)."' WHERE ID = '$post_id'";
	//print "sql: $sql\n";
	$wpdb->query($sql);
	$wpdb->flush();
//print "revised post_content : 
	//" . $post_content . "\n";
	
	return;
	
} //end func.


function sf_upload_media_item($src_url, $image_name, $post_id) {

	include_once(ABSPATH . 'wp-admin/includes/admin.php');
	include_once(ABSPATH . WPINC . '/class-IXR.php');

	$ch = curl_init($src_url);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); 		// set to zero for no timeout
	curl_setopt($ch, CURLOPT_TIMEOUT, 30); 				// set to zero for no timeout
	$data = curl_exec($ch);
	$curl_info = curl_getinfo($ch);
	curl_close($ch);
	$header_size = $curl_info['header_size'];
	$header = substr($data, 0, $header_size);
	preg_match('/Content-type: (.*)\n/i', $header, $content_type);
	$body = substr($data, $header_size);

	$name = sanitize_file_name( trim($image_name) );
	$type = trim($content_type[1]);
	$bits = $body;
	
	$upload = wp_upload_bits($name, $type, $bits);
	if ( ! empty($upload['error']) ) {
		$errorString = sprintf(__('Could not write file %1$s (%2$s)'), $name, $upload['error']);
		return false;
	}
	// Construct the attachment array
	// attach to post_id (if no post asociation set to = -1).
	$attachment = array(
		'post_title' => $name,
		'post_content' => '',
		'post_type' => 'attachment',
		'post_parent' => $post_id,
		'post_mime_type' => $type,
		'guid' => $upload[ 'url' ]
	);

	// Save the data
	$id = wp_insert_attachment( $attachment, $upload[ 'file' ], $post_id );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
		
	//print "name: " . $name . "\n";
	//print "url: " . $upload[ 'url' ] . "\n";
	//print "name: " . $type . "\n";
	
	return $upload['url'];

} //end func.


function sf_skip_this_list($src_url) {

	$pos = strpos($src_url, "application/x-shockwave-flash");
	if ( $pos > 0 ) { return true; }
	$pos = strpos($src_url, ".swf");
	if ( $pos > 0 ) { return true; }
	
	$pos = strpos($src_url, "creativecommons.org");
	if ( $pos > 0 ) { return true; }
	$pos = strpos($src_url, "sourcedfrom.com");
	if ( $pos > 0 ) { return true; }

	//$pos = strpos($src_url, "127.0.0.1");
	//if ( $pos > 0 ) { return true; }
	$pos = strpos($src_url, get_option('home'));	//dont add if already in media library.
	if ( $pos > 0 ) { return true; }

	return false; //means is not on the list go on as normal. (true = skip).

} //end func.

?>