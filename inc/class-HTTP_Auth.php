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
	}
}
