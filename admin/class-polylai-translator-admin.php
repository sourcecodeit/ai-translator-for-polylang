<?php

if (!defined('ABSPATH'))
	exit;

define("POLYLAI_NEED_TR_KEY", "polylai_needs_translation");
define("POLYLAI_TRANSLATING_KEY", "polylai_translating");
define("POLYLAI_PROCESSING_KEY", "polylai_processing");
define("POLYLAI_KEY", "polylai_perc");

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://sourcecode-agency.it/
 * @since      1.0.0
 *
 * @package    polylai
 * @subpackage polylai/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    polylai
 * @subpackage polylai/admin
 * @author     Diego Imbriani <diego@sourcecode.team>
 */
class polylai_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $cron;

	private $rating_postponed;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->gpt = new polylai_ChatGPT();
		$this->cron = new polylai_Cron();
		$this->rating_postponed = false;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/polylai-translator-admin.css', array(), $this->version, 'all');
		$user = wp_get_current_user();
		if (in_array('author', (array) $user->roles)) {
			wp_enqueue_style($this->plugin_name . "-author", plugin_dir_url(__FILE__) . 'css/polylai-translator-author.css', array(), $this->version, 'all');
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/polylai-translator-admin.js', array('jquery'), $this->version, false);		
	}

	public function add_column($columns)
	{
		if(! function_exists('pll_the_languages')) {
			return $columns;
		}
		return array_merge($columns, ['polylaitr' => __('PolylAI', 'ai-translator-for-polylang')]);
	}

	public function manage_column($column_key, $id)
	{
		$type = get_post_type($id);
		$allowed = ["post", "page"];

		if (!in_array($type, $allowed) && !polylai_fs()->can_use_premium_code()) {
			if ($column_key == 'polylaitr') {
				echo "<div class='polylai-hover'>";
				echo "<a href='". esc_url(polylai_fs()->get_upgrade_url())."'>Upgrade now</a>. Translations for custom posts are available only for PolylAI Premium";
				echo "</div>";
				return;
			}
		}

		if (current_user_can('edit_post', $id) && function_exists('pll_the_languages')) {
			if ($column_key == 'polylaitr') {
				echo "<div class='polylaitr-cell'>";
				echo "	<span class='polylaitr-progress dashicons dashicons-translation'></span>";
				echo "	<span class='polylaitr-progress-locales'></span>";
				echo "	<span data-id='" . esc_attr($id) . "' class='polylaitr-link'>Translate</span>";
				echo "</div>";
			}
		}
	}

	public function admin_menu()
	{
		add_options_page('PolylAI Translator', 'PolylAI Translator', 'manage_options', 'polylai-translator-options', [$this, 'settings_page']);		
	}

	public function settings_page()
	{
		require_once plugin_dir_path(__FILE__) . 'partials/settings.php';
	}

	public function handle_rating()
	{
		if (isset($_POST['polylai_rating_action'])) {
			if (wp_verify_nonce(sanitize_text_field( wp_unslash ($_REQUEST['polylai_rating_nonce'])), 'polylai_rating_asked')) {
				if($_POST['polylai_rating_action'] == 'remind') {
					$cookie_name = 'polylai_suspend_notice';
					$cookie_value = '1';
					$cookie_expiry = time() + (2 * 24 * 60 * 60); // 2 days from now

					// Set the cookie
					setcookie($cookie_name, $cookie_value, $cookie_expiry, "/");
					$this->rating_postponed = true;
					return;
				}
				if($_POST['polylai_rating_action'] == 'done') {
					update_option('polylai_rating_asked', true);
					return;
				}
			} else {
				wp_nonce_ays();
			}
		}
	}

	public function check_usage()
	{
		global $wpdb;

		$rating_asked = get_option('polylai_rating_asked');
		if ($rating_asked) {
			return;
		}

		if(isset($_COOKIE['polylai_suspend_notice'])) {
			return;
		}

		if($this->rating_postponed) {
			return;
		}

		$table_name = $wpdb->prefix . 'polylai_log';
		$query = $wpdb->prepare("SELECT DISTINCT `post_id` FROM %i where operation='create_translation'", $table_name);
		$results = $wpdb->get_results($query);

		if(count($results) >= 3) {

			$nonce = wp_create_nonce('polylai_rating_asked');

			?>
		<div class="notice notice-success">
			<h3>Thank you for using PolylAI Translator!</h3> 
			<p>If you like the plugin, please <a href="https://wordpress.org/support/plugin/ai-translator-for-polylang/reviews/#new-post" target="_blank">rate it with ⭐⭐⭐⭐⭐</a> on WordPress.org. It will help us to improve the plugin and make it better for everyone.</p> 
			<p>
				<form method="post" action="">	
					<input type="hidden" name="polylai_rating_nonce" value="<?php echo $nonce ?>"> 
					<button class="button" type="submit" name="polylai_rating_action" value="remind">Remind me later</button> 
					<button class="button" type="submit" name="polylai_rating_action" value="done">Done!</button>
				</form>
			</p>
		</div>
		<?php
		}
	}

	public function check_commands()
	{
		if (isset($_GET['polylai_reset'])) {
			delete_transient(polylai_Cron::LOCK_TRANSIENT_KEY);
			delete_transient(polylai_Cron::ACTIVITY_TRANSIENT_KEY);
		}
	}

	public function get_post_tr_status()
	{
		// nonce not needed here as it's a read-only action
		$id = intval($_POST['id']);

		$pll_translations = pll_get_post_translations($id);

		$translations = [];

		foreach ($pll_translations as $k => $translation) {
			$post = get_post($translation);
			if($post->post_status != 'trash') {
				$running[] = [
					"id" => $translation,
					"data" => get_post_meta($translation, POLYLAI_NEED_TR_KEY, true)
				];
				$translations[$k] = $translation;
			}
		}

		$allowed = polylai_Utils::allowed_langs();
		$has_cron = get_transient(polylai_Cron::ACTIVITY_TRANSIENT_KEY);

		header('Content-type: application/json');
		echo wp_json_encode([
			'post_slug' => pll_get_post_language($id, 'slug'),
			'post_lang' => pll_get_post_language($id, 'name'),
			'translations' => $translations,
			'langs' => pll_languages_list(['fields' => 'name']),
			'langs_slug' => pll_languages_list(['fields' => 'slug']),
			'locales' => pll_the_languages(['raw' => 1, 'hide_if_empty' => 0]),
			'running' => $running,
			'allowed' => $allowed,
			'has_cron' => !!$has_cron,
			'nonce' => wp_create_nonce('polylai_run_cron')
		]);

		wp_die();
	}

	public function register_api()
	{
		register_rest_route('polylai/v1', '/cron', array(
			'methods' => 'GET',
			'callback' => [$this->cron, 'execCron'],
			'permission_callback' => function () {
				return true;
				$user = wp_get_current_user();
				if (in_array('author', (array) $user->roles)) {
					return true;
				}
				if (in_array('editor', (array) $user->roles)) {
					return true;
				}
				if (in_array('administrator', (array) $user->roles)) {
					return true;
				}
				return false;
			}
		));
	}

	private function run_http_cron() {
		$cron_url = home_url('/index.php?action=polylai_run_cron');
		wp_remote_get($cron_url, [
			'timeout' => 1,
			'blocking' => false
		]);
	}

	public function enqueue_translations()
	{
		if (wp_verify_nonce(sanitize_text_field( wp_unslash ($_REQUEST['nonce'])), 'polylai_run_cron')) {

			$id = intval($_POST['id']);
			$locales = sanitize_text_field($_POST['locales']);
			$locales_names = sanitize_text_field($_POST['localesNames']);

			update_post_meta(
				$id,
				POLYLAI_NEED_TR_KEY,
				['locales' => $locales, 'locales_names' => $locales_names]
			);

			update_post_meta($id, POLYLAI_TRANSLATING_KEY, 'true');

			$has_cron = get_transient(polylai_Cron::ACTIVITY_TRANSIENT_KEY);
			if (!$has_cron) {
				$this->run_http_cron();
			}

			echo wp_json_encode(['status' => 'OK']);
	
			wp_die();
		} else {
			wp_nonce_ays();
		}
	}

	public function polylai_run_cron() {
		if(wp_verify_nonce(sanitize_text_field( wp_unslash ($_REQUEST['nonce'])), 'polylai_run_cron')) {
			$this->run_http_cron();
		} else {
			wp_nonce_ays();
		}
	}

	public function progress()
	{
		$args = array(
			'post_type' => 'any',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => POLYLAI_TRANSLATING_KEY,
					'value' => 'true',
					'compare' => '=',
				),
			),
		);

		$query = new WP_Query($args);
		$result = [];

		if ($query->have_posts()) {
			while ($query->have_posts()) {				
				$query->the_post();
				$id = get_the_id();
				$perc = get_post_meta($id, POLYLAI_KEY, true);
				$result[] = [
					'post' => $id,
					'title' => get_the_title($id),
					'locales' => get_post_meta($id, POLYLAI_NEED_TR_KEY, true),
					'perc' => $perc ? $perc : new stdClass(),
				];				
			}
		}

		header("Content-type: application/json");
		echo wp_json_encode($result);
		wp_die();
	}

	public function action_links($links)
	{
		$links[] = '<a href="options-general.php?page=polylai-translator-options">Settings</a>';
		return $links;
	}

	public function get_logs()
	{
		global $wpdb;

		$logs = [];
		$table_name = $wpdb->prefix . 'polylai_log';
		$query = $wpdb->prepare("SELECT `time`, `type`, `operation`, `post_id`, `post_title`, `message` FROM %i ORDER BY id DESC LIMIT 100", $table_name);
		$results = $wpdb->get_results($query);
		foreach ($results as $key => $row) {
			$logs[] = $row;
		}
		header("Content-type: application/json");
		echo wp_json_encode($logs);
		wp_die();
	}

	public function download_logs()
	{
		global $wpdb;

		$logs = [];
		$limit = polylai_Utils::is_debug() ? 1000 : 100;
		$table_name = $wpdb->prefix . 'polylai_log';
		$query = $wpdb->prepare("SELECT * FROM %i ORDER BY id DESC LIMIT %d", $table_name, $limit);
		$results = $wpdb->get_results($query);
		foreach ($results as $key => $row) {
			$logs[] = $row;
		}
		echo wp_json_encode($logs);
		wp_die();
	}
}
