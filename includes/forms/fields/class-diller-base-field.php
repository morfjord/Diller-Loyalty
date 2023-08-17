<?php
/**
 * Abstract class for all fields.
 * Subclasses need only override html()
 *
 * @abstract
 *
 */

abstract class Diller_Base_Field {

	/**
	 * Current field value.
	 *
	 * @var mixed
	 */
	public $value;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var array
	 */
	protected $args = array();

	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var string
	 */
	private $title;


	/**
	 * Diller_Base_Field constructor.
	 *
	 * @param string $name Field name/ID.
	 * @param string $title Title to display in field.
	 * @param array  $values Values to populate field(s) with.
	 * @param array  $args Optional. Field definitions/arguments.
	 */
	public function __construct( $name, $title, array $values, $args = array() ) {

		$this->id    = sprintf("%s-%s", $name, uniqid()); //Generate a dynamic unique id. Helpful for frontend JS code and avoid collisions with other plugins
		$this->name  = $name;
		$this->title = $title;
		$this->set_arguments( $args );

		// If the field has a custom value populator callback, use it
		if ( ! empty( $args['values_callback'] ) ) {
			$this->values = call_user_func( $args['values_callback'] );
		} else {
			$this->values = $values;
		}

		$this->value = reset( $this->values );
	}

	/**
	 * Establish baseline default arguments for a field.
	 *
	 * @return array Default arguments.
	 */
	public function default_args() {
		return array(
			'desc'                => '',
			'validation_rules'    => false,
			'readonly'            => false,
			'disabled'            => false,
			'required'            => false,
			'max_length'          => 255,
			'min_length'          => 0,
			'default'             => '',
			'placeholder'         => '',
			'style'               => '',
			'class'               => 'diller-form-control',
			'outer_class'         => 'diller-form-group',
			'data_delegate'       => '',
            'depends_on_field'    => '' // depends_on_field holds the name of another field, from which we want its value later on
		);
	}


	/**
	 * Get the default args for the abstract field.
	 * These args are available to all fields.
	 *
	 * @return array $args
	 */
	public function get_default_args() {
		return $this->args;
	}

	/**
	 * Return any inline scripts for this field.
	 *
	 * @uses inline_scripts()
	 */
	public function inline_scripts() {}

	public function get_validation_rules() {
		return (isset($this->args['validation_rules'])) ? $this->args['validation_rules'] : array();
	}

    public function get_title(){
	    return $this->title;
    }

	/**
	 * Output the field input ID attribute.
	 *
	 */
	public function id_attr() {
		printf( 'id="%s"', esc_attr( $this->get_id() ) );
	}

	/**
	 * Get the unique field ID. This ID will be used in the "for" attribute of the field label.
	 *
	 */
	public function get_id() {
		return  $this->id;
	}

	/**
	 * Set the ID for a field.
	 *
	 * @return string
	 */
	public function set_id($value) {
		$this->id = $value;
	}

	/**
	 * Output value attribute for a field.
	 */
	public function value_attr( $value ) {
        echo ' value="' . esc_attr($value) . '" ';
	}

	/**
	 * Returns whether this field has validations enabled or not
	 */
	public function validation_enabled() {
		return !empty($this->args['validation_rules']);
	}

	/**
	 * Returns whether this field is required or not
	 */
	public function is_required() {
		return $this->args['required'] || $this->args['validation_rules']['required'];
	}

	/**
	 * Output required attributes for a field (JQuery validate unobtrusive style).
	 */
	public function required_attr() {
		if ( $this->args['required'] ) {
			$required_message = !empty($this->args['required_message'])
				? $this->args['required_message']
				/* translators: %s: Field name. */
				: sprintf(__('%s is required', 'diller-loyalty'), $this->get_title());

			echo ' data-val-required="' . esc_attr($required_message) . '" data-val="true" ';
		}
	}

	public function min_length() {
		return (int)$this->args['min_length'];
	}

	public function max_length() {
		return (int)$this->args['max_length'];
	}


	/**
	 * Output min and max length attribute for a field (JQuery validate unobtrusive style).
	 */
	public function length_attr() {

        

		$validation_message = '';

		if ( (int)$this->args['min_length'] > 0) {
			$validation_message .= sprintf(
			        /* translators: 1: Field name, 2: Field min length */
                    __('%1$s must be minimum %2$d characters long', 'diller-loyalty'),
                    $this->get_title(),
                    (int)$this->args['min_length']
            );
			echo ' data-val-length-min="' . esc_attr($this->args['min_length']) . '" ';
		}

		if ( (int)$this->args['max_length'] > 0) {

			echo ' data-val-length-max="' . esc_attr($this->args['max_length']) . '" ';

			$validation_message .= ((int)$this->args['min_length'] > 0)
				? sprintf(
				    /* translators: %d: Field max length */
					__(' and maximum of %d characters long', 'diller-loyalty'),
                    (int)$this->args['max_length']
				)
				: sprintf(
				    /* translators: 1: Field name, 2: Field length */
					__('%1$s cannot be longer than %2$d characters', 'diller-loyalty'),
                    $this->get_title(),
                    (int)$this->args['max_length']
				);

			echo ' data-val-length="' . esc_attr($validation_message) . '" ';
		}
	}

	/**
	 * Output the field input ID attribute value.
	 *
	 *@see get_id
	 *
	 */
	public function for_attr() {
		printf( 'for="%s"', esc_attr( $this->get_id() ) );
	}

	/**
	 * Output HTML name attribute for a field.
	 *
	 * @see get_the_name_attr
	 *
	 * @param string $append Optional. Name to place.
	 */
	public function name_attr( $append = null ) {
		$name = $this->get_the_name_attr( $append );
		printf( 'name="%s"', esc_attr( $name ) );
	}

	/**
	 * Get the name attribute contents for a field.
	 *
	 * @param null $append Optional. Name to place.
	 * @return string Name attribute contents.
	 */
	public function get_the_name_attr( $append = null ) {
		$name = str_replace( '[]', '', $this->name );
		if ( !is_null( $append ) ) {
			$name .= $append;
		}
		return $name;
	}

	/**
	 * Output class attribute for a field.
	 *
	 * @param string $classes Optional. Classes to assign to the field.
	 */
	public function class_attr( $classes = '' ) {

		// Combine any passed-in classes and the ones defined in the arguments and sanitize them.
		$all_classes = array_unique( explode( ' ', $classes . ' ' . $this->args['class'] ) );
		$classes     = array_map( 'sanitize_html_class', array_filter( $all_classes ) );
		if ( $classes = implode( ' ', $classes ) ){
			printf( 'class="%s"', esc_attr( $classes ) );
		}
	}

	/**
	 * Output class attribute for the outer element of the field.
	 *
	 * @param string $classes Optional. Classes to assign to the outer element of the field.
	 */
	public function outer_class_attr( $classes = '' ) {
		// Combine any passed-in classes and the ones defined in the arguments and sanitize them.
		$all_classes = array_unique( explode( ' ', $classes . ' ' . $this->args['outer_class'] ) );
		$classes     = array_map( 'sanitize_html_class', array_filter( $all_classes ) );
		if ( $classes = implode( ' ', $classes ) ){
			printf( 'class="%s"', esc_attr( $classes ) );
		}
	}

	/*public function data_attr( $attrs = array() ) {
        foreach ($attrs as $data_attr => $value) {
            printf( ' data-%s="%s" ', esc_attr( $data_attr ), esc_attr( $value ));
        }
	}*/

	/**
	 * Print one or more HTML5 attributes for a field.
	 *
	 * @param array $attrs Optional. Attributes to define in the field.
	 */
	public function boolean_attr( $attrs = array() ) {

		if ( $this->args['readonly'] ) {
			$attrs[] = 'readonly';
		}

		if ( $this->args['disabled'] ) {
			$attrs[] = 'disabled';
		}

		if ( $this->args['required'] ) {
			//$attrs[] = 'required';
			//$attrs[] = 'data-validation="required"';
		}

		$attrs = array_filter( array_unique( $attrs ) );
		foreach ( $attrs as $attr ) {
			echo (strpos($attr, '=') > -1 ? esc_html( $attr ) : esc_html( $attr ) . '="' . esc_attr( $attr ) . '"');
		}

	}

	/**
	 * Get the name for a field.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get the placeholder value for a field.
	 *
	 * @return string
	 */
	public function get_placeholder() {
		return $this->args['placeholder'] ;
	}

	/**
	 * Check if this field has a data delegate set
	 *
	 * @return boolean Set or turned off.
	 */
	public function has_data_delegate() {
		return (bool) $this->args['data_delegate'];
	}

	/**
	 * Get the array of data from the data delegate.
	 *
	 * @return array mixed
	 */
	protected function get_delegate_data() {

		if ( $this->args['data_delegate'] ) {
			return call_user_func_array( $this->args['data_delegate'], array( $this ) );
		}

		return array();
	}


	public function get_default_value() {
		return $this->args['default'];
	}

	/**
	 * Get the existing or default value for a field.
	 *
	 * @return mixed
	 */
	public function get_value() {
		return ( $this->value || '0' === $this->value  ) ? $this->value : $this->get_default_value();
	}

	/**
	 * Get the existing or default value for a field.
	 *
	 * @return mixed
	 */
	public function set_value($value) {
		$this->value = $value;
	}

	/**
	 * Get multiple values for a field.
	 *
	 * @return array
	 */
	public function get_values() {
		return $this->values;
	}

	/**
	 * Define multiple values for a field and completely remove the singular value variable.
	 *
	 * @param array $values Field values.
	 */
	public function set_values( array $values ) {
		$this->values = $values;
		unset( $this->value );
	}

	/**
	 * Parse and validate a single value.
	 *
	 * Meant to be extended.
	 */
	public function parse_save_value() {}

	/**
	 * Validate values for the field. Override this method, to apply additional validation rules.
     *
	 * @param array $values Values to validate.
	 */
	public function validate($values) {

		// Don't save readonly values.
		if ( $this->args['readonly'] ) {
			return true;
		}

		if ( $this->args['required'] && (!isset($values) || empty($values))) {
			/* translators: %s: Field name. */
			return new WP_Error('empty', sprintf(__('Field: %s is required.', 'diller-loyalty'), $this->get_title()));
		}
		return true;
	}

	/**
	 * Print out a field.
	 */
	public function display() {
        ?>
            <div
	            <?php $this->outer_class_attr(); ?>>
                <label <?php $this->for_attr(); ?>><?php echo esc_html( $this->title ); ?></label>
                <?php $this->html(); ?>
            </div>
		<?php
	}

	/**
	 * Setup arguments for the class.
	 *
	 * @param $arguments
	 */
	public function set_arguments( $arguments ) {

		// Initially set arguments up.
		$this->args = wp_parse_args( $arguments, $this->default_args() );

		if ( !empty( $this->args['options'] ) && is_array(reset($this->args['options'])) ) {
			$re_format = array();
			foreach ( $this->args['options'] as $option ) {
				$re_format[ $option['value'] ] = $option['name'];
			}
			$this->args['options'] = $re_format;
		}
	}
}
