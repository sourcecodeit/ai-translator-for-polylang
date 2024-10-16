<?php
if (!defined('ABSPATH'))
	exit;

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://sourcecode-agency.it/
 * @since      1.0.0
 *
 * @package    polylai
 * @subpackage polylai/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    polylai
 * @subpackage polylai/includes
 * @author     Diego Imbriani <diego@sourcecode.team>
 */

define("POLYLAI_TD", "ai-translator-for-polylang");

class polylai_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			POLYLAI_TD,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
