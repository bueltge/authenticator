<?php

/**
 * handles the settings for the authenticator plugin
 *
 * @package Authenticator
 * @since 1.1.0
 */

class Authenticator_Settings {

	/**
	 * default options
	 *
	 * @var array
	 */
	protected static $default_options = array(
		'feed_authentication' => 'none',
		'auth_token'          => '',
		'show_token_to_users' => '0',
		'cookie_lifetime'     => '0',
		'disable_xmlrpc'      => '1'
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
			__( 'Authenticator Options', Authenticator::TEXTDOMAIN ),
			array( $this, 'description' ),
			$this->page
		);

		add_settings_field(
			'feed_authentication',
			__( 'What type of feed authentication you prefer?', Authenticator::TEXTDOMAIN ),
			array( $this, 'auth_checkbox' ),
			$this->page,
			$this->section,
			array(
				'id'        => 'feed_authentication',
				'name'      => Authenticator::KEY . '[feed_authentication]',
			)
		);

		add_settings_field(
			'show_token_to_users',
			__( 'Show auth token on the users profile settings page?', Authenticator::TEXTDOMAIN ),
			array( $this, 'checkbox' ),
			$this->page,
			$this->section,
			array(
				'id'        => 'show_token_to_users',
				'name'      => Authenticator::KEY . '[show_token_to_users]',
				'label_for' => 'show_token_to_users'
			)
		);

		add_settings_field(
			'cookie_lifetime',
			__( 'Cookie lifetime in days', Authenticator::TEXTDOMAIN ),
			array( $this, 'textinput' ),
			$this->page,
			$this->section,
			array(
				'id'        => 'cookie_lifetime',
				'name'      => Authenticator::KEY . '[cookie_lifetime]',
				'label_for' => 'cookie_lifetime',
				'notice'    => __( 'User will be logged in for this time', Authenticator::TEXTDOMAIN )
			)
		);

		add_settings_field(
			'disable_xmlrpc',
			__( 'Disable XMLRPC Interface?', Authenticator::TEXTDOMAIN ),
			array( $this, 'checkbox' ),
			$this->page,
			$this->section,
			array(
				'id'        => 'disable_xmlrpc',
				'name'      => Authenticator::KEY . '[disable_xmlrpc]',
				'label_for' => 'disable_xmlrpc',
				'notice'    => __( 'This setting will disable the interface even for logged in users.', Authenticator::TEXTDOMAIN )
			)
		);


		new Authenticator_Settings_UI();
		new Authenticator_User_Profile();
	}

	/**
	 * prints the form field
	 *
	 * @param array $attr
	 * @return void
	 */
	public function auth_checkbox( $attr ) {

		$id      = $attr[ 'id' ];
		$name    = $attr[ 'name' ];
		$current = $this->options[ $id ];
		?>
		<p>
			<input
				type="radio"
				name="<?php echo $name; ?>"
				id="<?php echo $id . '_none'; ?>"
				value="none"
				<?php checked( $current, 'none' ); ?>
			/>
			<label for="<?php echo $id . '_none'; ?>">
				<?php _e( 'None (redirect to the login form if user is not logged in)', Authenticator::TEXTDOMAIN ); ?>
			</label>
			<br />
			<input
				type="radio"
				name="<?php echo $name; ?>"
				id="<?php echo $id . '_http'; ?>"
				value="http"
				<?php checked( $current, 'http' ); ?>
			/>
			<label for="<?php echo $id . '_http'; ?>">
				<?php _e( 'HTTP Authentication (Basic) with Username/Password of your Wordpress account.', Authenticator::TEXTDOMAIN ); ?>
			</label>
		</p>
		<p>
			<input
				type="radio"
				name="<?php echo $name; ?>"
				id="<?php echo $id . '_token'; ?>"
				value="token"
				<?php checked( $current, 'token' ); ?>
			/>
			<label for="<?php echo $id . '_token'; ?>">
				<?php _e( 'Token Authentication.', '' ); ?>
				<span class="description">
					<?php _e( 'Append the following token as a parameter to the feed URL:', Authenticator::TEXTDOMAIN ); ?>
				</span>
			</label>
			<br />
			<input
				id="authenticator_feed_token"
				size="32"
				type="text"
				readonly="readonly"
				<?php
				if ( 'token' !== $this->options[ 'feed_authentication' ] ) : ?>
					disabled="disabled"
					<?php
				else : ?>
					value="<?php echo $this->get_auth_token(); ?>"
					<?php
				endif; ?>
			/>
			<?php
			if ( 'token' === $this->options[ 'feed_authentication' ] ) : ?>
				<span id="authenticator_regenerate_token_wrapper">
					<input
						type="checkbox"
						id="authenticator_regenerate_token"
						value="1"
						name="authenticator_regenerate_token"
					/>
					<label for="authenticator_regenerate_token">
						<?php _e( 'Regenerate token.', Authenticator::TEXTDOMAIN ); ?>
					</label>
				</span>
				<span class="description">
					<?php _e( '(Note everyone will have to update the URL in its feedreader!)', Authenticator::TEXTDOMAIN ); ?>
				</span>
				<?php
			endif; ?>
		</p>
		<?php
		if ( 'token' === $this->options[ 'feed_authentication' ] ) :
			$example_url = add_query_arg( $this->options[ 'auth_token' ], '', get_bloginfo('rss2_url') );
			#wrap the urlparameter with a span-element
			$example_url = preg_replace(
				'~^(.+[?|&])([a-z0-9]{32})$~',
				'$1<span id="authenticator_token_example">$2</span>',
				$example_url
			);
			?>
			<p class="description">
				<?php printf(
					__( 'Use the Token for every feed URL like so: %s', Authenticator::TEXTDOMAIN ),
					'<code>' . $example_url . '</code>'
				); ?>
			</p>
			<?php
		endif;
	}

	/**
	 * prints a checkbox
	 *
	 * @param array $attr
	 * @return void
	 */
	public function checkbox( $attr ) {

		$id      = $attr[ 'id' ];
		$name    = $attr[ 'name' ];
		$current = $this->options[ $id ];
		?>
		<input
			type="checkbox"
			name="<?php echo $name; ?>"
			id="<?php echo $id; ?>"
			value="1"
			<?php checked( $current, '1' ); ?>
		/>
		<?php
	}

	/**
	 * prints a text input field
	 *
	 * @param array $attr
	 * @return void
	 */
	public function textinput( $attr ) {

		$id      = $attr[ 'id' ];
		$name    = $attr[ 'name' ];
		$current = 0 == $this->options[ $id ]
			? ''
			: $this->options[ $id ];
		?>
		<input
			type="text"
			name="<?php echo $name; ?>"
			id="<?php echo $id; ?>"
			value="<?php echo esc_attr( $current ); ?>"
		/>
		<?php
		if ( ! empty( $attr[ 'notice' ] ) ) : ?>
			<p class="description">
				<?php echo esc_attr( $attr[ 'notice' ] ); ?>
			</p>
			<?php
		endif;
	}


	/**
	 * validate the input
	 *
	 * @param array $request
	 * @return array
	 */
	public function validate( $request ) {

		if ( ! isset( $request[ 'feed_authentication' ] ) )
			$request[ 'feed_authentication' ] = 'none';

		switch ( $request[ 'feed_authentication' ] ) {

			case 'http' :
			case 'none' :
				# nothing to do
				break;

			case 'token' :
				if ( empty( $this->options[ 'auth_token' ] )
				  || isset( $_POST[ 'authenticator_regenerate_token' ] )
				)
					$this->generate_auth_token();

				break;

			default :
				$request[ 'feed_authentication' ] = 'none';
				break;
		}

		if ( ! isset( $request[ 'show_token_to_users' ] ) )
			$request[ 'show_token_to_users' ] = '0';
		else
			$request[ 'show_token_to_users' ] = '1';

		$request[ 'auth_token' ] = $this->get_auth_token();

		$request[ 'cookie_lifetime' ] = ( int ) $request[ 'cookie_lifetime' ];

		if ( empty( $request[ 'disable_xmlrpc' ] ) )
			$request[ 'disable_xmlrpc' ] = '0';
		else
			$request[ 'disable_xmlrpc' ] = '1';


		return $request;
	}

	/**
	 * get the auth token
	 *
	 * @return string
	 */
	public function get_auth_token() {

		if ( ! isset( $this->options[ 'auth_token' ] ) ) {
			$this->options[ 'auth_token' ] = self::$default_options[ 'auth_token' ];
			$this->update_options();
		}

		return $this->options[ 'auth_token' ];
	}

	/**
	 * generate an auth token
	 *
	 * @return string
	 */
	protected function generate_auth_token() {

		if ( ! is_user_logged_in() )
			return;

		$user = wp_get_current_user();

		$this->options[ 'auth_token' ] = md5(
			$user->data->user_pass . uniqid()
		);

		return $this->options[ 'auth_token' ];
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
			$this->options = self::$default_options;
			$this->update_options();
		} else {
			$this->options = $options;
			#check options for updates on default options
			$update = FALSE;
			foreach ( self::$default_options as $k => $v ) {
				if ( ! isset( $this->options[ $k ] ) ) {
					$this->options[ $k ] = $v;
					$update = TRUE;
				}
			}
			if ( $update )
				$this->update_options();

		}
	}

	/**
	 * update options manually
	 *
	 * @return void
	 */
	protected function update_options() {

		update_option( Authenticator::KEY, $this->options );
	}

}
