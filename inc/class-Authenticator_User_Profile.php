<?php

/**
 * shows the auth token on the user settings page
 *
 * @package Authenticator
 * @since   1.1.0
 */
class Authenticator_User_Profile {

	// plugins settings
	public $options = array();

	/**
	 * constructor
	 */
	public function __construct() {

		$this->options = get_option( Authenticator::KEY );

		add_action( 'show_user_profile', array( $this, 'add_custom_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'add_custom_profile_fields' ) );
	}

	/**
	 * Add custom profile fields
	 *
	 * @param  array $user
	 *
	 * @return void
	 */
	public function add_custom_profile_fields( $user ) {

		if ( 'token' !== $this->options[ 'feed_authentication' ] ) {
			return;
		}

		if ( '0' === $this->options[ 'show_token_to_users' ]
		     || ! user_can( $user, 'read' )
		) {
			return;
		}
		?>
		<h3><?php _e( 'Authenticator', Authenticator::TEXTDOMAIN ); ?></h3>

		<table class="form-table">
			<tr id="post_subscription">
				<th>
					<label for="auth_token"><?php _e( 'Auth token for feeds', Authenticator::TEXTDOMAIN ); ?></label>
				</th>
				<td>
					<input
						name="auth_token"
						type="text"
						id="auth_token"
						readonly="readonly"
						size="32"
						value="<?php echo $this->options[ 'auth_token' ]; ?>"
						/>
					<span class="description">
						<?php printf(
							__( 'Append this token to your feed URLs like: %s', Authenticator::TEXTDOMAIN ),
							'<code>' . add_query_arg( $this->options[ 'auth_token' ], '', get_bloginfo( 'rss2_url' ) ) . '</code>'
						);?>
					</span>
				</td>
			</tr>
		</table>
	<?php
	}
}
