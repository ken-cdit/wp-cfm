<?php
/**
 * WP-CFM
 *
 * @category  General
 * @package   WPCFM
 * @author    Forum One <wordpress@forumone.com>
 * @copyright 2016 Forum One
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 * @link      https://github.com/forumone
 *
 * @wordpress-plugin
 * Plugin Name:       WP-CFM
 * Plugin URI:        https://forumone.github.io/wp-cfm/
 * Description:       WordPress Configuration Management
 * Version:           1.7.10
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Forum One
 * Author URI:        http://forumone.com/
 * Text Domain:       wp-cfm
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/forumone/wp-cfm
 */

defined( 'ABSPATH' ) or exit;

if ( PHP_VERSION_ID >= 50604 ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

class WPCFM_Core {


	const VERSION = '1.7.10';
	public $readwrite;
	public $readwrite_overrides;
	public $registry;
	public $options;
	public $helper;
	private $pantheon_env = '';
	private static $instance;


	function __construct() {

		// setup variables
		define( 'WPCFM_VERSION', self::VERSION );
		define( 'WPCFM_DIR', __DIR__ );

		$config_dir = WP_CONTENT_DIR . '/config';
		$config_url = WP_CONTENT_URL . '/config';
		$overrides_dir = false;

		// Check if we are on Pantheon hosting environment.
		if ( defined( 'PANTHEON_ENVIRONMENT' ) ) {
			// Set the Pantheon environment to test or live
			if ( in_array( PANTHEON_ENVIRONMENT, array( 'test', 'live' ) ) ) {
				$this->pantheon_env = PANTHEON_ENVIRONMENT;
				// Otherwise, default to dev for dev and multidev
			} else {
				$this->pantheon_env = 'dev';
			}

			// Change the config directory to private/config on Pantheon
			$config_dir = untrailingslashit( $_ENV['DOCROOT'] ) . '/private/config';
			$config_url = home_url() . '/private/config';
		}

		// Register multiple environments.
		define( 'WPCFM_REGISTER_MULTI_ENV', $this->set_multi_env() );

		// If multiple environments were defined.
		if ( ! empty( WPCFM_REGISTER_MULTI_ENV ) ) {
			// Set the current environment where the WordPress site is running.
			define( 'WPCFM_CURRENT_ENV', $this->set_current_env() );
			// If we have an env name, append it to create a subfolder inside wp-content/config/ directory.
			if ( ! empty( WPCFM_CURRENT_ENV ) ) {
				$config_dir .= '/' . WPCFM_CURRENT_ENV;
				$config_url .= '/' . WPCFM_CURRENT_ENV;
			}
		}

		define( 'WPCFM_CONFIG_DIR', apply_filters( 'wpcfm_config_dir', $config_dir ) );
		define( 'WPCFM_OVERRIDES_DIR', apply_filters( 'wpcfm_overrides_dir', $overrides_dir) );
		define( 'WPCFM_CONFIG_URL', apply_filters( 'wpcfm_config_url', $config_url ) );
		if ( PHP_VERSION_ID < 50604 ) {
			define( 'WPCFM_CONFIG_FORMAT', 'json' );
			define( 'WPCFM_CONFIG_FORMAT_REQUESTED', apply_filters( 'wpcfm_config_format', 'json' ) );
		} else {
			define( 'WPCFM_CONFIG_FORMAT', apply_filters( 'wpcfm_config_format', 'json' ) );
		}
		define( 'WPCFM_CONFIG_USE_YAML_DIFF', apply_filters( 'wpcfm_config_use_yaml_diff', true ) );
		define( 'WPCFM_URL', plugins_url( '', __FILE__ ) );

		define( 'WPCFM_DISABLE_PUSH', apply_filters( 'wpcfm_disable_push', false));
		define( 'WPCFM_DISABLE_PULL', apply_filters( 'wpcfm_disable_pull', false));
		define( 'WPCFM_HIDE_ATTRIBUTION', apply_filters( ' wpcfm_hide_attribution', false));
		define( 'WPCFM_IGNORED_ITEMS', apply_filters( 'wpcfm_ignored_items', [] ));

		// WP is loaded
		add_action( 'init', array( $this, 'init' ), 1 );
	}


	/**
	 * Enables multi environment feature on WP-CFM.
	 *
	 * @return array
	 */
	private function set_multi_env() {
		$environments = array();

		// If we are in a Pantheon environment, set the 3 instances slugs out of the box.
		if ( ! empty( $this->pantheon_env ) ) {
			$environments = array( 'dev', 'test', 'live' );
		}

		return apply_filters( 'wpcfm_multi_env', $environments );
	}


	/**
	 * Defines the current environment.
	 *
	 * @return string
	 */
	private function set_current_env() {
		$compare_env = null;
		// Get Compare Env when rendering the settings page.
		if ( ! wp_doing_ajax() || ! defined( 'WP_CLI' ) ) {
			if ( isset( $_GET['compare_env'] ) ) {
				$compare_env = $this->filter_string_polyfill( $_GET['compare_env'] );
			}
			if ( ! empty( $compare_env ) ) {
				define( 'WPCFM_COMPARE_ENV', $compare_env );
			}
		}

		// Get Compare Env when doing AJAX.
		if ( wp_doing_ajax() ) {
			if ( isset( $_POST['compare_env'] ) ) {
				$compare_env = $this->filter_string_polyfill( $_POST['compare_env'] );
				if ( ! empty( $compare_env ) && in_array( $compare_env, WPCFM_REGISTER_MULTI_ENV ) ) {
					return strval( $compare_env );
				}
			}
		}

		return apply_filters( 'wpcfm_current_env', $this->pantheon_env );
	}


	/**
	 * Initialize the singleton
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Initialize classes and WP hooks
	 */
	function init() {

		// i18n
		$this->load_textdomain();

		// hooks
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		// includes
		foreach ( array( 'options', 'readwrite', 'readwrite-overrides', 'registry', 'helper', 'ajax' ) as $class ) {
			include WPCFM_DIR . "/includes/class-$class.php";
		}

		// WP-CLI
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			include WPCFM_DIR . '/includes/class-wp-cli.php';
		}

		$this->options = new WPCFM_Options();
		$this->readwrite = new WPCFM_Readwrite();
		$this->readwrite_overrides = new WPCFM_ReadWriteOverrides();
		$this->registry = new WPCFM_Registry();
		$this->helper = new WPCFM_Helper();
		$ajax = new WPCFM_Ajax();

		// Make sure is_plugin_active() is available
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Third party integrations
		$integrations = scandir( WPCFM_DIR . '/includes/integrations' );
		foreach ( $integrations as $filename ) {
			if ( '.' != substr( $filename, 0, 1 ) ) {
				include WPCFM_DIR . "/includes/integrations/$filename";
			}
		}

		// Set Plugin's options tracked with WP-CFM to load their values from the bundled JSON files.
		if ( apply_filters( 'wpcfm_is_ssot', false ) ) {
			$this->set_as_ssot();
		}
	}


	/**
	 *  Set WP-CFM file bundle's config as the Single Source of Truth.
	 *  Override DB values for all tracked options.
	 */
	private function set_as_ssot() {
		$file_bundles = WPCFM()->helper->get_file_bundles();
		if ( $file_bundles ) {
			$plugin_opts = array_reduce(
				array_column( $file_bundles, 'config' ),
				'array_merge',
				array()
			);

			// Loop available plugin options and a pre_option_{$option} filter for them.
			foreach ( $plugin_opts as $key => $value ) {
				add_filter(
					'pre_option_' . $key,
					function ( $pre ) use ( $value ) {
						return maybe_unserialize( $value );
					}
				);
			}
		}
	}

	private function register_admin_notices() {
		$add_notice = function($message, $type) {
			add_action( 'admin_notices', function() use ($message, $type) {
				echo '<div class="notice notice-' . $type . '"><p><b>WP-CFM</b>: ' . $message . '</p></div>';
			} );
		};

		if ( ! ( 'options-general.php' === $GLOBALS['pagenow'] && isset($_REQUEST['page']) && $_REQUEST['page'] == 'wpcfm' ) ) {
			return;
		}

		// check read/write errors
		if ( ! empty( $this->readwrite->error) ) {
			$add_notice($this->readwrite->error, 'error');
		}

		if( WPCFM_DISABLE_PULL === true && WPCFM_DISABLE_PUSH === true ) {
			$add_notice("Push and pull functionalities are explicitly disabled for this environment.  You can still push and pull using the WordPress CLI.", 'info');
		}
		else {
			if( WPCFM_DISABLE_PUSH === true ) {
				$add_notice("Push functionality is explicitly disabled for this environment.  You can still push from the WordPress CLI.", 'info');
			}

			if( WPCFM_DISABLE_PULL === true ) {
				$add_notice("Push functionality is explicitly disabled for this environment.  You can still pull from the WordPress CLI.", 'info');
			}
		}

		if( defined('WPCFM_IGNORED_ITEMS') && ! empty( WPCFM_IGNORED_ITEMS )) {
			$ignored_items = implode(', ', WPCFM_IGNORED_ITEMS);
			$add_notice("The following config items are being explicitly blocked by configuration via 'wpcfm_ignored_items' hook:</br>\n"
				. "<div style=\"margin:4px 6px;\">$ignored_items</div>", 'info');
		}

		// check version compatability with yaml format
		if ( defined( 'WPCFM_CONFIG_FORMAT_REQUESTED' ) && in_array( WPCFM_CONFIG_FORMAT_REQUESTED, array( 'yml', 'yaml' ) ) ) {
			$add_notice("Your PHP version is not compatible with Yaml export format. Upgrade to at least PHP 5.6.4.", 'error');
		}

		// build warnings for duplicates
		$options_by_bundles = [];

		foreach($this->registry->get_duplicates() as $option => $bundles) {
			$bundles_key = implode(', ', $bundles);
			if(!array_key_exists($bundles_key, $options_by_bundles)) {
				$options_by_bundles[$bundles_key] = [];
			}
			$options_by_bundles[$bundles_key][] = $option;
		}

		foreach($options_by_bundles as $bundles => $options) {
			$add_notice(
				"Some config items are tracked by multiple bundles and may result in unwanted side effects.</br></br>\n"
				. "<b>Bundles:</b> $bundles</br>\n"
				. "<b>Items:</b> " . implode(', ', $options)
			, 'warning');
		}
	}

	/**
	 * Register the settings page
	 */
	function admin_menu() {
		$this->register_admin_notices();
		add_options_page( 'WP-CFM', 'WP-CFM', 'manage_options', 'wpcfm', array( $this, 'settings_page' ) );
	}


	/**
	 * Register the multi-site settings page
	 */
	function network_admin_menu() {
		$this->register_admin_notices();
		add_submenu_page( 'settings.php', 'WP-CFM', 'WP-CFM', 'manage_options', 'wpcfm', array( $this, 'settings_page' ) );
	}


	/**
	 * Enqueue WP-CFM admin styles and javascript.
	 */
	function admin_scripts( $hook ) {
		// Exit this funtion if doing AJAX.
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( 'settings_page_wpcfm' == $hook ) {
			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_style( 'media-views' );
			wp_enqueue_script( 'wpcfm-multiselect', plugins_url( "assets/js/multiselect/jquery.multiselect{$min}.js", __FILE__ ), array( 'jquery' ), WPCFM_VERSION );
			wp_enqueue_script( 'wpcfm-diff-match-patch', plugins_url( "assets/js/pretty-text-diff/diff_match_patch{$min}.js", __FILE__ ), array( 'jquery' ), WPCFM_VERSION );
			wp_enqueue_script( 'wpcfm-pretty-text-diff', plugins_url( "assets/js/pretty-text-diff/jquery.pretty-text-diff{$min}.js", __FILE__ ), array( 'jquery' ), WPCFM_VERSION );
			wp_enqueue_script( 'wpcfm-admin-js', plugins_url( "assets/js/admin{$min}.js", __FILE__ ), array( 'jquery', 'wpcfm-multiselect', 'wpcfm-pretty-text-diff' ), WPCFM_VERSION );

			// Safely get env value from plugin backend URL, if exists.
			$compare_env = isset( $_GET['compare_env'] )
			  ? sanitize_text_field( $_GET['compare_env'] )
				: '';

			wp_localize_script(
				'wpcfm-admin-js',
				'compare_env',
				array(
					'ajax_url'         => admin_url( 'admin-ajax.php' ),
					'env'              => $compare_env,
					'wpcfm_ajax_nonce' => wp_create_nonce( 'wpcfm_ajax_nonce' ),
				)
			);

			wp_enqueue_style( 'wpcfm-admin', plugins_url( "assets/css/admin{$min}.css", __FILE__ ), array(), WPCFM_VERSION );
		}
	}


	/**
	 * Route to the correct edit screen
	 */
	function settings_page() {
		include WPCFM_DIR . '/templates/page-settings.php';
	}


	/**
	 * i18n support.
	 *
	 * @return void
	 */
	function load_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wpcfm' );
		$mofile = WP_LANG_DIR . '/wpcfm-' . $locale . '.mo';

		if ( file_exists( $mofile ) ) {
			load_textdomain( 'wpcfm', $mofile );
		} else {
			load_plugin_textdomain( 'wpcfm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
	}

	/**
	 * Deprecated `FILTER_SANITIZE_STRING` polyfill.
	 *
	 * @param string $string A raw string that should be sanitized.
	 *
	 * @return string|bool
	 */
	private function filter_string_polyfill( string $string ) {
		$str = preg_replace( '/\x00|<[^>]*>?/', '', $string );

		if ( empty( $str ) ) {
			return false;
		}

		return str_replace(
			array( "'", '"' ),
			array( '&#39;', '&#34;' ),
			$str
		);
	}

	public function is_push_disabled() {
		return WPCFM_DISABLE_PUSH;
	}

	public function is_pull_disabled() {
		return WPCFM_DISABLE_PULL;
	}

	public function is_attribution_hidden() {
		return WPCFM_HIDE_ATTRIBUTION;
	}
}

WPCFM();


/**
 * Allow direct access to WPCFM classes
 * For example, use WPCFM()->options to access WPCFM_Options
 */
function WPCFM() {
	return WPCFM_Core::instance();
}
