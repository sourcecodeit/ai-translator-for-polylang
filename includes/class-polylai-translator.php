<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://sourcecode-agency.it/
 * @since      1.0.0
 *
 * @package    polylai
 * @subpackage polylai/includes
 */
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    polylai
 * @subpackage polylai/includes
 * @author     Diego Imbriani <diego@sourcecode.team>
 */
class polylai {
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      polylai_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    protected $settings;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( defined( 'polylai_translator_VERSION' ) ) {
            $this->version = polylai_translator_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'ai-translator-for-polylang';
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        // $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - polylai_Loader. Orchestrates the hooks of the plugin.
     * - polylai_i18n. Defines internationalization functionality.
     * - polylai_Admin. Defines all hooks for the admin area.
     * - polylai_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-polylai-translator-aiengine.php';
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-polylai-translator-loader.php';
        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-polylai-translator-i18n.php';
        /**
         * The class responsible for chatgpt interaction
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-polylai-translator-chatgpt.php';
        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-polylai-translator-admin.php';
        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        // require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-polylai-translator-public.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-polylai-translator-settings.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-polylai-translator-cron.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-polylai-translator-utils.php';
        $this->loader = new polylai_Loader();
        $this->settings = new polylai_Settings();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the polylai_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new polylai_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new polylai_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'admin_menu' );
        $this->loader->add_action( 'admin_init', $this->settings, 'register_settings' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'handle_rating' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'check_commands' );
        $this->loader->add_action( 'admin_notices', $plugin_admin, 'check_usage' );
        $this->loader->add_filter( 'manage_posts_columns', $plugin_admin, 'add_column' );
        $this->loader->add_filter( 'manage_pages_columns', $plugin_admin, 'add_column' );
        $this->loader->add_filter( "manage_trip_posts_columns", $plugin_admin, 'add_column' );
        $this->loader->add_action(
            'manage_posts_custom_column',
            $plugin_admin,
            'manage_column',
            10,
            2
        );
        $this->loader->add_action(
            'manage_pages_custom_column',
            $plugin_admin,
            'manage_column',
            10,
            2
        );
        //$this->loader->add_filter('manage_pages_columns', $plugin_admin, 'add_column');
        $this->loader->add_action( 'wp_ajax_polylai_get_post_tr_status', $plugin_admin, 'get_post_tr_status' );
        $this->loader->add_action( 'wp_ajax_polylai_run_cron', $plugin_admin, 'polylai_run_cron' );
        $this->loader->add_action( 'wp_ajax_polylai_enqueue_translations', $plugin_admin, 'enqueue_translations' );
        $this->loader->add_action( 'wp_ajax_polylai_progress', $plugin_admin, 'progress' );
        $this->loader->add_action( 'wp_ajax_polylai_logs', $plugin_admin, 'get_logs' );
        $this->loader->add_action( 'wp_ajax_polylai_logs_download', $plugin_admin, 'download_logs' );
        $this->loader->add_action( 'plugin_action_links_polylai-translator/polylai-translator.php', $plugin_admin, 'action_links' );
        $this->loader->add_action( 'rest_api_init', $plugin_admin, 'register_api' );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    polylai_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}
