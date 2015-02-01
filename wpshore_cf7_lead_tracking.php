<?php
/*
Plugin Name: Contact Form 7 Lead info with country
Plugin URI: http://www.wpshore.com/plugins/contact-form-7-leads-tracking/
Description: Adds tracking info to contact form 7 outgoing emails when pasting the [tracking-info] shortcode in the Message body. The lead tracking info includes: Form Page URL, Original Referrer, Landing Page, User IP, Country of the User IP and Browser. In order to display the Country it needs the "GeoIP Detection" plugin that can be found in the WordPress plugin repository.
Author: Apasionados
Author URI: http://apasionados.es/
Version: 1.1
Text Domain: wpshore_cf7_lead_tracking
*/

/*  Copyright 2013 Nablasol
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

$plugin_header_translate = array( __('Contact Form 7 Leads Tracking', 'wpshore_cf7_lead_tracking'), __('Contact Form 7 Leads Tracking Enhanced', 'wpshore_cf7_lead_tracking'), __('Adds tracking info to contact form 7 outgoing emails when pasting the [tracking-info] shortcode in the Message body. The lead tracking info includes: Form Page URL, Original Referrer, Landing Page, User IP, Country of the User IP and Browser. In order to display the Country it needs the "GeoIP Detection" plugin that can be found in the WordPress plugin repository.', 'wpshore_cf7_lead_tracking') );

if ( ! function_exists('is_plugin_active')) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if ( !is_plugin_active( 'contact-form-7-leads-tracking/wpshore_cf7_lead_tracking.php' ) ) {
	add_action( 'admin_init', 'wpshore_cf7_lead_tracking_load_language' );
	function wpshore_cf7_lead_tracking_load_language() {
		load_plugin_textdomain( 'wpshore_cf7_lead_tracking', false,  dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	// Add the info to the email
	function wpshore_wpcf7_before_send_mail($array) {
	
		load_plugin_textdomain( 'wpshore_cf7_lead_tracking', false,  dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	
		global $wpdb;
	
		if(wpautop($array['body']) == $array['body']) // The email is of HTML type
			$lineBreak = "<br/>";
		else
			$lineBreak = "\n";
			
		$trackingInfo .= $lineBreak . __('-- Tracking Info --','wpshore_cf7_lead_tracking') . $lineBreak;
		
		$trackingInfo .= __('The user filled the form on:','wpshore_cf7_lead_tracking') . ' ' . $_SERVER['HTTP_REFERER'] . $lineBreak;
	
		if (isset ($_SESSION['OriginalRef']) )
			$trackingInfo .= __('The user came to your website from:','wpshore_cf7_lead_tracking') . ' ' . $_SESSION['OriginalRef'] . $lineBreak;
			
		if (isset ($_SESSION['LandingPage']) )
			$trackingInfo .= __('Landing page on your website:','wpshore_cf7_lead_tracking') . ' ' . $_SESSION['LandingPage'] . $lineBreak;
	
		if ( isset ($_SERVER["REMOTE_ADDR"]) )
		$trackingInfo .= __('IP:','wpshore_cf7_lead_tracking') . ' ' . $_SERVER["REMOTE_ADDR"] . $lineBreak;

		if ( is_plugin_active( 'geoip-detect/geoip-detect.php' ) ) {
			//$trackingCountry = geoip_detect_get_info_from_current_ip(); This function does not provide Region and City info.
			$trackingCountry = geoip_detect_get_info_from_ip($_SERVER["REMOTE_ADDR"]); // This function provides Region and City info.
			$trackingInfo .= __('Country:','wpshore_cf7_lead_tracking') . ' ' . $trackingCountry->country_name . ' (' . $trackingCountry->country_code . ' - ' . $trackingCountry->continent_code . ')';
			if (!empty($trackingCountry->region_name)) {
				$trackingInfo .= ' - ' . __('Region:','wpshore_cf7_lead_tracking') . ' ' . $trackingCountry->region_name . '(' . $trackingCountry->region . ')';
			}
			if (!empty($trackingCountry->city)) {			
				$trackingInfo .= ' - ' . __('Postal Code + City:','wpshore_cf7_lead_tracking') . ' ' . $trackingCountry->postal_code . ' ' . $trackingCountry->city;		
			}
			$trackingInfo .= $lineBreak;
		}
		
		if ( isset ($_SERVER["HTTP_X_FORWARDED_FOR"]))
			$trackingInfo .= __('Proxy Server IP:','wpshore_cf7_lead_tracking') . ' ' . $_SERVER["HTTP_X_FORWARDED_FOR"] . $lineBreak . $lineBreak;
	
		if ( isset ($_SERVER["HTTP_USER_AGENT"]) )
			$trackingInfo .= __('Browser is:','wpshore_cf7_lead_tracking') . ' ' . $_SERVER["HTTP_USER_AGENT"] . $lineBreak;
	
		$array['body'] = str_replace('[tracking-info]', $trackingInfo, $array['body']);
	
		return $array;
	
	}
	add_filter('wpcf7_mail_components', 'wpshore_wpcf7_before_send_mail');

	// Original Referrer 
	function wpshore_set_session_values() 
	{
		if (!session_id()) 
		{
			session_start();
		}
	
		if (!isset($_SESSION['OriginalRef'])) 
		{
			$_SESSION['OriginalRef'] = $_SERVER["HTTP_REFERER"]; 
		}
	
		if (!isset($_SESSION['LandingPage'])) 
		{
			$_SESSION['LandingPage'] = "http://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]; 
		}
	
	}
	add_action('init', 'wpshore_set_session_values');
}
