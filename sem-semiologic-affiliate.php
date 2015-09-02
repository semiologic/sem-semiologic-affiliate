<?php
/*
Plugin Name: Semiologic Affiliate
Plugin URI: http://www.semiologic.com/software/sem-affiliate/
Description: RETIRED - Automatically adds your affiliate ID to all links to Semiologic.
Version: 2.7.3
Author: Denis de Bernardy & Mike Koepke
Author URI: https://www.semiologic.com
Text Domain: sem-semiologic-affiliate
Domain Path: /lang
License: Dual licensed under the MIT and GPLv2 licenses
*/

/*
Terms of use
------------

This software is copyright Denis de Bernardy & Mike Koepke, and is distributed under the terms of the MIT and GPLv2 licenses.
**/

/*
 * This plugin has been retired.  No further development will occur on it.
 * */

// Disable the plugin

$active_plugins = get_option('active_plugins');

if ( !is_array($active_plugins) )
{
	$active_plugins = array();
}

foreach ( (array) $active_plugins as $key => $plugin )
{
	if ( $plugin == 'sem-semiologic-affiliate/sem-semiologic-affiliate.php' )
	{
		unset($active_plugins[$key]);
		break;
	}
}

sort($active_plugins);

update_option('active_plugins', $active_plugins);



if ( !defined('semiologic_affiliate_debug') )
	define('semiologic_affiliate_debug', false);


/**
 * semiologic_affiliate
 *
 * @package Semiologic Affiliate
 **/

class semiologic_affiliate {

	protected $anchor_utils;

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
			dirname(plugin_basename(__FILE__)) . '/lang'
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
			if ( !class_exists('sem_affiliate_anchor_utils') )
				include $this->plugin_path . '/sem-affiliate-anchor-utils.php';

			$this->anchor_utils = new sem_affiliate_anchor_utils( $this );
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
	 * process_content()
	 *
	 * @param string $text
	 * @param string $context
	 * @return string $text
	 **/

	function process_content($text, $context = "global") {
		// short circuit if there's no anchors at all in the text
		if ( false === stripos($text, '<a ') )
			return($text);

		$escape_needed = array( 'global', 'content', 'widgets' );
		if ( in_array($context, $escape_needed ) ) {
			global $escape_anchor_filter;
			$escape_anchor_filter = array();

			$text = $this->escape($text, $context);
		}

		// find all occurrences of anchors and fill matches with links
		preg_match_all("/
					<\s*a\s+
					([^<>]+)
					>
					(.*?)
					<\s*\/\s*a\s*>
					/isx", $text, $matches, PREG_SET_ORDER);

		$raw_links = array();
		$processed_links = array();

		foreach ($matches as $match)
		{
			$updated_link = $this->process_link($match);
			if ( $updated_link ) {
				$raw_links[]     = $match[0];
				$processed_links[] = $updated_link;
			}
		}

		if ( !empty($raw_links) && !empty($processed_links) )
			$text = str_replace($raw_links, $processed_links, $text);

		if ( in_array($context, $escape_needed ) ) {
			$text = $this->unescape($text);
		}

		return $text;
	} # process_content()

	/**
	 * escape()
	 *
	 * @param string $text
	 * @param string $context
	 * @return string $text
	 **/
	function escape($text, $context) {
		global $escape_anchor_filter;

		if ( !isset($escape_anchor_filter) )
			$escape_anchor_filter = array();

		$exclusions = array();

		if ( $context == 'global' )
			$exclusions['head'] = "/
							.*?
							<\s*\/\s*head\s*>
							/isx";

		$exclusions['blocks'] = "/
						<\s*(script|style|object|textarea)(?:\s.*?)?>
						.*?
						<\s*\/\s*\\1\s*>
						/isx";

		foreach ( $exclusions as $regex ) {
			$text = preg_replace_callback($regex, array($this, 'escape_callback'), $text);
		}

		return $text;
	} # escape()


	/**
	 * escape_callback()
	 *
	 * @param array $match
	 * @return string $text
	 **/

	function escape_callback($match) {
		global $escape_anchor_filter;

		$tag_id = "----escape_auto_thickbox:" . md5($match[0]) . "----";
		$escape_anchor_filter[$tag_id] = $match[0];

		return $tag_id;
	} # escape_callback()


	/**
	 * unescape()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function unescape($text) {
		global $escape_anchor_filter;

		if ( !$escape_anchor_filter )
			return $text;

		$unescape = array_reverse($escape_anchor_filter);

		return str_replace(array_keys($unescape), array_values($unescape), $text);
	} # unescape()


	/**
	 * filter_callback()
	 *
	 * @param array $match
	 * @return string $str
	 **/

	function process_link($match) {
		# parse anchor
		$anchor = $this->parse_anchor($match);

		if ( !$anchor )
			return false;

		# filter anchor
		$anchor = $this->filter_anchor( $anchor );

		if ( $anchor )
			$anchor = $this->build_anchor($match[0], $anchor);

		return $anchor;
	} # process_link()


	/**
	 * parse_anchor()
	 *
	 * @param array $match
	 * @return array $anchor
	 **/

	function parse_anchor($match) {
		$anchor = array();
		$anchor['attr'] = $this->parseAttributes( $match[1] );

		if ( !is_array($anchor['attr']) || empty($anchor['attr']['href']) # parser error or no link
			|| trim($anchor['attr']['href']) != esc_url($anchor['attr']['href'], null, 'db') ) # likely a script
			return false;

		foreach ( array('class', 'rel') as $attr ) {
			if ( !isset($anchor['attr'][$attr]) ) {
				$anchor['attr'][$attr] = array();
			} else {
				$anchor['attr'][$attr] = explode(' ', $anchor['attr'][$attr]);
				$anchor['attr'][$attr] = array_map('trim', $anchor['attr'][$attr]);
			}
		}

		$anchor['body'] = $match[2];

		$anchor['attr']['href'] = @html_entity_decode($anchor['attr']['href'], ENT_COMPAT, get_option('blog_charset'));

		return $anchor;
	} # parse_anchor()

	/**
	 * build_anchor()
	 *
	 * @param $link
	 * @param array $anchor
	 * @return string $anchor
	 */

	function build_anchor($link, $anchor) {

		$attrs = array( 'href');

		foreach ( $attrs as $attr ) {
			if ( isset($anchor['attr'][$attr]) ) {
				$new_attr_value = null;
				$values = $anchor['attr'][$attr];
				if ( is_array($values) ) {
					$values = array_unique($values);
					if ( $values )
						$new_attr_value = implode(' ',  $values );
				} else {
					$new_attr_value = $values;
				}

				if ( $new_attr_value )
					$link = $this->update_attribute($link, $attr, $new_attr_value);
			}
		}

		return $link;
	} # build_anchor()


	/**
	 * Updates attribute of an HTML tag.
	 *
	 * @param $html
	 * @param $attr_name
	 * @param $new_attr_value
	 * @return string
	 */
	function update_attribute($html, $attr_name, $new_attr_value) {

		$attr_value     = false;
		$quote          = false; // quotes to wrap attribute values

		preg_match('/(<a.*>)/iU', $html, $match);

		$link_str = $match[1];
		if ($link_str == "")
			return $html;

		$re = '/' . preg_quote($attr_name) . '=([\'"])?((?(1).+?|[^\s>]+))(?(1)\1)/is';
		if (preg_match($re, $link_str, $matches)
		) {
			// two possible ways to get existing attributes
			$attr_value = $matches[2];

			$quote = false !== stripos($html, $attr_name . "='") ? "'" : '"';
		}

		if ($attr_value)
		{
			//replace current attribute
			$html = str_ireplace("$attr_name=" . $quote . "$attr_value" . $quote,
				$attr_name . '="' . esc_attr($new_attr_value) . '"', $html);
		}
		else {
			// attribute does not currently exist, add it
			$pos = strpos( $html, '>' );
			if ($pos !== false) {
				$html = substr_replace( $html, " $attr_name=\"" . esc_attr($new_attr_value) . '">', $pos, strlen('>') );
			}
		}

		return $html;
	} # update_attribute()

	/**
	 * filter_anchor()
	 *
	 * @param $anchor
	 * @return string
	 */

	function filter_anchor($anchor) {
		if ( (strpos($anchor['attr']['href'], 'http://') !== false)
			&& (strpos($anchor['attr']['href'], 'https://') !== false) )
			return $anchor;

		if ( !preg_match("/(?:www\.)?(?:get)?semiologic\.com\b/", $anchor['attr']['href']) )
			return $anchor;
		
		if ( strpos($anchor['attr']['href'], '?') === false )
			$anchor['attr']['href'] .= '?aff=' . urlencode(semiologic_affiliate::get_campaign());
		
		return $anchor;
	} # filter()

	function parseAttributes($text) {
	    $attributes = array();
	    $pattern = '#(?(DEFINE)
	            (?<name>[a-zA-Z][a-zA-Z0-9-:]*)
	            (?<value_double>"[^"]+")
	            (?<value_single>\'[^\']+\')
	            (?<value_none>[^\s>]+)
	            (?<value>((?&value_double)|(?&value_single)|(?&value_none)))
	        )
	        (?<n>(?&name))(=(?<v>(?&value)))?#xs';

	    if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
	        foreach ($matches as $match) {
	            $attributes[$match['n']] = isset($match['v'])
	                ? trim($match['v'], '\'"')
	                : null;
	        }
	    }

	    return $attributes;
	}

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
} # semiologic_affiliate

$semiologic_affiliate = semiologic_affiliate::get_instance();
