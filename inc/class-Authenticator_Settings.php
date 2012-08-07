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
		'feed_authentication' => 'none',
		'auth_token'          => ''
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
			array( $this, 'checkbox' ),
			$this->page,
			$this->section,
			array(
				'id'        => 'feed_authentication',
				'name'      => Authenticator::KEY . '[feed_authentication]',
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
				<input
					type="checkbox"
					id="authenticator_regenerate_token"
					value="1"
					name="authenticator_regenerate_token"
				/>
				<label for="authenticator_regenerate_token">
					<?php _e( 'Regenerate token.', Authenticator::TEXTDOMAIN ); ?>
				</label>
				<span class="description">
					<?php _e( '(Note everyone will have to update the URL in its feedreader!)', Authenticator::TEXTDOMAIN ); ?>
				</span>
				<?php
			endif; ?>
		</p>
		<?php
		if ( 'token' === $this->options[ 'feed_authentication' ] ) : ?>
			<p class="description">
				<?php printf(
					__( 'Use the Token for every feed URL like so: %s', Authenticator::TEXTDOMAIN ),
					'<code>' . get_bloginfo('rss2_url') . '?<span id="authenticator_token_example">' . $this->get_auth_token() . '</span></code>'
				); ?>
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

		$request[ 'auth_token' ] = $this->get_auth_token();
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
