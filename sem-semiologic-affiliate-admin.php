<?php
/**
 * semiologic_affiliate_admin
 *
 * @package Semiologic Affiliate
 **/

class semiologic_affiliate_admin {
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
	 * Constructor.
	 *
	 *
	 */
	public function __construct() {
		$this->plugin_url    = plugins_url( '/', __FILE__ );
		$this->plugin_path   = plugin_dir_path( __FILE__ );

		$this->init();
    }


	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		// more stuff: register actions and filters
		add_action('settings_page_semiologic_affiliate', array($this, 'save_options'), 0);
	}

    /**
	 * save_options()
	 *
	 * @return void
	 **/

	function save_options() {
		if ( !$_POST || !current_user_can('manage_options') )
			return;
		
		if ( function_exists('is_super_admin') && !is_super_admin() )
			return;
		
		check_admin_referer('semiologic_affiliate');
		
		$campaign_key = stripslashes($_POST['campaign_key']);
		
		if ( preg_match("/\?aff=(.+)/i", $campaign_key, $match) )
			$campaign_key = end($match);
		
		$campaign_key = trim(preg_replace("/[^0-9a-zA-Z_-]/", "", $campaign_key));
		
		update_site_option('sem_campaign_key', $campaign_key);
		
		echo "<div class=\"updated fade\">\n"
			. "<p>"
				. "<strong>"
				. __('Settings saved.', 'sem-semiologic-affiliate')
				. "</strong>"
			. "</p>\n"
			. "</div>\n";
	} # save_options()
	
	
	/**
	 * edit_options()
	 *
	 * @return void
	 **/

	static function edit_options() {
		if ( function_exists('is_super_admin') && !is_super_admin() )
			return;
		
		echo '<div class="wrap">' . "\n";
		
		echo '<form method="post" action="">' . "\n";
		
		wp_nonce_field('semiologic_affiliate');
		
		echo '<h2>' . __('Semiologic Affiliate Settings', 'sem-semiologic-affiliate') . '</h2>' . "\n";
		
		$aff_id = semiologic_affiliate::get_campaign();
		
		echo '<table class="form-table">' . "\n";
		
		echo '<p>'
			. __('Fill in the field that follows to make all links to semiologic.com and getsemiologic.com on the site use your campaign ID automatically.', 'sem-semiologic-affiliate')
			. '</p>' . "\n";

		echo '<tr>' . "\n"
			. '<th scope="row">'
			. '<label for="campaign_key">'
			. sprintf(__('Your <a href="%s">Campaign ID</a>', 'sem-semiologic-affiliate'), 'http://oldbackend.semiologic.com')
			. '</label>'
			. '</th>' . "\n"
			. '<td>'
			. '<label for="campaign_key">http://www.getsemiologic.com?aff=</label>'
			. '<input type="text"'
				. ' name="campaign_key" id="campaign_key"'
				. ' value="' . esc_attr($aff_id) . '"'
				. ' />'
			. '</td>'
			. '</tr>' . "\n";
		
		echo '</table>' . "\n";
		
		echo '<div class="submit">'
			. '<input type="submit" value="' . esc_attr(__('Save Changes', 'sem-semiologic-affiliate')) . '" />'
			. '</div>' . "\n";
		
		echo '</form>' . "\n";
		
		echo '</div>' . "\n";
	} # edit_options()
} # semiologic_affiliate_admin

$semiologic_affiliate_admin = semiologic_affiliate_admin::get_instance();
