<?php
/**
* SourcedFrom for simplepie to allow known source videos like youtube
* Version: 0.1
* Author: SourcedFrom
* Author URI: http://sourcedfrom.com
*
* @package SourcedFrom
*/

/**
* Last Mod: 20 July 2009.
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


//set gloabl as security to prevent <youtube tag being origional
$video_tag_sec_code = '';

//SF MOD for simplepie to allow known source videos like youtube

function sf_set_allowVideos($pcre, $data) {
	global $video_tag_sec_code;
	
	//note may not support IE
	if ( preg_match_all($pcre, $data, $video_res) == true ) { //look through all img tags
		foreach ($video_res[0] as $video) {
			
			//print "video: $video\n";
							
			if (strpos($video, 'youtube.com/')) {
				//get youtubes url
				preg_match('#src=\"(.*?)\"#is', $video, $vid_src_res);
				if ($vid_src_res == NULL) {
					preg_match('#data=\"(.*?)\"#is', $video, $vid_src_res);
				}
				if ($vid_src_res == NULL) {
					return $data;
				}

				$video_src = $vid_src_res[1];
				//print "video_src: $video_src\n";
										
				//make sure is actually from youtube
				if (!strpos($video_src, 'youtube.com/')) {
					return $data;
				}
										
				
				if ($video_tag_sec_code == '') {
					$video_tag_sec_code = md5(uniqid(rand(), true));	//random 32 character (a 128 bit hex number) identifier.
				}	
				
				$new_tag = '<youtube_'.$video_tag_sec_code.'_object width="425" height="344"><youtube_'.$video_tag_sec_code.'_param name="movie" value="'.$video_src.'"></youtube_'.$video_tag_sec_code.'_param><youtube_'.$video_tag_sec_code.'_param name="allowFullScreen" value="true"></youtube_'.$video_tag_sec_code.'_param><youtube_'.$video_tag_sec_code.'_param name="allowscriptaccess" value="always"></youtube_'.$video_tag_sec_code.'_param><youtube_'.$video_tag_sec_code.'_embed src="'.$video_src.'" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></youtube_'.$video_tag_sec_code.'_embed></youtube_'.$video_tag_sec_code.'_object>';						
																	
				//print "new_tag:$new_tag\n";

				$replace_tag = ':'.preg_quote($video).':is';
				
				//print "replace_tag:$replace_tag\n";
				
				//$data = preg_replace_callback($pcre, array(&$this, 'do_strip_htmltags'), $data);
				$data = preg_replace($replace_tag, $new_tag, $data);
				
				//print "revised_data: $data\n";
								
			}
		
		} //end for each video
	} //end if any videos

	return array ($data, $video_tag_sec_code);
}


function sf_restore_allowVideos($data, $video_tag_sec_code) {

	$data = preg_replace('#<youtube_'.$video_tag_sec_code.'_#is', '<', $data);
	$data = preg_replace('#<\/youtube_'.$video_tag_sec_code.'_#is', '</', $data);
	
	return $data;

}						
?>