<?php
/**
 * Standard text field for email.
 *
 * @since    2.0.0
 *
 * @extends Diller_Base_Field
 */

class Diller_Email_Field extends Diller_Base_Field {

	/**
	 * Print out field HTML.
	 */
	public function html() {
		?>

		<input type="email" value="<?php echo esc_attr( $this->get_value() ); ?>"
            <?php $this->id_attr(); ?>
            <?php $this->boolean_attr(); ?>
            <?php $this->class_attr( ); ?>
            <?php $this->name_attr(); ?>
		    <?php //$this->required_attr(); ?>
			<?php //$this->length_attr(); ?>
        />

		<?php
	}


	/**
	 * Validate field value
	 */
	public function validate($values) {
		$result = parent::validate($values);
		if(is_wp_error($result)){
			return $result;
		}

		// check e-mail pattern
		if (!filter_var($values, FILTER_VALIDATE_EMAIL)) {
			return new WP_Error('invalid', sprintf(
			        /* translators: %s: Field name. */
                    __('Please provide a valid value for field: %s .', 'diller-loyalty'),
                    esc_html($this->get_title())
            ));
		}
		return true;
	}
}
