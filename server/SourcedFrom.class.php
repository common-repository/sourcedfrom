<?php
/*****************************************************************

SourcedFrom - Analytics API client
Version: 0.1
Last Mod: 9 July 2009.
  
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
* 

The latest version can be obtained from: http://sourcedfrom.com/analytics/

*****************************************************************/


class SourcedFrom {
	
	/* public user definable vars */
	var $account						 	= 	"";			// your SourcedFrom Analytics account username.
	var $guid 								= 	"";			// the entry URL (address) you wish to track.
	var $name_in_footer 					= 	"";			// the content creators name, change to suit.
	var $publisher 							= 	"";			// optional (requires 'publisher_email' if using).
	var $publisher_email 					= 	"";			// optional (requires 'publisher' if using).
	var $title								= 	"";			// optional (255 chracters max).
	var $description 						= 	"";			// optional (255 chracters max).
	
	var $error_message						=	"";			// the error messages returned.
	var $error_code							=	0;			// the error code.
	
	var $token_id							=	"";			// the returned token ID.
	var $token_url							=	"";			// the returned token URL.
	var $token_footer_xhtml					=	"";			// xhtml formatted footer which can be used in entry.
	
	/* private vars */
	var $_token_generator_api_endpoint 		= 	"http://sourcedfrom.com/analytics/token-generator.php";	
	

	function request_analytics_token() {
		
		// make sure is valid submit:
		if ( $this->account == '' || $this->guid == '' ) { 
			$this->error_code = 001;
			$this->error_message = 'Invalid parameters sent';
			$this->token_footer_xhtml = $this->_formatfooter('http://sourcedfrom.com/analytics/token.png');
			return false;
		}
		
		// make sure is valid entry URL submitted:
		if ( preg_match('#^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?#i', $this->guid) == false ) {
			$this->error_code = 005;
			$this->error_message = 'The entry URL sent is invalid, please enter a valid web address including http://';
			$this->token_footer_xhtml = $this->_formatfooter('http://sourcedfrom.com/analytics/token.png');
			return false;
		}
		
		// uses the Snoopy PHP class (http://sourceforge.net/projects/snoopy/) to call and return SourcedFrom API, thanks Snoopy!
		require_once(ABSPATH.WPINC.'/class-snoopy.php');													// use WP's copy of snoopy
		$snoopy = new Snoopy;
		$snoopy->read_timeout = 20;																			// seconds
		
		$parameters = '?account='.md5($this->account);
		$parameters .= '&guid='.urlencode(substr($this->guid, 0, 255));
		$parameters .= '&title='.urlencode(substr($this->_striptextspaces($snoopy->_striptext($this->title)), 0, 255));
		$parameters .= '&description='.urlencode(substr($this->_striptextspaces($snoopy->_striptext($this->description)), 0, 255));
		if ( $this->publisher_email != '' ) {
			//must have both or none
			$parameters .= '&publisher='.urlencode(substr($this->publisher, 0, 100));
			$parameters .= '&publisher_email='.md5($this->publisher_email);
		}
		
		//echo "url: " . $this->_token_generator_api_endpoint.$parameters . "\n"; 											// for debug
		
		if ($snoopy->fetch($this->_token_generator_api_endpoint.$parameters)) {
			$res = $snoopy->results;
			unset($snoopy);
			//var_dump($res); 																					// for debug
			$pos = strpos($res, 'sf_api_server_rsp status="ok"');
			if ($pos === false) { 																			// failed
				$pos2 = strpos($res, 'sf_api_server_rsp status="fail"');
				if ($pos2 === false) { 
					$this->error_code = 003;
					$this->error_message = 'The SourcedFrom Analytics API is not responding at this time, please try again.';
					$this->token_footer_xhtml = $this->_formatfooter('http://sourcedfrom.com/analytics/token.png');
					return false;
				} else {
					preg_match('#msg="(.*)"#s', $res, $err_msg);
					$this->error_code = 004;
					$this->error_message = $err_msg[1];
					$this->token_footer_xhtml = $this->_formatfooter('http://sourcedfrom.com/analytics/token.png');
					return false;
				}
			}
			preg_match('#<token_id>(.*)<\/token_id>#s', $res, $token_id);
			preg_match('#<token_url>(.*)<\/token_url>#s', $res, $token_url);
			$this->status = 1;
			$this->token_id = $token_id[1];
			$this->token_url = $token_url[1];
			$this->token_footer_xhtml = $this->_formatfooter($token_url[1]);
			return true;																					// success
		} else {																							// failed
			unset($snoopy);
			//echo "Snoopy error: ".$snoopy->error."\n";																// for debug
			$this->error_code = 002;
			$this->error_message = 'The SourcedFrom Analytics API is not responding at this time, please try again.';
			$this->token_footer_xhtml = $this->_formatfooter('http://sourcedfrom.com/analytics/token.png');
			return false;
		}
		
		return false;
		
	}
	
	
	// xhtml formatted footer which can be used in entry (webpage/post/page/etc)
	function _formatfooter($token_url) {
		return '<p class="vcard author"><a title="SourcedFrom" href="http://sourcedfrom.com"><img style="border: 0px none;margin:0 0 -6px 0;padding:0;" src="'.$token_url.'" alt="SourcedFrom" width="15" height="21" /></a> Sourced from: <a class="url fn" style="margin:0;padding:0;" href="'.$this->guid.'">'.strip_tags($this->name_in_footer).'</a></p>';
	}

	
	// Strips returns and double spaces
	function _striptextspaces($txt) {
		$search = array('@\n@s',																			// Stip out new lines
					   '@\r@s',																				// Stip out returns
					   '@  @s',
					   '@  @s',
					   '@  @s',
					   '@<@s',
					   '@>@s',
					   '@\t@s'																				// Stip out tabs
		);	
		$text = preg_replace($search, ' ', $txt);
		unset($search);
		return trim($text);
	} //end func.

}
?>