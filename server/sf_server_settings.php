<?php
/**
* SourcedFrom Server Settings for WordPress
* Version: 0.1
* Author: SourcedFrom
* Author URI: http://sourcedfrom.com
*
* @package SourcedFrom
*/

/**
* Last Mod: 11 Jun 2009.
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


if ( strpos(dirname(__FILE__), "mu-plugins") == true ) {
	require_once(WP_MUPLUGIN_DIR . '/sourcedfrom/cc_jurisdictions.php'); 
} else {
	require_once(WP_PLUGIN_DIR . '/sourcedfrom/cc_jurisdictions.php'); 
}

echo'<div class="wrap">
';
	if (SF_WP_VER_GTE_27) {	
		echo '	<div id="icon-options-general" class="icon32"><br /></div>
		';
	}

	//TO DO if WPMU change to save in each blog options
	if ( isset($_POST['action']) ) {
		if ( $_POST['action'] == 'update_sourcedfrom_server' ) {
		
			if (!get_option('sf_installed')) {
				sf_install();
			}
		
			//must have an account name!
			if ( $_POST['sf_account_name'] != '' ) {
				delete_option("sf_account_name");
				add_option("sf_account_name", $_POST['sf_account_name'], '', 'yes');
				delete_option("sf_sourcedfrom_username");
				add_option("sf_sourcedfrom_username", $_POST['sf_sourcedfrom_username'], '', 'yes');
				if (!isset($_POST['sf_pp'])) { $_POST['sf_pp'] = 'pp'; }
				if ( $_POST['sf_pp'] == '' ) { $_POST['sf_pp'] = 'pp'; }
				delete_option("sf_pp");
				add_option("sf_pp", $_POST['sf_pp'], '', 'yes');
				
				if (!isset($_POST['sf_can_modify'])) { $_POST['sf_can_modify'] = 'false'; }
				if ( $_POST['sf_can_modify'] != 'true' ) { $_POST['sf_can_modify'] = 'false'; }				
				delete_option("sf_can_modify");
				add_option("sf_can_modify", $_POST['sf_can_modify'], '', 'yes');
				if (!isset($_POST['sf_use_copyright'])) { $_POST['sf_use_copyright'] = 'false'; }				
				if ( $_POST['sf_use_copyright'] != 'true' ) { $_POST['sf_use_copyright'] = 'false'; }				
				delete_option("sf_use_copyright");
				add_option("sf_use_copyright", $_POST['sf_use_copyright'], '', 'yes');	
				delete_option("sf_copyright_text");
				if (!isset($_POST['sf_copyright_text'])) { $_POST['sf_copyright_text'] = ''; }
				add_option("sf_copyright_text", $_POST['sf_copyright_text'], '', 'yes');	
				delete_option("sf_use_cc");
				if (!isset($_POST['sf_use_cc'])) { $_POST['sf_use_cc'] = 'false'; $_POST['sf_cc_commercial'] = 'true'; }
				if ( $_POST['sf_use_cc'] != 'true' ) { $_POST['sf_use_cc'] = 'false'; $_POST['sf_cc_commercial'] = 'true'; }
				add_option("sf_use_cc", $_POST['sf_use_cc'], '', 'yes');
				
				if (!isset($_POST['sf_cc_commercial'])) { $_POST['sf_cc_commercial'] = 'false'; }
				if ( $_POST['sf_cc_commercial'] != 'false' ) { $_POST['sf_cc_commercial'] = 'true'; }
				if ( $_POST['sf_cc_commercial'] == '' ) { $_POST['sf_cc_commercial'] = 'true'; }
				delete_option("sf_cc_commercial");
				add_option("sf_cc_commercial", $_POST['sf_cc_commercial'], '', 'yes');
				
				if (!isset($_POST['sf_cc_modifications'])) { $_POST['sf_cc_modifications'] = 'yes'; }
				if ( $_POST['sf_cc_modifications'] == '' ) { $_POST['sf_cc_modifications'] = 'no'; }
				delete_option("sf_cc_modifications");
				add_option("sf_cc_modifications", $_POST['sf_cc_modifications'], '', 'yes');
				
				if (!isset($_POST['sf_cc_jurisdiction'])) { $_POST['sf_cc_jurisdiction'] = 'unported'; }
				if ( $_POST['sf_cc_jurisdiction'] == '' ) { $_POST['sf_cc_jurisdiction'] = 'unported'; }	
				delete_option("sf_cc_jurisdiction");
				add_option("sf_cc_jurisdiction", $_POST['sf_cc_jurisdiction'], '', 'yes');
				delete_option("sf_avatar_uri");
				add_option("sf_avatar_uri", $_POST['sf_avatar_uri'], '', 'yes');
				if ( $_POST['sf_status'] == '' || $_POST['sf_status'] != 'true' ) { $_POST['sf_status'] = 'false'; }	
				delete_option("sf_status");
				add_option("sf_status", $_POST['sf_status'], '', 'yes');
				
				global $blog_id;
				if ( $_POST['sf_status'] == 'false' && $blog_id != 1 ) {
					$save_msg = '<strong>API server turned off</strong> (can be overridden by WPMU Administrators\' settings)';
				} elseif ( $_POST['sf_status'] == 'false' && $blog_id == 1 ) {
					$save_msg = '<strong>API server turned off</strong>';
				} else {
					$save_msg = '<strong>Settings saved</strong>';
				}
				
				//Reset the feed/api makes sure will work and if stops working will reset (flush) on resaving or turning api server off then on. This fixes a known bug in WP add_feed.
				global $wp_rewrite;
				$wp_rewrite->flush_rules();
				
				//Settings saved.
				sf_display_msg('message', $save_msg);
			} else {
				sf_display_msg('error', 'You must have an account name! Please enter one.');
			} //end if must have account name.
		} //end if post action.
	} //end if.

	$copyright_text = get_option('sf_copyright_text');
	if ( $copyright_text == '' ) {			
		$copyright_text = 'Copyright All Rights Reserved '.date('Y').'.';
	}
	if (get_option('sf_use_cc') == 'true') {
		$sf_use_cc_checked = ' checked="checked"';
	} else {
		$sf_use_cc_checked = '';
	}
	if (get_option('sf_use_copyright') == 'true') {
		$sf_use_copyright_checked = ' checked="checked"';
	} else {
		$sf_use_copyright_checked = '';
	}
	if (get_option('sf_cc_commercial') == 'true') {
		$sf_cc_commercial_yes_checked = ' checked="checked"';
		$sf_cc_commercial_no_checked = '';
	} else {
		$sf_cc_commercial_no_checked = ' checked="checked"';
		$sf_cc_commercial_yes_checked = '';
	}	
	if (get_option('sf_cc_modifications') == 'yes') {
		$sf_cc_modifications_yes_checked = ' checked="checked"';
		$sf_cc_modifications_yesSA_checked = '';
		$sf_cc_modifications_no_checked = '';
	} elseif (get_option('sf_cc_modifications') == 'sa') {
		$sf_cc_modifications_yes_checked = '';
		$sf_cc_modifications_yesSA_checked = ' checked="checked"';
		$sf_cc_modifications_no_checked = '';
	} else {
		$sf_cc_modifications_yes_checked = '';
		$sf_cc_modifications_yesSA_checked = '';
		$sf_cc_modifications_no_checked = ' checked="checked"';
	}
	if (get_option('sf_cc_commercial') == '') {
		$sf_cc_commercial_yes_checked = ' checked="checked"';
		$sf_cc_commercial_no_checked = '';
	}
	if (get_option('sf_cc_modifications') == '') {
		$sf_cc_modifications_yes_checked = ' checked="checked"';
		$sf_cc_modifications_yesSA_checked = '';
		$sf_cc_modifications_no_checked = '';
	}
	if (get_option('sf_can_modify') == 'false') {
		$sf_can_modify_checked = '';
	} else {
		$sf_can_modify_checked = ' checked="checked"';
	}
	if (get_option('sf_can_modify') == '') {
		$sf_can_modify_checked = ' checked="checked"';
	}	
	if (get_option('sf_pp') == 'post') {
		$sf_pp_post_only_checked = ' checked="checked"';
		$sf_pp_page_only_checked = '';
		$sf_pp_both_checked = '';
	} elseif (get_option('sf_pp') == 'page') {
		$sf_pp_post_only_checked = '';
		$sf_pp_page_only_checked = ' checked="checked"';
		$sf_pp_both_checked = '';
	} else {
		$sf_pp_post_only_checked = '';
		$sf_pp_page_only_checked = '';
		$sf_pp_both_checked = ' checked="checked"';
	}
	
?>
<h2>SourcedFrom Server Settings</h2>

<div class="clear" style="padding-top:5px"></div>
<?php
	global $sf_display_msg;
	if ( $sf_display_msg ) { echo $sf_display_msg; }
?>
<form name="accountFM" method="post" action="">
<input type="hidden" name="option_page" value="SourcedFrom Server" />
<input type="hidden" name="action" value="update_sourcedfrom_server" />
<input type="hidden" name="sf_status" value="true" />
<input type="hidden" name="_wp_http_referer" value="/wordpress/wp-admin/options-reading.php" />
<script type="text/javascript">
function license_onload() {
	if ( document.forms[0].sf_use_cc.checked == false ) {
		document.forms[0].sf_cc_commercial[0].disabled = true;
		document.forms[0].sf_cc_commercial[1].disabled = true;
		document.forms[0].sf_cc_modifications[0].disabled = true;
		document.forms[0].sf_cc_modifications[1].disabled = true;
		document.forms[0].sf_cc_modifications[2].disabled = true;
		document.forms[0].sf_cc_jurisdiction.disabled = true;
		document.getElementById("cc_info_form").style.color='#808080';
		document.getElementById("copyright_info_form").style.color='#808080';
		document.forms[0].sf_copyright_text.disabled = true;
	} else {
		document.forms[0].sf_use_copyright.disabled = true;
		document.forms[0].sf_copyright_text.disabled = true;
		document.getElementById("copyright_info_form").style.color='#808080';
		document.forms[0].sf_can_modify.disabled = true;
	}
	if ( document.forms[0].sf_use_copyright.checked == true ) {
		document.forms[0].sf_cc_commercial[0].disabled = true;
		document.forms[0].sf_cc_commercial[1].disabled = true;
		document.forms[0].sf_cc_modifications[0].disabled = true;
		document.forms[0].sf_cc_modifications[1].disabled = true;
		document.forms[0].sf_cc_modifications[2].disabled = true;
		document.forms[0].sf_cc_jurisdiction.disabled = true;
		document.getElementById("cc_info_form").style.color='#808080';
		document.getElementById("copyright_info_form").style.color='#000';
		document.forms[0].sf_copyright_text.disabled = false;
		document.forms[0].sf_use_cc.disabled = true;
	}
	
}
window.onload=license_onload;
function cc_use_onclick() {
	if ( document.forms[0].sf_use_cc.checked == true ) {
		document.forms[0].sf_cc_commercial[0].disabled = false;
		document.forms[0].sf_cc_commercial[1].disabled = false;
		document.forms[0].sf_cc_modifications[0].disabled = false;
		document.forms[0].sf_cc_modifications[1].disabled = false;
		document.forms[0].sf_cc_modifications[2].disabled = false;
		document.forms[0].sf_cc_jurisdiction.disabled = false;
		document.getElementById("cc_info_form").style.color='#000';
		document.getElementById("copyright_info_form").style.color='#808080';
		document.forms[0].sf_copyright_text.disabled = true;
		document.forms[0].sf_use_copyright.disabled = true;
		document.forms[0].sf_use_cc.disabled = false;
		document.forms[0].sf_use_copyright.checked == false; 
		
		document.forms[0].sf_can_modify.disabled = true;
		
	} else {
		document.forms[0].sf_cc_commercial[0].disabled = true;
		document.forms[0].sf_cc_commercial[1].disabled = true;
		document.forms[0].sf_cc_modifications[0].disabled = true;
		document.forms[0].sf_cc_modifications[1].disabled = true;
		document.forms[0].sf_cc_modifications[2].disabled = true;
		document.forms[0].sf_cc_jurisdiction.disabled = true;
		document.getElementById("cc_info_form").style.color='#808080';
		document.forms[0].sf_use_cc.checked == false;
		if ( document.forms[0].sf_use_copyright.checked == true ) {
			document.getElementById("copyright_info_form").style.color='#000';
			document.forms[0].sf_copyright_text.disabled = false;
		}
		document.forms[0].sf_use_copyright.disabled = false;
		if ( document.forms[0].sf_use_copyright.checked == true ) {
			document.forms[0].sf_use_cc.disabled = true;
		}
		document.forms[0].sf_can_modify.disabled = false;
	}
}
function copyright_use_onclick() {
	if ( document.forms[0].sf_use_copyright.checked == true ) {
		document.forms[0].sf_cc_commercial[0].disabled = true;
		document.forms[0].sf_cc_commercial[1].disabled = true;
		document.forms[0].sf_cc_modifications[0].disabled = true;
		document.forms[0].sf_cc_modifications[1].disabled = true;
		document.forms[0].sf_cc_modifications[2].disabled = true;
		document.forms[0].sf_cc_jurisdiction.disabled = true;
		document.getElementById("cc_info_form").style.color='#808080';
		document.getElementById("copyright_info_form").style.color='#000';
		document.forms[0].sf_copyright_text.disabled = false;
		document.forms[0].sf_use_copyright.disabled = false;
		document.forms[0].sf_use_cc.disabled = true;

	} else {
		if ( document.forms[0].sf_use_cc.checked == true ) {
			document.forms[0].sf_cc_commercial[0].disabled = false;
			document.forms[0].sf_cc_commercial[1].disabled = false;
			document.forms[0].sf_cc_modifications[0].disabled = false;
			document.forms[0].sf_cc_modifications[1].disabled = false;
			document.forms[0].sf_cc_modifications[2].disabled = false;
			document.forms[0].sf_cc_jurisdiction.disabled = false;
			document.getElementById("cc_info_form").style.color='#000';
		}
		document.forms[0].sf_use_cc.disabled = false;
		document.forms[0].sf_copyright_text.disabled = true;
		document.getElementById("copyright_info_form").style.color='#808080';
	}
}
</script>

<div id="sf_status"<?php if (get_option('sf_status') == 'false' && get_option('sf_status') != '') { echo 'style="display:none;"'; } ?>>
<table class="form-table">

<tr valign="top" class="form-required">
<th scope="row"><label>Account Name (required)</label></th>
<td><input type="text" name="sf_account_name" id="sf_account_name" class="regular-text" size="40" value="<?php echo get_option('sf_account_name'); ?>" />
<span class="setting-description">Name or Organization or Blog (will be displayed to client publishers and in the published entries footer, EG: Sourced from: ____).</span></td>
</tr>

<tr valign="top">
<th scope="row"><label>SourcedFrom Username (optional)</label></th>
<td><input type="text" name="sf_sourcedfrom_username" id="sf_sourcedfrom_username" class="regular-text" size="40" value="<?php echo get_option('sf_sourcedfrom_username'); ?>" />
<span class="setting-description">If you have a SourcedFrom Analytics account enter your username to have SourcedFrom track your entries. If you don't yet have a SourcedFrom Analytics account, <a href="http://sourcedfrom.com/analytics/app/signup.php" target="_blank">please create one</a>.</span></td>
</tr>

<tr valign="top">
<th scope="row"><label>Available for publishing</label></th>
<td>
<label><input type="radio" value="pp" name="sf_pp"<?php echo $sf_pp_both_checked; ?> /> Posts and Pages</label><br/>
<label><input type="radio" value="post" name="sf_pp"<?php echo $sf_pp_post_only_checked; ?> /> Only Posts</label><br/>
<label><input type="radio" value="page" name="sf_pp"<?php echo $sf_pp_page_only_checked; ?> /> Only Pages</label>
</td>
</tr>

<tr valign="top">
<th scope="row"><label>Allow posts to be modified</label></th>
<td><label><input type="checkbox" name="sf_can_modify" value="true"<?php echo $sf_can_modify_checked; ?> /> Publishers can modify posts/pages</label></td>
</tr>

<tr valign="top">
<th scope="row"><label>License your entries under Copyright (optional)</label></th>
<td><label><input type="checkbox" name="sf_use_copyright" value="true" onclick="copyright_use_onclick()"<?php echo $sf_use_copyright_checked; ?> /> Use Copyright License</label><div id="copyright_info_form">
&#169; <input type="text" name="sf_copyright_text" id="sf_copyright_text" class="regular-text" size="40" value="<?php echo $copyright_text; ?>" /></div></td>
</tr>

<tr valign="top">	
<th scope="row"><label>License your entries under Creative Commons (optional)</label></th>
<td>			
				
<div id="cc_info">
<label><input type="checkbox" name="sf_use_cc" value="true" onclick="cc_use_onclick()"<?php echo $sf_use_cc_checked; ?> /> Use Creative Commons License</label>
<div id="cc_info_form">
<h3>Allow commercial use of your work?</h3>

<label><input type="radio" value="true" name="sf_cc_commercial"<?php echo $sf_cc_commercial_yes_checked; ?> /> Yes<img src="<?php echo get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/images/info-icon.gif'; ?>" alt="info" border="0" class="infohelp" title="Commercial Use: The licensor permits others to copy, distribute, display, and perform the work, including for commercial purposes."/></label><br/>
<label><input type="radio" value="false" name="sf_cc_commercial"<?php echo $sf_cc_commercial_no_checked; ?> /> No<img src="<?php echo get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/images/info-icon.gif'; ?>" alt="info" border="0" class="infohelp" title="NonCommercial: The licensor permits others to copy, distribute, display, and perform the work for non-commercial purposes only."/></label><br/>
				
<h3>Allow modifications of your work?</h3>
<label><input type="radio" value="yes" name="sf_cc_modifications"<?php echo $sf_cc_modifications_yes_checked; ?> /> Yes<img src="<?php echo get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/images/info-icon.gif'; ?>" alt="info" border="0" class="infohelp" title="Allow Derivative Works: The licensor permits others to copy, distribute, display and perform the work, as well as make derivative works based on it."/></label><br/>
<label><input type="radio" value="sa" name="sf_cc_modifications"<?php echo $sf_cc_modifications_yesSA_checked; ?> /> Yes, as long as others share alike<img src="<?php echo get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/images/info-icon.gif'; ?>" alt="info" border="0" class="infohelp" title="Share Alike: The licensor permits others to distribute derivative works only under the same license or one compatible with the one that governs the licensor's work."/></label><br/>
<label><input type="radio" value="no" name="sf_cc_modifications"<?php echo $sf_cc_modifications_no_checked; ?> /> No<img src="<?php echo get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/images/info-icon.gif'; ?>" alt="info" border="0" class="infohelp" title="No Derivative Works: The licensor permits others to copy, distribute, display and perform only unaltered copies of the work, not derivative works based on it."/></label>
				
<h3>Jurisdiction of your license<img src="<?php echo get_bloginfo('wpurl').'/wp-content/plugins/sourcedfrom/images/info-icon.gif'; ?>" alt="info" border="0" class="infohelp" title="Jurisdiction: If you desire a license governed by the Copyright Law of a specific jurisdiction, please select the appropriate jurisdiction."/></h3>
<select name="sf_cc_jurisdiction" id="sf_cc_jurisdiction_Obj">
	<?php sf_creativecommons_jurisdication_options_list(); ?>
</select>
<script type="text/javascript">
	var selObj = document.getElementById('sf_cc_jurisdiction_Obj');
	selObj.selectedIndex = <?php $selectedCountry = sf_creativecommons_jurisdiction(get_option('sf_cc_jurisdiction')); echo $selectedCountry['id']; ?>;
</script>	
</div>
</div>
</td>
</tr>


<tr valign="top">
<th scope="row"><label>Email or URL address to Avatar</label></th>
<td><input type="text" name="sf_avatar_uri" id="sf_avatar_uri" class="regular-text" size="40" value="<?php echo get_option('sf_avatar_uri'); ?>" />
<span class="setting-description">Can visit <a href="http://en.gravatar.com/" target="_blank">Gravatar</a> to generate if don't have one.
 Or just use this current avatar&nbsp;&nbsp;<?php 
	if ( substr(get_option('sf_avatar_uri'), 0, 4) == 'http' ) {
		echo get_avatar(get_option('sf_avatar_uri'), '32', get_option('sf_avatar_uri'), '');
	} elseif ( strpos(get_option('sf_avatar_uri'), '@') ) {
		echo get_avatar(get_option('sf_avatar_uri'), '32');
	} else {
		echo get_avatar(get_settings('admin_email'), '32');
	}
?></span></td>
</tr>

</table>
</div>

<p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Changes" /></p>
<?php if (get_option('sf_status') != '') { 
	if (get_option('sf_status') == 'true') {
		$turn_what = 'off';
		$turn_bool = 'false';
	} else {
		$turn_what = 'on';
		$turn_bool = 'true';
	}
?>
<script type="text/javascript">
	function change_sf_server_status() {
		document.forms[0].sf_status.value = '<?php echo $turn_bool; ?>';
	}
	<?php if (get_option('sf_status') == 'true') { ?>
	if (document.getElementById) {
		// this is the way the standards work
		var style = document.getElementById('sf_status').style;
		style.display = style.display? "":"block";
	} else if (document.all) {
		// this is the way old msie versions work
		var style = document.all['sf_status'].style;
		style.display = style.display? "":"block";
	} else if (document.layers) {
		// this is the way nn4 works
		var style = document.layers['sf_status'].style;
		style.display = style.display? "":"block";
	}
	<?php } ?>
</script>	
<p style="margin-top:2em;"><input type="submit" onclick="return change_sf_server_status();" value="Turn <?php echo $turn_what; ?> the SourcedFrom server" class="button" /></p>
<?php } ?>
</form>

</div>