<?php
/*
Plugin Name: Semiologic Affiliate
Plugin URI: http://www.semiologic.com/software/sem-affiliate/
Description: Automatically adds your affiliate ID to all links to Semiologic.
Version: 2.3 dev
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


if ( !defined('semiologic_affiliate_debug') )
	define('semiologic_affiliate_debug', false);


/**
 * semiologic_affiliate
 *
 * @package Semiologic Affiliate
 **/

class semiologic_affiliate {
	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @type object
	 */
	protected static $instance = NULL;

	/**
	 * URL to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_url = '';

	/**
	 * Path to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_path = '';

	/**
	 * Access this plugin’s working instance
	 *
	 * @wp-hook plugins_loaded
	 * @return  object of this class
	 */
	public static function get_instance()
	{
		NULL === self::$instance and self::$instance = new self;

		return self::$instance;
	}


	/**
	 * Loads translation file.
	 *
	 * Accessible to other classes to load different language files (admin and
	 * front-end for example).
	 *
	 * @wp-hook init
	 * @param   string $domain
	 * @return  void
	 */
	public function load_language( $domain )
	{
		load_plugin_textdomain(
			$domain,
			FALSE,
			$this->plugin_path . 'lang'
		);
	}

	/**
	 * Constructor.
	 *
	 *
	 */

	public function __construct() {
		$this->plugin_url    = plugins_url( '/', __FILE__ );
		$this->plugin_path   = plugin_dir_path( __FILE__ );
		$this->load_language( 'sem-semiologic-affiliate' );

		add_action( 'plugins_loaded', array ( $this, 'init' ) );
    }


	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
			// more stuff: register actions and filters
		if ( !is_admin() && semiologic_affiliate::get_campaign() ) {
			if ( !class_exists('anchor_utils') )
				include $this->plugin_path . '/anchor-utils/anchor-utils.php';

			if ( !semiologic_affiliate_debug ) {
			    add_filter('ob_filter_anchor', array($this, 'filter'));
			} else {
			    add_filter('filter_anchor', array($this, 'filter'));
			}
		}

		if ( is_admin() ) {
			add_action('admin_menu', array($this, 'admin_menu'));
			add_action('load-settings_page_semiologic_affiliate', array($this, 'semiologic_affiliate_admin'));
		}
	}

	/**
	* semiologic_affiliate_admin()
	*
	* @return void
	**/
	function semiologic_affiliate_admin() {
		include $this->plugin_path . '/sem-semiologic-affiliate-admin.php';
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

$semiologic_affiliate = semiologic_affiliate::get_instance();
