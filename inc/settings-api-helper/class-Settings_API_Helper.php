<?php
/**
 * helper for the settings API of Wordpress
 *
 * @version 0.1
 * @package Wordpress
 * @subpackage WP Settings API Helper
 * @author David Naber <kontakt@dnaber.de>
 */

if ( ! class_exists( 'Settings_API_Helper' ) ) :

class Settings_API_Helper {

	/**
	 * section name
	 *
	 * @var string
	 */
	protected $section = '';

	/**
	 * description of this section
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * general error message
	 *
	 * @var string
	 */
	protected $error_message = '';

	/**
	 * option key
	 *
	 * @var string
	 */
	protected $option_key = '';

	/**
	 * page
	 *
	 * @var string
	 */
	protected $page = '';

	/**
	 * fields
	 *
	 * @var array
	 */
	protected $fields = array();

	/**
	 * constructor
	 *
	 * @param string $option_key
	 * @param string $page
	 * @param string $lable (The heading text)
	 * @param string $description (Optional) (Small description of the section, may contain HTML)
	 * @param string $error_msg (Optional) (A general error message, unused at the moment)
	 * @param string $section (Optional)
	 */
	public function __construct( $option_key, $page, $label, $description = '', $error_msg = '', $section = '' ) {

		$this->option_key = $option_key;
		$this->page = $page;
		$this->description = $description;
		$this->error_message = $error_msg;

		if ( empty( $section ) )
			$section = $option_key . '_section';
		$this->section = $section;

		register_setting(
			$this->page,
			$this->option_key,
			array( $this, 'validate' )
		);

		add_settings_section(
			$this->section,
			$label,
			array( $this, 'description' ),
			$this->page
		);

	}

	/**
	 * print description
	 *
	 * @return void
	 */
	public function description() {

		if ( empty( $this->description ) )
			return;
		?>
		<div class="inside">
			<?php echo wpautop( $this->description ); ?>
		</div>
		<?php
	}

	/**
	 * validate the input
	 *
	 * @param array $request
	 * @return array (sanitized input)
	 */
	public function validate( $request ) {

		foreach ( $this->fields as $field ) {
			$field->validate( &$request );
			if ( $field->is_invalid() ) {
				$this->invalid_fields[] = $field;
			}
		}

		return $request;
	}


	/**
	 * add field
	 *
	 * @param string $name
	 * @param string $type
	 * @param array $options (Optional)
	 * @return void
	 */
	public function add_field( $name, $label, $type, $options ) {

		$new_field
			= new Settings_API_Field(
				$name,
				$label,
				$type,
				$options,
				$this->section,
				$this->page,
				$this->option_key
		);
		$this->fields[] = $new_field;

	}

	/**
	 * add a text field
	 *
	 * @param string $name
	 * @param string $label
	 * @param array $options (Optional)
	 * @return void
	 */
	public function add_text( $name, $label, $options = array() ) {

		$defaults = array(
			#'sanitize_callback' => 'sanitize_text_field',
		);
		$options = wp_parse_args( $options, $defaults );
		$this->add_field( $name, $label, 'text', $options );
	}

	/**
	 * add a checkbox
	 *
	 * @param string $name
	 * @param string $label (Optional)
	 * @param array $options
	 * @return void
	 */
	public function add_checkbox( $name, $label, $options = array() ) {

		$this->add_field( $name, $label, 'checkbox', $options );
	}

	/**
	 * add a select-element
	 *
	 * @param string $name
	 * @param string $label
	 * @param array $options (Optional)
	 * @return void
	 */
	public function add_select( $name, $label, $options = array() ) {

		$defaults = array(
			'options' => array( '' )
		);
		$options = wp_parse_args( $options, $defaults );
		$this->add_field( $name, $label, 'select', $options );
	}

	/**
	 * add a textarea
	 *
	 * @param string $name
	 * @param string $label
	 * @param array $options (Optional)
	 * @return void
	 */
	public function add_textarea( $name, $label, $options = array() ) {

		$this->add_field( $name, $label, 'textarea', $options );
	}

	/**
	 * add a bunch of radio-buttons
	 *
	 * @param string $name
	 * @param string $label
	 * @param array $options (Optional)
	 * @return void
	 */
	public function add_radio( $name, $label, $options = array() ) {

		$this->add_field( $name, $label, 'radio', $options );
	}

	/**
	 * add a color-input
	 *
	 * @param string $name
	 * @param string $label
	 * @param array $options (Optional)
	 * @return void
	 */
	public function add_color( $name, $label, $options = array() ) {

		$defaults = array(
			'pattern'           => '~^#[0-9a-f]{6}$~i',
			'sanitize_callback' => 'sh_sanitize_hex_color'
		);
		$options = wp_parse_args( $options, $defaults );
		$this->add_field( $name, $label, 'color', $options );
	}

	/**
	 * add a url input
	 *
	 * @param string $name
	 * @param string $label
	 * @param array $options (Optional)
	 * @return void
	 */
	public function add_url( $name, $label, $options = array() ) {

		$defaults = array(
			'validate_callback' => 'sh_validate_url',
			'sanitize_callback' => 'sh_sanitize_url'
		);
		$options = wp_parse_args( $options, $defaults );
		$this->add_field( $name, $label, 'url', $options );
	}

	/**
	 * add a email input
	 *
	 * @param string $name
	 * @param string $label
	 * @param array $options (Optional)
	 * @return void
	 */
	public function add_email( $name, $label, $options = array() ) {

		$defaults = array(
			'validate_callback' => 'sh_validate_email',
			'sanitize_callback' => 'sh_sanitize_email'
		);
		$options = wp_parse_args( $options, $defaults );
		$this->add_field( $name, $label, 'email', $options );
	}

	/**
	 * add a email input
	 *
	 * @param string $name
	 * @param string $label
	 * @param array $options (Optional)
	 * @return void
	 */
	public function add_date( $name, $label, $options = array() ) {

		$defaults = array(
			'validate_callback' => 'sh_validate_date',
			'sanitize_callback' => 'sh_sanitize_date'
		);
		$options = wp_parse_args( $options, $defaults );
		$this->add_field( $name, $label, 'date', $options );
	}

	/**
	 * add a email input
	 *
	 * @param string $name
	 * @param string $label
	 * @param array $options (Optional)
	 * @return void
	 */
	public function add_time( $name, $label, $options = array() ) {

		$defaults = array(
			'validate_callback' => 'sh_validate_time',
			'sanitize_callback' => 'sh_sanitize_time'
		);
		$options = wp_parse_args( $options, $defaults );
		$this->add_field( $name, $label, 'time', $options );
	}

	/**
	 * add a email input
	 *
	 * @param string $name
	 * @param string $label
	 * @param array $options (Optional)
	 * @return void
	 */
	public function add_datetime( $name, $label, $options = array() ) {

		$defaults = array(
			'validate_callback' => 'sh_validate_datetime',
			'sanitize_callback' => 'sh_sanitize_datetime',
		);
		$options = wp_parse_args( $options, $defaults );
		$this->add_field( $name, $label, 'datetime', $options );
	}

	/**
	 * add a number input
	 *
	 * @param string $name
	 * @param string $label
	 * @param array $options (Optional)
	 * @return void
	 */
	public function add_number( $name, $label, $options = array() ) {

		$defaults = array(
			'validate_callback' => 'sh_validate_number',
			'sanitize_callback' => 'sh_sanitize_number',
			'range'             => array(
				'', #min
				'', #max
				''  #step
			)
		);
		$options = wp_parse_args( $options, $defaults );
		$this->add_field( $name, $label, 'number', $options );
	}

	/**
	 * add a range input
	 *
	 * @param string $name
	 * @param string $label
	 * @param array $options (Optional)
	 * @return void
	 */
	public function add_range( $name, $label, $options = array() ) {

		$defaults = array(
			'validate_callback' => 'sh_validate_number',
			'sanitize_callback' => 'sh_sanitize_number',
			'range'             => array(
				'', #min
				'', #max
				''  #step
			)
		);
		$options = wp_parse_args( $options, $defaults );
		$this->add_field( $name, $label, 'range', $options );
	}

	/**
	 * getter for the section name
	 *
	 * @return string
	 */
	public function get_section() {

		return $this->section;
	}

	/**
	 * print all! sections of the current (custom) page
	 *
	 * @return void
	 */
	public function the_section() {

		settings_fields( $this->page );
		do_settings_sections( $this->page );
	}

	/**
	 * prints the errors for the current section
	 *
	 * @return void
	 */
	public function the_errors() {

		settings_errors( $this->page );
	}

	/**
	 * print formular
	 *
	 * @return void
	 */
	public function the_form() {
		global $settings_2;
		?>
		<div class="inside">
		<form method="post" action="<?php echo admin_url( 'options.php' ); ?>">
		<?php
		# get all errors for this page
		$this->the_errors();
		# get all sections for this page
		$this->the_section();
		?>
			<div class="inside">
			<p><input type="submit" class="button-primary" value="<?php esc_attr_e( 'Submit' ); ?>" /></p>
			</div>
		</form>
		</div>
		<?php
	}
}

endif; # class exists
