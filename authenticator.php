<?php
/**
 * Plugin Name: Authenticator
 * Plugin URI:  https://github.com/bueltge/Authenticator
 * Description: This plugin allows you to make your WordPress site accessible to logged in users only. In other words to view your site they have to create / have an account in your site and be logged in. No configuration necessary, simply activating - that's all.
 * Author:      Inpsyde GmbH
 * Version:     1.2.1
 * Author URI:  http://inpsyde.com/
 * License:     GPLv2+
 * License URI: ./assets/license.txt
 * Textdomain:  authenticator
 */

// check for uses in WP
if ( ! function_exists( 'add_filter' ) ) {
	echo "Hi there! I'm just a part of plugin, not much I can do when called directly.";
	exit;
}

spl_autoload_register( array( 'Authenticator', 'load_classes' ) );
register_uninstall_hook( __FILE__, array( 'Authenticator', 'uninstall' ) );
//start on a lower priority to allow other plugins to place their hooks to the Authenticator-API
add_action( 'plugins_loaded', array( 'Authenticator', 'get_instance' ), 11 );

class Authenticator {

	/**
	 * option key
	 *
	 * @since 1.1.0
	 * @const sring
	 */
	const KEY = 'authenticator_options';

	/**
	 * textdomain
	 *
	 * @since 1.1.0
	 * @const string
	 */
	const TEXTDOMAIN = 'authenticator';

	/**
	 * Version
	 *
	 * @since 1.1.0
	 * @const string
	 */
	const VERSION = '1.2.1';

	/**
	 * absolute path to this directory
	 *
	 * @since 1.1.0
	 * @var string
	 */
	public static $dir = '';

	/**
	 * absolute URL to this directory
	 *
	 * @since 1.1.0
	 * @var string
	 */
	public static $url = '';

	/**
	 * instance of self
	 *
	 * @var Authenticator
	 */
	private static $instance = NULL;

	/**
	 * Array for pages, there are checked for exclude the redirect
	 * admin-ajax.php is handled separately
	 */
	public static $exclude_pagenows = array( 'wp-login.php', 'wp-register.php' );

	/**
	 * Array for posts (post_title), there are checked for exclude the redirect
	 * Used for custom login formulars, default is empty
	 *
	 * @since 1.1.0
	 */
	public static $exclude_posts = array();

	/**
	 * Array for actions, which are allowed with wp-ajax
	 *
	 * @since 1.1.0
	 */
	public static $exclude_ajax_actions = array();

	/**
	 * options
	 *
	 * @var array
	 */
	protected static $options = array();

	/**
	 * instance of settins handling class
	 *
	 * @var Authenticator_Settings
	 */
	public $settings = NULL;

	/**
	 * @type Authenticator_Protect_Upload
	 */
	public $protect_uploads = NULL;

	/**
	 * get the instance
	 *
	 * @since   1.1.0
	 * @return  Authenticator
	 */
	public static function get_instance() {

		if ( ! self::$instance instanceof self ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Constructor, init redirect on defined hooks
	 *
	 * @since   0.4.0
	 * @return  Authenticator
	 */
	public function __construct() {

		self::$dir = plugin_dir_path( __FILE__ );
		self::$url = plugins_url( '', __FILE__ );

		$this->localize();

		// allow other plugins to change the list of excluded (non redirected) urls
		self::$exclude_pagenows = apply_filters( 'authenticator_exclude_pagenows', self::$exclude_pagenows );

		// allow other plugins to change the list of excluded (non redirected) urls
		self::$exclude_posts = apply_filters( 'authenticator_exclude_posts', self::$exclude_posts );

		// allow other plugins to change the list of excluded (non redirected) ajax actions
		self::$exclude_ajax_actions = apply_filters( 'authenticator_exclude_ajax_actions', self::$exclude_ajax_actions );

		// check if the user needs to authenticate
		$authenticate_method = $this->get_authenticate_method();
		if ( 'redirect' == $authenticate_method ) {
			add_action( 'template_redirect', array( __CLASS__, $authenticate_method ) );
		} elseif ( 'authenticate_ajax' == $authenticate_method ) {
			add_action( 'admin_init', array( __CLASS__, $authenticate_method ) );
		}

		# set cookie lifetime
		add_filter( 'auth_cookie_expiration', array( $this, 'filter_cookie_lifetime' ) );

		add_action( 'admin_init', array( $this, 'init_settings' ) );
		add_filter( 'authenticator_get_options', array( $this, 'get_options' ) );
		self::$options = get_option( self::KEY, array() );

		add_action( 'init', array( $this, 'protect_upload' ) );
		add_action( 'init', array( $this, 'disable_xmlrpc' ) );

		add_action( 'login_footer', array( $this, 'remove_back_to_blog_link' ) );
	}

	/**
	 * get the method to authenticate or NULL
	 * if no authentication is required
	 *
	 * @since 1.1.0
	 * @global $pagenow
	 * @return string|NULL
	 */
	public function get_authenticate_method() {

		if ( ! isset( $GLOBALS[ 'pagenow' ] ) ) {
			return 'redirect';
		}

		//shorthand
		$p = $GLOBALS[ 'pagenow' ];

		// exclude some pagenows ?
		if ( in_array( $p, self::$exclude_pagenows ) ) {
			return NULL;
		}

		if ( 'admin-ajax.php' == $p ) {
			if ( isset( $_REQUEST[ 'action' ] ) && in_array( $_REQUEST[ 'action' ], self::$exclude_ajax_actions ) ) {
				return NULL;
			} else {
				return 'authenticate_ajax';
			}
		}

		return 'redirect';
	}

	/**
	 * load the language files
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function localize() {

		load_plugin_textdomain(
			'authenticator',
			FALSE,
			dirname( plugin_basename( __FILE__ ) ) . '/language'
		);
	}

	/**
	 * init the settings api
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function init_settings() {

		$this->settings = new Authenticator_Settings();
	}

	/**
	 * Init to protect uploads
	 *
	 * @since   10/11/2012
	 * @return  void
	 */
	public function protect_upload() {

		$this->protect_uploads = new Authenticator_Protect_Upload();
	}

	/**
	 * Get redirect to login-page, if user not logged in blogs of network and single install
	 *
	 * @since  0.4.2
	 * @return  void
	 */
	public static function redirect() {

		if ( is_feed() ) {
			if ( TRUE === apply_filters( 'authenticator_bypass_feed_auth', FALSE ) ) {
				return;
			}

			switch ( self::$options[ 'feed_authentication' ] ) {
				case 'http' :
					self::http_auth_feed();
					return;
					break;
				case 'token' :
					if ( isset( $_GET[ self::$options[ 'auth_token' ] ] ) ) {
						return;
					}
					self::_exit_403();
					break;
				case 'none' :
				default :
					# nothing to do
					break;
			}
		}

		/**
		 * Checks if a user is logged in or has rights on the blog in multisite,
		 * if not redirects them to the login page
		 */
		if ( ! self::authenticate_user() && ( ! is_singular() || ! in_array( get_the_title(), self::$exclude_posts ) ) ) {
			$reauth =
				! current_user_can( 'read' )
				&& function_exists( 'is_multisite' )
				&& is_multisite()
					? TRUE
					: FALSE;

			nocache_headers();
			wp_redirect(
				wp_login_url( $_SERVER[ 'REQUEST_URI' ], $reauth ),
				$status = 302
			);
			exit();
		}
	}

	/**
	 * checks if the current visitor is logged in and has the
	 * permisson to 'read' this blog
	 *
	 * @since 1.1.0
	 * @return bool
	 */
	public static function authenticate_user() {

		# user must reauth when we are in multisite
		# and he has not the permission to 'read'
		$reauth = ! current_user_can( 'read' ) &&
		          function_exists( 'is_multisite' ) &&
		          is_multisite() ? TRUE : FALSE;

		return is_user_logged_in() && ! $reauth;
	}

	/**
	 * checks for authenticated requests on the ajax-interface
	 *
	 * @wp_hook admin_init
	 * @since   1.1.0
	 * @return void
	 */
	public static function authenticate_ajax() {

		if ( ! self::authenticate_user() ) {
			self::_exit_403();
		}
	}

	/**
	 * authenticate users requesting feeds via HTTP Basic auth
	 *
	 * @since   1.1.0
	 * @return  void
	 */
	protected static function http_auth_feed() {

		$auth = new HTTP_Auth( 'Feed of ' . get_bloginfo( 'name' ) );
		$user = $auth->get_user();
		$user = wp_authenticate( $user[ 'name' ], $user[ 'pass' ] );

		if ( ! is_a( $user, 'WP_User' ) || ! user_can( $user, 'read' ) ) {
			$auth->auth_required();
		}
	}

	/**
	 * get the cookie lifetime if set
	 *
	 * @wp-hook auth_cookie_expiration
	 *
	 * @param int $default_lifetime
	 *
	 * @return int
	 */
	public function filter_cookie_lifetime( $default_lifetime ) {

		if ( ( int ) self::$options[ 'cookie_lifetime' ] > 0 ) {
			return 60 * 60 * 24 * ( int ) self::$options[ 'cookie_lifetime' ];
		}

		return $default_lifetime;
	}

	/**
	 * disable_xmlrpc dependend to the setting
	 *
	 * @since   1.1.0
	 * @wp-hook init
	 * @return void
	 */
	public function disable_xmlrpc() {

		if ( ! defined( 'XMLRPC_REQUEST' ) ) {
			return;
		}

		if ( ! empty( self::$options[ 'disable_xmlrpc' ] )
		     && '1' === self::$options[ 'disable_xmlrpc' ]
		) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
		}
	}

	/**
	 * get the options
	 *
	 * @return string
	 */
	public function get_options() {

		return self::$options;
	}

	/**
	 * Remove Back to Blog link
	 * Not useful, only a loop with rewrite
	 *
	 * @since   1.1.0
	 * @return  void
	 */
	public function remove_back_to_blog_link() {
		?>
		<script type="text/javascript">
			var link = document.getElementById('backtoblog'),
				nav = document.getElementById('nav');
			link.parentNode.removeChild(link);
			//nav.parentNode.removeChild( nav );
		</script>
	<?php
	}

	/**
	 * just exit
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function _exit_403() {

		$protocol = 'HTTP/1.1' === $_SERVER[ 'SERVER_PROTOCOL' ]
			? 'HTTP/1.1'
			: 'HTTP/1.0';
		header( $protocol . ' 403 Forbidden' );
		exit( '<h1>403 Forbidden</h1>' );
	}

	/**
	 * autoloader
	 *
	 * @since   1.1.0
	 *
	 * @param   string $class_name
	 *
	 * @return  void
	 */
	public static function load_classes( $class_name ) {

		$file_name = dirname( __FILE__ ) . '/inc/class-' . $class_name . '.php';
		if ( file_exists( $file_name ) ) {
			require_once $file_name;
		}
	}

	/**
	 * uninstall routine
	 *
	 * @since   1.1.0
	 * @global  $wpdb
	 * @return  void
	 */
	public static function uninstall() {
		global $wpdb;

		ignore_user_abort( - 1 );
		if ( is_network_admin() && isset( $wpdb->blogs ) ) {
			$blogs = $wpdb->get_results(
				'SELECT blog_id FROM ' .
				$wpdb->blogs,
				ARRAY_A
			);
			foreach ( $blogs as $row ) {
				$id = ( int ) $row[ 'blog_id' ];
				delete_blog_option( $id, self::KEY );
			}

			return;
		}

		delete_option( self::KEY );
	}

} // end class
