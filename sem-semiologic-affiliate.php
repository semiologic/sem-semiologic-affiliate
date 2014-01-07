<?php
/*
Plugin Name: Semiologic Affiliate
Plugin URI: http://www.semiologic.com/software/sem-affiliate/
Description: Automatically adds your affiliate ID to all links to Semiologic.
Version: 2.2.1
Author: Denis de Bernardy & Mike Koepke
Author URI: http://www.getsemiologic.com
Text Domain: sem-semiologic-affiliate
Domain Path: /lang
License: Dual licensed under the MIT and GPLv2 licenses
*/

/*
Terms of use
------------

This software is copyright Denis de Bernardy & Mike Koepke, and is distributed under the terms of the MIT and GPLv2 licenses.
**/


load_plugin_textdomain('sem-semiologic-affiliate', false, dirname(plugin_basename(__FILE__)) . '/lang');

if ( !defined('semiologic_affiliate_debug') )
	define('semiologic_affiliate_debug', false);


/**
 * semiologic_affiliate
 *
 * @package Semiologic Affiliate
 **/

class semiologic_affiliate {
    /**
     * semiologic_affiliate()
     */
	public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));

        if ( !is_admin() && semiologic_affiliate::get_campaign() ) {
	        if ( !class_exists('anchor_utils') )
	     				include dirname(__FILE__) . '/anchor-utils/anchor-utils.php';

        	if ( !semiologic_affiliate_debug ) {
        		add_filter('ob_filter_anchor', array($this, 'filter'));
        	} else {
        		add_filter('filter_anchor', array($this, 'filter'));
        	}
        }
    }


    /**
	 * admin_menu()
	 *
	 * @return void
	 **/

	static function admin_menu() {
		if ( function_exists('is_super_admin') && !is_super_admin() )
			return;
		
		add_options_page(
			__('Semiologic Affiliate', 'sem-semiologic-affiliate'),
			__('Semiologic Affiliate', 'sem-semiologic-affiliate'),
			'manage_options',
			'semiologic_affiliate',
			array('semiologic_affiliate_admin', 'edit_options')
			);
	} # admin_menu()
	
	
	/**
	 * get_campaign()
	 *
	 * @return string $aff_id
	 **/

	static function get_campaign() {
		$o = get_site_option('sem_campaign_key');
		
		if ( $o === false )
			$o = semiologic_affiliate::init_campaign();
		
		return $o;	
	} # get_campaign()
	
	
	/**
	 * init_campaign()
	 *
	 * @return string $aff_id
	 **/

	function init_campaign() {
		$o = get_option('sem_semiologic_affiliate_params');
		
		if ( $o !== false ) {
			$o = isset($options['aff_id']) ? trim($options['aff_id']) : '';
			delete_option('sem_semiologic_affiliate_params');
		}
		
		if ( !$o ) {
			$o = get_option('sem_campaign_key') ? get_option('sem_campaign_key') : '';
		}
		
		update_site_option('sem_campaign_key', $o);
		
		return $o;
	} # init_campaign()
	
	
	/**
	 * filter()
	 *
	 * @param array $anchor
	 * @return array $anchor
	 **/

	function filter($anchor) {
		if ( !preg_match("/^https?:\/\/(?:www\.)?(?:get)?semiologic\.com\b/", $anchor['attr']['href']) )
			return $anchor;
		
		if ( strpos($anchor['attr']['href'], '?') === false )
			$anchor['attr']['href'] .= '?aff=' . urlencode(semiologic_affiliate::get_campaign());
		
		return $anchor;
	} # filter()
} # semiologic_affiliate


function semiologic_affiliate_admin() {
	include dirname(__FILE__) . '/sem-semiologic-affiliate-admin.php';
}

add_action('load-settings_page_semiologic_affiliate', 'semiologic_affiliate_admin');

$semiologic_affiliate = new semiologic_affiliate();
