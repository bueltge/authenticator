<?php

/**
 * include this file to load the Settings API Helper
 */

if ( ! class_exists( 'Settings_Api_Autoload' ) ) {

	class Settings_API_Autoload {

		/**
		 * include paths
		 *
		 * @var array
		 */
		protected static $include_paths = array();


		/**
		 * add include paths
		 *
		 * @param array|string $paths
		 * @return void
		 */
		public static function add_path( $paths ) {

			if ( is_array( $paths ) ) {
				foreach ( $paths as $path ) {
					if ( is_dir( $path ) ) {
						self :: $include_paths[] = $path;
					}
				}
			} else {
				if ( is_dir( (string)$paths ) ) {
					self :: $include_paths[] = $paths;
				}
			}
		}

		/**
		 * autoloader
		 *
		 * @param string $class
		 * @return void
		 */
		public static function load( $class ) {

			$class = 'class-' . $class . '.php';
			foreach ( self::$include_paths as $path ) {
				# remove trailing slash
				$path = rtrim( $path, '/' );
				if ( file_exists( $path . '/' . $class ) ) {
					require_once $path . '/' .$class ;
					return;
				}
			}
		}
	}
	Settings_API_Autoload::add_path( dirname( __FILE__ ) );
	spl_autoload_register( array( 'Settings_API_Autoload', 'load' ) );
	#functions
	require_once dirname( __FILE__ ) . '/settings-api-helper-functions.php';

}
