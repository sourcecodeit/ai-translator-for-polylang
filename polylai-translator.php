<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://sourcecode-agency.it/
 * @since             1.0.0
 * @package           polylai
 *
 * @wordpress-plugin
 * Plugin Name:       AI Translator for Polylang
 * Plugin URI:        https://www.wp-polylai.com
 * Requires Plugins:  polylang
 * Requires PHP:      7.0
 * Requires at least: 5.0
 * Description:       Translate your Polylang posts with AI
 * Version:           1.0.9
 * Author:            Source Code SRL
 * Author URI:        https://sourcecode-agency.it/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ai-translator-for-polylang
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die;
}
/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'polylai_translator_VERSION', '1.0.0' );
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-polylai-translator-activator.php
 */
function polylai_activate() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-polylai-translator-activator.php';
    polylai_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-polylai-translator-deactivator.php
 */
function polylai_deactivate() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-polylai-translator-deactivator.php';
    polylai_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'polylai_activate' );
register_deactivation_hook( __FILE__, 'polylai_deactivate' );
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-polylai-translator.php';
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function polylai_run() {
    $plugin = new polylai();
    $plugin->run();
}

if ( !function_exists( 'polylai_fs' ) ) {
    // Create a helper function for easy SDK access.
    function polylai_fs() {
        global $polylai_fs;
        if ( !isset( $polylai_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $polylai_fs = fs_dynamic_init( array(
                'id'             => '15708',
                'slug'           => 'ai-translator-for-polylang',
                'premium_slug'   => 'polylai-translator-premium',
                'type'           => 'plugin',
                'public_key'     => 'pk_fe7dde2a72823221dcac1d8dea75f',
                'is_premium'     => false,
                'has_addons'     => false,
                'has_paid_plans' => true,
                'trial'          => array(
                    'days'               => 3,
                    'is_require_payment' => false,
                ),
                'menu'           => array(
                    'slug'       => 'polylai-translator-options',
                    'first-path' => 'options-general.php?page=polylai-translator-options',
                    'parent'     => array(
                        'slug' => 'options-general.php',
                    ),
                ),
                'is_live'        => true,
            ) );
        }
        return $polylai_fs;
    }

    // Init Freemius.
    polylai_fs();
    // Signal that SDK was initiated.
    do_action( 'polylai_fs_loaded' );
}
polylai_run();