<?php

/**
 * Class to enhance the settings user interface with a bit javascript
 *
 * @package Authenticator
 * @since   1.1.0
 */
class Authenticator_Settings_UI extends Authenticator_Settings {

	// wp nonce key
	const NONCE_KEY = 'authenticator_ui';

	/**
	 * instance of Authenticator Settings
	 *
	 * @var Authenticator_Settings
	 */
	protected $settings = NULL;

	/**
	 * constructor
	 * note: it's not neccessarty to call the parrents constructor here
	 *
	 * @return Authenticator_Settings_UI
	 */
	public function __construct() {

		$this->load_options();
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'wp_ajax_regenerate_token', array( $this, 'regenerate_token' ) );

	}

	/**
	 * load javascript and pass data to it
	 *
	 * @return void
	 */
	public function load_scripts() {

		wp_enqueue_script(
			'authenticator_admin_ui',
			Authenticator::$url . '/js/admin-ui.js',
			array( 'jquery' ),
			Authenticator::VERSION,
			TRUE
		);

		wp_localize_script(
			'authenticator_admin_ui',
			'authenticatorUI',
			$this->get_script_data()
		);
	}

	/**
	 * return an array of data which will passed to the javascript
	 *
	 * @return array
	 */
	protected function get_script_data() {

		return array(
			'nonce'                => wp_create_nonce( self::NONCE_KEY ),
			'actionHook'           => 'regenerate_token',
			'ajaxURL'              => admin_url( 'admin-ajax.php' ),
			'tokenFieldId'         => 'authenticator_feed_token',
			'exampleURLTokenId'    => 'authenticator_token_example',
			'tokenCheckboxWrapper' => 'authenticator_regenerate_token_wrapper',
			'tokenButtonId'        => 'authenticator_regenerate_token',
			'tokenButtonMarkup'    =>
				'<a class="button-secondary" href="#" id="authenticator_regenerate_token">'
				. __( 'Regenerate token.', Authenticator::TEXTDOMAIN )
				. '</a>',
			'confirmMessage'       => __( 'Are you shure, you want to create a new token?', Authenticator::TEXTDOMAIN )
		);
	}

	/**
	 * regenerate token via ajax
	 *
	 * @return void
	 */
	public function regenerate_token() {

		if ( ! defined( 'DOING_AJAX' ) ) {
			exit;
		}

		if ( ! isset( $_POST[ 'nonce' ] )
		     || ! wp_verify_nonce( $_POST[ 'nonce' ], self::NONCE_KEY )
		) {
			exit;
		}

		$token = $this->generate_auth_token();
		# remove the filter from sanitize_option which triggers Authenticator_Settings::validate
		remove_all_filters( 'sanitize_option_' . Authenticator::KEY );
		$this->update_options();
		echo $token;
		exit;
	}

}

