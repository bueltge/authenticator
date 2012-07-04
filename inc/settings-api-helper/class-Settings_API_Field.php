<?php
/**
 * Add various types of input fields to a settings section
 *
 * @package Wordpress
 * @subpackage WP Settings API Helper
 * @author David Naber <kontakt@dnaber.de>
 */

if ( ! class_exists( 'Settings_API_Field' ) ) :

class Settings_API_Field {

	/**
	 * name
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * label
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * parameter of this field
	 *
	 * @var array
	 */
	protected $params = array();

	/**
	 * is invalid
	 *
	 * @var bool
	 */
	protected $is_invalid = FALSE;

	/**
	 * current error message
	 *
	 * @var string
	 */
	protected $error_message = '';

	/**
	 * make shure to set an error only once
	 *
	 * @var bool
	 */
	protected $has_error = FALSE;

	/**
	 * option key
	 *
	 * @var string
	 */
	protected $option_key = '';

	/**
	 * value of the option table
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * settings section
	 *
	 * @var string
	 */
	protected $section = '';

	/**
	 * settings page
	 *
	 * @var string
	 */
	protected $page = '';

	/**
	 * default value
	 *
	 * @var mixed
	 */
	protected $default = NULL;

	/**
	 * constructor
	 *
	 * @param string $name
	 * @param string $label
	 * @param string $type
	 * @param array $options
	 * @param string $section
	 * @param string $page
	 */
	public function __construct( $name, $label, $type, $options, $section, $page, $option_key ) {

		$this->name       = $name;
		$this->label      = $label;
		$this->option_key = $option_key;
		$this->section    = $section;
		$this->page       = $page;
		$this->settings   = get_option( $this->option_key, array() );

		$defaults = array(
			'id'                => $this->name . '_id',
			'label_for'         => $this->name . '_id',
			'class'             => '',
			'options'           => array( /* name => label */ ), # for select-elements and radiobuttons
			'pattern'           => '~.*~',
			'value'             => '1', # will be used for checkboxes (not the default value of any other element!)
			'required'          => FALSE,
			'autofocus'         => FALSE,
			'placeholder'       => '',
			'default'           => '',
			'range'             => array( /*$min, $max, $step*/ ),
			'atts'              => array(), #additional attributes
			'html_before'       => '',
			'html_after'        => '',
			'validate_callback' => '',
			'sanitize_callback' => '',
			'error_messages' => apply_filters(
				'sh_field_error_messages',
				array(
					'missing_required' => 'The field »%s« is required',
					'mismatch_pattern' => '<code>%2$s</code> is not a valid value for »%1$s«',
				)
			)
		);
		# some default patterns
		# @todo check this
		switch ( $type ) {
			case 'date' :
				$defaults[ 'pattern' ] = '~^\d{4}-\d\d–\d\d$~';
				break;
			case 'datetime' :
				$defaults[ 'pattern' ] = '~^\d{4}-\d\d–\d\d\s\d\d:\d\d(?:\:\d\d)?$~';
				break;
			case 'time' :
				$defaults[ 'pattern' ] = '~^\d\d:\d\d(?:\:\d\d)?$~';
				break;
			case 'color' :
				$defaults[ 'pattern' ] = '~^#[0-9a-f]{6}$~';
				break;
			case 'number' :
				$defaults[ 'pattern' ] = '~^\d*(?:[.|,]\d+)?$~';
				break;
		}

		# merge error messages
		if ( empty( $options[ 'error_messages' ] ) )
			$options[ 'error_messages' ] = array();
		$options[ 'error_messages' ] = wp_parse_args( $options[ 'error_messages' ], $defaults[ 'error_messages' ] );

		# merge options with defaults
		$options = wp_parse_args( $options, $defaults );

		if ( ! empty( $options[ 'default' ] ) )
			$this->default = $options[ 'default' ];
		$this->set_default(); # write defaults into DB

		$options[ 'atts' ][ 'value' ] = $this->settings[ $this->name ];
		$options[ 'atts' ][ 'name' ] = $this->option_key . '[' . $this->name . ']';

		# build range attributes
		if ( ! empty( $options[ 'range' ] ) ) {

			# cast as strings
			foreach ( $options[ 'range' ] as $k => $v ) {
				$options[ 'range' ][ $k ] = ( string ) $v;
			}

			if ( ! empty( $options[ 'range' ][ 0 ] ) )
				$options[ 'atts' ][ 'min' ] = $options[ 'range' ][ 0 ];

			if ( ! empty( $options[ 'range' ][ 1 ] ) )
				$options[ 'atts' ][ 'max' ] = $options[ 'range' ][ 1 ];

			if ( ! empty( $options[ 'range' ][ 2 ] ) )
				$options[ 'atts' ][ 'step' ] = $options[ 'range' ][ 2 ];

		}

		# some type-specific things
		if ( 'radio' === $type || empty( $options[ 'label_for' ] ) )
			unset( $options[ 'label_for' ] );

		$this->params = $options;

		# handle all HTML-attributes in one array
		$html_atts = array(
			'id',
			'class',
			'required',
			'placeholder'
		);
		$bool_atts = array(
			'required',
			'autofocus'
		);
		foreach ( $options as $k => $v ) {
			if ( ! in_array( $k, $html_atts ) )
				continue;

			if ( in_array( $k, $bool_atts ) ) {
				if ( TRUE === ( bool ) $v )
					$options[ 'atts' ][ $k ] = $k;
			}
			else
				$options[ 'atts' ][ $k ] = $options[ $k ];

			unset( $options[ $k ] );
		}


		add_settings_field(
			$this->name . '_id',
			$label,
			array( $this, 'input_' . $type ),
			$page,
			$section,
			$options
		);
	}

	/**
	 * general getter for object properties
	 *
	 * @param string $poperty
	 * @return mixed
	 */
	public function get( $property ) {

		if ( isset( $this->{ $property } ) )
			return $this->{ $property };

		return NULL;
	}

	/**
	 * set default value
	 *
	 * @return void
	 */
	protected function set_default() {

		if ( isset( $this->settings[ $this->name ] ) )
			return;

		$this->settings[ $this->name ] = $this->default;
		update_option( $this->option_key, $this->settings );
	}

	/**
	 * sets the field to invalid and sets the error message
	 *
	 * @param string $cause (Key of the default error messa
	 * @return void
	 */
	public function set_invalid( $cause = 'mismatch_pattern' ) {

		$this->is_invalid = TRUE;
		$this->error_message = $this->params[ 'error_messages' ][ $cause ];
	}

	/**
	 * validate
	 *
	 * @param array $request (Passed by Reference)
	 * @return mixed
	 */
	public function validate( &$request ) {

		# call a hand validation function
		if ( ! empty( $this->params[ 'validate_callback' ] ) ) {
			$request[ $this->name ] =
				call_user_func_array(
					$this->params[ 'validate_callback' ],
					array(
						$request[ $this->name ],
						&$this
					)
				);
		} elseif ( ! empty( $this->params[ 'pattern' ] ) ) {
			if ( ! preg_match( $this->params[ 'pattern' ], $request[ $this->name ] ) )
				$this->set_invalid( 'mismatch_pattern' );
		} elseif ( $this->params[ 'required' ] && empty( $request[ $this->name ] ) ) {
			$this->set_invalid( 'missing_required' );
		}

		# sanitize
		if ( $this->is_invalid ) {
			$this->add_error( $request[ $this->name ] );
			if ( ! empty( $this->params[ 'sanitize_callback' ] ) ) {
				$request[ $this->name ] =
					call_user_func_array(
						$this->params[ 'sanitize_callback' ],
						array(
							$request[ $this->name ],
							&$this
						)
					);
			} elseif ( $this->default ) {
				$request[ $this->name ] = $this->default;
			}
		}
	}

	/**
	 * getter for is_invalid
	 *
	 * @return bool
	 */
	public function is_invalid() {

		return $this->is_invalid;
	}

	/**
	 * adds a setting error
	 *
	 * @param mixed $value
	 * @param mixed $code (Error code)
	 * @param string $message
	 * @return bool
	 */
	public function add_error( $value = '', $code = 0, $message = '' ) {

		if ( empty( $message ) )
			$message = $this->error_message;

		if ( empty( $code ) )
			$code = $this->name;

		if ( $this->has_error )
			return;

		add_settings_error(
			$this->page,
			$code,
			sprintf( $message, $this->name, $value )
		);
		$this->has_error = TRUE;
	}

	/**
	 * prints a text-input fields
	 *
	 * @param array $args
	 * @return void
	 */
	public function input_text( $args, $type = 'text' ) {

		$atts = $this->build_atts( $args[ 'atts' ] );
		echo $args[ 'html_before' ];
		?>
		<input type="<?php echo $type; ?>" <?php echo $atts; ?> />
		<?php
		echo $args[ 'html_after' ];

	}

	/**
	 * prints a color-input field
	 *
	 * @param array $args
	 * @return void
	 */
	public function input_color( $args ) {

		$this->input_text( $args, 'color' );
	}

	/**
	 * prints a url-input field
	 *
	 * @param array $args
	 * @return void
	 */
	public function input_url( $args ) {

		$this->input_text( $args, 'url' );
	}

	/**
	 * prints a email-input field
	 *
	 * @param array $args
	 * @return void
	 */
	public function input_email( $args ) {

		$this->input_text( $args, 'email' );
	}

	/**
	 * prints a time-input field
	 *
	 * @param array $args
	 * @return void
	 */
	public function input_time( $args ) {

		$this->input_text( $args, 'time' );
	}
	/**
	 * prints a date-input field
	 *
	 * @param array $args
	 * @return void
	 */
	public function input_date( $args ) {

		$this->input_text( $args, 'date' );
	}

	/**
	 * prints a datetime-input field
	 *
	 * @param array $args
	 * @return void
	 */
	public function input_datetime( $args ) {

		$this->input_text( $args, 'datetime' );
	}

	/**
	 * prints a number-input field
	 *
	 * @param array $args
	 * @return void
	 */
	public function input_number( $args ) {

		$this->input_text( $args, 'number' );
	}

	/**
	 * prints a number-input field
	 *
	 * @param array $args
	 * @return void
	 */
	public function input_range( $args ) {

		$this->input_text( $args, 'range' );
	}

	/**
	 * prints a checkbox field
	 *
	 * @param array $args
	 * @return void
	 */
	public function input_checkbox( $args ) {

		$checked = checked( $args[ 'atts' ][ 'value' ], $args[ 'value' ], FALSE );
		unset( $args[ 'atts' ][ 'value' ] );
		$atts = $this->build_atts( $args[ 'atts' ] );

		echo $args[ 'html_before' ];
		?>
		<input
			type="checkbox"
			value="<?php echo $args[ 'value' ]; ?>"
			<?php echo $checked; ?>
			<?php echo $atts; ?>
		/>
		<?php
		echo $args[ 'html_after' ];

	}

	/**
	 * prints a select-element
	 *
	 * @param array $args
	 * @return void
	 */
	public function input_select( $args ) {

		$current = $args[ 'atts' ][ 'value' ];
		unset( $args[ 'atts' ][ 'value' ] );
		$atts = $this->build_atts( $args[ 'atts' ] );

		echo $args[ 'html_before' ];
		?>
		<select <?php echo $atts; ?>>
			<?php
			foreach ( $args[ 'options' ] as $value => $label ) {
				if ( is_int( $value ) )
					$value = $label;
				?>
				<option
					value="<?php echo $value;?>"
					<?php selected( $value, $current ); ?>
				>
					<?php echo $label; ?>
				</option>
				<?php
			} ?>
		</select>
		<?php
		echo $args[ 'html_after' ];
	}

	/**
	 * prints a textarea
	 *
	 * @param array $args
	 * @return void
	 */
	public function input_textarea( $args ) {

		$value = $args[ 'atts' ][ 'value' ];
		unset( $args[ 'atts' ][ 'value' ] );
		$atts = $this->build_atts( $args[ 'atts' ] );

		echo $args[ 'html_before' ];
		?>
		<textarea <?php echo $atts; ?>><?php
			echo esc_attr( $value );
		?></textarea>
		<?php
		echo $args[ 'html_after' ];
	}

	/**
	 * prints a bunsh of radiobuttons
	 *
	 * @param array $args
	 * @return void
	 */
	public function input_radio( $args ) {

		$current = $args[ 'atts' ][ 'value' ];
		unset( $args[ 'atts' ][ 'value' ] );
		$atts = $args[ 'atts' ];
		$i = 1;

		echo $args[ 'html_before' ];
		foreach ( $args[ 'options' ] as $value => $label ) {
			if ( is_int( $value ) )
				$value = $label; # nummeric arrays

			$tmp_atts = $atts;
			$tmp_atts[ 'value' ] = $value;
			$tmp_atts[ 'id' ] .= '_' . $i;
			$tmp_id = $tmp_atts[ 'id' ];
			$i++;

			$tmp_atts = $this->build_atts( $tmp_atts );
			?>
			<input
				type="radio"
				<?php checked( $current, $value ) ?>
				<?php echo $tmp_atts; ?>
			/> <label for="<?php echo $tmp_id; ?>"><?php echo $label; ?></label>
			<br />
			<?php
		}
		echo $args[ 'html_after' ];
	}

	/**
	 * build html attributes from an array
	 *
	 * @param array $atts
	 * @return string
	 */
	protected function build_atts( $atts ) {

		$html = '';
		foreach ( $atts as $name => $value ) {
			if ( empty( $value ) )
				continue;

			if ( is_array( $value ) )
				$value = implode( ' ', $value );

			$html .= $name . '="' . $value . '" ';
		}

		return $html;
	}

	/**
	 * author's best
	 */
	public function dump() {
		$args = func_get_args();
		echo '<pre>';
		foreach ( $args as $arg ) {
			var_dump( $arg );
		}
		exit;
	}

}

endif; # class exists
