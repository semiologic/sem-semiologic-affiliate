<?php
/*
 * Sem Affiliate Anchor Utils
 * Author: Denis de Bernardy & Mike Koepke <http://www.semiologic.com>
 * Version: 1.5
 */


if ( @ini_get('pcre.backtrack_limit') <= 1000000 )
	@ini_set('pcre.backtrack_limit', 1000000);
if ( @ini_get('pcre.recursion_limit') <= 250000 )
	@ini_set('pcre.recursion_limit', 250000);

/**
 * sem_affiliate_anchor_utils
 *
 * @package Anchor Utils
 **/

class sem_affiliate_anchor_utils {

	private $semiologic_affiliate = null;
	/**
     * constructor
     */
    public function __construct( semiologic_affiliate $semiologic_affiliate ) {

	    $this->semiologic_affiliate = $semiologic_affiliate;


	    add_action('template_redirect', array($this, 'ob_start'), 100);

    } #external_links_anchor_utils


    /**
	 * ob_start()
	 *
	 * @return void
	 **/

	function ob_start() {
		static $done = false;

		if ($done)
			return;

		ob_start(array($this, 'ob_filter'));
		add_action('wp_footer', array($this, 'ob_flush'), 100000);

		$done = true;
	} # ob_start()

	/**
	 * ob_filter()
	 *
	 * @param string $text
	 * @return string $text
	 **/

	function ob_filter($text) {

		$text = $this->semiologic_affiliate->process_content( $text );

		return $text;
	}

	/**
	 * ob_flush()
	 *
	 * @return void
	 **/

	function ob_flush() {
		static $done = true;

		if ($done)
			return;

		ob_end_flush();

		$done = true;
	} # ob_flush()
} # sem_affiliate_anchor_utils
