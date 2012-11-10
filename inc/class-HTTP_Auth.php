<?php
# @charset utf-8

/**
 * Implements Basic HTTP Authentication
 *
 * @author David Naber <kontakt@dnaber.de>
 */

if ( ! class_exists( 'HTTP_Auth' ) ) {

	class HTTP_Auth {

		/**
		 * the user
		 * keys are 'name' and 'pass'
		 *
		 * @var array
		 */
		protected $user = array();

		/**
		 * Name of the protected zone
		 *
		 * @var string
		 */
		protected $realm = '';

		/**
		 * constructor
		 *
		 * @param string $auth_type
		 * @param string $realm
		 * @return HTTP_Auth
		 */
		public function __construct( $realm = 'private area' ) {

			$this->realm = $realm;
			$this->parse_user_input();
		}

		/**
		 * get user input
		 *
		 * @return void
		 */
		protected function parse_user_input() {

			if ( isset( $_SERVER[ 'PHP_AUTH_USER' ] ) && isset( $_SERVER['PHP_AUTH_PW' ] ) ) {
				$this->user[ 'name' ] = $_SERVER[ 'PHP_AUTH_USER' ];
				$this->user[ 'pass' ] = $_SERVER[ 'PHP_AUTH_PW' ];

			} elseif ( isset( $_SERVER[ 'REDIRECT_HTTP_AUTHORIZATION' ] ) # apache may rename our variable
			        || isset( $_SERVER[ 'HTTP_AUTHORIZATION' ] )
			        || isset( $_ENV[ 'HTTP_AUTHORIZATION' ] )
			) {
				/**
				 * work around for PHP-CGI systems
				 * requires mod_rewirte and the rule
				 * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
				 * or if mod_setenvif is available
				 * SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1
				 */
				$auth_header = isset( $_SERVER[ 'HTTP_AUTHORIZATION' ] )
					? $_SERVER[ 'HTTP_AUTHORIZATION' ]
					: (
						isset( $_SERVER[ 'REDIRECT_HTTP_AUTHORIZATION' ] )
							? $_SERVER[ 'REDIRECT_HTTP_AUTHORIZATION' ]
							: $_ENV[ 'HTTP_AUTHORIZATION' ]
					  );
				$user = array();
				if ( preg_match( '~Basic\s+(.*)$~i', $auth_header, $user ) ) {
					$user = explode( ':', base64_decode( $user[ 1 ] ) );
					$this->user[ 'name' ] = ! empty( $user[ 0 ] )
						? trim( $user[ 0 ] )
						: '';
					$this->user[ 'pass' ] = ! empty( $user[ 1 ] )
						? trim( $user[ 1 ] )
						: '';
				}
			} else {
				$this->auth_required();
			}
		}

		/**
		 * get the user-data
		 *
		 * @return array()
		 */
		public function get_user() {

			return $this->user;
		}

		/**
		 * prints the auth-form and exit
		 *
		 * @return void
		 */
		public function auth_required() {

			$protocol = $_SERVER["SERVER_PROTOCOL"];
			if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol )
				$protocol = 'HTTP/1.0';

			header( 'WWW-Authenticate: Basic realm="' . $this->realm . '"' );
			header( $protocol . ' 401 Unauthorized' );
			echo '<h1>Authentication failed</h1>';
			exit;
		}
	
	} // end class
	
} // end if class exists
