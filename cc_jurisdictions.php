<?php
/**
* SourcedFrom Creative Commons License Jurisdictions
* Version: 0.1
* Author: SourcedFrom
* Author URI: http://sourcedfrom.com
*
* @package SourcedFrom
*/

/**
* Last Mod: 2 Jun 2009.
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


//the cc jurisdiction licenses as array
function sf_creativecommons_jurisdication_array() {
	$cc_array = array( 
					array( 'code' => 'Unported', 'country' => 'Unported', 'license' => '3.0' ),
					array( 'code' => 'ar', 'country' => 'Argentina', 'license' => '2.5' ),
					array( 'code' => 'at', 'country' => 'Austria', 'license' => '3.0' ),
					array( 'code' => 'au', 'country' => 'Australia', 'license' => '2.5' ),
					array( 'code' => 'be', 'country' => 'Belgium', 'license' => '2.0' ),
					array( 'code' => 'bg', 'country' => 'Bulgaria', 'license' => '2.5' ),
					array( 'code' => 'br', 'country' => 'Brazil', 'license' => '2.5' ),
					array( 'code' => 'ca', 'country' => 'Canada', 'license' => '2.5' ),
					array( 'code' => 'ch', 'country' => 'Switzerland', 'license' => '2.5' ),
					array( 'code' => 'cl', 'country' => 'Chile', 'license' => '2.0' ),
					array( 'code' => 'cn', 'country' => 'China Mainland', 'license' => '2.5' ),
					array( 'code' => 'co', 'country' => 'Colombia', 'license' => '2.5' ),
					array( 'code' => 'cz', 'country' => 'Czech Republic', 'license' => '3.0' ),
					array( 'code' => 'de', 'country' => 'Germany', 'license' => '3.0' ),
					array( 'code' => 'dk', 'country' => 'Denmark', 'license' => '2.5' ),
					array( 'code' => 'ec', 'country' => 'Ecuador', 'license' => '3.0' ),
					array( 'code' => 'es', 'country' => 'Spain', 'license' => '3.0' ),
					array( 'code' => 'fi', 'country' => 'Finland', 'license' => '1.0' ),
					array( 'code' => 'fr', 'country' => 'France', 'license' => '2.0' ),
					array( 'code' => 'gr', 'country' => 'Greece', 'license' => '3.0' ),
					array( 'code' => 'gt', 'country' => 'Guatemala', 'license' => '3.0' ),
					array( 'code' => 'hk', 'country' => 'Hong Kong', 'license' => '3.0' ),
					array( 'code' => 'hr', 'country' => 'Croatia', 'license' => '3.0' ),
					array( 'code' => 'hu', 'country' => 'Hungary', 'license' => '2.5' ),
					array( 'code' => 'il', 'country' => 'Israel', 'license' => '2.5' ),
					array( 'code' => 'in', 'country' => 'India', 'license' => '2.5' ),
					array( 'code' => 'it', 'country' => 'Italy', 'license' => '2.5' ),
					array( 'code' => 'jp', 'country' => 'Japan', 'license' => '2.1' ),
					array( 'code' => 'kr', 'country' => 'Korea', 'license' => '2.0' ),
					array( 'code' => 'mx', 'country' => 'Mexico', 'license' => '2.5' ),
					array( 'code' => 'my', 'country' => 'Malaysia', 'license' => '2.5' ),
					array( 'code' => 'nl', 'country' => 'Netherlands', 'license' => '3.0' ),
					array( 'code' => 'no', 'country' => 'Norway', 'license' => '3.0' ),
					array( 'code' => 'nz', 'country' => 'New Zealand', 'license' => '3.0' ),
					array( 'code' => 'pe', 'country' => 'Peru', 'license' => '2.5' ),
					array( 'code' => 'ph', 'country' => 'Philippines', 'license' => '3.0' ),
					array( 'code' => 'pl', 'country' => 'Poland', 'license' => '2.5' ),
					array( 'code' => 'pr', 'country' => 'Puerto Rico', 'license' => '3.0' ),
					array( 'code' => 'pt', 'country' => 'Portugal', 'license' => '2.5' ),
					array( 'code' => 'ro', 'country' => 'Romania', 'license' => '3.0' ),
					array( 'code' => 'rs', 'country' => 'Serbia', 'license' => '3.0' ),
					array( 'code' => 'scotland', 'country' => 'UK: Scotland', 'license' => '2.5' ),
					array( 'code' => 'se', 'country' => 'Sweden', 'license' => '2.5' ),
					array( 'code' => 'rs', 'country' => 'Singapore', 'license' => '3.0' ),
					array( 'code' => 'si', 'country' => 'Slovenia', 'license' => '2.5' ),
					array( 'code' => 'th', 'country' => 'Thailand', 'license' => '3.0' ),
					array( 'code' => 'tw', 'country' => 'Taiwan', 'license' => '2.5' ),
					array( 'code' => 'uk', 'country' => 'UK: England &amp; Wales', 'license' => '2.0' ),
					array( 'code' => 'us', 'country' => 'United States', 'license' => '3.0' ),
					array( 'code' => 'za', 'country' => 'South Africa', 'license' => '2.5' )		
					 );			
	return $cc_array;							
}


//return the cc country name and license version.
function sf_creativecommons_jurisdiction($country_code) {
	$cc_array = sf_creativecommons_jurisdication_array();
	for ($i = 0; $i < count($cc_array); $i++) {
		if ( $cc_array[$i]['code'] == $country_code ) {
			$resArray[0] = $cc_array[$i]['country'];
			$resArray[1] = $cc_array[$i]['license'];
			$resArray[2] = $i;
			return array( 'id' => $i, 'code' => $cc_array[$i]['code'], 'country' => $cc_array[$i]['country'], 'license' => $cc_array[$i]['license'] );
		}
	}
	//if nada return unported
	return array( 'id' => 0, 'code' => 'Unported', 'country' => 'Unported', 'license' => '3.0' );
}


//print the cc options list for pulldown menu in sf server settings form
function sf_creativecommons_jurisdication_options_list() {
	$cc_array = sf_creativecommons_jurisdication_array();
	for ($i = 0; $i < count($cc_array); $i++) {
		if ( $i === 0 ) {
			echo '<option value="'.$cc_array[$i]['code'].'" selected="selected">'.$cc_array[$i]['country'].'</option>'."\n";
		} else {
			echo '	<option value="'.$cc_array[$i]['code'].'">'.$cc_array[$i]['country'].'</option>'."\n";
		}
	}
}

?>
