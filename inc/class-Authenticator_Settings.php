<?php

/**
 * handles the settings for the authenticator plugin
 *
 * @since 1.1.0
 */

class Authenticator_Settings {


	/**
	 * default options
	 *
	 * @var array
	 */
	protected static $default_options = array(
		'http_auth_feed' => '0'
	);

	/**
	 * current options
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * the settings page
	 *
	 * @var string
	 */
	public $page = 'reading';

	/**
	 * section identifyer
	 *
	 * @var string
	 */
	public $section = 'authenticator_reading';

	/**
	 * constructor
	 *
	 * @return Authenticator_Settings
	 */
	public function __construct() {

		$this->load_options();

		register_setting(
			$this->page,
			Authenticator::KEY,
			array( $this, 'validate' )
		);

		add_settings_section(
			$this->section,
			'Authenticator Options',
			array( $this, 'description' ),
			$this->page
		);

		add_settings_field(
			'http_auth_feed',
			'Require HTTP-Auth (Basic) for feeds instead of the WP login form.',
			array( $this, 'checkbox' ),
			$this->page,
			$this->section,
			array(
				'id'        => 'http_auth_feed',
				'name'      => Authenticator::KEY . '[http_auth_feed]',
				'label_for' => 'http_auth_feed'
			)
		);
	}

	/**
	 * prints the form field
	 *
	 * @param array $attr
	 * @return void
	 */
	public function checkbox( $attr ) {

		$id      = $attr[ 'label_for' ];
		$name    = $attr[ 'name' ];
		$current = $this->options[ $id ];
		?>
		<input
			type="checkbox"
			name="<?php echo $name; ?>"
			id="<?php echo $attr[ 'label_for' ]; ?>"
			value="1"
			<?php checked( $current, '1' ); ?>
		/>
		<?php
	}

	/**
	 * validate the input
	 *
	 * @param array $request
	 * @return array
	 */
	public function validate( $request ) {

		if ( ! empty( $request[ 'http_auth_feed' ] ) && '1' === $request[ 'http_auth_feed' ] )
			$request[ 'http_auth_feed' ] = '1';
		else
			$request[ 'http_auth_feed' ] = '0';

		return $request;
	}

	/**
	 * prints the sections description, if it were needed
	 *
	 * @return void
	 */
	public function description() {

		return;
	}

	/**
	 * load options and set defaults if necessary
	 *
	 * @return void
	 */
	public function load_options() {

		$options = get_option( Authenticator::KEY, '' );

		if ( ! is_array( $options ) ) {
			$options = self::$default_options;
			update_option( Authenticator::KEY, $options );
		}
		$this->options = $options;
	}

}
