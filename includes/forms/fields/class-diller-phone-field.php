<?php
/**
 * Text box field with phone support
 *
 * @since 2.0.0
 *
 * @extends Diller_Base_Field
 *
 */

class Diller_Phone_Field extends Diller_Base_Field {
    
	/**
	 * Print out field HTML.
	 */
	public function html() {
		$classes = '';
		?>

		<input type="tel" <?php $this->name_attr(); ?> value="<?php echo esc_attr( $this->get_value() ); ?>"
            <?php $this->id_attr(); ?>
            <?php $this->boolean_attr(); ?>
            <?php $this->class_attr( $classes ); ?>
			<?php //$this->required_attr(); ?>
			<?php //$this->length_attr(); ?>
        />
		<?php
	}

	/**
	 * Validate values for the field
	 *
	 * @param array $values Values to validate.
	 */
	public function validate($values) {

        $result = parent::validate($values);
		if (is_wp_error($result)) {
			return $result;
		}

		// 'depends_on_field' holds the name of another field, from which we want to use its value later on
		if ( !empty( $this->args['depends_on_field'] ) ) {
		    $dep_field_value = sanitize_text_field($_POST[$this->args['depends_on_field']]);
			if(!empty($dep_field_value)) {
			    $values = $dep_field_value . $values; // Concat phone country code with phone number
            }
		}

		//Parse and validate phone number
		$is_valid = Diller_Loyalty_Helpers::is_valid_phone_number($values);
		return ($is_valid)? true : new WP_Error('phone-number', __('Phone number is invalid.', 'diller-loyalty'));
	}
}
