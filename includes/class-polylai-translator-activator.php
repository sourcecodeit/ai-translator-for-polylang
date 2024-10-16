<?php if (!defined('ABSPATH'))
	exit; ?>
<?php

/**
 * Fired during plugin activation
 *
 * @link       https://sourcecode-agency.it/
 * @since      1.0.0
 *
 * @package    polylai
 * @subpackage polylai/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    polylai
 * @subpackage polylai/includes
 * @author     Diego Imbriani <diego@sourcecode.team>
 */
class polylai_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		global $polylai_db_version;

		$polylai_db_version = '1.1';
		
		$table_name = $wpdb->prefix . 'polylai_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			type tinytext NOT NULL,
			operation text NULL,
			post_id int(11) NULL,
			post_title text NULL,
			message text NULL,
			text longtext NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);

		add_option('polylai_db_version', $polylai_db_version);
	}
}
